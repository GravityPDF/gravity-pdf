<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\RendersWithMpdf;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * The regression guard for 7.0's whole premise: a site with an empty fonts directory still renders
 *
 * Until 7.0 the test suite forced mPDF into core-fonts mode through a mu-plugin, so no test ever proved a real
 * font was embedded. That stub is gone, and these cases are what replace it.
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Bundled_Render extends TestCase {

	use RendersWithMpdf;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		/*
		 * A real form only because Helper_PDF's constructor needs one. Note what is deliberately NOT here:
		 * copy_test_fonts(). The point of these cases is that an empty uploads fonts directory still renders.
		 */
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}

	public static function tear_down_after_class(): void {
		static::cleanup_class_fixtures();
		parent::tear_down_after_class();
	}

	public function test_the_font_directories_come_from_the_package_layers_bundled_first() {
		global $gfpdf;

		$mpdf = $this->mpdf_for();

		$reflection = new \ReflectionProperty( \GFPDF_Vendor\Mpdf\Mpdf::class, 'fontDir' );

		if ( PHP_VERSION_ID < 80100 ) {
			$reflection->setAccessible( true );
		}

		$font_dir = $reflection->getValue( $mpdf );

		$this->assertSame( untrailingslashit( PDF_PLUGIN_DIR . 'fonts' ), $font_dir[0] );
		$this->assertSame( untrailingslashit( $gfpdf->data->template_font_location ), $font_dir[1] );
	}

	public function test_the_bundled_font_heads_the_available_list() {
		$mpdf = $this->mpdf_for();

		$this->assertSame( 'gfpdf-arimo', $mpdf->available_unifonts[0] );
		$this->assertSame( [ 'gfpdf-arimo', 'gfpdf-dejavu-symbols' ], array_slice( $mpdf->backupSubsFont, 0, 2 ) );
		$this->assertSame( 'gfpdf-arimo', $mpdf->sans_fonts[0] );
	}

	public function test_a_latin_pdf_renders_from_the_bundled_font_alone() {
		$mpdf = $this->mpdf_for();

		$mpdf->WriteHTML( '<p>Latin, Ελληνικά, Кириллица, Tiếng Việt</p>' );

		/* The guard against a re-introduced core-fonts stub: core fonts embed nothing */
		$this->assertFalse( $mpdf->onlyCoreFonts );
		$this->assertArrayHasKey( 'gfpdf-arimo', $mpdf->fonts );
	}

	public function test_bold_and_italic_load_their_own_faces_rather_than_being_faked() {
		$mpdf = $this->mpdf_for();

		$mpdf->WriteHTML( '<p>plain <b>bold</b> <i>italic</i> <b><i>both</i></b></p>' );

		foreach ( [ 'gfpdf-arimo', 'gfpdf-arimoB', 'gfpdf-arimoI', 'gfpdf-arimoBI' ] as $face ) {
			$this->assertArrayHasKey( $face, $mpdf->fonts, $face );
		}
	}

	public function test_the_consent_ticks_come_from_the_symbol_supplement() {
		$mpdf = $this->mpdf_for();

		$mpdf->WriteHTML( '<p><span style="font-family: gfpdf-dejavu-symbols, sans-serif;">&#10004; &#10006;</span></p>' );

		$this->assertArrayHasKey( 'gfpdf-dejavu-symbols', $mpdf->fonts );
	}

	public function test_an_unknown_font_family_falls_back_to_the_bundled_font() {
		$mpdf = $this->mpdf_for();

		$mpdf->WriteHTML( '<p style="font-family: NoSuchFontAnywhere;">fallback</p>' );

		$this->assertArrayHasKey( 'gfpdf-arimo', $mpdf->fonts );
		$this->assertFalse( $mpdf->onlyCoreFonts );
	}

	public function test_the_registry_aliases_arial_and_helvetica_to_the_bundled_font() {
		$aliases = \GPDFAPI::get_font_registry()->bundled_package()->getFontAliases();

		$this->assertSame( 'gfpdf-arimo', $aliases['arial'] );
		$this->assertSame( 'gfpdf-arimo', $aliases['helvetica'] );
	}

	public function test_arial_resolves_to_the_bundled_font() {
		$mpdf = $this->mpdf_for();

		$this->assertSame( 'gfpdf-arimo', $mpdf->fonttrans['arial'] );

		/*
		 * `helvetica` is asserted on the registry above rather than here: mPDF's initFontRegistry() runs
		 * array_unique() over the alias map, which drops the second of two aliases naming one font. Fixed on the
		 * fork in GravityPDF/mpdf#36; tighten this to fonttrans once the plugin re-pins.
		 */
	}

	public function test_serif_resolves_to_arimo_until_a_serif_pack_is_installed() {
		$mpdf = $this->mpdf_for();

		$mpdf->WriteHTML( '<p style="font-family: serif;">serif</p>' );

		$this->assertArrayHasKey( 'gfpdf-arimo', $mpdf->fonts );
	}

	public function test_the_document_language_and_script_reach_mpdf() {
		$mpdf = $this->mpdf_for( [ 'pdf_language' => 'th' ] );

		$this->assertSame( 'th', $mpdf->default_lang );
		$this->assertSame( 'th', $mpdf->currentLang );
	}

	public function test_a_complete_pdf_is_produced() {
		$mpdf = $this->mpdf_for();

		$mpdf->WriteHTML( '<h1>Heading</h1><p>Body copy with <b>bold</b>.</p>' );
		$output = $mpdf->Output( '', 'S' );

		$this->assertStringStartsWith( '%PDF-', $output );
		$this->assertGreaterThan( 1000, strlen( $output ) );

		/* Subsetted and embedded, not referenced by name */
		$this->assertStringContainsString( 'Arimo', $output );
	}
}
