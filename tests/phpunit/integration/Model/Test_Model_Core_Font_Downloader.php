<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Concerns\AccessesPrivateState;
use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\RecordsFontFailures;
use GFPDF\Tests\Concerns\Seamed_Core_Font_Downloader;
use GFPDF\Tests\Concerns\StubsFontSources;
use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger;
use GFPDF_Vendor\Mpdf\Fonts\FontFileFinder;
use GPDFAPI;
use WP_Error;

/**
 * @group model
 * @group fonts
 */
class Test_Model_Core_Font_Downloader extends TestCase {

	use AccessesPrivateState;
	use CopiesFontFixtures;
	use RecordsFontFailures;
	use StubsFontSources;

	private const FONT = 'DejaVuSerifCondensed.ttf';

	/**
	 * @var Model_Core_Font_Downloader
	 */
	private $downloader;

	/**
	 * @var TestHandler
	 */
	private $log;

	/**
	 * @var string[] The tmp folder's files before the test
	 */
	private $tmp_files;

	public function set_up(): void {
		parent::set_up();

		$this->downloader = $this->new_downloader();
		$this->tmp_files  = $this->get_tmp_files();
	}

	public function tear_down(): void {
		wp_delete_file( $this->font_path() );
		$this->remove_font_fixtures();
		$this->delete_font_failures();
		delete_transient( Model_Core_Font_Downloader::SYNC_REMAINING );
		wp_clear_scheduled_hook( Model_Core_Font_Downloader::SYNC_EVENT );
		wp_clear_scheduled_hook( Model_Core_Font_Downloader::RETRY_EVENT );
		GPDFAPI::get_options_class()->delete_option( 'default_font' );

		parent::tear_down();
	}

	public function test_the_manifest_lists_core_fonts_and_their_licences() {
		$manifest = $this->downloader->get_manifest();

		$this->assertSame( 334040, $manifest[ self::FONT ]['size'] );
		$this->assertSame( 'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/v1.0.0/' . self::FONT, $manifest[ self::FONT ]['url'] );
		$this->assertArrayHasKey( 'DejaVuinfo.txt', $manifest );
	}

	public function test_is_core_font_file_only_matches_fonts_on_the_manifest() {
		$this->assertTrue( $this->downloader->is_core_font_file( self::FONT ) );
		$this->assertTrue( $this->downloader->is_core_font_file( 'Aegean.otf' ) );
		$this->assertFalse( $this->downloader->is_core_font_file( 'DejaVuinfo.txt' ) );
		$this->assertFalse( $this->downloader->is_core_font_file( 'GravityPDF-Arimo-Regular.ttf' ) );
		$this->assertFalse( $this->downloader->is_core_font_file( '../' . self::FONT ) );
	}

	public function test_download_saves_a_verified_font() {
		$this->serve_font_downloads();

		$this->assertTrue( $this->downloader->download( self::FONT ) );
		$this->assertFileEquals( $this->font_fixture( self::FONT ), $this->font_path() );
		$this->assertSame( [ $this->downloader->get_manifest()[ self::FONT ]['url'] ], $this->requested );
		$this->assertNoStrayTmpFiles();
	}

	public function test_download_skips_a_font_already_in_the_fonts_folder() {
		copy( $this->font_fixture( self::FONT ), $this->font_path() );
		$this->serve_font_downloads();

		$this->assertTrue( $this->downloader->download( self::FONT ) );
		$this->assertSame( [], $this->requested );
	}

	public function test_a_forced_download_replaces_the_font_in_the_fonts_folder() {
		file_put_contents( $this->font_path(), 'old' );
		$this->serve_font_downloads();

		$this->assertTrue( $this->downloader->download( self::FONT, true ) );
		$this->assertFileEquals( $this->font_fixture( self::FONT ), $this->font_path() );
		$this->assertNoStrayTmpFiles();
	}

	public function test_a_failed_forced_download_keeps_the_font_in_the_fonts_folder() {
		copy( $this->font_fixture( self::FONT ), $this->font_path() );
		$this->fail_font_downloads();

		$this->assertFalse( $this->downloader->download( self::FONT, true ) );
		$this->assertFileEquals( $this->font_fixture( self::FONT ), $this->font_path() );
	}

	/**
	 * @dataProvider provider_unapproved_files
	 */
	public function test_download_rejects_a_file_not_on_the_manifest( string $file ) {
		$this->serve_font_downloads();

		$this->assertFalse( $this->downloader->download( $file ) );
		$this->assertSame( [], $this->requested );
	}

	public function provider_unapproved_files(): array {
		return [
			'unknown'   => [ 'NotARealFont.ttf' ],
			'traversal' => [ '../' . self::FONT ],
			'empty'     => [ '' ],
		];
	}

