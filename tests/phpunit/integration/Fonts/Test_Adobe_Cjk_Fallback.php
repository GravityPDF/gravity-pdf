<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Concerns\RendersWithMpdf;
use GFPDF\Helper\Mpdf\Mpdf;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;
use ReflectionProperty;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * What a CJK document renders with when its pack has not landed
 *
 * The last resort, and the one that is not the plugin's own fonts: mPDF writes a non-embedded `CIDFontType0` and
 * the reader's viewer supplies the glyphs. Worse than the real pack in every way except the one that counts —
 * an emailed PDF nobody can read is worse still.
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Adobe_Cjk_Fallback extends TestCase {

	use HasCatalogRows;
	use HasFontRows;
	use RendersWithMpdf;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$gfpdf->get_font_repository()->ensure_ready();
		$this->drop_catalog_rows();

		/* Off, so the render reaches the fallback rather than the network */
		GPDFAPI::get_options_class()->update_option( 'auto_install_fonts', 'No' );

		$this->insert_catalog_row(
			'packs',
			'japanese',
			[
				'coverage'  => 1,
				'scripts'   => 'ja',
				'languages' => 'ja',
				'font_keys' => 'notosansjp',
			]
		);
	}

	public function tear_down(): void {
		GPDFAPI::get_options_class()->update_option( 'auto_install_fonts', 'Yes' );

		$this->drop_catalog_rows();
		$this->remove_font_rows();

		parent::tear_down();
	}

	/**
	 * What mPDF itself would resolve a tag to
	 *
	 * Read through the private property mPDF keeps it on, because the registry the config hands over is the only
	 * place the answer exists — asking `Registry` again would be asserting the test's own arithmetic.
	 *
	 * @return string The font family, or `''`
	 */
	protected function resolves( Mpdf $mpdf, string $tag ): string {
		$property = new ReflectionProperty( \GFPDF_Vendor\Mpdf\Mpdf::class, 'languageToFont' );

		/* Required before 8.1, deprecated from 8.5, and both versions are in CI */
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}

		[ , $font ] = $property->getValue( $mpdf )->getLanguageOptions( $tag, $mpdf->useAdobeCJK );

		return (string) $font;
	}

	public function test_a_japanese_document_with_no_pack_falls_back_to_the_adobe_family() {
		$mpdf = $this->mpdf_for( [], [ '1' => 'ひらがな' ] );

		$this->assertTrue( $mpdf->useAdobeCJK );
		$this->assertSame( 'sjis', $this->resolves( $mpdf, 'ja' ) );
	}

	/**
	 * `AddCJKFont()` throws outright under either, so the fallback would turn a missing font into a failed render
	 */
	public function test_an_archival_pdf_renders_with_the_bundled_faces_instead() {
		foreach ( [ 'pdfa1b', 'pdfx1a' ] as $format ) {
			$mpdf = $this->mpdf_for( [ 'format' => $format ], [ '1' => 'ひらがな' ] );

			$this->assertFalse( $mpdf->useAdobeCJK, $format );
			$this->assertSame( '', $this->resolves( $mpdf, 'ja' ), $format );
		}
	}

	public function test_a_latin_document_never_reaches_the_fallback() {
		$mpdf = $this->mpdf_for( [], [ '1' => 'Hello world' ] );

		$this->assertFalse( $mpdf->useAdobeCJK );
		$this->assertSame( '', $this->resolves( $mpdf, 'ja' ) );
	}

	/**
	 * The stand-in is only ever a stand-in: once the pack is there the map answers and this never applies again
	 */
	public function test_an_installed_pack_wins_over_the_fallback() {
		$this->install_font_row( 'notosansjp', [ 'meta' => [ 'languages' => [ 'ja' ] ] ] );

		$mpdf = $this->mpdf_for( [], [ '1' => 'ひらがな' ] );

		$this->assertFalse( $mpdf->useAdobeCJK );
		$this->assertSame( 'notosansjp', $this->resolves( $mpdf, 'ja' ) );
	}

	/**
	 * A pack short of one of its faces still has files outstanding, so the detector keeps asking for it — but the
	 * face that decides this document has landed, and a stand-in would replace a real font with a worse one
	 */
	public function test_a_pack_whose_japanese_face_landed_is_not_stood_in_for() {
		$this->insert_catalog_row(
			'packs',
			'cjk',
			[
				'coverage'  => 1,
				'scripts'   => 'ja,ko',
				'font_keys' => 'notosansjp,notosanskr',
			]
		);

		$this->install_font_row( 'notosansjp', [ 'meta' => [ 'languages' => [ 'ja' ] ] ] );

		$mpdf = $this->mpdf_for( [], [ '1' => 'ひらがな' ] );

		$this->assertFalse( $mpdf->useAdobeCJK );
		$this->assertSame( 'notosansjp', $this->resolves( $mpdf, 'ja' ) );
	}

	/**
	 * Han with no better signal is Simplified, which is the pack split and the only one covering all of Han
	 */
	public function test_a_kanji_only_document_falls_back_to_simplified_chinese() {
		$this->insert_catalog_row(
			'packs',
			'chinese-simplified',
			[
				'coverage'  => 1,
				'scripts'   => 'und-hans',
				'font_keys' => 'notosanssc',
			]
		);

		$mpdf = $this->mpdf_for( [], [ '1' => '漢字' ] );

		$this->assertTrue( $mpdf->useAdobeCJK );
		$this->assertSame( 'gb', $this->resolves( $mpdf, 'und-Hans' ) );
	}

	/**
	 * An admin's own override is a choice, not a stand-in, so it still wins
	 */
	public function test_a_language_override_wins_over_the_fallback() {
		$this->install_font_row( 'notosansjp' );
		GPDFAPI::get_options_class()->update_option( 'font_language_overrides', [ 'ja' => 'notosansjp' ] );

		$mpdf = $this->mpdf_for( [], [ '1' => 'ひらがな' ] );

		$this->assertSame( 'notosansjp', $this->resolves( $mpdf, 'ja' ) );

		GPDFAPI::get_options_class()->update_option( 'font_language_overrides', [] );
	}
}
