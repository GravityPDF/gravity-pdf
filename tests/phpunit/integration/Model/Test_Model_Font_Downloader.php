<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\RecordsFontFailures;
use GFPDF\Tests\Concerns\Seamed_Font_Downloader;
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
class Test_Model_Font_Downloader extends TestCase {

	use CopiesFontFixtures;
	use RecordsFontFailures;

	private const FONT = 'DejaVuSerifCondensed.ttf';

	/**
	 * @var Model_Font_Downloader
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

		/* Only prune() reads it */
		$this->set_installer_manifest();
	}

	public function tear_down(): void {
		wp_clear_scheduled_hook( Model_Font_Downloader::CLEANUP_EVENT );
		wp_delete_file( $this->font_path( self::FONT ) );
		$this->remove_font_fixtures();
		$this->delete_font_failures();
		delete_transient( Model_Font_Downloader::SYNC_REMAINING );
		wp_clear_scheduled_hook( Model_Font_Downloader::SYNC_EVENT );
		GPDFAPI::get_options_class()->delete_option( 'default_font' );

		parent::tear_down();
	}

	public function test_download_saves_a_verified_font() {
		$this->serve_font_downloads();

		$this->assertTrue( $this->downloader->download( self::FONT ) );
		$this->assertFileEquals( $this->font_fixture( self::FONT ), $this->font_path( self::FONT ) );
		$this->assertSame( [ 'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/v1.1.1/' . self::FONT ], $this->requested );
		$this->assertNoStrayTmpFiles();
	}

	public function test_download_skips_a_font_already_in_the_fonts_folder() {
		copy( $this->font_fixture( self::FONT ), $this->font_path( self::FONT ) );
		$this->serve_font_downloads();

		$this->assertTrue( $this->downloader->download( self::FONT ) );
		$this->assertSame( [], $this->requested );
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
		$this->assertFileDoesNotExist( $this->font_path( self::FONT ) );
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
		$this->assertFileDoesNotExist( $this->font_path( self::FONT ) );
		$this->assertNoStrayTmpFiles();
		$this->assertCount( 1, $this->log_messages( 'Font Verification Failed' ) );
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
		mkdir( $this->font_path( self::FONT ) );
		$this->serve_font_downloads();

		try {
			$this->assertFalse( $this->downloader->download( self::FONT ) );
			$this->assertNoStrayTmpFiles();
			$this->assertCount( 1, $this->log_messages( 'Font Move Failed' ) );
		} finally {
			rmdir( $this->font_path( self::FONT ) );
		}
	}

	public function test_a_move_that_fails_part_way_removes_the_partial_font() {
		$downloader = $this->new_downloader( 'partial' );
		$this->serve_font_downloads();

		$this->assertFalse( $downloader->download( self::FONT ) );
		$this->assertFileDoesNotExist( $this->font_path( self::FONT ) );
		$this->assertNoStrayTmpFiles();
		$this->assertCount( 1, $this->log_messages( 'Font Move Failed' ) );
	}

	public function test_a_failed_move_keeps_a_valid_font_another_request_saved() {
		$downloader = $this->new_downloader( file_get_contents( $this->font_fixture( self::FONT ) ) );
		$this->serve_font_downloads();

		$this->assertFalse( $downloader->download( self::FONT ) );
		$this->assertFileEquals( $this->font_fixture( self::FONT ), $this->font_path( self::FONT ) );
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
			$this->assertCount( 1, $this->log_messages( 'Font folder is not writable' ) );
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
		$this->assertCount( 1, $this->log_messages( 'Font temporary file could not be created' ) );
	}

	public function test_a_missing_bundled_fallback_is_logged() {
		$font_sources = new class( GPDFAPI::get_data_class(), GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' ), GPDFAPI::get_log_class() ) extends Model_Font_Sources {
			public function get_fallback_file(): string {
				return '/missing/gfpdf-Arimo-Regular.ttf';
			}
		};

		$this->log  = new TestHandler();
		$downloader = new Model_Font_Downloader( $font_sources, GPDFAPI::get_data_class(), GPDFAPI::get_options_class(), GPDFAPI::get_misc_class(), new Logger( 'test', [ $this->log ] ), GPDFAPI::get_mvc_class( 'Model_Required_Fonts' ) );

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

		$this->assertIsInt( $this->get_back_off( self::FONT ) );
		$this->assertSame( [ self::FONT ], array_keys( $this->get_font_failures() ) );

		$this->requested = [];
		$this->assertFalse( $this->new_downloader()->download( self::FONT ) );
		$this->assertSame( [], $this->requested );
	}

	public function test_a_back_off_expires_after_fifteen_minutes() {
		$this->fail_font_downloads();
		$before = time();
		$this->downloader->download( self::FONT );

		$this->assertGreaterThanOrEqual( $before + Model_Font_Downloader::BACK_OFF, $this->get_back_off_expiry( self::FONT ) );
		$this->assertLessThanOrEqual( time() + Model_Font_Downloader::BACK_OFF, $this->get_back_off_expiry( self::FONT ) );

		/* Once it has, the file downloads again */
		remove_all_filters( 'pre_http_request' );
		$this->requested = [];
		$this->serve_font_downloads();
		$this->delete_back_off( self::FONT );

		$this->assertTrue( $this->new_downloader()->download( self::FONT ) );
		$this->assertCount( 1, $this->requested );
	}

	public function test_the_kill_switch_filter_is_given_the_file() {
		$given = [];
		add_filter(
			'gfpdf_auto_install_fonts',
			static function ( $download, $file ) use ( &$given ) {
				$given[] = [ $download, $file ];

				return $download;
			},
			10,
			2
		);
		$this->serve_font_downloads();

		$this->assertTrue( $this->downloader->download( self::FONT ) );
		$this->assertSame( [ [ true, self::FONT ] ], $given );
	}

	/**
	 * @dataProvider provider_kill_switch
	 *
	 * @param bool|null $constant GPDF_AUTO_INSTALL_FONTS, or null when it isn't defined
	 * @param mixed     $filtered What the filter returns
	 */
	public function test_the_kill_switch_constant_wins_over_the_filter( ?bool $constant, $filtered, bool $downloads ) {
		add_filter(
			'gfpdf_auto_install_fonts',
			static function () use ( $filtered ) {
				return $filtered;
			}
		);
		$this->serve_font_downloads();

		$downloader = new class( ...$this->downloader_args() ) extends Model_Font_Downloader {
			/** @var bool|null */
			public $constant;

			protected function get_auto_install_constant(): ?bool {
				return $this->constant;
			}
		};
		$downloader->constant = $constant;

		$this->assertSame( $downloads, $downloader->download( self::FONT ) );
		$this->assertCount( $downloads ? 1 : 0, $this->requested );
		$this->assertFalse( $this->get_back_off( self::FONT ) );
		$this->assertEmpty( $this->get_font_failures() );
	}

	public function provider_kill_switch(): array {
		return [
			'the constant on, the filter off'  => [ true, false, true ],
			'the constant off, the filter on'  => [ false, true, false ],
			'no constant, a falsy filter'      => [ null, '', false ],
			'no constant, a truthy filter'     => [ null, 'yes', true ],
		];
	}

	/**
	 * Defined in its own process, so the constant can't reach the other tests
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_the_kill_switch_constant_turns_downloads_off() {
		define( 'GPDF_AUTO_INSTALL_FONTS', false );
		add_filter( 'gfpdf_auto_install_fonts', '__return_true' );
		$this->serve_font_downloads();

		$this->assertFalse( $this->downloader->download( self::FONT ) );
		$this->assertSame( [], $this->requested );
	}

	public function test_download_saves_the_font_under_its_hashed_name_when_another_font_holds_the_name() {
		$this->put_other_font_at( self::FONT, self::FONT );
		$this->serve_font_downloads();
		$hashed = GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_hashed_name( self::FONT );

		$this->assertTrue( $this->downloader->download( self::FONT ) );
		$this->assertFileEquals( $this->font_fixture( self::FONT ), $this->font_path( $hashed ) );
		$this->assertFileEquals( $this->font_fixture( 'Chewy.ttf' ), $this->font_path( self::FONT ), 'The other font is left alone' );
		$this->assertSame( $this->font_path( $hashed ), $this->downloader->get_font_path( self::FONT ) );
		$this->assertCount( 1, $this->log_messages( 'Font saved under a new name as another font has its name' ) );
		$this->assertNoStrayTmpFiles();

		/* The next request finds the copy and asks for nothing */
		$this->requested = [];
		$this->assertTrue( $this->new_downloader()->download( self::FONT ) );
		$this->assertSame( [], $this->requested );
	}

	public function test_saving_a_hashed_copy_removes_the_copy_of_an_older_release_but_not_a_lookalike() {
		$old = 'DejaVuSerifCondensed-' . substr( (string) key( $this->fixture_blob( 'DejaVuSans.ttf' ) ), 0, 8 ) . '.ttf';

		$this->put_other_font_at( self::FONT, self::FONT );
		$this->copy_fixture( 'DejaVuSans.ttf', $old );
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSerifCondensed-deadbeef.ttf' );
		$this->serve_font_downloads();

		$this->assertTrue( $this->downloader->download( self::FONT ) );
		$this->assertFileDoesNotExist( $this->font_path( $old ) );
		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed-deadbeef.ttf' ), 'Its name is not its own hash' );
		$this->assertFileExists( $this->font_path( GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_hashed_name( self::FONT ) ) );
		$this->assertFileEquals( $this->font_fixture( 'Chewy.ttf' ), $this->font_path( self::FONT ) );
	}

	public function test_the_hashed_name_is_the_stem_the_first_eight_of_the_sha_and_the_extension() {
		$sources = GPDFAPI::get_mvc_class( 'Model_Font_Sources' );
		$sha     = $sources->get_manifest()[ self::FONT ]['sha'];

		$this->assertSame( 'DejaVuSerifCondensed-' . substr( $sha, 0, 8 ) . '.ttf', $sources->get_hashed_name( self::FONT ) );
		$this->assertSame( '', $sources->get_hashed_name( 'NotAFont.ttf' ) );
		$this->assertTrue( $sources->is_hashed_copy( $sources->get_hashed_name( self::FONT ) ) );
		$this->assertFalse( $sources->is_hashed_copy( self::FONT ) );
		$this->assertFalse( $sources->is_hashed_copy( 'DejaVuSerifCondensed-00000000.ttf' ) );
	}

	public function test_another_fonts_file_stays_when_downloads_are_off() {
		$this->put_other_font_at( self::FONT, self::FONT );
		add_filter( 'gfpdf_auto_install_fonts', '__return_false' );
		$this->serve_font_downloads();

		$this->assertFalse( $this->downloader->download( self::FONT ) );
		$this->assertSame( [], $this->requested );
		$this->assertSame( $this->font_path( self::FONT ), $this->downloader->get_font_path( self::FONT ) );
		$this->assertTrue( GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->is_downloaded( self::FONT ), 'mPDF can still load it' );
	}

	public function test_a_failed_download_beside_another_font_is_recorded_under_the_manifest_name() {
		$this->put_other_font_at( self::FONT, self::FONT );
		$this->fail_font_downloads();

		$this->assertFalse( $this->downloader->download( self::FONT ) );
		$this->assertSame( [ self::FONT ], array_keys( $this->get_font_failures() ) );
		$this->assertIsInt( $this->get_back_off( self::FONT ) );
		$this->assertFileEquals( $this->font_fixture( 'Chewy.ttf' ), $this->font_path( self::FONT ) );
	}

	public function test_a_file_of_a_known_size_is_not_hashed_unless_verifying() {
		$this->put_other_font_at( self::FONT, self::FONT, true );
		$this->serve_font_downloads();
		$downloader = $this->seamed( [] );

		$this->assertTrue( $downloader->download( self::FONT ) );
		$this->assertSame( [], $downloader->hashed );
		$this->assertSame( [], $this->requested );

		/* Verifying finds that it isn't the manifest's */
		$this->assertTrue( $downloader->download( self::FONT, true ) );
		$this->assertSame( [ self::FONT ], $downloader->hashed );
		$this->assertFileEquals( $this->font_fixture( self::FONT ), $this->font_path( GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_hashed_name( self::FONT ) ) );
	}

	public function test_a_file_from_an_older_release_is_kept_when_verifying() {
		copy( $this->font_fixture( 'DejaVuSerifCondensed-v1.0.0.ttf' ), $this->font_path( self::FONT ) );
		$this->copied_fonts[] = $this->font_path( self::FONT );
		$this->serve_font_downloads();

		/* The real list of what the installer downloaded, not the fixtures set_up() stands in for it */
		$this->set_private( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), 'legacy_manifests', [] );

		$this->assertTrue( $this->downloader->download( self::FONT, true ) );
		$this->assertSame( [], $this->requested );
	}

	public function test_sync_hashes_each_required_file_on_disk_once_and_saves_the_other_fonts_under_new_names() {
		$this->copy_fixture( self::FONT );
		$this->put_other_font_at( 'DejaVuSansCondensed.ttf', 'DejaVuSansCondensed.ttf', true );
		$this->serve_font_downloads();
		$sources = GPDFAPI::get_mvc_class( 'Model_Font_Sources' );

		$downloader = $this->seamed_sync( [ self::FONT, 'DejaVuSansCondensed.ttf' ] );
		$downloader->sync();

		$this->assertEqualsCanonicalizing( [ self::FONT, 'DejaVuSansCondensed.ttf' ], $downloader->hashed );
		$this->assertCount( 1, $this->requested );
		$this->assertFileExists( $this->font_path( $sources->get_hashed_name( 'DejaVuSansCondensed.ttf' ) ) );
		$this->assertFileDoesNotExist( $this->font_path( $sources->get_hashed_name( self::FONT ) ) );

		/* A file with a copy under its hashed name is not hashed again */
		$next = $this->seamed_sync( [ self::FONT, 'DejaVuSansCondensed.ttf' ] );
		$next->sync();

		$this->assertSame( [ self::FONT ], $next->hashed );
		$this->assertCount( 1, $this->requested );
	}

	public function test_a_retry_for_a_file_beside_another_font_is_not_cleared() {
		$this->put_other_font_at( self::FONT, self::FONT );
		$this->set_font_failures( [ self::FONT => $this->failure() ] );
		$this->set_back_off( self::FONT );
		$this->serve_font_downloads();

		$this->seamed_sync( [] )->sync();

		$this->assertFileExists( $this->font_path( GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_hashed_name( self::FONT ) ) );
		$this->assertEmpty( $this->get_font_failures() );
	}

	public function test_prune_never_removes_a_hashed_copy_or_a_replaced_file() {
		$sources = GPDFAPI::get_mvc_class( 'Model_Font_Sources' );
		$hashed  = $sources->get_hashed_name( self::FONT );

		/* The unmodified installer file goes, beside the manifest's copy of it */
		$this->copy_fixture( self::FONT );
		$this->copy_fixture( self::FONT, $hashed );
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSans.ttf' );
		$this->copy_fixture( 'DejaVuSans.ttf', $sources->get_hashed_name( 'DejaVuSans.ttf' ) );

		$this->assertSame( [ self::FONT ], $this->downloader->prune( [] ) );
		$this->assertFileExists( $this->font_path( $hashed ) );
		$this->assertFileExists( $this->font_path( 'DejaVuSans.ttf' ), 'A replacement' );
		$this->assertFileExists( $this->font_path( $sources->get_hashed_name( 'DejaVuSans.ttf' ) ) );
	}

	public function test_the_missing_files_are_the_required_files_not_on_disk() {
		$this->list_fixture_as( 'Chewy.ttf', 'Garuda.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Garuda.ttf' );

		$this->assertSame( [ self::FONT ], $this->seamed( [ 'required' => [ 'Garuda.ttf', self::FONT ] ] )->get_missing_files() );
	}

	public function test_a_file_another_font_holds_the_name_of_is_missing_until_its_hashed_copy_is_saved() {
		$this->put_other_font_at( self::FONT, self::FONT );
		$downloader = $this->seamed( [ 'required' => [ self::FONT ] ] );

		$this->assertSame( [ self::FONT ], $downloader->get_missing_files() );

		$this->copy_fixture( self::FONT, GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_hashed_name( self::FONT ) );
		$this->assertSame( [], $downloader->get_missing_files() );
	}

	public function test_the_auto_install_status_does_not_depend_on_the_missing_files() {
		add_filter( 'gfpdf_auto_install_fonts', '__return_false' );

		$downloader = $this->seamed( [ 'required' => [] ] );
		$this->assertSame( [], $downloader->get_missing_files() );
		$this->assertSame( 'Off (gfpdf_auto_install_fonts filter)', $downloader->get_auto_install_status() );
		$this->assertNull( $downloader->get_auto_install_blocker( [] ) );
	}

	public function test_the_auto_install_status_is_partly_off_when_the_filter_blocks_some_files() {
		$this->list_fixture_as( 'Chewy.ttf', 'Garuda.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Garuda.ttf' );
		add_filter(
			'gfpdf_auto_install_fonts',
			static function ( $download, $file ) {
				return $file !== 'Garuda.ttf';
			},
			10,
			2
		);

		$downloader = $this->seamed( [ 'required' => [ 'Garuda.ttf', self::FONT ] ] );
		$this->assertSame( 'Partly off (gfpdf_auto_install_fonts filter)', $downloader->get_auto_install_status() );

		/* Installed, so nothing it blocks is needed yet */
		$this->assertNull( $downloader->get_auto_install_blocker( $downloader->get_missing_files() ) );

		wp_delete_file( $this->font_path( 'Garuda.ttf' ) );
		$this->assertSame( 'gfpdf_auto_install_fonts filter', $downloader->get_auto_install_blocker( $downloader->get_missing_files() ) );
	}

	/**
	 * @dataProvider provider_auto_install_status
	 *
	 * @param bool|null $constant GPDF_AUTO_INSTALL_FONTS, or null when it isn't defined
	 */
	public function test_the_auto_install_status_names_what_turned_downloads_off( ?bool $constant, bool $filtered, string $expected ) {
		add_filter( 'gfpdf_auto_install_fonts', $filtered ? '__return_true' : '__return_false' );

		$downloader = $this->seamed(
			[
				'required' => [ self::FONT ],
				'constant' => $constant,
			]
		);

		$this->assertSame( $expected, $downloader->get_auto_install_status() );
	}

	public function provider_auto_install_status(): array {
		return [
			'the constant on, the filter off' => [ true, false, 'On' ],
			'the constant off, the filter on' => [ false, true, 'Off (GPDF_AUTO_INSTALL_FONTS)' ],
			'no constant, the filter off'     => [ null, false, 'Off (gfpdf_auto_install_fonts filter)' ],
			'no constant, the filter on'      => [ null, true, 'On' ],
		];
	}

	public function test_prune_removes_unmodified_core_fonts_and_licences() {
		$this->set_installer_manifest( array_combine( static::$core_fixtures, static::$core_fixtures ) + [ 'DejaVuinfo.txt' => 'chewy-LICENSE.txt' ] );
		$this->copy_fixture( 'chewy-LICENSE.txt', 'DejaVuinfo.txt' );
		$this->copy_fixture( self::FONT );
		$this->copy_fixture( 'DejaVuSans.ttf' );
		$this->copy_fixture( 'DejaVuSans-Bold.ttf' );
		$this->copy_fixture( 'DejaVuSansCondensed.ttf' );
		$this->copy_fixture( 'Chewy.ttf' );
		$this->change_font( 'DejaVuSans-Bold.ttf', 'same size, other bytes' );
		file_put_contents( $this->font_path( 'DejaVuSansCondensed.ttf' ), 'a longer file', FILE_APPEND );

		$removed = $this->downloader->prune( [ 'DejaVuSans.ttf', 'NotOnDisk.ttf' ] );

		$this->assertEqualsCanonicalizing( [ self::FONT, 'DejaVuinfo.txt' ], $removed );
		$this->assertFileDoesNotExist( $this->font_path( self::FONT ) );
		$this->assertFileDoesNotExist( $this->font_path( 'DejaVuinfo.txt' ) );
		$this->assertFileExists( $this->font_path( 'DejaVuSans.ttf' ), 'Kept' );
		$this->assertFileExists( $this->font_path( 'DejaVuSans-Bold.ttf' ), 'Modified, same size' );
		$this->assertFileExists( $this->font_path( 'DejaVuSansCondensed.ttf' ), 'Modified, other size' );
		$this->assertFileExists( $this->font_path( 'Chewy.ttf' ), 'Not a core font' );

		$summary = array_values( $this->log_messages( 'Core Font cleanup complete' ) );
		$this->assertCount( 1, $summary );
		$this->assertSame(
			[
				'removed' => 2,
				'bytes'   => (int) filesize( $this->font_fixture( self::FONT ) ) + (int) filesize( $this->font_fixture( 'chewy-LICENSE.txt' ) ),
			],
			$summary[0]['context']
		);
	}

	public function test_prune_never_hashes_kept_files_or_those_of_another_size() {
		$this->copy_fixture( self::FONT );
		$this->copy_fixture( 'DejaVuSans.ttf' );
		$this->copy_fixture( 'DejaVuSans-Bold.ttf' );
		file_put_contents( $this->font_path( 'DejaVuSans.ttf' ), 'resized', FILE_APPEND );

		$downloader = new class( ...$this->downloader_args() ) extends Model_Font_Downloader {
			/** @var string[] */
			public $hashed = [];

			protected function is_unmodified( string $path, array $entry ): bool {
				$this->hashed[] = basename( $path );

				return parent::is_unmodified( $path, $entry );
			}
		};

		$this->assertSame( [ 'DejaVuSans-Bold.ttf' ], $downloader->prune( [ self::FONT ] ) );
		$this->assertSame( [ 'DejaVuSans-Bold.ttf' ], $downloader->hashed );
	}

	public function test_prune_deletes_nothing_without_a_manifest() {
		$this->set_installer_manifest( [] );
		$this->copy_fixture( self::FONT );

		$this->assertSame( [], $this->downloader->prune( [] ) );
		$this->assertFileExists( $this->font_path( self::FONT ) );
		$this->assertCount( 1, $this->log_messages( 'Core Font cleanup skipped as the font manifest could not be read' ) );
	}

	public function test_prune_keeps_the_fonts_downloads_are_turned_off_for() {
		$this->copy_fixture( self::FONT );
		$this->copy_fixture( 'DejaVuSans.ttf' );
		add_filter(
			'gfpdf_auto_install_fonts',
			static function ( $download, $file ) {
				return $file !== self::FONT;
			},
			10,
			2
		);

		$this->assertSame( [ 'DejaVuSans.ttf' ], $this->downloader->prune( [] ) );
		$this->assertFileExists( $this->font_path( self::FONT ) );

		$kept = array_values( $this->log_messages( 'Core Font cleanup kept the fonts that downloads are turned off for' ) );
		$this->assertSame( [ 'files' => [ self::FONT ] ], $kept[0]['context'] );

		/* Turned off for every font, nothing goes */
		add_filter( 'gfpdf_auto_install_fonts', '__return_false', 20 );
		$this->assertSame( [], $this->downloader->prune( [] ) );
		$this->assertFileExists( $this->font_path( self::FONT ) );
	}

	public function test_prune_logs_a_font_it_could_not_delete() {
		$this->copy_fixture( self::FONT );
		$this->copy_fixture( 'DejaVuSans.ttf' );
		$font = $this->font_path( self::FONT );
		add_filter(
			'wp_delete_file',
			static function ( $file ) use ( $font ) {
				return $file === $font ? '' : $file;
			}
		);

		$this->assertSame( [ 'DejaVuSans.ttf' ], $this->downloader->prune( [] ) );
		$this->assertFileExists( $font );
		$this->assertCount( 1, $this->log_messages( 'Core Font could not be removed' ) );
	}

	public function test_prune_without_a_fonts_folder_removes_nothing() {
		$data     = GPDFAPI::get_data_class();
		$original = $data->template_font_location;

		try {
			$data->template_font_location = '/tmp/gpdf-missing-fonts-' . uniqid() . '/';

			$this->assertSame( [], $this->downloader->prune( [] ) );
			$this->assertCount( 1, $this->log_messages( 'Core Font cleanup complete' ) );
		} finally {
			$data->template_font_location = $original;
		}
	}

	public function test_prune_keeps_files_written_since_the_time_given() {
		$this->copy_fixture( self::FONT );
		$this->copy_fixture( 'DejaVuSans.ttf' );
		touch( $this->font_path( 'DejaVuSans.ttf' ), time() - HOUR_IN_SECONDS );

		$this->assertSame( [ 'DejaVuSans.ttf' ], $this->downloader->prune( [], time() - MINUTE_IN_SECONDS ) );
	}

	public function test_the_cleanup_reads_a_site_per_run_once_the_budget_is_spent_then_cleans_the_folder() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );
		if ( is_multisite() ) {
			self::factory()->blog->create_many( 2 );
		}

		$this->copy_fixture( self::FONT );
		touch( $this->font_path( self::FONT ), time() - HOUR_IN_SECONDS );

		$downloader = $this->seamed( [ 'budget' => 0 ] );
		$sites      = GPDFAPI::get_mvc_class( 'Model_Required_Fonts' )->get_site_ids();
		$downloader->schedule_cleanup( 'arimo' );

		foreach ( array_keys( $sites ) as $i ) {
			$this->assertSame( array_slice( $sites, $i ), get_network_option( get_main_network_id(), Model_Font_Downloader::CLEANUP )['sites'] );
			$this->assertNotFalse( wp_next_scheduled( Model_Font_Downloader::CLEANUP_EVENT ) );
			$this->assertFileExists( $this->font_path( self::FONT ) );
			wp_clear_scheduled_hook( Model_Font_Downloader::CLEANUP_EVENT );

			$downloader->cleanup();
		}

		$this->assertFalse( wp_next_scheduled( Model_Font_Downloader::CLEANUP_EVENT ) );
		$this->assertTrue( get_network_option( get_main_network_id(), Model_Font_Downloader::CLEANUP ) );
		$this->assertFileDoesNotExist( $this->font_path( self::FONT ) );
	}

	/**
	 * @dataProvider provider_cleanup_states
	 *
	 * @param mixed $state
	 */
	public function test_the_cleanup_is_queued_once_per_install( $state ) {
		$this->copy_fixture( self::FONT );
		update_network_option( get_main_network_id(), Model_Font_Downloader::CLEANUP, $state );

		$this->downloader->schedule_cleanup( 'arimo' );
		$this->downloader->cleanup();

		$this->assertFalse( wp_next_scheduled( Model_Font_Downloader::CLEANUP_EVENT ) );
		$this->assertFileExists( $this->font_path( self::FONT ) );
	}

	public function provider_cleanup_states(): array {
		return [
			'done'    => [ true ],
			'corrupt' => [ 'yes' ],
		];
	}

	/**
	 * A deliberate trade-off: a Font Manager record pointing at a core font's file loses it, and mPDF downloads it
	 * again by name when the font is next used
	 */
	public function test_a_custom_font_copying_a_core_font_is_downloaded_again_after_prune() {
		$this->copy_fixture( self::FONT );
		$this->add_record( 'serif-copy', [ 'regular' => self::FONT ] );

		$this->assertSame( [ self::FONT ], $this->downloader->prune( [] ) );
		$this->assertFileDoesNotExist( $this->font_path( self::FONT ) );

		$this->serve_font_downloads();
		$fontdata = GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_font_data( true );
		$finder   = $this->downloader->get_font_file_finder(
			[
				'fontDir'  => [ GPDFAPI::get_data_class()->template_font_location ],
				'fontdata' => $fontdata,
			]
		);

		$this->assertSame( self::FONT, $fontdata['serif-copy']['R'] );
		$this->assertSame( $this->font_path( self::FONT ), $finder->findFontFile( $fontdata['serif-copy']['R'] ) );
		$this->assertFileEquals( $this->font_fixture( self::FONT ), $this->font_path( self::FONT ) );
		$this->assertCount( 1, $this->requested );
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
		$this->assertCount( 1, $this->log_messages( 'Font event could not be scheduled' ) );
	}

	public function test_sync_downloads_the_required_set_as_it_is_when_it_runs() {
		$this->serve_font_downloads();
		GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );
		$this->downloader->schedule_sync();

		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavuserifcondensed' );
		$this->copied_fonts = array_merge( $this->copied_fonts, $this->font_paths( GPDFAPI::get_mvc_class( 'Model_Required_Fonts' )->get_font_files( 'dejavuserifcondensed' ) ) );
		$this->new_downloader()->sync();

		$this->assertContains( GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_manifest()[ self::FONT ]['url'], $this->requested );
		$this->assertFileExists( $this->font_path( self::FONT ) );
	}

	public function test_sync_skips_files_already_on_disk() {
		$this->copy_fixture( 'Chewy.ttf', 'Garuda.ttf' );
		$this->list_fixture_as( 'Chewy.ttf', 'Garuda.ttf' );
		$this->serve_font_downloads();

		$this->seamed_sync( [ 'Garuda.ttf', self::FONT ] )->sync();

		$this->assertSame( [ GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_manifest()[ self::FONT ]['url'] ], $this->requested );
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
		$this->assertSame( [ self::FONT ], get_transient( Model_Font_Downloader::SYNC_REMAINING ) );
		$this->assertCount( 1, $this->log_messages( 'Font sync paused until its next run' ) );

		/* The next run takes what's left without working the required set out again */
		wp_clear_scheduled_hook( Model_Font_Downloader::SYNC_EVENT );
		$downloader->set_required( [] );
		$downloader->sync();

		$this->assertCount( 2, $this->requested );
		$this->assertFileExists( $this->font_path( self::FONT ) );
		$this->assertFalse( get_transient( Model_Font_Downloader::SYNC_REMAINING ) );
		$this->assertSame( [], $this->get_scheduled_args() );
	}

	public function test_a_fresh_trigger_works_the_required_set_out_again() {
		set_transient( Model_Font_Downloader::SYNC_REMAINING, [ 'Garuda.ttf' ], DAY_IN_SECONDS );

		$this->downloader->schedule_sync();

		$this->assertFalse( get_transient( Model_Font_Downloader::SYNC_REMAINING ) );
	}

	/**
	 * @dataProvider provider_junk_remaining
	 *
	 * @param mixed $remaining
	 */
	public function test_a_junk_remaining_list_is_guarded( $remaining, array $expected ) {
		$this->serve_font_downloads();
		set_transient( Model_Font_Downloader::SYNC_REMAINING, $remaining, DAY_IN_SECONDS );

		$this->seamed_sync( [ 'Garuda.ttf' ] )->sync();

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
		$this->seamed_sync( [ 'NotAFixture.ttf', 'FreeSans.ttf', self::FONT ] )->sync();

		$this->assertFileExists( $this->font_path( self::FONT ) );
		$this->assertCount( 2, $this->requested );

		/* The one event is FreeSans's retry */
		$this->assertSame( [ [] ], $this->get_scheduled_args() );
		$this->assertSame( [ 'FreeSans.ttf' ], array_keys( $this->get_font_failures() ) );
	}

	public function test_a_first_failure_schedules_the_sync_for_its_retry() {
		$this->fail_font_downloads();
		$downloader = $this->seamed( [ 'jitter' => 0 ] );

		$before = time();
		$downloader->download( self::FONT );
		$downloader->download( 'FreeSans.ttf' );
		$after = time();

		/* With no jitter the retry is due at once, and the second failure doesn't add another event */
		$events = $this->get_scheduled();
		$this->assertCount( 1, $events );
		$this->assertGreaterThanOrEqual( $before, $events[0] );
		$this->assertLessThanOrEqual( $after, $events[0] );
		$this->assertSame( [ 0, 0 ], array_column( $this->get_font_failures(), 'gave_up' ) );
		$this->assertSame( [], $downloader->get_failed_files() );
	}

	public function test_the_retry_is_due_within_five_minutes() {
		$this->fail_font_downloads();

		$before = time();
		$this->downloader->download( self::FONT );
		$after = time();

		$retry_at = $this->get_font_failures()[ self::FONT ]['retry_at'];
		$this->assertGreaterThanOrEqual( $before, $retry_at );
		$this->assertLessThanOrEqual( $after + 5 * MINUTE_IN_SECONDS, $retry_at );
		$this->assertSame( [ $retry_at ], $this->get_scheduled() );
	}

	public function test_a_retry_due_after_a_pending_sync_joins_it() {
		$this->downloader->schedule_sync();
		$pending = $this->get_scheduled();

		$this->fail_font_downloads();
		$this->seamed( [ 'jitter' => 0 ] )->download( self::FONT );

		$this->assertSame( $pending, $this->get_scheduled() );
	}

	public function test_an_earlier_retry_pulls_the_sync_forward() {
		wp_schedule_single_event( time() + HOUR_IN_SECONDS, Model_Font_Downloader::SYNC_EVENT );

		$this->fail_font_downloads();
		$this->seamed( [ 'jitter' => 0 ] )->download( self::FONT );

		$events = $this->get_scheduled();
		$this->assertCount( 1, $events );
		$this->assertLessThanOrEqual( time(), $events[0] );
	}

	public function test_a_later_trigger_does_not_delay_a_due_retry() {
		$due = time() - 1;
		$this->set_font_failures( [ self::FONT => $this->failure( 0, $due ) ] );
		wp_schedule_single_event( $due, Model_Font_Downloader::SYNC_EVENT );

		$this->downloader->schedule_sync();

		$this->assertSame( [ $due ], $this->get_scheduled() );
	}

	public function test_a_sync_before_a_retry_is_due_reschedules_for_it() {
		$retry_at = time() + 2 * MINUTE_IN_SECONDS;
		$this->set_font_failures( [ self::FONT => $this->failure( 0, $retry_at ) ] );
		$this->set_back_off( self::FONT );
		$this->serve_font_downloads();

		/* Required, but still backing off and not yet due */
		$this->seamed_sync( [ self::FONT ] )->sync();

		$this->assertSame( [], $this->requested );
		$this->assertSame( [ $retry_at ], $this->get_scheduled() );
	}

	public function test_a_due_retry_that_succeeds_clears_both_stores() {
		$this->fail_font_downloads();
		$this->seamed( [ 'jitter' => 0 ] )->download( self::FONT );
		$this->assertIsInt( $this->get_back_off( self::FONT ) );

		remove_all_filters( 'pre_http_request' );
		$this->serve_font_downloads();
		wp_clear_scheduled_hook( Model_Font_Downloader::SYNC_EVENT );

		/* Minutes later, inside the back-off */
		$this->seamed_sync( [] )->sync();

		$this->assertFileExists( $this->font_path( self::FONT ) );
		$this->assertEmpty( $this->get_font_failures() );
		$this->assertFalse( $this->get_back_off( self::FONT ) );
		$this->assertSame( [], $this->get_scheduled() );
	}

	public function test_a_retry_that_fails_gives_up() {
		$this->fail_font_downloads();
		$this->seamed( [ 'jitter' => 0 ] )->download( self::FONT );
		wp_clear_scheduled_hook( Model_Font_Downloader::SYNC_EVENT );

		$downloader = $this->seamed_sync( [] );
		$downloader->sync();

		$this->assertSame( [ self::FONT ], array_keys( $downloader->get_failed_files() ) );
		$this->assertSame( [], $this->get_scheduled() );
		$this->assertCount( 1, $this->log_messages( "Font download failed again, and won't be retried automatically" ) );
	}

	public function test_a_later_failure_keeps_the_first_give_up_time() {
		$gave_up = time() - DAY_IN_SECONDS;
		$this->set_font_failures( [ self::FONT => $this->failure( $gave_up ) ] );
		$this->fail_font_downloads();

		$downloader = $this->new_downloader();
		$this->assertFalse( $downloader->download( self::FONT ) );

		/* A later trigger may try again, but schedules no more retries and logs no second give-up */
		$this->assertSame( [], $this->get_scheduled() );
		$this->assertSame( [ self::FONT => $gave_up ], $downloader->get_failed_files() );
		$this->assertCount( 0, $this->log_messages( "Font download failed again, and won't be retried automatically" ) );
		$this->assertIsInt( $this->get_back_off( self::FONT ) );
	}

	public function test_a_sync_neither_retries_nor_downloads_a_file_that_gave_up() {
		$this->set_font_failures(
			[
				self::FONT     => $this->failure(),
				'FreeSans.ttf' => $this->failure( time() ),
			]
		);
		$this->set_back_off( self::FONT );
		$this->serve_font_downloads();

		$this->seamed_sync( [ 'FreeSans.ttf' ] )->sync();

		$this->assertSame( [ GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_manifest()[ self::FONT ]['url'] ], $this->requested );
	}

	public function test_a_due_retry_for_a_file_already_on_disk_is_cleared() {
		$this->set_font_failures( [ self::FONT => $this->failure() ] );
		$this->set_back_off( self::FONT );
		$this->copy_fixture( self::FONT );
		$this->serve_font_downloads();

		$this->seamed_sync( [] )->sync();

		$this->assertSame( [], $this->requested );
		$this->assertEmpty( $this->get_font_failures() );
		$this->assertFalse( $this->get_back_off( self::FONT ) );
		$this->assertSame( [], $this->get_scheduled() );
	}

	public function test_a_sync_schedules_its_first_failures_retry_once_it_is_done() {
		$this->fail_font_downloads();

		$this->seamed_sync( [ self::FONT, 'FreeSans.ttf' ] )->sync();

		$retry_at = min( array_column( $this->get_font_failures(), 'retry_at' ) );
		$this->assertSame( [ $retry_at ], $this->get_scheduled() );
	}

	public function test_a_sync_out_of_time_keeps_the_due_retries_for_its_next_run() {
		$this->set_font_failures(
			[
				'Garuda.ttf' => $this->failure(),
				self::FONT   => $this->failure(),
			]
		);
		$this->serve_fixture_as( 'Chewy.ttf', 'Garuda.ttf' );
		$this->serve_font_downloads();

		$downloader = $this->seamed_sync( [], 0 );
		$downloader->sync();

		$this->assertCount( 1, $this->requested );
		$this->assertCount( 1, $this->get_scheduled() );

		wp_clear_scheduled_hook( Model_Font_Downloader::SYNC_EVENT );
		$downloader->sync();

		$this->assertCount( 2, $this->requested );
		$this->assertSame( [], $this->get_scheduled() );
	}

	public function test_a_retry_that_cannot_be_scheduled_is_logged() {
		add_filter( 'schedule_event', '__return_false' );
		$this->fail_font_downloads();

		$this->downloader->download( self::FONT );

		$this->assertCount( 1, $this->log_messages( 'Font event could not be scheduled' ) );
	}

	/**
	 * @dataProvider provider_failure_records
	 *
	 * @param mixed $stored
	 */
	public function test_failure_records_of_any_shape_are_read_safely( $stored, bool $retried, array $failed ) {
		$this->set_font_failures( $stored );
		$this->serve_font_downloads();

		$downloader = $this->seamed_sync( [] );
		$downloader->sync();

		$this->assertSame( $retried, $this->requested !== [] );
		$this->assertSame( $failed, array_keys( $downloader->get_failed_files() ) );
	}

	public function provider_failure_records(): array {
		/* PHPUnit runs every provider before the suite, so no case here may depend on how much time has passed */
		$now = time();

		return [
			'a due retry'            => [ [ self::FONT => [ 'retry_at' => $now, 'gave_up' => 0 ] ], true, [] ],
			'a font that gave up'    => [ [ self::FONT => [ 'retry_at' => 0, 'gave_up' => $now ] ], false, [ self::FONT ] ],
			'a give-up with a retry' => [ [ self::FONT => [ 'retry_at' => $now, 'gave_up' => $now ] ], false, [ self::FONT ] ],
			'a string'               => [ 'junk', false, [] ],
			'a number'               => [ 42, false, [] ],
			'a numeric file'         => [ [ 7 => [ 'retry_at' => $now, 'gave_up' => 0 ] ], false, [] ],
			'a bare timestamp'       => [ [ self::FONT => $now ], false, [] ],
			'a null entry'           => [ [ self::FONT => null ], false, [] ],
			'neither time'           => [ [ self::FONT => [ 'retry_at' => 0, 'gave_up' => 0 ] ], false, [] ],
			'string times'           => [ [ self::FONT => [ 'retry_at' => (string) $now, 'gave_up' => 'yes' ] ], false, [] ],
			'a list, not a record'   => [ [ self::FONT => [ $now ] ], false, [] ],
		];
	}

	public function test_a_retry_still_to_come_is_not_retried() {
		$this->set_font_failures( [ self::FONT => $this->failure( 0, time() + 60 ) ] );
		$this->serve_font_downloads();

		$downloader = $this->seamed_sync( [] );
		$downloader->sync();

		$this->assertSame( [], $this->requested );
		$this->assertSame( [], $downloader->get_failed_files() );
	}

	public function test_an_expired_back_off_leaves_the_retry_state() {
		$this->fail_font_downloads();
		$this->seamed( [ 'jitter' => MINUTE_IN_SECONDS ] )->download( self::FONT );

		/* The back-off expires on its own; the pending retry is kept */
		$this->delete_back_off( self::FONT );

		$this->assertSame( 0, $this->get_font_failures()[ self::FONT ]['gave_up'] );
		$this->assertGreaterThan( 0, $this->get_font_failures()[ self::FONT ]['retry_at'] );

		/* So the next failure, past the back-off, is the retry's */
		$this->requested = [];
		$downloader      = $this->new_downloader();
		$this->assertFalse( $downloader->download( self::FONT ) );
		$this->assertCount( 1, $this->requested );
		$this->assertSame( [ self::FONT ], array_keys( $downloader->get_failed_files() ) );
	}

	public function test_the_failures_are_kept_on_the_main_network() {
		$this->fail_font_downloads();
		$this->downloader->download( self::FONT );

		$this->assertSame( [ self::FONT ], array_keys( get_network_option( get_main_network_id(), Model_Font_Downloader::FAILURES ) ) );
	}

	public function test_dismissing_hides_the_failed_files_until_one_fails_again() {
		$this->set_font_failures(
			[
				self::FONT   => $this->failure( time() - 10 ),
				'Garuda.ttf' => $this->failure(),
			]
		);

		$this->assertSame( [ self::FONT ], $this->downloader->get_undismissed_failed_files() );

		$this->downloader->dismiss_failed_files();
		$this->assertSame( [], $this->downloader->get_undismissed_failed_files() );
		$this->assertSame( [ self::FONT ], array_keys( $this->downloader->get_failed_files() ) );

		/* Read afresh, as another site would */
		$this->assertSame( [], $this->new_downloader()->get_undismissed_failed_files() );

		/* A font that gives up later is listed */
		$this->set_font_failures( $this->get_font_failures() + [ 'FreeSans.ttf' => $this->failure( time() ) ] );
		$this->assertSame( [ 'FreeSans.ttf' ], $this->new_downloader()->get_undismissed_failed_files() );
	}

	public function test_only_a_true_dismissal_counts() {
		$this->set_font_failures( [ self::FONT => [ 'retry_at' => 0, 'gave_up' => time(), 'dismissed' => 'yes' ] ] );

		$this->assertSame( [ self::FONT ], $this->new_downloader()->get_undismissed_failed_files() );
	}

	/**
	 * The downloader's constructor arguments, logging to a fresh handler
	 */
	private function downloader_args(): array {
		$this->log = new TestHandler();

		return [ GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), GPDFAPI::get_data_class(), GPDFAPI::get_options_class(), GPDFAPI::get_misc_class(), new Logger( 'test', [ $this->log ] ), GPDFAPI::get_mvc_class( 'Model_Required_Fonts' ) ];
	}

	/**
	 * @param string|null $failed_move_leaves When set, every move fails and leaves this at the font's path: part of
	 *                                        a font, as a move across filesystems can, or a whole one another
	 *                                        request saved meanwhile
	 */
	private function new_downloader( ?string $failed_move_leaves = null ): Model_Font_Downloader {
		$args = $this->downloader_args();

		if ( $failed_move_leaves === null ) {
			return new Model_Font_Downloader( ...$args );
		}

		$downloader = new class( ...$args ) extends Model_Font_Downloader {
			/** @var string */
			public $leaves = '';

			protected function move( string $from, string $to ): bool {
				file_put_contents( $to, $this->leaves );

				return false;
			}
		};

		$downloader->leaves = $failed_move_leaves;

		return $downloader;
	}

	/**
	 * A downloader with the seams given, logging to a fresh handler
	 *
	 * @param array $seams See Seamed_Font_Downloader
	 */
	private function seamed( array $seams ): Seamed_Font_Downloader {
		$this->log = new TestHandler();

		return new Seamed_Font_Downloader( new Logger( 'test', [ $this->log ] ), $seams );
	}

	/**
	 * A downloader whose required set is the files given, with the time budget given and the manifest
	 * serve_fixture_as() stubs on the shared one
	 *
	 * @param string[] $files
	 */
	private function seamed_sync( array $files, int $budget = Model_Font_Downloader::SYNC_BUDGET ): Seamed_Font_Downloader {
		$this->copied_fonts = array_merge( $this->copied_fonts, $this->font_paths( $files ) );

		return $this->seamed(
			[
				'required' => $files,
				'budget'   => $budget,
			]
		);
	}

	/**
	 * Each file's path in the fonts folder
	 *
	 * @param string[] $files
	 *
	 * @return string[]
	 */
	private function font_paths( array $files ): array {
		return array_map( [ GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), 'get_font_path' ], $files );
	}

	/**
	 * The arguments of each queued sync
	 *
	 * @return array[] The args of each queued sync
	 */
	private function get_scheduled_args(): array {
		return array_column( $this->get_scheduled_events(), 'args' );
	}

	/**
	 * When each queued sync is due
	 *
	 * @return int[] When each queued sync is due
	 */
	private function get_scheduled(): array {
		return array_column( $this->get_scheduled_events(), 'time' );
	}

	/**
	 * Each queued sync event, read from the cron array
	 *
	 * @return array<int, array{time: int, args: array}>
	 */
	private function get_scheduled_events(): array {
		$events = [];
		foreach ( _get_cron_array() ?: [] as $time => $hooks ) {
			foreach ( $hooks[ Model_Font_Downloader::SYNC_EVENT ] ?? [] as $event ) {
				$events[] = [
					'time' => (int) $time,
					'args' => $event['args'],
				];
			}
		}

		return $events;
	}

	/**
	 * Overwrite the start of a font in the fonts folder, keeping its size
	 */
	private function change_font( string $file, string $bytes ): void {
		$handle = fopen( $this->font_path( $file ), 'r+' );
		fwrite( $handle, $bytes );
		fclose( $handle );
	}

	/**
	 * @return string[]
	 */
	private function get_tmp_files(): array {
		return glob( GPDFAPI::get_data_class()->template_tmp_location . '*.tmp' ) ?: [];
	}

	/**
	 * Assert the download left no tmp file behind
	 */
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
