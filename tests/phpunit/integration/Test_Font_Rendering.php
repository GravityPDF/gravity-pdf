<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Integration;

use GFPDF\Helper\Fonts\FlushCache;
use GFPDF\Model\Model_Core_Font_Downloader;
use GFPDF\Tests\Concerns\AssertsSnapshots;
use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger;
use GFPDF_Vendor\Mpdf\Mpdf;
use GPDFAPI;
use ReflectionProperty;
use RuntimeException;
use WP_Error;

/**
 * Golden files of the fonts a rendered PDF embeds and the characters each font file draws
 *
 * @group snapshot
 * @group fonts
 */
class Test_Font_Rendering extends TestCase {

	use AssertsSnapshots;
	use CopiesFontFixtures;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
		wp_mkdir_p( GPDFAPI::get_data_class()->template_font_location );
		static::copy_test_fonts();
	}

	public static function tear_down_after_class(): void {
		static::remove_test_fonts();
		parent::tear_down_after_class();
	}

	public function tear_down(): void {
		$this->remove_font_fixtures();
		static::copy_test_fonts();

		delete_transient( Model_Core_Font_Downloader::FAILURES );
		$this->set_downloader_state( 'failures', null );
		GPDFAPI::get_options_class()->delete_option( 'debug_mode' );
		GPDFAPI::get_options_class()->delete_option( 'default_font' );

		parent::tear_down();
	}

	public function test_bundled_arimo_renders_every_style(): void {
		$this->assertRenderMatchesSnapshot(
			'arimo-styles',
			'<p style="font-family: arimo">Regular <b>Bold</b> <i>Italic</i> <b><i>Bold Italic</i></b></p>'
		);
	}

	public function test_characters_arimo_lacks_are_substituted(): void {
		$this->assertRenderMatchesSnapshot( 'arimo-substitution', '<p style="font-family: arimo">Agree ✔ Decline ✖</p>' );
	}

	public function test_consent_ticks_render_from_the_bundled_symbols(): void {
		$this->assertRenderMatchesSnapshot(
			'consent-ticks',
			'<p style="font-family: arimo"><span style="font-family:gfpdf-dejavu-symbols,sans-serif;">&#10004;</span> Agree <span style="font-family:gfpdf-dejavu-symbols,sans-serif;">&#10006;</span> Decline</p>'
		);
	}

	public function test_a_core_font_renders_from_the_fonts_folder(): void {
		$this->assertRenderMatchesSnapshot( 'core-font', '<p style="font-family: dejavusans">Regular <b>Bold</b></p>' );
	}

	public function test_a_custom_font_overrides_the_bundled_arimo(): void {
		$this->copy_fixture( 'Chewy.ttf' );
		$this->add_record( 'arimo', [ 'regular' => 'Chewy.ttf' ] );

		$this->assertRenderMatchesSnapshot( 'custom-arimo', '<p style="font-family: arimo">Custom</p>' );
	}

	public function test_an_adopted_template_arimo_record_renders_the_bundled_faces(): void {
		$this->copy_fixture( 'Chewy.ttf', 'Arimo-Regular.ttf' );
		$this->add_record( 'arimo', [ 'regular' => 'Arimo-Regular.ttf' ] );

		$this->assertSame( [ 'arimo' ], $this->get_model_with_legacy( 'Chewy.ttf' )->adopt_records( 'bundled' ) );
		$this->assertRenderMatchesSnapshot( 'adopted-arimo', '<p style="font-family: arimo">Adopted</p>' );
	}

	public function test_a_render_with_its_fonts_on_disk_does_not_load_the_manifest(): void {
		$this->set_downloader_state( 'manifest', null );

		$this->render( '<p style="font-family: dejavusans">Regular <b>Bold</b></p>' );

		$this->assertNull( $this->get_downloader_state( 'manifest' ) );
	}

	public function test_a_missing_core_font_downloads_while_rendering(): void {
		$this->empty_fonts_folder();

		/* Cached metrics change which substitutes mPDF loads */
		FlushCache::flush();
		$this->serve_fixture_as( 'DejaVuSerifCondensed.ttf', 'Garuda.ttf' );
		$this->serve_font_downloads();

		/* DejaVu Serif Condensed has the OpenType tables Garuda's font data needs, but no Thai */
		$this->assertRenderMatchesSnapshot( 'downloaded-thai-font', '<p lang="th">สวัสดี</p>', [ 'font' => 'arimo' ] );

		/* mPDF starts in Arimo, so only the Thai font downloads, then the substitutes for the glyphs it lacks */
		$this->assertSame(
			[
				'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/v1.0.0/Garuda.ttf',
				'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/v1.0.0/DejaVuSansCondensed.ttf',
				'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/v1.0.0/FreeSans.ttf',
				'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/v1.0.0/Sun-ExtA.ttf',
			],
			$this->requested
		);
	}

	public function test_a_font_that_cannot_download_renders_in_arimo_when_the_default_font_is_missing(): void {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavusanscondensed' );
		$this->empty_fonts_folder();
		$this->fail_font_downloads();

		$this->assertRenderMatchesSnapshot( 'fallback-arimo', '<p lang="th">Thai สวัสดี</p>', [ 'font' => 'arimo' ] );
	}

	public function test_a_font_that_cannot_download_renders_in_the_pdfs_font(): void {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavusanscondensed' );
		$this->fail_font_downloads();

		$this->assertRenderMatchesSnapshot( 'fallback-pdf-font', '<p style="font-family: freeserif">Fallback</p>' );
	}

	public function test_a_font_that_cannot_download_fails_the_pdf_in_debug_mode(): void {
		GPDFAPI::get_options_class()->update_option( 'debug_mode', 'Yes' );
		$this->empty_fonts_folder();
		$this->fail_font_downloads();

		$log = new TestHandler();
		GPDFAPI::get_log_class()->pushHandler( $log );
		try {
			[ $result, $render ] = $this->generate( '<p lang="th">สวัสดี</p>', [ 'font' => 'arimo' ] );
		} finally {
			GPDFAPI::get_log_class()->popHandler();
		}

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertNull( $render );
		$this->assertTrue(
			$log->hasRecordThatPasses(
				static function ( $record ) {
					return strpos( $record['context']['exception'] ?? '', 'Cannot find TTF TrueType font file' ) !== false;
				},
				Logger::ERROR
			)
		);
	}

	protected function assertRenderMatchesSnapshot( string $name, string $html, array $settings = [] ): void {
		$this->assertMatchesSnapshot( "fonts/$name.txt", $this->describe_fonts( $this->render( $html, $settings ) ) );
	}

	/**
	 * Render the fixture entry's PDF with the HTML swapped in, and stop once mPDF has written it
	 *
	 * @return array{mpdf: Mpdf, pdf: string}
	 */
	protected function render( string $html, array $settings = [] ): array {
		[ $result, $render ] = $this->generate( $html, $settings );

		$this->assertInstanceOf( WP_Error::class, $result, 'The render should have been stopped' );
		$this->assertNotNull( $render, 'The PDF failed before it was written: ' . $result->get_error_message() );

		return $render;
	}

	/**
	 * @return array{0: mixed, 1: array{mpdf: Mpdf, pdf: string}|null} The generate_and_save_pdf() result and the render
	 */
	protected function generate( string $html, array $settings = [] ): array {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		$capture = new class() extends RuntimeException {
			/** @var array{mpdf: Mpdf, pdf: string} */
			public $result;
		};

		$swap = static function () use ( $html ): string {
			return $html;
		};

		$stop = static function ( $mpdf ) use ( $capture ): void {
			$capture->result = [
				'mpdf' => $mpdf,
				'pdf'  => $mpdf->Output( '', 'S' ),
			];

			throw $capture;
		};

		add_filter( 'gfpdf_pdf_html_output_' . $form['id'], $swap, PHP_INT_MAX );
		add_action( 'gfpdf_pre_pdf_generation_output', $stop, PHP_INT_MAX );

		$settings = array_merge( $form['gfpdf_form_settings']['555ad84787d7e'], [ 'template' => 'zadani' ], $settings );
		$result   = GPDFAPI::get_mvc_class( 'Model_PDF' )->generate_and_save_pdf( $entry, $settings, true );

		remove_filter( 'gfpdf_pdf_html_output_' . $form['id'], $swap, PHP_INT_MAX );
		remove_action( 'gfpdf_pre_pdf_generation_output', $stop, PHP_INT_MAX );

		return [ $result, $capture->result ];
	}

	/**
	 * Remove the class's core fonts (tear_down() puts them back), leaving only the bundled fonts
	 */
	protected function empty_fonts_folder(): void {
		static::remove_test_fonts();
	}

	/**
	 * @return mixed
	 */
	protected function get_downloader_state( string $name ) {
		$property = new ReflectionProperty( Model_Core_Font_Downloader::class, $name );
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}

		return $property->getValue( GPDFAPI::get_mvc_class( 'Model_Core_Font_Downloader' ) );
	}

	/**
	 * The shared downloader remembers this request's failures, so reset it between tests
	 *
	 * @param mixed $value
	 */
	protected function set_downloader_state( string $name, $value ): void {
		$property = new ReflectionProperty( Model_Core_Font_Downloader::class, $name );
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}

		$property->setValue( GPDFAPI::get_mvc_class( 'Model_Core_Font_Downloader' ), $value );
	}

	/**
	 * The fonts the PDF embeds, then one line per font mPDF used: its key, its file and any non-ASCII characters it
	 * drew (mPDF adds all of printable ASCII to every subset)
	 *
	 * @param array{mpdf: Mpdf, pdf: string} $render
	 */
	protected function describe_fonts( array $render ): string {
		/* Strip the random six-letter subset tag */
		preg_match_all( '#/BaseFont /(?:[A-Z]{6}\+)?([^\s/]+)#', $render['pdf'], $matches );
		$embedded = array_unique( $matches[1] );
		sort( $embedded );

		$lines = [ 'BaseFont: ' . implode( ', ', $embedded ) ];
		foreach ( $render['mpdf']->fonts as $key => $font ) {
			if ( empty( $font['used'] ) ) {
				continue;
			}

			$codes = array_filter(
				array_keys( $font['subset'] ?? [] ),
				function ( $code ) {
					return $code > 127;
				}
			);
			sort( $codes );

			$lines[] = rtrim( sprintf( '%s: %s %s', $key, basename( $font['ttffile'] ), implode( '', array_map( 'mb_chr', $codes ) ) ) );
		}

		sort( $lines );

		return implode( "\n", $lines ) . "\n";
	}
}
