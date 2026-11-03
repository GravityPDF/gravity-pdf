<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\RecordsFontFailures;
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
	}

	public function tear_down(): void {
		wp_delete_file( $this->font_path() );
		$this->remove_font_fixtures();

		parent::tear_down();
	}

	public function test_download_saves_a_verified_font() {
		$this->serve_font_downloads();

		$this->assertTrue( $this->downloader->download( self::FONT ) );
		$this->assertFileEquals( $this->font_fixture( self::FONT ), $this->font_path() );
		$this->assertSame( [ 'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/v1.0.0/' . self::FONT ], $this->requested );
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
		mkdir( $this->font_path() );
		$this->serve_font_downloads();

		try {
			$this->assertFalse( $this->downloader->download( self::FONT ) );
			$this->assertNoStrayTmpFiles();
			$this->assertCount( 1, $this->log_messages( 'Font Move Failed' ) );
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
		$this->assertCount( 1, $this->log_messages( 'Font Move Failed' ) );
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
		$downloader = new Model_Font_Downloader( $font_sources, GPDFAPI::get_data_class(), GPDFAPI::get_options_class(), GPDFAPI::get_misc_class(), new Logger( 'test', [ $this->log ] ) );

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

	/**
	 * The downloader's constructor arguments, logging to a fresh handler
	 */
	private function downloader_args(): array {
		$this->log = new TestHandler();

		return [ GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), GPDFAPI::get_data_class(), GPDFAPI::get_options_class(), GPDFAPI::get_misc_class(), new Logger( 'test', [ $this->log ] ) ];
	}

	/**
	 * @param bool $partial_move A move that leaves part of the font behind, as one across filesystems can
	 * @param bool $failed_move  A move that fails without touching the destination
	 */
	private function new_downloader( bool $partial_move = false, bool $failed_move = false ): Model_Font_Downloader {
		$args = $this->downloader_args();

		if ( ! $partial_move && ! $failed_move ) {
			return new Model_Font_Downloader( ...$args );
		}

		$downloader = new class( ...$args ) extends Model_Font_Downloader {
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

	/**
	 * Where the test font downloads to
	 */
	private function font_path(): string {
		return GPDFAPI::get_data_class()->template_font_location . self::FONT;
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
