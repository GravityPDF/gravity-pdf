<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Integration;

use GFPDF\Helper\Fonts\FlushCache;
use GFPDF\Helper\Helper_Render_Health;
use GFPDF\Model\Model_Font_Downloader;
use GFPDF\Tests\Concerns\AssertsSnapshots;
use GFPDF\Tests\Concerns\RecordsFontFailures;
use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger;
use GFPDF_Vendor\Mpdf\Mpdf;
use GPDFAPI;
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
	use RecordsFontFailures;
	use CopiesFontFixtures;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
		static::copy_test_fonts();
	}

	public static function tear_down_after_class(): void {
		static::remove_test_fonts();
		parent::tear_down_after_class();
	}

	public function tear_down(): void {
		$this->remove_font_fixtures();
		static::copy_test_fonts();

		$this->delete_font_failures();
		wp_clear_scheduled_hook( Model_Font_Downloader::SYNC_EVENT );
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

	public function test_symbols_arimo_lacks_render_from_the_bundled_subset_without_a_download(): void {
		static::remove_test_fonts();
		$this->serve_font_downloads();

		$this->assertRenderMatchesSnapshot( 'symbols-substitution', '<p style="font-family: arimo">Next ⇒ ┏━┓</p>', [ 'font' => 'arimo' ] );
		$this->assertSame( [], $this->requested );
	}

	/**
	 * @dataProvider provider_large_substitutes
	 */
	public function test_text_only_a_large_file_draws_queues_it_and_keeps_the_render_out_of_the_cache( string $text, string $file ): void {
		static::remove_test_fonts();
		FlushCache::flush();
		$this->serve_font_downloads();

		$render = $this->render( '<p style="font-family: arimo">' . $text . '</p>', [ 'font' => 'arimo' ] );

		$this->assertSame( [], $this->requested );
		$this->assertTrue( $render['degraded'] );
		$this->assertSame( [ $file ], $this->wanted_files() );
		$this->assertNotFalse( wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ) );
	}

	public function provider_large_substitutes(): array {
		return [
			'CJK from Sun-ExtA, beside an emoji none has' => [ '😀 日本', 'Sun-ExtA.ttf' ],
			'CJK Extension B from Sun-ExtB' => [ '𠀀', 'Sun-ExtB.ttf' ],
		];
	}

	public function test_text_the_listed_fonts_draw_queues_nothing_and_is_cached(): void {
		$this->serve_font_downloads();

		$render = $this->render( '<p style="font-family: arimo">Hello café Привет</p>', [ 'font' => 'arimo' ] );

		$this->assertFalse( $render['degraded'] );
		$this->assertSame( [], $this->wanted_files() );
	}

	public function test_a_large_pdf_font_is_queued_and_the_pdf_renders_in_arimo(): void {
		FlushCache::flush();
		$this->serve_font_downloads();

		$render = $this->render( '<p>日本</p>', [ 'font' => 'sun-exta' ] );

		$this->assertSame( [], $this->requested );
		$this->assertTrue( $render['degraded'] );
		$this->assertSame( 'gfpdf-Arimo-Regular.ttf', basename( $render['mpdf']->fonts['sun-exta']['ttffile'] ?? '' ) );
		$this->assertSame( [ 'Sun-ExtA.ttf' ], $this->wanted_files() );
	}

	public function test_a_large_file_another_font_holds_the_name_of_is_queued_and_the_render_uses_that_font_and_is_not_cached(): void {
		$this->put_other_font_at( 'Sun-ExtA.ttf' );
		FlushCache::flush();
		$this->serve_font_downloads();

		$render = $this->render( '<p>日本</p>', [ 'font' => 'sun-exta' ] );

		$this->assertSame( [], $this->requested );
		$this->assertTrue( $render['degraded'] );
		$this->assertSame( 'Sun-ExtA.ttf', basename( $render['mpdf']->fonts['sun-exta']['ttffile'] ?? '' ) );
		$this->assertSame( [ 'Sun-ExtA.ttf' ], $this->wanted_files() );
	}

	/**
	 * @dataProvider provider_substitutes
	 */
	public function test_a_substitute_downloads_when_it_has_the_characters( string $text, string $font, string $file ): void {
		static::remove_test_fonts();
		FlushCache::flush();

		/* The others have no fixture, and the ranges file decides which substitute is fetched, not the file served. DejaVu
		   Serif Condensed has the OpenType tables the script fonts' data needs */
		if ( $file !== 'DejaVuSansCondensed.ttf' ) {
			$this->serve_fixture_as( 'DejaVuSerifCondensed.ttf', $file );
		}
		$this->serve_font_downloads();

		$render = $this->render( '<p style="font-family: arimo">Text ' . $text . '</p>', [ 'font' => 'arimo' ] );

		$this->assertSame( [ 'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/v1.1.2/' . $file ], $this->requested );

		/* The text is drawn in the substitute. Arimo records each character it draws, even one it lacks (see the emoji
		   snapshot), so none of the text stayed in Arimo */
		$this->assertTrue( $render['mpdf']->fonts[ $font ]['used'] ?? false );
		$this->assertSame( [], array_intersect( array_map( 'mb_ord', mb_str_split( $text ) ), $render['mpdf']->fonts['arimo']['subset'] ?? [] ) );
	}

	public function provider_substitutes(): array {
		return [
			'Armenian from DejaVu Sans Condensed' => [ 'Բարեւ', 'dejavusanscondensed', 'DejaVuSansCondensed.ttf' ],
			'Syriac from FreeSans'                => [ 'ܫܠܡܐ', 'freesans', 'FreeSans.ttf' ],
			'Thai from Garuda'                    => [ 'สวัสดี', 'garuda', 'Garuda.ttf' ],
			'Khmer from Khmer OS'                 => [ 'ភាសាខ្មែរ', 'khmeros', 'KhmerOS.ttf' ],
			'Myanmar from Padauk'                 => [ 'မြန်မာ', 'padaukbook', 'Padauk-book.ttf' ],
			'Sinhala from Kaputa'                 => [ 'සිංහල', 'kaputaunicode', 'kaputaunicode.ttf' ],
			'Ethiopic from Abyssinica SIL'        => [ 'ሰላምዓለም', 'abyssinicasil', 'Abyssinica_SIL.ttf' ],
		];
	}

	public function test_sun_exta_on_disk_is_substituted_from_without_a_download(): void {
		/* Sun-ExtA's font data needs no OpenType tables, so any fixture stands in; Chewy has no CJK */
		$this->copy_fixture( 'Chewy.ttf', 'Sun-ExtA.ttf' );
		$this->list_fixture_as( 'Chewy.ttf', 'Sun-ExtA.ttf' );
		FlushCache::flush();
		$this->fail_font_downloads();

		$render = $this->render( '<p style="font-family: arimo">Smile 😀 日本</p>', [ 'font' => 'arimo' ] );

		$this->assertSame( 'sun-exta', array_slice( $render['mpdf']->backupSubsFont, -1 )[0] );
		$this->assertSame( 'Sun-ExtA.ttf', basename( $render['mpdf']->fonts['sun-exta']['ttffile'] ?? '' ) );
		$this->assertStringNotContainsString( 'Sun-ExtA', implode( ',', $this->requested ) );
	}

	public function test_a_cjk_extension_b_character_queues_sun_extb_through_sun_extas_link_too(): void {
		/* Sun-ExtA's link to Sun-ExtB is the other way mPDF reaches it */
		$this->copy_fixture( 'Chewy.ttf', 'Sun-ExtA.ttf' );
		$this->list_fixture_as( 'Chewy.ttf', 'Sun-ExtA.ttf' );
		FlushCache::flush();
		$this->serve_font_downloads();

		foreach ( [ 'arimo', 'sun-exta' ] as $font ) {
			$render = $this->render( '<p style="font-family: ' . $font . '">𠀀</p>', [ 'font' => $font ] );
			$this->assertTrue( $render['degraded'], $font );
		}

		$this->assertStringNotContainsString( 'Sun-ExtB', implode( ',', $this->requested ) );
		$this->assertSame( [ 'Sun-ExtB.ttf' ], $this->wanted_files() );
	}

	public function test_sun_extb_on_disk_is_the_cjk_extension_b_fallback_without_a_download(): void {
		/* Sun-ExtB's font data needs no OpenType tables, so any fixture stands in */
		$this->copy_fixture( 'Chewy.ttf', 'Sun-ExtB.ttf' );
		$this->list_fixture_as( 'Chewy.ttf', 'Sun-ExtB.ttf' );
		FlushCache::flush();
		$this->fail_font_downloads();

		$render = $this->render( '<p style="font-family: arimo">𠀀</p>', [ 'font' => 'arimo' ] );

		$this->assertSame( 'Sun-ExtB.ttf', basename( $render['mpdf']->fonts['sun-extb']['ttffile'] ?? '' ) );
		$this->assertStringNotContainsString( 'Sun-ExtB', implode( ',', $this->requested ) );
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

	public function test_greek_and_cyrillic_a_font_lacks_are_substituted_from_arimo(): void {
		$this->copy_fixture( 'Chewy.ttf' );
		$this->add_record( 'chewy', [ 'regular' => 'Chewy.ttf' ] );

		$this->assertRenderMatchesSnapshot( 'greek-cyrillic-substitution', '<p style="font-family: chewy">Hello Γειά Привет</p>', [ 'font' => 'chewy' ] );
	}

	public function test_an_adopted_template_arimo_record_renders_the_bundled_faces(): void {
		$this->copy_fixture( 'Chewy.ttf', 'Arimo-Regular.ttf' );
		$this->add_record( 'arimo', [ 'regular' => 'Arimo-Regular.ttf' ] );

		$this->assertSame( [ 'arimo' ], $this->get_model_with_legacy( 'Chewy.ttf' )->adopt_records( 'bundled' ) );
		$this->assertRenderMatchesSnapshot( 'adopted-arimo', '<p style="font-family: arimo">Adopted</p>' );
	}

	public function test_a_render_with_its_fonts_on_disk_makes_no_request(): void {
		$this->serve_font_downloads();

		$this->render( '<p style="font-family: dejavusans">Regular <b>Bold</b></p>' );

		$this->assertSame( [], $this->requested );
	}

	public function test_a_render_with_bundled_fonts_does_not_load_the_manifest(): void {
		$this->set_private( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), 'manifest', null );

		$this->render( '<p style="font-family: arimo">Regular <b>Bold</b></p>', [ 'font' => 'arimo' ] );

		$this->assertNull( $this->get_private( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), 'manifest' ) );
	}

	public function test_a_core_font_another_font_holds_the_name_of_renders_from_its_hashed_copy(): void {
		$sources = GPDFAPI::get_mvc_class( 'Model_Font_Sources' );
		$hashed  = $sources->get_hashed_name( 'DejaVuSans.ttf' );

		/* Another font's file at the name, and the manifest's own beside it */
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSans.ttf' );
		$this->copy_fixture( 'DejaVuSans.ttf', $hashed );
		FlushCache::flush();
		$this->serve_font_downloads();

		$render = $this->render( '<p style="font-family: dejavusans">Regular</p>', [ 'font' => 'dejavusans' ] );

		$this->assertSame( $hashed, basename( $render['mpdf']->fonts['dejavusans']['ttffile'] ) );
		$this->assertSame( [], $this->requested );
	}

	public function test_a_core_font_another_font_holds_the_name_of_downloads_beside_it_and_renders_from_the_download(): void {
		$hashed = GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_hashed_name( 'DejaVuSans.ttf' );

		$this->put_longer_font_at( 'DejaVuSans.ttf', 'DejaVuSans.ttf' );
		FlushCache::flush();
		$this->serve_font_downloads();

		$render = $this->render( '<p style="font-family: dejavusans">Regular</p>', [ 'font' => 'dejavusans' ] );

		$this->assertSame( $hashed, basename( $render['mpdf']->fonts['dejavusans']['ttffile'] ) );
		$this->assertSame( [ 'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/v1.1.2/DejaVuSans.ttf' ], $this->requested );
		$this->assertFileEquals( $this->font_fixture( 'DejaVuSans.ttf' ), $this->font_path( $hashed ) );
		$this->assertSame( strlen( (string) file_get_contents( $this->font_fixture( 'DejaVuSans.ttf' ) ) ) + 1, filesize( $this->font_path( 'DejaVuSans.ttf' ) ), 'The other font is left alone' );
	}

	public function test_a_font_manager_font_and_the_core_font_with_the_same_file_name_each_render_their_own(): void {
		$hashed = GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_hashed_name( 'DejaVuSans.ttf' );

		$this->put_longer_font_at( 'DejaVuSans.ttf', 'DejaVuSans.ttf' );
		$this->add_record( 'mine', [ 'regular' => 'DejaVuSans.ttf' ] );
		FlushCache::flush();
		$this->serve_font_downloads();

		$custom = $this->render( '<p style="font-family: mine">Custom</p>', [ 'font' => 'mine' ] );
		$core   = $this->render( '<p style="font-family: dejavusans">Core</p>', [ 'font' => 'dejavusans' ] );

		$this->assertSame( 'DejaVuSans.ttf', basename( $custom['mpdf']->fonts['mine']['ttffile'] ) );
		$this->assertSame( $hashed, basename( $core['mpdf']->fonts['dejavusans']['ttffile'] ) );
		$this->assertCount( 1, $this->requested );
	}

	public function test_a_core_font_another_font_holds_the_name_of_renders_from_that_font_when_it_cannot_download(): void {
		$this->put_longer_font_at( 'DejaVuSans.ttf', 'DejaVuSans.ttf' );
		FlushCache::flush();
		$this->fail_font_downloads();

		$render = $this->render( '<p style="font-family: dejavusans">Regular</p>', [ 'font' => 'dejavusans' ] );

		$this->assertSame( 'DejaVuSans.ttf', basename( $render['mpdf']->fonts['dejavusans']['ttffile'] ) );
		$this->assertCount( 1, $this->requested );
	}

	/**
	 * Render the HTML and compare its fonts with the snapshot
	 *
	 * @param array $settings Overrides of the fixture PDF's settings
	 */
	protected function assertRenderMatchesSnapshot( string $name, string $html, array $settings = [] ): void {
		$this->assertMatchesSnapshot( "fonts/$name.txt", $this->describe_fonts( $this->render( $html, $settings ) ) );
	}

	/**
	 * Render the fixture entry's PDF with the HTML swapped in, and stop once mPDF has written it
	 *
	 * @return array{mpdf: Mpdf, pdf: string, degraded: bool}
	 */
	protected function render( string $html, array $settings = [] ): array {
		[ $result, $render ] = $this->generate( $html, $settings );

		$this->assertInstanceOf( WP_Error::class, $result, 'The render should have been stopped' );
		$this->assertNotNull( $render, 'The PDF failed before it was written: ' . $result->get_error_message() );

		return $render;
	}

	/**
	 * @return array{0: mixed, 1: array{mpdf: Mpdf, pdf: string, degraded: bool}|null} The generate_and_save_pdf() result and the render
	 */
	protected function generate( string $html, array $settings = [] ): array {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		$capture = new class() extends RuntimeException {
			/** @var array{mpdf: Mpdf, pdf: string, degraded: bool}|null */
			public $result;
		};

		$swap = static function () use ( $html ): string {
			return $html;
		};

		$stop = static function ( $mpdf ) use ( $capture ): void {
			$capture->result = [
				'mpdf'     => $mpdf,
				'pdf'      => $mpdf->Output( '', 'S' ),
				'degraded' => Helper_Render_Health::is_degraded(),
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

	/**
	 * @return string[]
	 */
	private function wanted_files(): array {
		return GPDFAPI::get_mvc_class( 'Model_Font_Downloader' )->get_wanted_files();
	}
}
