<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\RecordsFontFailures;
use GFPDF\Tests\Concerns\Seamed_Font_Downloader;
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
	use RecordsFontFailures;

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
	protected function site_with_fonts( array $fonts, array $settings = [] ): int {
		$site_id = self::factory()->blog->create();
		$records = [];
		foreach ( $fonts as $id => $files ) {
			$records[ $id ] = $this->font_record( $id, $files );
		}

		update_blog_option( $site_id, 'gfpdf_settings', [ 'custom_fonts' => $records ] + $settings );

		return $site_id;
	}

	/**
	 * A site with fonts whose font usage is recorded, so core font deletes don't wait on it
	 */
	protected function recorded_site( array $fonts ): int {
		$site_id = $this->site_with_fonts( $fonts );
		GPDFAPI::get_mvc_class( 'Model_Required_Fonts' )->write_site_fonts( $site_id );

		return $site_id;
	}

	protected function sources(): Model_Font_Sources {
		return GPDFAPI::get_mvc_class( 'Model_Font_Sources' );
	}

	protected function downloader(): Model_Font_Downloader {
		return GPDFAPI::get_mvc_class( 'Model_Font_Downloader' );
	}

	/**
	 * Record the fonts of the sites with no record, as the font sync does
	 */
	protected function backfill(): bool {
		$backfill = new ReflectionMethod( Model_Font_Downloader::class, 'backfill_site_fonts' );
		if ( PHP_VERSION_ID < 80100 ) {
			$backfill->setAccessible( true );
		}

		return $backfill->invoke( $this->downloader() );
	}

	/**
	 * Run the 6.19 fonts folder cleanup over this site and another
	 */
	protected function clean_up( int $site_id ): void {
		$this->set_installer_manifest();
		$this->set_font_cleanup( $this->font_cleanup_state( [ 'sites' => [ get_current_blog_id(), $site_id ] ] ) );

		$this->downloader()->cleanup();

		$this->assertTrue( $this->get_font_cleanup() );
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

		$this->assertTrue( $this->backfill() );

		$this->assertSame( [ 'theirs' => [ 'Theirs.ttf' ] ], $this->record( $site_id ) );
		$this->assertArrayNotHasKey( 'theirs', $this->add_to_mpdf() );
	}

	public function test_the_sync_reads_the_sites_it_records_ahead_in_one_query() {
		$sites = [];
		foreach ( [ 'One', 'Two', 'Three' ] as $name ) {
			$site_id = $this->site_with_fonts( [ strtolower( $name ) => [ 'regular' => "$name.ttf" ] ] );
			$this->model()->delete_site_record( $site_id );
			delete_network_option( get_main_network_id(), Model_Required_Fonts::SITE_FONTS_PREFIX . $site_id );
			$sites[ $site_id ] = $name;
		}

		$queries = $this->site_option_queries(
			function () {
				$this->assertTrue( $this->backfill() );
			}
		);

		foreach ( $sites as $site_id => $name ) {
			$this->assertSame( [ strtolower( $name ) => [ "$name.ttf" ] ], $this->record( $site_id ) );
			$this->assertIsArray( get_network_option( get_main_network_id(), Model_Required_Fonts::SITE_FONTS_PREFIX . $site_id ) );
		}

		/* One read ahead for the font keys and one for the custom fonts, none per site */
		$this->assertCount( 2, $queries );
	}

	public function test_the_cleanup_reads_its_sites_ahead_in_one_query() {
		/* A site deleted since it was queued would fail the query */
		$sites = [ get_current_blog_id(), 999999 ];
		foreach ( [ 'One', 'Two' ] as $name ) {
			$sites[] = $this->site_with_fonts( [ strtolower( $name ) => [ 'regular' => "$name.ttf" ] ] );
		}

		$this->set_installer_manifest();
		$this->set_font_cleanup( $this->font_cleanup_state( [ 'sites' => $sites ] ) );
		$queries = $this->site_option_queries(
			function () {
				$this->downloader()->cleanup();
			}
		);

		$this->assertTrue( $this->get_font_cleanup() );
		$this->assertCount( 1, $queries );
	}

	public function test_the_sync_leaves_a_record_written_since_alone() {
		$site_id = $this->site_with_fonts( [ 'theirs' => [ 'regular' => 'Theirs.ttf' ] ] );
		update_network_option( get_main_network_id(), Model_Custom_Fonts::SITE_FONTS_PREFIX . $site_id, [ 'newer' => [] ] );

		$this->model()->record_missing_site_fonts( $site_id );

		$this->assertSame( [ 'newer' => [] ], $this->record( $site_id ) );
	}

	public function test_the_sync_records_nothing_for_a_site_it_cant_read() {
		$site_id = $this->site_with_fonts( [ 'theirs' => [ 'regular' => 'Theirs.ttf' ] ] );
		$this->model()->delete_site_record( $site_id );
		wp_clear_scheduled_hook( Model_Font_Downloader::SYNC_EVENT );

		$this->with_failing_site_option_reads(
			function () use ( $site_id ) {
				$this->model()->record_missing_site_fonts( $site_id );
			}
		);

		$this->assertFalse( $this->record( $site_id ) );
		$this->assertEqualsWithDelta( time() + HOUR_IN_SECONDS, wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ), 5 );
	}

	public function test_the_sync_records_no_fonts_for_a_site_without_an_options_table() {
		$site_id = $this->site_with_fonts( [ 'theirs' => [ 'regular' => 'Theirs.ttf' ] ] );
		$this->model()->delete_site_record( $site_id );
		wp_clear_scheduled_hook( Model_Font_Downloader::SYNC_EVENT );

		$this->drop_site_options_table( $site_id );
		$this->without_db_errors(
			function () use ( $site_id ) {
				$this->model()->record_missing_site_fonts( $site_id );
			}
		);

		$this->assertSame( [], $this->record( $site_id ) );
		$this->assertFalse( wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ) );
	}

	public function test_sites_with_no_record_are_read_in_one_query() {
		$expected = [];
		foreach ( [ 'One', 'Two', 'Three' ] as $name ) {
			$site_id = $this->site_with_fonts( [ strtolower( $name ) => [ 'regular' => "$name.ttf" ] ] );
			$this->model()->delete_site_record( $site_id );
			$expected[] = "$name.ttf";
		}

		$files   = [];
		$queries = $this->site_option_queries(
			function () use ( &$files ) {
				$files = $this->model()->get_files_used_by_other_sites();
			}
		);

		sort( $files );
		sort( $expected );
		$this->assertSame( $expected, $files );
		$this->assertCount( 1, $queries );
	}

	public function test_a_site_that_cant_be_read_could_use_any_file_or_font_id() {
		$site_id = $this->site_with_fonts( [ 'brand' => [ 'regular' => 'Brand.ttf' ] ] );
		$this->model()->delete_site_record( $site_id );

		$this->with_failing_site_option_reads(
			function () {
				$this->assertNull( $this->model()->get_files_used_by_other_sites() );
				$this->assertMatchesRegularExpression( '/^brand\d+$/', $this->model()->get_unique_id( 'brand' ) );
			}
		);
	}

	public function test_a_site_without_an_options_table_has_no_fonts() {
		$site_id = $this->site_with_fonts( [ 'brand' => [ 'regular' => 'Brand.ttf' ] ] );
		$this->model()->delete_site_record( $site_id );
		$this->drop_site_options_table( $site_id );

		$this->without_db_errors(
			function () {
				$this->assertSame( [], $this->model()->get_files_used_by_other_sites() );
				$this->assertSame( 'brand', $this->model()->get_unique_id( 'brand' ) );
			}
		);
	}

	public function test_deleting_a_site_keeps_its_files_while_another_site_cant_be_read() {
		$this->copy_fixture( 'Chewy.ttf', 'Own.ttf' );
		$site_id = $this->site_with_fonts( [ 'mine' => [ 'regular' => 'Own.ttf' ] ] );
		$this->model()->delete_site_record( $this->site_with_fonts( [] ) );

		$this->with_failing_site_option_reads(
			static function () use ( $site_id ) {
				wp_delete_site( $site_id );
			}
		);

		$this->assertFileExists( $this->font_path( 'Own.ttf' ) );
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

	public function test_deleting_a_core_font_keeps_the_file_another_sites_custom_font_uses() {
		$this->put_own_serif_copies();
		$this->recorded_site( [ 'theirs' => [ 'regular' => 'DejaVuSerifCondensed.ttf' ] ] );

		$result = $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' );

		$this->assertSame( [ 'DejaVuSerifCondensed-Bold.ttf' ], $result['removed'] );
		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertFileDoesNotExist( $this->font_path( 'DejaVuSerifCondensed-Bold.ttf' ) );
	}

	public function test_another_sites_custom_font_at_a_core_name_is_not_the_core_font() {
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSerifCondensed.ttf' );
		$this->site_with_fonts( [ 'theirs' => [ 'regular' => 'DejaVuSerifCondensed.ttf' ] ] );
		$sources = $this->sources();
		$hashed  = $sources->get_hashed_name( 'DejaVuSerifCondensed.ttf' );

		$this->assertNotSame( '', $hashed );
		$this->assertTrue( $sources->is_record_file( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertSame( $hashed, $sources->register_font_data( [] )['dejavuserifcondensed']['R'] );
		$rows = array_column( $this->downloader()->get_installed_fonts(), null, 'id' );
		$this->assertSame( 'available', $rows['dejavuserifcondensed']['type'], 'Listed as not downloaded' );
	}

	public function test_another_sites_copy_of_a_core_font_is_still_the_core_font() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->site_with_fonts( [ 'theirs' => [ 'regular' => 'DejaVuSerifCondensed.ttf' ] ] );
		$sources = $this->sources();

		$this->assertFalse( $sources->is_record_file( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertSame( 'DejaVuSerifCondensed.ttf', $sources->register_font_data( [] )['dejavuserifcondensed']['R'] );
	}

	public function test_deleting_a_core_font_keeps_the_file_of_a_site_not_recorded_yet() {
		$this->put_own_serif_copies();
		$site_id = $this->recorded_site( [ 'theirs' => [ 'regular' => 'DejaVuSerifCondensed.ttf' ] ] );
		$this->model()->delete_site_record( $site_id );

		$result = $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' );

		$this->assertSame( [ 'DejaVuSerifCondensed-Bold.ttf' ], $result['removed'] );
		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	public function test_deleting_a_core_font_keeps_its_plain_files_while_a_site_cant_be_read() {
		$this->put_own_serif_copies();
		$this->model()->delete_site_record( $this->recorded_site( [] ) );

		$result = $this->with_failing_site_option_reads(
			function () {
				return $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' );
			}
		);

		$this->assertSame( [], $result['removed'] );
		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed-Bold.ttf' ) );
	}

	/**
	 * The family's own Regular and Bold on disk, the Bold a fixture listed as its build
	 */
	private function put_own_serif_copies(): void {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSerifCondensed-Bold.ttf' );
		$this->list_fixture_as( 'Chewy.ttf', 'DejaVuSerifCondensed-Bold.ttf' );
	}

	public function test_deleting_a_core_font_removes_its_hashed_copy_beside_another_sites_custom_font() {
		$hashed = GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_hashed_name( 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf', $hashed );
		$this->recorded_site( [ 'theirs' => [ 'regular' => 'DejaVuSerifCondensed.ttf' ] ] );

		$result = $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' );

		$this->assertSame( [ 'DejaVuSerifCondensed.ttf' ], $result['removed'] );
		$this->assertFileDoesNotExist( $this->font_path( $hashed ) );
		$this->assertFileEquals( $this->font_fixture( 'Chewy.ttf' ), $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	public function test_the_cleanup_keeps_a_core_file_another_sites_custom_font_needs() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		touch( $this->font_path( 'DejaVuSerifCondensed.ttf' ), time() - HOUR_IN_SECONDS );
		$site_id = $this->site_with_fonts( [ 'Serif-Copy' => [ 'regular' => 'DejaVuSerifCondensed.ttf' ] ], [ 'default_font' => 'Serif-Copy' ] );

		$this->clean_up( $site_id );

		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	public function test_a_paused_cleanup_keeps_the_custom_font_files_it_has_read() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		touch( $this->font_path( 'DejaVuSerifCondensed.ttf' ), time() - HOUR_IN_SECONDS );
		$site_id = $this->site_with_fonts( [ 'serif-copy' => [ 'regular' => 'DejaVuSerifCondensed.ttf' ] ], [ 'default_font' => 'serif-copy' ] );
		$this->set_installer_manifest();
		$this->set_font_cleanup( $this->font_cleanup_state( [ 'sites' => [ $site_id, get_current_blog_id() ] ] ) );

		$downloader = new Seamed_Font_Downloader( GPDFAPI::get_log_class(), [ 'budget' => 0 ] );
		$downloader->cleanup();

		$this->assertSame( [ 'DejaVuSerifCondensed.ttf' ], $this->get_font_cleanup()['files'] );

		$downloader->cleanup();

		$this->assertTrue( $this->get_font_cleanup() );
		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	public function test_the_cleanup_removes_a_core_file_only_an_unused_custom_font_has() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		touch( $this->font_path( 'DejaVuSerifCondensed.ttf' ), time() - HOUR_IN_SECONDS );
		$site_id = $this->site_with_fonts( [ 'serif-copy' => [ 'regular' => 'DejaVuSerifCondensed.ttf' ] ] );

		$this->clean_up( $site_id );

		$this->assertFileDoesNotExist( $this->font_path( 'DejaVuSerifCondensed.ttf' ), 'It downloads again by name when used' );
	}

	public function test_deleting_something_other_than_a_site_does_nothing() {
		$this->copy_fixture( 'Chewy.ttf', 'Own.ttf' );

		GPDFAPI::get_mvc_class( 'Controller_Custom_Fonts' )->delete_site_fonts( 'not a site' );

		$this->assertFileExists( $this->font_path( 'Own.ttf' ) );
	}
}
