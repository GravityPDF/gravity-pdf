<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Mpdf;

use GFPDF\Model\Model_Core_Font_Downloader;
use GFPDF\Model\Model_Font_Sources;
use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\RecordsFontFailures;
use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Mpdf\Config\FontVariables;
use GFPDF_Vendor\Mpdf\Fonts\FontFileFinder;
use GFPDF_Vendor\Mpdf\MpdfException;
use GFPDF_Vendor\Psr\Log\NullLogger;
use GPDFAPI;
use ReflectionProperty;

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
	 * @var Model_Core_Font_Downloader
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
		$this->downloader = new Model_Core_Font_Downloader( $this->sources, GPDFAPI::get_data_class(), GPDFAPI::get_options_class(), GPDFAPI::get_misc_class(), new NullLogger(), GPDFAPI::get_mvc_class( 'Model_Required_Fonts' ) );
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
		$this->assertNull( $this->get_property( $this->sources, 'manifest' ) );
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

	public function test_a_failed_download_falls_back_to_arimo_when_the_default_font_is_missing() {
		$this->fail_font_downloads();

		$this->assertSame( PDF_PLUGIN_DIR . 'fonts/GravityPDF-Arimo-Regular.ttf', $this->finder()->findFontFile( self::FONT ) );
	}

	public function test_a_failed_download_throws_in_debug_mode() {
		copy( $this->font_fixture( self::DEFAULT_FONT ), $this->font_dir . self::DEFAULT_FONT );
		$this->fail_font_downloads();

		$this->expectException( MpdfException::class );
		$this->expectExceptionMessage( 'Cannot find TTF TrueType font file "' . self::FONT . '"' );

		GPDFAPI::get_options_class()->update_option( 'debug_mode', 'Yes' );
		$this->finder()->findFontFile( self::FONT );
	}

	public function test_an_empty_default_font_falls_back_to_arimo() {
		$this->fail_font_downloads();

		$this->assertSame( PDF_PLUGIN_DIR . 'fonts/GravityPDF-Arimo-Regular.ttf', $this->finder( '' )->findFontFile( self::FONT ) );
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
		$downloader = new class( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), GPDFAPI::get_data_class(), GPDFAPI::get_options_class(), GPDFAPI::get_misc_class(), new NullLogger(), GPDFAPI::get_mvc_class( 'Model_Required_Fonts' ) ) extends Model_Core_Font_Downloader {
			public function get_fallback_file(): string {
				return '';
			}
		};
		$this->fail_font_downloads();

		$this->expectException( MpdfException::class );
		$this->expectExceptionMessage( 'Cannot find TTF TrueType font file "' . self::FONT . '"' );

		( new On_Demand_Font_File_Finder( [ $this->font_dir ], $downloader, '' ) )->findFontFile( self::FONT );
	}

	public function test_the_downloader_builds_the_finder_from_the_mpdf_config() {
		$dirs   = [ $this->font_dir, PDF_PLUGIN_DIR . 'fonts/' ];
		$finder = $this->downloader->get_font_file_finder(
			[
				'fontDir'      => $dirs,
				'default_font' => 'DejaVuSans',
				'fontdata'     => ( new FontVariables() )->getDefaults()['fontdata'],
			]
		);

		$this->assertSame( $dirs, $this->get_property( $finder, 'directories', FontFileFinder::class ) );
		$this->assertSame( 'DejaVuSans.ttf', $this->get_property( $finder, 'default_font_file' ) );
	}

	public function test_an_unregistered_default_font_has_no_default_font_fallback() {
		$finder = $this->downloader->get_font_file_finder( [ 'default_font' => 'notafont' ] );

		$this->assertSame( [], $this->get_property( $finder, 'directories', FontFileFinder::class ) );
		$this->assertSame( '', $this->get_property( $finder, 'default_font_file' ) );
	}

	public function test_the_downloader_answers_the_finders_miss_path() {
		$this->assertSame( $this->font_dir . self::FONT, $this->downloader->get_font_path( self::FONT ) );
		$this->assertSame( PDF_PLUGIN_DIR . 'fonts/GravityPDF-Arimo-Regular.ttf', $this->downloader->get_fallback_file() );
		$this->assertFalse( $this->downloader->is_debug_mode() );

		GPDFAPI::get_options_class()->update_option( 'debug_mode', 'Yes' );
		$this->assertTrue( $this->downloader->is_debug_mode() );
	}

	private function finder( string $default_font_file = self::DEFAULT_FONT ): On_Demand_Font_File_Finder {
		return new On_Demand_Font_File_Finder( [ $this->font_dir ], $this->downloader, $default_font_file );
	}

	private function get_property( object $object, string $name, string $class = '' ) {
		$property = new ReflectionProperty( $class !== '' ? $class : get_class( $object ), $name );
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}

		return $property->getValue( $object );
	}
}
