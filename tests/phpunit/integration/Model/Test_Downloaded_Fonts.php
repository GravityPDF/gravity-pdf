<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\RecordsFontFailures;
use GFPDF\Tests\Concerns\Seamed_Font_Downloader;
use GFPDF\Tests\Concerns\StubsFontSources;
use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger;
use GPDFAPI;
use WP_Error;

/**
 * The Font Manager's downloaded fonts: listing them with why they're in use, and deleting the unused ones
 *
 * @group model
 * @group fonts
 */
class Test_Downloaded_Fonts extends TestCase {

	use CopiesFontFixtures;
	use RecordsFontFailures;
	use StubsFontSources;

	/**
	 * @var string[] The DejaVu Serif Condensed family's files
	 */
	private const SERIF = [ 'DejaVuSerifCondensed.ttf', 'DejaVuSerifCondensed-Bold.ttf', 'DejaVuSerifCondensed-Italic.ttf', 'DejaVuSerifCondensed-BoldItalic.ttf' ];

	/**
	 * @var TestHandler
	 */
	private $log;

	public function set_up(): void {
		parent::set_up();

		GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );
	}

	public function tear_down(): void {
		$this->remove_font_fixtures();
		$this->delete_font_failures();
		GPDFAPI::get_options_class()->delete_option( 'default_font' );

		parent::tear_down();
	}

	public function test_only_downloadable_families_with_files_on_disk_are_listed() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSerifCondensed-Bold.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'gfpdf-Arimo-Regular.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuinfo.txt' );

		$this->assertSame(
			[
				[
					'id'           => 'dejavuserifcondensed',
					'font_name'    => 'Dejavu Serif Condensed',
					'type'         => 'core',
					'regular'      => 'DejaVuSerifCondensed.ttf',
					'italics'      => '',
					'bold'         => 'DejaVuSerifCondensed-Bold.ttf',
					'bolditalics'  => '',
					'can_delete'   => ! is_multisite() || is_super_admin(),
					'auto_install' => true,
				],
			],
			$this->core_rows( $this->downloader() )
		);
	}

	public function test_bundled_fonts_are_listed_but_not_deletable_and_hidden_ones_are_not_listed() {
		$rows = array_values( wp_list_filter( $this->downloader()->get_installed_fonts(), [ 'type' => 'bundled' ] ) );

		$this->assertSame( [ 'arimo' ], array_column( $rows, 'id' ) );
		$this->assertSame( 'Arimo', $rows[0]['font_name'] );
		$this->assertSame( 'gfpdf-Arimo-BoldItalic.ttf', $rows[0]['bolditalics'] );
		$this->assertFalse( $rows[0]['can_delete'] );
	}

	public function test_custom_fonts_share_the_list_sorted_by_name_and_replace_a_core_font_of_the_same_key() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->put_freeserif_on_disk();
		GPDFAPI::get_options_class()->update_option(
			'custom_fonts',
			[
				'freeserif' => [
					'id'        => 'freeserif',
					'font_name' => 'My Free Serif',
					'regular'   => 'custom-serif.ttf',
				],
				'brand'     => [
					'id'        => 'brand',
					'font_name' => 'Brand',
					'regular'   => 'brand.ttf',
				],
			]
		);

		$fonts = $this->downloader()->get_installed_fonts();

		$this->assertSame(
			[ 'arimo', 'brand', 'dejavuserifcondensed', 'freeserif' ],
			array_column( $fonts, 'id' )
		);
		$this->assertSame( [ 'bundled', 'custom', 'core', 'custom' ], array_column( $fonts, 'type' ) );
		$this->assertSame( 'My Free Serif', $fonts[3]['font_name'] );
		$this->assertTrue( $fonts[1]['can_delete'] );
	}

	public function test_a_custom_font_says_why_it_is_in_use() {
		GPDFAPI::get_options_class()->update_option(
			'custom_fonts',
			[
				'brand' => [
					'id'        => 'brand',
					'font_name' => 'Brand',
					'regular'   => 'brand.ttf',
				],
			]
		);
		GPDFAPI::get_options_class()->update_option( 'default_font', 'brand' );
		$this->add_form_with_pdfs( [ [ 'font' => 'brand' ] ] );

		$this->assertSame( [ 'Default font', 'Used by 1 PDF' ], $this->downloader()->get_font_usage()['brand'] );
	}

	public function test_another_sites_use_of_a_key_does_not_hold_a_custom_font() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		GPDFAPI::get_options_class()->update_option(
			'custom_fonts',
			[
				'brand' => [
					'id'        => 'brand',
					'font_name' => 'Brand',
					'regular'   => 'brand.ttf',
				],
			]
		);

		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );
		update_option( 'gfpdf_settings', [ 'default_font' => 'brand' ] );
		restore_current_blog();

		$this->assertArrayNotHasKey( 'brand', $this->downloader()->get_font_usage() );
	}

	public function test_nothing_is_listed_with_an_empty_fonts_folder() {
		$this->assertSame( [], $this->core_rows( $this->downloader() ) );
	}

	public function test_in_use_families_say_why() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavuserifcondensed' );
		$this->set_languages( [ 'th' ] );
		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ], [ 'sidebar_font' => 'FreeSerif' ], [ 'font' => 'arimo' ] ] );

		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Garuda.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'FreeMono.ttf' );
		$this->put_freeserif_on_disk();

		$sources = $this->stub_font_sources(
			static function ( array $sources ): array {
				$sources['fixture']             = $sources['core'];
				$sources['fixture']['required'] = true;
				$sources['fixture']['families'] = [ 'fixturemono' => [ 'R' => 'FreeMono.ttf' ] ];

				return $sources;
			}
		);

		$in_use = $this->downloader( $sources )->get_font_usage();

		$this->assertSame( [ 'Default font' ], $in_use['dejavuserifcondensed'] );
		$this->assertSame( [ 'Site language (th)' ], $in_use['garuda'] );
		$this->assertSame( [ 'Used by 2 PDFs' ], $in_use['freeserif'] );
		$this->assertSame( [ 'Required by an add-on' ], $in_use['fixturemono'] );
	}

	public function test_the_downloads_off_flag_is_per_family() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->put_freeserif_on_disk();

		add_filter(
			'gfpdf_auto_install_fonts',
			static function ( $download, $file ) {
				return $file !== 'FreeSerifBold.ttf';
			},
			10,
			2
		);

		$this->assertSame(
			[
				'dejavuserifcondensed' => true,
				'freeserif'            => false,
			],
			array_column( $this->core_rows( $this->downloader() ), 'auto_install', 'id' )
		);
	}

	public function test_delete_removes_the_family_whatever_its_hash_and_keeps_the_licences() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSerifCondensed-Bold.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuinfo.txt' );
		$this->put_freeserif_on_disk();

		$result = $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' );

		$this->assertSame(
			[
				'id'      => 'dejavuserifcondensed',
				'removed' => [ 'DejaVuSerifCondensed.ttf', 'DejaVuSerifCondensed-Bold.ttf' ],
				'bytes'   => filesize( $this->font_fixture( 'DejaVuSerifCondensed.ttf' ) ) + filesize( $this->font_fixture( 'Chewy.ttf' ) ),
			],
			$result
		);

		$this->assertFileDoesNotExist( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertFileDoesNotExist( $this->font_path( 'DejaVuSerifCondensed-Bold.ttf' ) );
		$this->assertFileExists( $this->font_path( 'DejaVuinfo.txt' ) );
		$this->assertFileExists( $this->font_path( 'FreeSerif.ttf' ) );
		$this->assertCount( 1, $this->log_messages( 'Downloaded font deleted' ) );
	}

	public function test_delete_clears_the_familys_failures_and_metrics() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->set_font_failures(
			[
				'DejaVuSerifCondensed-Bold.ttf' => $this->failure( time() ),
				'FreeSerif.ttf'                 => $this->failure(),
			]
		);
		$this->set_back_off( 'DejaVuSerifCondensed-Italic.ttf' );

		$metrics = GPDFAPI::get_misc_class()->get_mpdf_cache_location() . '/ttfontdata/';
		wp_mkdir_p( $metrics );
		file_put_contents( $metrics . 'dejavuserifcondensedB.mtx.json', '{}' );
		file_put_contents( $metrics . 'freeserif.mtx.json', '{}' );

		try {
			$this->assertIsArray( $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' ) );

			$this->assertSame( [ 'FreeSerif.ttf' ], array_keys( $this->get_font_failures() ) );
			$this->assertFalse( $this->get_back_off( 'DejaVuSerifCondensed-Italic.ttf' ) );
			$this->assertFileDoesNotExist( $metrics . 'dejavuserifcondensedB.mtx.json' );
			$this->assertFileExists( $metrics . 'freeserif.mtx.json' );
		} finally {
			wp_delete_file( $metrics . 'freeserif.mtx.json' );
		}
	}

	public function test_an_in_use_family_is_not_deleted() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavuserifcondensed' );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );

		$result = $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' );

		$this->assert_font_error( $result, 'font_in_use', 409 );
		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	/**
	 * @dataProvider provider_not_downloaded
	 */
	public function test_a_family_that_is_not_downloaded_is_not_found( string $key ) {
		$this->copy_fixture( 'Chewy.ttf', 'gfpdf-Arimo-Regular.ttf' );

		$this->assert_font_error( $this->downloader()->delete_downloaded_font( $key ), 'font_not_downloaded', 404 );
	}

	public function provider_not_downloaded(): array {
		return [
			'bundled'     => [ 'arimo' ],
			'unknown'     => [ 'notafont' ],
			'not on disk' => [ 'freeserif' ],
			'traversal'   => [ '../freeserif' ],
		];
	}

	public function test_another_sites_default_font_and_pdfs_are_in_use() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->put_freeserif_on_disk();

		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );
		update_option( 'gfpdf_settings', [ 'default_font' => 'DejaVuSerifCondensed' ] );
		restore_current_blog();

		/* A site without the settings or Gravity Forms tables */
		self::factory()->blog->create();

		$downloader = $this->downloader();

		$usage = $downloader->get_font_usage();

		$this->assertSame( [ 'Used on 1 other site' ], $usage['dejavuserifcondensed'] );
		$this->assertArrayNotHasKey( 'freeserif', $usage );

		$this->assert_font_error( $downloader->delete_downloaded_font( 'dejavuserifcondensed' ), 'font_in_use', 409 );
	}

	public function test_another_sites_locale_font_is_in_use() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->copy_fixture( 'Chewy.ttf', 'Garuda.ttf' );

		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );

		/* WordPress only keeps a locale it has installed, but this site has no pack for it */
		$installed = static function () {
			return [ 'th' ];
		};
		add_filter( 'get_available_languages', $installed );
		update_option( 'WPLANG', 'th' );
		remove_filter( 'get_available_languages', $installed );
		restore_current_blog();

		$this->assertSame( [ 'Used on 1 other site' ], $this->downloader()->get_font_usage()['garuda'] );
	}

	/**
	 * @param mixed $result
	 */
	private function assert_font_error( $result, string $code, int $status ): void {
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $code, $result->get_error_code() );
		$this->assertSame( [ 'status' => $status ], $result->get_error_data() );
	}

	private function downloader( ?Model_Font_Sources $sources = null ): Seamed_Font_Downloader {
		$this->log = new TestHandler();

		return new Seamed_Font_Downloader( new Logger( 'test', [ $this->log ] ), [ 'sources' => $sources ?? $this->stub_font_sources() ] );
	}

	private function add_form_with_pdfs( array $pdfs ): void {
		$this->gf_factory()->form->create(
			[],
			[
				'title'               => 'Fonts',
				'fields'              => [],
				'gfpdf_form_settings' => $pdfs,
			]
		);
	}

	/**
	 * @return array[]
	 */
	private function log_messages( string $message ): array {
		return array_values(
			array_filter(
				$this->log->getRecords(),
				static function ( $record ) use ( $message ) {
					return $record['message'] === $message;
				}
			)
		);
	}

	/**
	 * @return array[] The list's downloaded fonts
	 */
	protected function core_rows( Seamed_Font_Downloader $downloader ): array {
		return array_values( wp_list_filter( $downloader->get_installed_fonts(), [ 'type' => 'core' ] ) );
	}
}
