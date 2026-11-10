<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;
use ReflectionMethod;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Which site owns each custom font file in the fonts folder a Multisite shares
 *
 * @group model
 * @group fonts
 * @group ms-required
 */
class Test_Site_Custom_Fonts extends TestCase {

	use CopiesFontFixtures;

	public function set_up(): void {
		parent::set_up();

		$this->skipWithoutMultisite();
		wp_mkdir_p( GPDFAPI::get_data_class()->template_font_location );
	}

	public function tear_down(): void {
		$this->remove_font_fixtures();

		/* A subsite leaves these behind */
		global $wp_settings_errors, $wp_rewrite;
		$wp_settings_errors = [];
		$wp_rewrite->init();

		parent::tear_down();
	}

	protected function model(): Model_Custom_Fonts {
		return GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' );
	}

	protected function record( int $site_id ) {
		return get_network_option( get_main_network_id(), Model_Custom_Fonts::SITE_FONTS_PREFIX . $site_id );
	}

	/**
	 * @param array<string, array<string, string>> $fonts Font ID => style => file name
	 */
	protected function site_with_fonts( array $fonts ): int {
		$site_id = self::factory()->blog->create();
		$records = [];
		foreach ( $fonts as $id => $files ) {
			$records[ $id ] = $this->font_record( $id, $files );
		}

		update_blog_option( $site_id, 'gfpdf_settings', [ 'custom_fonts' => $records ] );

		return $site_id;
	}

	protected function add_to_mpdf(): array {
		return GPDFAPI::get_mvc_class( 'Model_PDF' )->add_unregistered_fonts_to_mPDF( [] );
	}

	public function test_saving_a_font_records_its_files_for_the_network() {
		$this->add_record(
			'brand',
			[
				'regular' => 'Brand.ttf',
				'bold'    => 'Brand-Bold.ttf',
			]
		);

		$this->assertSame( [ 'brand' => [ 'Brand.ttf', 'Brand-Bold.ttf' ] ], $this->record( get_current_blog_id() ) );

		$this->model()->delete_font( 'brand' );

		$this->assertSame( [], $this->record( get_current_blog_id() ) );
	}

	public function test_another_sites_font_file_is_not_added_to_mpdf() {
		$this->copy_fixture( 'Chewy.ttf', 'Theirs.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Loose.ttf' );
		$this->site_with_fonts( [ 'theirs' => [ 'regular' => 'Theirs.ttf' ] ] );

		$fonts = $this->add_to_mpdf();

		$this->assertArrayNotHasKey( 'theirs', $fonts );
		$this->assertSame( [ 'R' => 'Loose.ttf' ], $fonts['loose'] );
	}

	public function test_a_changed_record_is_read_again() {
		$this->copy_fixture( 'Chewy.ttf', 'Theirs.ttf' );
		$site_id = $this->site_with_fonts( [ 'theirs' => [ 'regular' => 'Theirs.ttf' ] ] );
		$this->assertArrayNotHasKey( 'theirs', $this->add_to_mpdf() );

		update_network_option( get_main_network_id(), Model_Custom_Fonts::SITE_FONTS_PREFIX . $site_id, [] );

		$this->assertArrayHasKey( 'theirs', $this->add_to_mpdf() );
	}

	public function test_a_site_with_no_record_is_read_until_the_sync_records_it() {
		$this->copy_fixture( 'Chewy.ttf', 'Theirs.ttf' );
		$site_id = $this->site_with_fonts( [ 'theirs' => [ 'regular' => 'Theirs.ttf' ] ] );
		$this->model()->delete_site_record( $site_id );

		/* A PDF doesn't read every site, so the file is added until then; checks before a delete do read the site */
		$this->assertSame( [ 'R' => 'Theirs.ttf' ], $this->add_to_mpdf()['theirs'] );
		$this->assertSame( [ 'Theirs.ttf' ], $this->model()->get_files_used_by_other_sites() );
		$this->assertContains( $site_id, $this->model()->get_sites_without_records() );

		$backfill = new ReflectionMethod( Model_Font_Downloader::class, 'backfill_site_fonts' );
		if ( PHP_VERSION_ID < 80100 ) {
			$backfill->setAccessible( true );
		}
		$this->assertTrue( $backfill->invoke( GPDFAPI::get_mvc_class( 'Model_Font_Downloader' ) ) );

		$this->assertSame( [ 'theirs' => [ 'Theirs.ttf' ] ], $this->record( $site_id ) );
		$this->assertArrayNotHasKey( 'theirs', $this->add_to_mpdf() );
	}