	/**
	 * @dataProvider provider_failed_download
	 */
	public function test_failed_download_leaves_no_files( $response ) {
		$this->serve_font_downloads( $response );

		$this->assertFalse( $this->downloader->download( self::FONT ) );
		$this->assertFileDoesNotExist( $this->font_path() );
		$this->assertNoStrayTmpFiles();
	}

	public function provider_failed_download(): array {
		return [
			'request error'      => [ new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ) ],
			'rate limited'       => [ $this->http_response( 429 ) ],
			'server error'       => [ $this->http_response( 500 ) ],
			'not a response'     => [ 'junk' ],
			'no response code'   => [ [ 'body' => '' ] ],
		];
	}

	/**
	 * @dataProvider provider_unexpected_download
	 */
	public function test_download_that_does_not_match_the_manifest_is_removed( callable $change ) {
		$this->serve_font_downloads( null, $change );

		$this->assertFalse( $this->downloader->download( self::FONT ) );
		$this->assertFileDoesNotExist( $this->font_path() );
		$this->assertNoStrayTmpFiles();
		$this->assertCount( 1, $this->log_messages( 'Core Font Verification Failed' ) );
	}

	public function provider_unexpected_download(): array {
		return [
			'truncated' => [
				function ( $handle ) {
					ftruncate( $handle, 1024 );
				},
			],
			'same size' => [
				function ( $handle ) {
					fwrite( $handle, 'x' );
				},
			],
			'empty'     => [
				function ( $handle ) {
					ftruncate( $handle, 0 );
				},
			],
		];
	}

	public function test_a_failed_move_removes_the_download() {
		/* A folder in the font's place makes rename() fail */
		mkdir( $this->font_path() );
		$this->serve_font_downloads();

		try {
			$this->assertFalse( $this->downloader->download( self::FONT ) );
			$this->assertNoStrayTmpFiles();
			$this->assertCount( 1, $this->log_messages( 'Core Font Move Failed' ) );
		} finally {
			rmdir( $this->font_path() );
		}
	}

	public function test_a_move_that_fails_part_way_removes_the_partial_font() {
		$downloader = $this->new_downloader( true );
		$this->serve_font_downloads();

		$this->assertFalse( $downloader->download( self::FONT ) );
		$this->assertFileDoesNotExist( $this->font_path() );
		$this->assertNoStrayTmpFiles();
		$this->assertCount( 1, $this->log_messages( 'Core Font Move Failed' ) );
	}

	public function test_a_failed_move_keeps_a_valid_font_already_there() {
		copy( $this->font_fixture( self::FONT ), $this->font_path() );
		$downloader = $this->new_downloader( false, true );
		$this->serve_font_downloads();

		$this->assertFalse( $downloader->download( self::FONT, true ) );
		$this->assertFileEquals( $this->font_fixture( self::FONT ), $this->font_path() );
	}

	/**
	 * @dataProvider provider_unwritable_folder
	 */
	public function test_an_unwritable_folder_fails_without_a_request( string $folder ) {
		$dir = GPDFAPI::get_data_class()->$folder;
		chmod( $dir, 0555 );

		try {
			$this->serve_font_downloads();

			$this->assertFalse( $this->downloader->download( self::FONT ) );
			$this->assertSame( [], $this->requested );
			$this->assertCount( 1, $this->log_messages( 'Core Font folder is not writable' ) );
		} finally {
			chmod( $dir, 0755 );
		}
	}

	public function provider_unwritable_folder(): array {
		return [
			'fonts folder' => [ 'template_font_location' ],
			'tmp folder'   => [ 'template_tmp_location' ],
		];
	}

	public function test_a_temporary_file_that_cannot_be_created_fails_without_a_request() {
		add_filter(
			'wp_unique_filename',
			static function () {
				return 'missing-folder/download.tmp';
			}
		);
		$this->serve_font_downloads();

		$this->assertFalse( $this->downloader->download( self::FONT ) );
		$this->assertSame( [], $this->requested );
		$this->assertCount( 1, $this->log_messages( 'Core Font temporary file could not be created' ) );
	}

	public function test_a_missing_bundled_fallback_is_logged() {
		$font_sources = new class( GPDFAPI::get_data_class(), GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' ), GPDFAPI::get_log_class() ) extends Model_Font_Sources {
			public function get_fallback_file(): string {
				return '/missing/GravityPDF-Arimo-Regular.ttf';
			}
		};

		$this->log  = new TestHandler();
		$downloader = new Model_Core_Font_Downloader( $font_sources, GPDFAPI::get_data_class(), GPDFAPI::get_options_class(), GPDFAPI::get_misc_class(), new Logger( 'test', [ $this->log ] ) );

		$this->assertSame( '', $downloader->get_fallback_file() );
		$this->assertCount( 1, $this->log_messages( 'The bundled fallback font is missing; reinstall Gravity PDF' ) );
	}

	/**
	 * @dataProvider provider_finder_config
	 */
	public function test_get_font_file_finder_takes_what_it_can_from_a_malformed_config( array $config, array $dirs, string $default_file ) {
		$finder = $this->downloader->get_font_file_finder( $config );

		$this->assertSame( $dirs, $this->get_private( $finder, 'directories', FontFileFinder::class ) );
		$this->assertSame( $default_file, $this->get_private( $finder, 'default_font_file' ) );
	}

	public function provider_finder_config(): array {
		$fontdata = [ 'dejavusans' => [ 'R' => 'DejaVuSans.ttf' ] ];

		return [
			'a complete config'          => [ [ 'fontDir' => [ '/a', '/b' ], 'default_font' => 'DejaVuSans', 'fontdata' => $fontdata ], [ '/a', '/b' ], 'DejaVuSans.ttf' ],
			'an empty config'            => [ [], [], '' ],
			'a single font directory'    => [ [ 'fontDir' => '/a' ], [ '/a' ], '' ],
			'non-string font dirs'       => [ [ 'fontDir' => [ '/a', null, [ '/b' ], 3 ] ], [ '/a' ], '' ],
			'non-array font data'        => [ [ 'default_font' => 'dejavusans', 'fontdata' => 'junk' ], [], '' ],
			'a non-string default font'  => [ [ 'default_font' => [ 'dejavusans' ], 'fontdata' => $fontdata ], [], '' ],
			'a font entry that is a string' => [ [ 'default_font' => 'dejavusans', 'fontdata' => [ 'dejavusans' => 'DejaVuSans.ttf' ] ], [], '' ],
			'a non-string regular file'  => [ [ 'default_font' => 'dejavusans', 'fontdata' => [ 'dejavusans' => [ 'R' => [ 'x' ] ] ] ], [], '' ],
		];
	}

	public function test_a_failure_backs_off_the_next_request() {
		$this->serve_font_downloads( $this->http_response( 500 ) );
		$this->assertFalse( $this->downloader->download( self::FONT ) );

		$failures = $this->get_font_failures();
		$this->assertSame( [ self::FONT ], array_keys( $failures ) );

		$this->requested = [];
		$this->assertFalse( $this->new_downloader()->download( self::FONT ) );
		$this->assertSame( [], $this->requested );
	}

	public function test_back_off_reads_the_failures_once() {
		$this->set_font_failures( [ self::FONT => $this->failure() ] );
		$this->serve_font_downloads();

		$reads = 0;
		add_filter(
			( is_multisite() ? 'pre_site_option_' : 'pre_option_' ) . Model_Core_Font_Downloader::FAILURES,
			static function ( $value ) use ( &$reads ) {
				$reads++;

				return $value;
			}
		);

		$this->assertFalse( $this->downloader->download( self::FONT ) );
		$this->assertFalse( $this->downloader->download( self::FONT ) );
		$this->assertSame( 1, $reads );

		/* Recording this one's failure is a write, which reads the option back itself */
		$this->downloader->download( 'DejaVuSansMono.ttf' );

		$this->assertSame( [ 'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/v1.0.0/DejaVuSansMono.ttf' ], $this->requested );
		$this->assertCount( 2, $this->log_messages( 'Core Font download skipped after a recent failure' ) );
	}

	public function test_back_off_ends_after_fifteen_minutes() {
		$this->set_font_failures( [ self::FONT => $this->failure( 0, time() - Model_Core_Font_Downloader::BACK_OFF ) ] );
		$this->serve_font_downloads();

		$this->assertTrue( $this->downloader->download( self::FONT ) );
		$this->assertCount( 1, $this->requested );
	}

	public function test_downloads_can_be_turned_off() {
		add_filter( 'gfpdf_core_font_download', '__return_false' );
		$this->serve_font_downloads();

		$this->assertFalse( $this->downloader->download( self::FONT ) );
		$this->assertSame( [], $this->requested );
		$this->assertFalse( $this->get_font_failures() );
	}

	public function test_get_font_files_lists_a_core_keys_files_on_the_manifest() {
		$this->assertSame( [ 'DejaVuSerif.ttf', 'DejaVuSerif-Bold.ttf', 'DejaVuSerif-Italic.ttf', 'DejaVuSerif-BoldItalic.ttf' ], $this->downloader->get_font_files( ' DejaVuSerif ' ) );
		$this->assertSame( [ 'Sun-ExtA.ttf' ], $this->downloader->get_font_files( 'sun-exta' ) );
		$this->assertSame( [], $this->downloader->get_font_files( 'arimo' ) );
		$this->assertSame( [], $this->downloader->get_font_files( 'notafont' ) );
	}

	public function test_get_font_files_skips_a_custom_font() {
		$this->copy_fixture( 'Chewy.ttf' );
		$this->add_record( 'chewy', [ 'regular' => 'Chewy.ttf' ] );

		$this->assertSame( [], $this->downloader->get_font_files( 'chewy' ) );
	}

	/**
	 * @dataProvider provider_junk_font_data
	 *
	 * @param mixed $fontdata
	 */
	public function test_get_font_files_ignores_junk_font_data( $fontdata ) {
		add_filter(
			'mpdf_font_data',
			static function () use ( $fontdata ) {
				return $fontdata;
			},
			PHP_INT_MAX
		);

		$this->assertSame( [], $this->seamed( [ 'sources' => $this->stub_font_sources() ] )->get_font_files( 'dejavuserif' ) );
	}

	public function provider_junk_font_data(): array {
		return [
			'null'             => [ null ],
			'a string'         => [ 'dejavuserif' ],
			'a family string'  => [ [ 'dejavuserif' => 'DejaVuSerif.ttf' ] ],
			'non-string files' => [ [ 'dejavuserif' => [ 'R' => [ 'DejaVuSerif.ttf' ], 'B' => 1, 'I' => null ] ] ],
		];
	}

	public function test_the_required_set_has_the_default_language_and_selected_fonts() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'DejaVuSansCondensed' );
		$this->set_languages( [ 'th' ] );
		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ], [ 'sidebar_font' => 'DejaVuSerif', 'orientation' => 'portrait' ] ] );

		$this->assertSame(
			array_merge(
				[
					'DejaVuSansCondensed.ttf',
					'DejaVuSansCondensed-Bold.ttf',
					'DejaVuSansCondensed-Oblique.ttf',
					'DejaVuSansCondensed-BoldOblique.ttf',
					'Garuda.ttf',
					'Garuda-Bold.ttf',
					'Garuda-Oblique.ttf',
					'Garuda-BoldOblique.ttf',
				],
				Seamed_Core_Font_Downloader::FREESERIF,
				/* A template's own font picker counts too */
				[ 'DejaVuSerif.ttf', 'DejaVuSerif-Bold.ttf', 'DejaVuSerif-Italic.ttf', 'DejaVuSerif-BoldItalic.ttf' ]
			),
			$this->seamed( [ 'sources' => $this->stub_font_sources() ] )->get_required_font_files()
		);
	}

	public function test_the_font_selections_are_read_once_until_flushed() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );
		$this->assertSame( [], $this->downloader->get_required_font_files() );

		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ] ] );
		$this->assertSame( [], $this->downloader->get_required_font_files() );

		$this->downloader->flush();
		$this->assertSame( Seamed_Core_Font_Downloader::FREESERIF, $this->downloader->get_required_font_files() );
	}

	public function test_the_required_set_is_empty_for_a_bundled_default_on_an_english_site() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );
		$this->add_form_with_pdfs( [ [ 'font' => 'arimo' ] ] );

		$this->assertSame( [], $this->downloader->get_required_font_files() );
	}

	public function test_the_required_set_has_every_file_of_a_required_source() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );

		$sources = $this->stub_font_sources(
			static function ( array $sources ): array {
				$sources['fixture']             = $sources['core'];
				$sources['fixture']['required'] = true;
				$sources['fixture']['families'] = [];

				return $sources;
			}
		);

		$files = $this->seamed( [ 'sources' => $sources ] )->get_required_font_files();

		$this->assertCount( count( $this->downloader->get_manifest() ), $files );
		$this->assertContains( 'DejaVuinfo.txt', $files );
	}

	public function test_corrupt_settings_are_left_out_of_the_required_set() {
		GPDFAPI::get_options_class()->update_option( 'default_font', [ 'dejavuserif' ] );
		$this->add_form_with_pdfs( [ 'freeserif', [ 'font' => [ 'dejavuserif' ] ], [ 'font' => 7 ], [ 'font' => 'freeserif' ] ] );
		$this->gf_factory()->form->create(
			[],
			[
				'title'               => 'Corrupt',
				'fields'              => [],
				'gfpdf_form_settings' => 'dejavuserif',
			]
		);

		$this->assertSame( Seamed_Core_Font_Downloader::FREESERIF, $this->downloader->get_required_font_files() );
	}

	/**
	 * @dataProvider provider_pdf_needs_download
	 */
	public function test_a_pdf_needs_a_download_when_a_font_it_names_has_files_missing( array $pdf, bool $on_disk, bool $expected ) {
		if ( $on_disk ) {
			$this->put_freeserif_on_disk();
		}

		$this->assertSame( $expected, $this->downloader->pdf_needs_download( $pdf ) );
	}

	public function provider_pdf_needs_download(): array {
		return [
			'the font setting'        => [ [ 'font' => 'FreeSerif' ], false, true ],
			'a template font picker'  => [ [ 'sidebar_font' => 'freeserif', 'orientation' => 'portrait' ], false, true ],
			'already on disk'         => [ [ 'font' => 'freeserif' ], true, false ],
			'the bundled Arimo'       => [ [ 'font' => 'arimo' ], false, false ],
			'a font named in HTML'    => [ [ 'header' => '<p>freeserif</p>' ], false, false ],
			'nested or not a string'  => [ [ 'nested' => [ 'font' => 'freeserif' ], 'size' => 12 ], false, false ],
		];
	}

	/**
	 * @dataProvider provider_needs_download
	 *
	 * @param mixed $font
	 */
	public function test_needs_download_when_a_font_file_is_missing( $font, bool $on_disk, bool $expected ) {
		if ( $on_disk ) {
			$this->put_freeserif_on_disk();
		}

		$this->assertSame( $expected, $this->downloader->needs_download( $font ) );
	}

	public function provider_needs_download(): array {
		return [
			'missing'          => [ 'FreeSerif', false, true ],
			'on disk'          => [ 'freeserif', true, false ],
			'the bundled font' => [ 'arimo', false, false ],
			'an unknown font'  => [ 'notafont', false, false ],
			'not a string'     => [ [ 'freeserif' ], false, false ],
			'null'             => [ null, false, false ],
		];
	}

	public function test_schedule_sync_queues_one_event_without_arguments() {
		$this->downloader->schedule_sync();
		$this->downloader->schedule_sync();
		$this->new_downloader()->schedule_sync();

		$this->assertSame( [ [] ], $this->get_scheduled_args() );
	}

	public function test_a_failed_schedule_is_logged() {
		add_filter( 'schedule_event', '__return_false' );

		$this->downloader->schedule_sync();
		$this->assertSame( [], $this->get_scheduled_args() );
		$this->assertCount( 1, $this->log_messages( 'Core Font sync could not be scheduled' ) );
	}

	public function test_sync_downloads_the_required_set_as_it_is_when_it_runs() {
		$this->serve_font_downloads();
		GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );
		$this->downloader->schedule_sync();

		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavuserifcondensed' );
		$this->copied_fonts = array_merge( $this->copied_fonts, $this->font_paths( $this->downloader->get_font_files( 'dejavuserifcondensed' ) ) );
		$this->new_downloader()->sync();

		$this->assertContains( $this->downloader->get_manifest()[ self::FONT ]['url'], $this->requested );
		$this->assertFileExists( $this->font_path() );
	}

	public function test_sync_skips_files_already_on_disk() {
		$this->copy_fixture( 'Chewy.ttf', 'Garuda.ttf' );
		$this->serve_font_downloads();

		$this->seamed_sync( [ 'Garuda.ttf', self::FONT ], 20 )->sync();

		$this->assertSame( [ $this->downloader->get_manifest()[ self::FONT ]['url'] ], $this->requested );
		$this->assertSame( [], $this->get_scheduled_args() );
	}

	public function test_a_paused_sync_leaves_the_rest_for_its_next_run() {
		$this->serve_fixture_as( 'Chewy.ttf', 'Garuda.ttf' );
		$this->serve_font_downloads();

		/* No time at all still downloads one file */
		$downloader = $this->seamed_sync( [ 'Garuda.ttf', self::FONT ], 0 );
		$downloader->sync();

		$this->assertCount( 1, $this->requested );
		$this->assertSame( [ [] ], $this->get_scheduled_args() );
		$this->assertSame( [ self::FONT ], get_transient( Model_Core_Font_Downloader::SYNC_REMAINING ) );
		$this->assertCount( 1, $this->log_messages( 'Core Font sync paused until its next run' ) );

		/* The next run takes what's left without working the required set out again */
		wp_clear_scheduled_hook( Model_Core_Font_Downloader::SYNC_EVENT );
		$downloader->required = [];
		$downloader->sync();

		$this->assertCount( 2, $this->requested );
		$this->assertFileExists( $this->font_path() );
		$this->assertFalse( get_transient( Model_Core_Font_Downloader::SYNC_REMAINING ) );
		$this->assertSame( [], $this->get_scheduled_args() );
	}

	public function test_a_fresh_trigger_works_the_required_set_out_again() {
		set_transient( Model_Core_Font_Downloader::SYNC_REMAINING, [ 'Garuda.ttf' ], DAY_IN_SECONDS );

		$this->downloader->schedule_sync();

		$this->assertFalse( get_transient( Model_Core_Font_Downloader::SYNC_REMAINING ) );
	}

	/**
	 * @dataProvider provider_junk_remaining
	 *
	 * @param mixed $remaining
	 */
	public function test_a_junk_remaining_list_is_guarded( $remaining, array $expected ) {
		$this->serve_font_downloads();
		set_transient( Model_Core_Font_Downloader::SYNC_REMAINING, $remaining, DAY_IN_SECONDS );

		$this->seamed_sync( [ 'Garuda.ttf' ], 20 )->sync();

		$this->assertSame( $expected, array_map( 'basename', $this->requested ) );
	}

	public function provider_junk_remaining(): array {
		return [
			'not a list'      => [ 'DejaVuSerifCondensed.ttf', [ 'Garuda.ttf' ] ],
			'non-string file' => [ [ [ self::FONT ], 7, self::FONT ], [ self::FONT ] ],
		];
	}

	public function test_sync_carries_on_past_a_failed_download() {
		$this->serve_font_downloads();

		/* NotAFixture.ttf isn't on the manifest, and FreeSans.ttf has no fixture so fails verification */
		$this->seamed_sync( [ 'NotAFixture.ttf', 'FreeSans.ttf', self::FONT ], 20 )->sync();

		$this->assertFileExists( $this->font_path() );
		$this->assertCount( 2, $this->requested );
		$this->assertSame( [], $this->get_scheduled_args() );
	}

	public function test_a_first_failure_schedules_one_retry_within_the_jitter() {
		$this->fail_font_downloads();
		$downloader = $this->seamed( [ 'jitter' => 0 ] );

		$before = time();
		$downloader->download( self::FONT );
		$downloader->download( 'FreeSans.ttf' );
		$after = time();

		/* With no jitter the retry is due at once, and the second failure doesn't add another */
		$retries = $this->get_scheduled( Model_Core_Font_Downloader::RETRY_EVENT );
		$this->assertCount( 1, $retries );
		$this->assertGreaterThanOrEqual( $before, $retries[0] );
		$this->assertLessThanOrEqual( $after, $retries[0] );
		$this->assertSame( [], $downloader->get_failed_files() );
		$this->assertSame( [ 0, 0 ], array_column( $this->get_font_failures(), 'gave_up' ) );
	}

	public function test_the_retry_is_due_within_five_minutes() {
		$this->fail_font_downloads();

		$before = time();
		$this->downloader->download( self::FONT );
		$after = time();

		$retries = $this->get_scheduled( Model_Core_Font_Downloader::RETRY_EVENT );
		$this->assertCount( 1, $retries );
		$this->assertGreaterThanOrEqual( $before, $retries[0] );
		$this->assertLessThanOrEqual( $after + 5 * MINUTE_IN_SECONDS, $retries[0] );
	}

	public function test_a_retry_that_succeeds_clears_the_failure() {
		$this->fail_font_downloads();
		$this->assertFalse( $this->downloader->download( self::FONT ) );

		remove_all_filters( 'pre_http_request' );
		$this->serve_font_downloads();
		wp_clear_scheduled_hook( Model_Core_Font_Downloader::RETRY_EVENT );

		/* Minutes later, inside the back-off */
		$this->new_downloader()->retry_failed();

		$this->assertFileExists( $this->font_path() );
		$this->assertFalse( $this->get_font_failures() );
		$this->assertSame( [], $this->get_scheduled( Model_Core_Font_Downloader::RETRY_EVENT ) );
	}

	public function test_a_retry_that_fails_gives_up() {
		$this->fail_font_downloads();
		$this->downloader->download( self::FONT );
		wp_clear_scheduled_hook( Model_Core_Font_Downloader::RETRY_EVENT );

		$downloader = $this->new_downloader();
		$downloader->retry_failed();

		$this->assertSame( [ self::FONT ], array_keys( $downloader->get_failed_files() ) );
		$this->assertSame( [], $this->get_scheduled( Model_Core_Font_Downloader::RETRY_EVENT ) );
		$this->assertCount( 1, $this->log_messages( "Core Font download failed again, and won't be retried automatically" ) );
	}

	public function test_a_later_failure_keeps_the_first_give_up_time() {
		$gave_up = time() - DAY_IN_SECONDS;
		$this->set_font_failures( [ self::FONT => $this->failure( $gave_up, time() - HOUR_IN_SECONDS ) ] );
		$this->fail_font_downloads();

		$downloader = $this->new_downloader();
		$this->assertFalse( $downloader->download( self::FONT ) );

		/* A later trigger may try again, but schedules no more retries and logs no second give-up */
		$this->assertSame( [], $this->get_scheduled( Model_Core_Font_Downloader::RETRY_EVENT ) );
		$this->assertSame( [ self::FONT => $gave_up ], $downloader->get_failed_files() );
		$this->assertCount( 0, $this->log_messages( "Core Font download failed again, and won't be retried automatically" ) );
		$this->assertGreaterThan( time() - HOUR_IN_SECONDS, $this->get_font_failures()[ self::FONT ]['time'] );
	}

	public function test_a_retry_only_takes_files_that_have_not_given_up() {
		$this->set_font_failures(
			[
				self::FONT     => $this->failure(),
				'FreeSans.ttf' => $this->failure( time() ),
			]
		);
		$this->serve_font_downloads();

		$this->new_downloader()->retry_failed();

		$this->assertSame( [ $this->downloader->get_manifest()[ self::FONT ]['url'] ], $this->requested );
	}

	public function test_a_retry_out_of_time_reschedules_itself_once() {
		$this->set_font_failures(
			[
				'Garuda.ttf' => $this->failure(),
				self::FONT   => $this->failure(),
			]
		);
		$this->serve_fixture_as( 'Chewy.ttf', 'Garuda.ttf' );
		$this->serve_font_downloads();

		$downloader = $this->seamed_sync( [], 0 );
		$downloader->retry_failed();
		$downloader->retry_failed();

		$this->assertCount( 2, $this->requested );
		$this->assertCount( 1, $this->get_scheduled( Model_Core_Font_Downloader::RETRY_EVENT ) );
	}

	public function test_a_retry_that_cannot_be_scheduled_is_logged() {
		add_filter( 'schedule_event', '__return_false' );
		$this->fail_font_downloads();

		$this->downloader->download( self::FONT );

		$this->assertCount( 1, $this->log_messages( 'Core Font download retry could not be scheduled' ) );
	}

	/**
	 * @dataProvider provider_failure_records
	 *
	 * @param mixed $stored
	 */
	public function test_failure_records_of_any_shape_are_read_safely( $stored, bool $backing_off, array $failed ) {
		$this->set_font_failures( $stored );
		$this->serve_font_downloads();

		$downloader = $this->new_downloader();
		$downloader->download( self::FONT );

		$this->assertSame( $backing_off, $this->requested === [] );
		$this->assertSame( $failed, array_keys( $downloader->get_failed_files() ) );
	}

	public function provider_failure_records(): array {
		$now = time();

		return [
			'a recent failure'       => [ [ self::FONT => [ 'time' => $now, 'gave_up' => 0 ] ], true, [] ],
			'a font that gave up'    => [ [ self::FONT => [ 'time' => $now, 'gave_up' => $now ] ], true, [ self::FONT ] ],
			'a string'               => [ 'junk', false, [] ],
			'a number'               => [ 42, false, [] ],
			'a numeric file'         => [ [ 7 => [ 'time' => $now, 'gave_up' => 0 ] ], false, [] ],
			'a bare timestamp'       => [ [ self::FONT => $now ], false, [] ],
			'a string timestamp'     => [ [ self::FONT => [ 'time' => (string) $now ] ], false, [] ],
			'a null entry'           => [ [ self::FONT => null ], false, [] ],
			'no time'                => [ [ self::FONT => [ 'gave_up' => $now ] ], false, [] ],
			'a list, not a record'   => [ [ self::FONT => [ $now ] ], false, [] ],
			'a junk give-up time'    => [ [ self::FONT => [ 'time' => $now, 'gave_up' => 'yes' ] ], true, [] ],
		];
	}

	public function test_a_single_site_keeps_the_failures_out_of_autoload() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'A Multisite keeps them in sitemeta' );
		}

		$this->fail_font_downloads();
		$this->downloader->download( self::FONT );

		global $wpdb;
		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */
		$autoload = $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM $wpdb->options WHERE option_name = %s", Model_Core_Font_Downloader::FAILURES ) );

		$this->assertContains( $autoload, [ 'no', 'off' ] );
	}

	public function test_installed_counts_are_the_required_files_per_downloadable_source() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavuserifcondensed' );
		$this->copied_fonts = array_merge( $this->copied_fonts, $this->font_paths( $this->downloader->get_font_files( 'dejavuserifcondensed' ) ) );

		$this->assertSame(
			[
				'core' => [
					'installed' => 0,
					'total'     => 4,
					'manifest'  => true,
				],
			],
			$this->downloader->get_installed_counts()
		);

		copy( $this->font_fixture( self::FONT ), $this->font_path() );

		$this->assertSame( 1, $this->downloader->get_installed_counts()['core']['installed'] );
	}

	public function test_a_source_with_a_missing_manifest_is_flagged() {
		$this->assertSame(
			[
				'core' => [
					'installed' => 0,
					'total'     => 0,
					'manifest'  => false,
				],
			],
			$this->seamed( [ 'sources' => $this->font_sources_without_a_manifest() ] )->get_installed_counts()
		);
	}

	/**
	 * @param bool $partial_move A move that leaves part of the font behind, as one across filesystems can
	 * @param bool $failed_move  A move that fails without touching the destination
	 */
	private function new_downloader( bool $partial_move = false, bool $failed_move = false ): Model_Core_Font_Downloader {
		$this->log = new TestHandler();
		$args      = [ GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), GPDFAPI::get_data_class(), GPDFAPI::get_options_class(), GPDFAPI::get_misc_class(), new Logger( 'test', [ $this->log ] ) ];

		if ( ! $partial_move && ! $failed_move ) {
			return new Model_Core_Font_Downloader( ...$args );
		}

		$downloader = new class( ...$args ) extends Model_Core_Font_Downloader {
			/** @var bool */
			public $partial = false;

			protected function move( string $from, string $to ): bool {
				if ( $this->partial ) {
					file_put_contents( $to, 'partial' );
				}

				return false;
			}
		};

		$downloader->partial = $partial_move;

		return $downloader;
	}

	private function seamed( array $seams ): Seamed_Core_Font_Downloader {
		$this->log = new TestHandler();

		return new Seamed_Core_Font_Downloader( new Logger( 'test', [ $this->log ] ), $seams );
	}

	/**
	 * A downloader whose required set is the files given, with the time budget given and the manifest
	 * serve_fixture_as() stubs on the shared one
	 *
	 * @param string[] $files
	 */
	private function seamed_sync( array $files, int $budget ): Seamed_Core_Font_Downloader {
		$this->copied_fonts = array_merge( $this->copied_fonts, $this->font_paths( $files ) );

		return $this->seamed(
			[
				'required' => $files,
				'budget'   => $budget,
				'manifest' => GPDFAPI::get_mvc_class( 'Model_Core_Font_Downloader' )->get_manifest(),
			]
		);
	}

	private function put_freeserif_on_disk(): void {
		foreach ( Seamed_Core_Font_Downloader::FREESERIF as $file ) {
			$this->copy_fixture( 'Chewy.ttf', $file );
		}
	}

	/**
	 * @param array $pdfs Each PDF's settings
	 */
	private function add_form_with_pdfs( array $pdfs ): void {
		$form_id = $this->gf_factory()->form->create(
			[],
			[
				'title'               => 'Fonts',
				'fields'              => [],
				'gfpdf_form_settings' => $pdfs,
			]
		);

		$this->assertIsInt( $form_id );
	}

	/**
	 * @param string[] $files
	 *
	 * @return string[]
	 */
	private function font_paths( array $files ): array {
		return array_map(
			static function ( string $file ): string {
				return GPDFAPI::get_data_class()->template_font_location . $file;
			},
			$files
		);
	}

	/**
	 * @return array[] The args of each queued sync
	 */
	private function get_scheduled_args(): array {
		$args = [];
		foreach ( _get_cron_array() ?: [] as $events ) {
			foreach ( $events[ Model_Core_Font_Downloader::SYNC_EVENT ] ?? [] as $event ) {
				$args[] = $event['args'];
			}
		}

		return $args;
	}

	/**
	 * @return int[] When each of the hook's events is due
	 */
	private function get_scheduled( string $hook ): array {
		$times = [];
		foreach ( _get_cron_array() ?: [] as $time => $events ) {
			foreach ( $events[ $hook ] ?? [] as $event ) {
				$times[] = (int) $time;
			}
		}

		return $times;
	}

	private function font_path(): string {
		return GPDFAPI::get_data_class()->template_font_location . self::FONT;
	}

	/**
	 * @return string[]
	 */
	private function get_tmp_files(): array {
		return glob( GPDFAPI::get_data_class()->template_tmp_location . '*.tmp' ) ?: [];
	}

	private function assertNoStrayTmpFiles(): void {
		$this->assertSame( $this->tmp_files, $this->get_tmp_files() );
	}

	/**
	 * @return array[]
	 */
	private function log_messages( string $message ): array {
		return array_filter(
			$this->log->getRecords(),
			static function ( $record ) use ( $message ) {
				return $record['message'] === $message;
			}
		);
	}
}
