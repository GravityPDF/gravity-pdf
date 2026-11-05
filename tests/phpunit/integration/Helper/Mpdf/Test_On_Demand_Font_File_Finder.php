<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Mpdf;

use GFPDF\Model\Model_Font_Downloader;
use GFPDF\Model\Model_Font_Sources;
use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\RecordsFontFailures;
use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Mpdf\MpdfException;
use GFPDF_Vendor\Psr\Log\NullLogger;
use GPDFAPI;

/**
 * @group mpdf
 * @group fonts
 */
class Test_On_Demand_Font_File_Finder extends TestCase {

	use CopiesFontFixtures;
	use RecordsFontFailures;

	private const FONT = 'DejaVuSerifCondensed.ttf';

	private const DEFAULT_FONT = 'DejaVuSansCondensed.ttf';

	/**
	 * @var Model_Font_Downloader
	 */
	private $downloader;

	/**
	 * @var Model_Font_Sources
	 */
	private $sources;

	/**
	 * @var string
	 */
	private $font_dir;

	public function set_up(): void {
		parent::set_up();

		$this->font_dir   = GPDFAPI::get_data_class()->template_font_location;
		$this->sources    = new Model_Font_Sources( GPDFAPI::get_data_class(), GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' ), new NullLogger() );
		$this->downloader = new Model_Font_Downloader( $this->sources, GPDFAPI::get_data_class(), GPDFAPI::get_options_class(), GPDFAPI::get_misc_class(), new NullLogger(), GPDFAPI::get_mvc_class( 'Model_Required_Fonts' ) );
		wp_mkdir_p( $this->font_dir );
	}

	public function tear_down(): void {
		wp_delete_file( $this->font_dir . self::FONT );
		wp_delete_file( $this->font_dir . self::DEFAULT_FONT );
		$this->remove_font_fixtures();
		$this->delete_font_failures();
		GPDFAPI::get_options_class()->delete_option( 'debug_mode' );
		GPDFAPI::get_options_class()->delete_option( 'default_font' );

		parent::tear_down();
	}

	public function test_a_font_on_disk_is_found_without_a_request() {
		copy( $this->font_fixture( self::FONT ), $this->font_dir . self::FONT );
		$this->serve_font_downloads();

		$this->assertFileEquals( $this->font_fixture( self::FONT ), $this->finder()->findFontFile( self::FONT ) );
		$this->assertSame( [], $this->requested );
	}

	public function test_a_missing_core_font_is_downloaded() {
		$this->serve_font_downloads();

		$this->assertSame( $this->font_dir . self::FONT, $this->finder()->findFontFile( self::FONT ) );
		$this->assertFileEquals( $this->font_fixture( self::FONT ), $this->font_dir . self::FONT );
		$this->assertCount( 1, $this->requested );
	}

	public function test_a_download_lands_in_the_fonts_folder_after_mpdf_replaces_the_directories() {
		$this->serve_font_downloads();

		$finder = $this->finder();
		$finder->setDirectories( [ PDF_PLUGIN_DIR . 'fonts' ] );

		$this->assertSame( $this->font_dir . self::FONT, $finder->findFontFile( self::FONT ) );
		$this->assertFileExists( $this->font_dir . self::FONT );
	}

	public function test_a_missing_font_that_is_not_a_core_font_throws() {
		$this->serve_font_downloads();

		$this->expectException( MpdfException::class );
		$this->expectExceptionMessage( 'Cannot find TTF TrueType font file "MyCustomFont.ttf"' );

		try {
			$this->finder()->findFontFile( 'MyCustomFont.ttf' );
		} finally {
			$this->assertSame( [], $this->requested );
		}
	}

	public function test_a_failed_download_falls_back_to_the_default_font() {
		copy( $this->font_fixture( self::DEFAULT_FONT ), $this->font_dir . self::DEFAULT_FONT );
		$this->fail_font_downloads();

		$this->assertFileEquals( $this->font_fixture( self::DEFAULT_FONT ), $this->finder()->findFontFile( self::FONT ) );
		$this->assertFileDoesNotExist( $this->font_dir . self::FONT );
	}

	/**
	 * @dataProvider provider_unavailable_default_font
	 */
	public function test_a_failed_download_falls_back_to_arimo_without_the_default_font( string $default_font_file ) {
		$this->fail_font_downloads();

		$this->assertSame( PDF_PLUGIN_DIR . 'fonts/gfpdf-Arimo-Regular.ttf', $this->finder( $default_font_file )->findFontFile( self::FONT ) );
	}

	public function provider_unavailable_default_font(): array {
		return [
			'missing' => [ self::DEFAULT_FONT ],
			'empty'   => [ '' ],
		];
	}

	public function test_a_failed_download_throws_in_debug_mode() {
		copy( $this->font_fixture( self::DEFAULT_FONT ), $this->font_dir . self::DEFAULT_FONT );
		$this->fail_font_downloads();

		$this->expectException( MpdfException::class );
		$this->expectExceptionMessage( 'Cannot find TTF TrueType font file "' . self::FONT . '"' );

		$this->finder( self::DEFAULT_FONT, false )->findFontFile( self::FONT );
	}

	/**
	 * @dataProvider provider_unusable_names
	 *
	 * @param mixed $name
	 */
	public function test_an_unusable_font_name_throws_without_a_request( $name ) {
		$this->serve_font_downloads();

		try {
			$this->finder()->findFontFile( $name );
			$this->fail( 'The finder should have thrown' );
		} catch ( MpdfException $e ) {
			$this->assertStringStartsWith( 'Cannot find TTF TrueType font file', $e->getMessage() );
		}

		$this->assertSame( [], $this->requested );
	}

	public function provider_unusable_names(): array {
		return [
			'empty, which would match a folder' => [ '' ],
			'null'                              => [ null ],
			'an array'                          => [ [ self::FONT ] ],
			'a number'                          => [ 12 ],
		];
	}

	public function test_a_finder_with_no_directories_still_downloads() {
		$this->serve_font_downloads();

		$finder = new On_Demand_Font_File_Finder( [], $this->downloader, self::DEFAULT_FONT );

		$this->assertSame( $this->font_dir . self::FONT, $finder->findFontFile( self::FONT ) );
	}

	public function test_a_failed_download_throws_when_the_bundled_fallback_is_missing_too() {
		$downloader = new class( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), GPDFAPI::get_data_class(), GPDFAPI::get_options_class(), GPDFAPI::get_misc_class(), new NullLogger(), GPDFAPI::get_mvc_class( 'Model_Required_Fonts' ) ) extends Model_Font_Downloader {
			public function get_fallback_file(): string {
				return '';
			}
		};
		$this->fail_font_downloads();

		$this->expectException( MpdfException::class );
		$this->expectExceptionMessage( 'Cannot find TTF TrueType font file "' . self::FONT . '"' );

		( new On_Demand_Font_File_Finder( [ $this->font_dir ], $downloader, '' ) )->findFontFile( self::FONT );
	}