	public function test_the_sync_leaves_a_record_written_since_alone() {
		$site_id = $this->site_with_fonts( [ 'theirs' => [ 'regular' => 'Theirs.ttf' ] ] );
		update_network_option( get_main_network_id(), Model_Custom_Fonts::SITE_FONTS_PREFIX . $site_id, [ 'newer' => [] ] );

		$this->model()->record_missing_site_fonts( $site_id );

		$this->assertSame( [ 'newer' => [] ], $this->record( $site_id ) );
	}

	public function test_the_sync_records_nothing_for_a_site_it_cant_read() {
		global $wpdb;

		$site_id = $this->site_with_fonts( [ 'theirs' => [ 'regular' => 'Theirs.ttf' ] ] );
		$this->model()->delete_site_record( $site_id );
		wp_clear_scheduled_hook( Model_Font_Downloader::SYNC_EVENT );

		$break = static function ( $query ) {
			return strpos( $query, "option_name = 'gfpdf_settings' LIMIT 1" ) !== false ? str_replace( 'FROM', 'FROM gfpdf_missing_table,', $query ) : $query;
		};
		add_filter( 'query', $break );
		$suppress = $wpdb->suppress_errors( true );
		try {
			$this->model()->record_missing_site_fonts( $site_id );
		} finally {
			remove_filter( 'query', $break );
			$wpdb->suppress_errors( $suppress );
		}

		$this->assertFalse( $this->record( $site_id ) );
		$this->assertEqualsWithDelta( time() + HOUR_IN_SECONDS, wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ), 5 );
	}

	public function test_a_corrupt_record_is_read_again() {
		$site_id = $this->site_with_fonts( [ 'theirs' => [ 'regular' => 'Theirs.ttf' ] ] );
		update_network_option( get_main_network_id(), Model_Custom_Fonts::SITE_FONTS_PREFIX . $site_id, 'corrupt' );

		$this->assertSame( [ 'Theirs.ttf' ], $this->model()->get_files_used_by_other_sites() );
		$this->assertContains( $site_id, $this->model()->get_sites_without_records() );
	}

	public function test_a_font_id_another_site_has_is_not_reused() {
		$this->assertSame( 'brand', $this->model()->get_unique_id( 'brand' ) );

		$this->site_with_fonts( [ 'brand' => [ 'regular' => 'Brand.ttf' ] ] );

		$this->assertMatchesRegularExpression( '/^brand\d+$/', $this->model()->get_unique_id( 'brand' ) );
		$this->assertFalse( $this->model()->matches_custom_font_id( 'brand' ) );
	}

	public function test_deleting_a_site_removes_only_the_files_no_other_site_uses() {
		$this->copy_fixture( 'Chewy.ttf', 'Shared.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Own.ttf' );
		$this->add_record( 'mine', [ 'regular' => 'Shared.ttf' ] );
		$site_id = $this->site_with_fonts(
			[
				'theirs' => [
					'regular' => 'Shared.ttf',
					'bold'    => 'Own.ttf',
				],
			]
		);

		wp_delete_site( $site_id );

		$this->assertFileExists( $this->font_path( 'Shared.ttf' ) );
		$this->assertFileDoesNotExist( $this->font_path( 'Own.ttf' ) );
		$this->assertFalse( $this->record( $site_id ) );
	}

	public function test_deleting_a_site_with_no_record_reads_its_fonts() {
		$this->copy_fixture( 'Chewy.ttf', 'Own.ttf' );
		$site_id = $this->site_with_fonts( [ 'theirs' => [ 'regular' => 'Own.ttf' ] ] );
		$this->model()->delete_site_record( $site_id );

		wp_delete_site( $site_id );

		$this->assertFileDoesNotExist( $this->font_path( 'Own.ttf' ) );
	}

	public function test_deleting_something_other_than_a_site_does_nothing() {
		$this->copy_fixture( 'Chewy.ttf', 'Own.ttf' );

		GPDFAPI::get_mvc_class( 'Controller_Custom_Fonts' )->delete_site_fonts( 'not a site' );

		$this->assertFileExists( $this->font_path( 'Own.ttf' ) );
	}
}
