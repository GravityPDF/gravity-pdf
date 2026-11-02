<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger;
use GFPDF_Vendor\Mpdf\Fonts\FontFileFinder;
use GPDFAPI;
use ReflectionProperty;
use WP_Error;

/**
 * @group model
 * @group fonts
 */
class Test_Model_Core_Font_Downloader extends TestCase {

	use CopiesFontFixtures;

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
		delete_transient( Model_Core_Font_Downloader::FAILURES );

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

	/**
	 * @dataProvider provider_failures_transient
	 *
	 * @param mixed $failures
	 */
	public function test_an_unexpected_failures_transient_does_not_back_off( $failures ) {
		set_transient( Model_Core_Font_Downloader::FAILURES, $failures, Model_Core_Font_Downloader::BACK_OFF );
		$this->serve_font_downloads();

		$this->assertTrue( $this->downloader->download( self::FONT ) );
		$this->assertCount( 1, $this->requested );
	}

	public function provider_failures_transient(): array {
		return [
			'a string'               => [ 'junk' ],
			'a number'               => [ 42 ],
			'a string timestamp'     => [ [ self::FONT => (string) time() ] ],
			'a null timestamp'       => [ [ self::FONT => null ] ],
			'a nested array'         => [ [ self::FONT => [ time() ] ] ],
		];
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

		$failures = get_transient( Model_Core_Font_Downloader::FAILURES );
		$this->assertSame( [ self::FONT ], array_keys( $failures ) );

		$this->requested = [];
		$this->assertFalse( $this->new_downloader()->download( self::FONT ) );
		$this->assertSame( [], $this->requested );
	}

	public function test_back_off_reads_the_transient_once() {
		set_transient( Model_Core_Font_Downloader::FAILURES, [ self::FONT => time() ], Model_Core_Font_Downloader::BACK_OFF );
		$this->serve_font_downloads();

		$reads = 0;
		add_filter(
			'pre_transient_' . Model_Core_Font_Downloader::FAILURES,
			static function ( $value ) use ( &$reads ) {
				$reads++;

				return $value;
			}
		);

		$this->assertFalse( $this->downloader->download( self::FONT ) );
		$this->assertFalse( $this->downloader->download( self::FONT ) );
		$this->downloader->download( 'DejaVuSansMono.ttf' );

		$this->assertSame( 1, $reads );
		$this->assertSame( [ 'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/v1.0.0/DejaVuSansMono.ttf' ], $this->requested );
		$this->assertCount( 2, $this->log_messages( 'Core Font download skipped after a recent failure' ) );
	}

	public function test_back_off_ends_after_fifteen_minutes() {
		set_transient( Model_Core_Font_Downloader::FAILURES, [ self::FONT => time() - Model_Core_Font_Downloader::BACK_OFF ], Model_Core_Font_Downloader::BACK_OFF );
		$this->serve_font_downloads();

		$this->assertTrue( $this->downloader->download( self::FONT ) );
		$this->assertCount( 1, $this->requested );
	}

	public function test_downloads_can_be_turned_off() {
		add_filter( 'gfpdf_core_font_download', '__return_false' );
		$this->serve_font_downloads();

		$this->assertFalse( $this->downloader->download( self::FONT ) );
		$this->assertSame( [], $this->requested );
		$this->assertFalse( get_transient( Model_Core_Font_Downloader::FAILURES ) );
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

	/**
	 * @return mixed
	 */
	private function get_private( object $object, string $name, string $class = '' ) {
		$property = new ReflectionProperty( $class !== '' ? $class : get_class( $object ), $name );
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}

		return $property->getValue( $object );
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