	public function test_debug_mode_builds_a_finder_without_a_fallback() {
		$this->assertTrue( $this->get_private( $this->downloader->get_font_file_finder( [] ), 'fallback' ) );

		GPDFAPI::get_options_class()->update_option( 'debug_mode', 'Yes' );
		$this->assertFalse( $this->get_private( $this->downloader->get_font_file_finder( [] ), 'fallback' ) );
	}

	public function test_a_file_at_the_name_the_manifest_does_not_own_is_left_and_the_font_saved_under_its_hashed_name() {
		$this->put_other_font_at( self::FONT, self::FONT );
		$this->serve_font_downloads();
		$hashed = $this->sources->get_hashed_name( self::FONT );

		$this->assertMatchesRegularExpression( '/^DejaVuSerifCondensed-[0-9a-f]{8}\.ttf$/', $hashed );
		$this->assertSame( $this->font_dir . $hashed, $this->finder()->findFontFile( self::FONT ) );
		$this->assertFileEquals( $this->font_fixture( self::FONT ), $this->font_dir . $hashed );
		$this->assertFileEquals( $this->font_fixture( 'Chewy.ttf' ), $this->font_dir . self::FONT );
		$this->assertCount( 1, $this->requested );
	}

	public function test_a_font_manager_records_file_at_a_core_fonts_name_is_not_replaced_by_the_core_font() {
		$this->put_other_font_at( self::FONT, self::FONT );
		$this->add_record( 'mine', [ 'regular' => self::FONT ] );
		$this->serve_font_downloads();

		$this->assertFileEquals( $this->font_fixture( 'Chewy.ttf' ), $this->finder()->findFontFile( self::FONT ) );
		$this->assertSame( [], $this->requested );
		$this->assertFileDoesNotExist( $this->font_dir . $this->sources->get_hashed_name( self::FONT ) );
	}

	public function test_the_hashed_copy_is_found_before_the_file_at_the_name_without_a_request_or_hashing() {
		$this->put_other_font_at( self::FONT, self::FONT );
		$this->copy_fixture( self::FONT, $this->sources->get_hashed_name( self::FONT ) );
		$this->serve_font_downloads();
		$downloader = $this->counting_downloader();

		$finder = new On_Demand_Font_File_Finder( [ $this->font_dir ], $downloader, self::DEFAULT_FONT );

		$this->assertSame( $this->font_dir . $this->sources->get_hashed_name( self::FONT ), $finder->findFontFile( self::FONT ) );
		$this->assertSame( [], $this->requested );
		$this->assertSame( 0, $downloader->hashed );
	}

	public function test_a_file_of_a_known_size_is_not_hashed_while_rendering() {
		copy( $this->font_fixture( self::FONT ), $this->font_dir . self::FONT );
		$this->put_other_font_at( self::DEFAULT_FONT, self::DEFAULT_FONT, true );
		$this->serve_font_downloads();
		$downloader = $this->counting_downloader();

		$finder = new On_Demand_Font_File_Finder( [ $this->font_dir ], $downloader, self::DEFAULT_FONT );

		$this->assertSame( $this->font_dir . self::FONT, $finder->findFontFile( self::FONT ) );
		$this->assertSame( $this->font_dir . self::DEFAULT_FONT, $finder->findFontFile( self::DEFAULT_FONT ) );
		$this->assertSame( [], $this->requested );
		$this->assertSame( 0, $downloader->hashed );
	}

	public function test_a_file_from_an_older_release_is_not_another_fonts() {
		copy( $this->font_fixture( 'DejaVuSerifCondensed-v1.0.0.ttf' ), $this->font_dir . self::FONT );
		$this->serve_font_downloads();

		$this->assertSame( $this->font_dir . self::FONT, $this->finder()->findFontFile( self::FONT ) );
		$this->assertSame( [], $this->requested );
		$this->assertFileDoesNotExist( $this->font_dir . $this->sources->get_hashed_name( self::FONT ) );
	}

	public function test_a_file_at_the_name_stays_in_use_when_downloads_are_off() {
		$this->put_other_font_at( self::FONT, self::FONT );
		add_filter( 'gfpdf_auto_install_fonts', '__return_false' );
		$this->serve_font_downloads();

		$this->assertFileEquals( $this->font_fixture( 'Chewy.ttf' ), $this->finder()->findFontFile( self::FONT ) );
		$this->assertSame( [], $this->requested );
		$this->assertEmpty( $this->get_font_failures() );
	}

	public function test_a_file_at_the_name_stays_in_use_when_the_download_fails_and_backs_off() {
		$this->put_other_font_at( self::FONT, self::FONT );
		$this->fail_font_downloads();

		$this->assertFileEquals( $this->font_fixture( 'Chewy.ttf' ), $this->finder()->findFontFile( self::FONT ) );
		$this->assertSame( [ self::FONT ], array_keys( $this->get_font_failures() ) );
		$this->assertCount( 1, $this->requested );

		/* Backing off: no second request, and the same file */
		$this->assertFileEquals( $this->font_fixture( 'Chewy.ttf' ), $this->finder()->findFontFile( self::FONT ) );
		$this->assertCount( 1, $this->requested );
		$this->assertFileDoesNotExist( $this->font_dir . $this->sources->get_hashed_name( self::FONT ) );
	}

	public function test_a_file_at_the_name_stays_in_use_in_debug_mode_when_the_download_fails() {
		$this->put_other_font_at( self::FONT, self::FONT );
		$this->fail_font_downloads();

		$this->assertFileEquals( $this->font_fixture( 'Chewy.ttf' ), $this->finder( self::DEFAULT_FONT, false )->findFontFile( self::FONT ) );
	}

	public function test_a_font_in_another_directory_is_found_without_loading_the_manifest() {
		$this->serve_font_downloads();

		$this->assertSame( PDF_PLUGIN_DIR . 'fonts/gfpdf-Arimo-Regular.ttf', ( new On_Demand_Font_File_Finder( [ PDF_PLUGIN_DIR . 'fonts' ], $this->downloader, '' ) )->findFontFile( 'gfpdf-Arimo-Regular.ttf' ) );
		$this->assertNull( $this->get_private( $this->sources, 'manifest' ) );
		$this->assertSame( [], $this->requested );
	}

	private function counting_downloader(): Model_Font_Downloader {
		return new class( $this->sources, GPDFAPI::get_data_class(), GPDFAPI::get_options_class(), GPDFAPI::get_misc_class(), new NullLogger(), GPDFAPI::get_mvc_class( 'Model_Required_Fonts' ) ) extends Model_Font_Downloader {
			/** @var int */
			public $hashed = 0;

			protected function hash_blob( string $path, int $size ): string {
				$this->hashed++;

				return parent::hash_blob( $path, $size );
			}
		};
	}

	/**
	 * A finder searching the fonts folder
	 */
	private function finder( string $default_font_file = self::DEFAULT_FONT, bool $fallback = true ): On_Demand_Font_File_Finder {
		return new On_Demand_Font_File_Finder( [ $this->font_dir ], $this->downloader, $default_font_file, $fallback );
	}
}
