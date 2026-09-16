<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Helper\Mpdf\Mpdf;
use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Concerns\RendersWithMpdf;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Which font each run of a PDF actually renders in, once the language settings are in play
 *
 * The §4.4 behaviour table, pinned. Every case here is decided by the interaction of three things a unit test of
 * any one of them would miss: the language map, the document language mPDF compares a run's tag against, and
 * `autoScriptToLang`, which is what puts a tag on the run in the first place.
 *
 * The fonts are stand-ins — DejaVu Sans wherever a case needs a face with Cyrillic, Chewy wherever it needs one
 * without — because what is under test is which font mPDF *chose*, which is answered before a glyph is looked up.
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Language_Behaviour extends TestCase {

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

		/* Off, so a document whose script has no pack reaches the map rather than the network */
		GPDFAPI::get_options_class()->update_option( 'auto_install_fonts', 'No' );
	}

	public function tear_down(): void {
		$options = GPDFAPI::get_options_class();

		$options->update_option( 'auto_install_fonts', 'Yes' );
		$options->update_option( 'font_language_overrides', [] );
		$options->update_option( 'default_pdf_language', '' );
		$options->update_option( 'default_font', '' );

		$this->remove_font_rows();
		$this->remove_font_files();
		$this->drop_catalog_rows();

		parent::tear_down();
	}

	/**
	 * A font row backed by a face mPDF can parse, claiming the codes named
	 *
	 * Two rows may name the same fixture file, which is the same thing a site with one file under two keys has —
	 * the row is what the registry reads, and a `path` is not unique to a row.
	 */
	protected function install_face( string $font_key, string $fixture, array $languages = [] ): void {
		$this->install_font_row( $font_key, [ 'meta' => [ 'languages' => $languages ] ], $fixture );
	}

	/**
	 * Render one fragment and answer with the font keys mPDF ended up loading
	 *
	 * @return string[]
	 */
	protected function fonts_used( string $html, array $settings = [] ): array {
		$mpdf = $this->mpdf_for( $settings );

		$mpdf->WriteHTML( $html );

		return array_keys( $mpdf->fonts );
	}

	protected function language( string $code ): void {
		GPDFAPI::get_options_class()->update_option( 'default_pdf_language', $code );
	}

	/* --- The document's own language is never re-tagged (§4.4 `default_lang`) --- */

	public function test_a_japanese_document_keeps_its_chosen_font_for_han_and_kana() {
		$this->install_face( 'notosansjp', 'DejaVuSans.ttf', [ 'ja', 'und-hans' ] );
		$this->install_face( 'displayjp', 'Chewy.ttf' );

		$this->language( 'ja' );

		$used = $this->fonts_used( '<p>漢字 ひらがな</p>', [ 'font' => 'displayjp' ] );

		$this->assertContains( 'displayjp', $used );
		$this->assertNotContains( 'notosansjp', $used );
	}

	public function test_the_same_document_in_english_loses_its_han_to_the_installed_pack() {
		$this->install_face( 'notosansjp', 'DejaVuSans.ttf', [ 'ja', 'und-hans' ] );
		$this->install_face( 'displayjp', 'Chewy.ttf' );

		$this->language( 'en' );

		$this->assertContains( 'notosansjp', $this->fonts_used( '<p>漢字</p>', [ 'font' => 'displayjp' ] ) );
	}

	public function test_the_sentinel_is_the_way_back_for_a_run_a_pack_would_take() {
		$this->install_face( 'notosansjp', 'DejaVuSans.ttf', [ 'ja', 'und-hans' ] );
		$this->install_face( 'displayjp', 'Chewy.ttf' );

		$this->language( 'en' );
		GPDFAPI::get_options_class()->update_option( 'font_language_overrides', [ 'und-hans' => '*' ] );

		$used = $this->fonts_used( '<p>漢字</p>', [ 'font' => 'displayjp' ] );

		$this->assertContains( 'displayjp', $used );
		$this->assertNotContains( 'notosansjp', $used );
	}

	public function test_an_explicit_lang_attribute_reaches_the_map_on_any_document() {
		$this->install_face( 'notosansjp', 'DejaVuSans.ttf', [ 'ja' ] );
		$this->install_face( 'displayjp', 'Chewy.ttf' );

		$this->language( 'en' );

		$this->assertContains(
			'notosansjp',
			$this->fonts_used( '<p lang="ja">ひらがな</p>', [ 'font' => 'displayjp' ] )
		);
	}

	public function test_a_pdfs_own_language_overrides_the_site_wide_one() {
		$this->install_face( 'notosansjp', 'DejaVuSans.ttf', [ 'ja', 'und-hans' ] );
		$this->install_face( 'displayjp', 'Chewy.ttf' );

		$this->language( 'en' );

		$used = $this->fonts_used(
			'<p>漢字</p>',
			[
				'font'         => 'displayjp',
				'pdf_language' => 'ja',
			]
		);

		$this->assertNotContains( 'notosansjp', $used );
	}

	/* --- Latin is left alone (§4.4: no Latin-language rows, `autoVietnamese` off) --- */

	public function test_vietnamese_text_is_not_flipped_out_of_the_document_font() {
		$this->install_face( 'notosansvi', 'DejaVuSans.ttf', [ 'vi' ] );
		$this->install_face( 'lato', 'Chewy.ttf' );

		$used = $this->fonts_used( '<p>Tiếng Việt</p>', [ 'font' => 'lato' ] );

		$this->assertContains( 'lato', $used );
		$this->assertNotContains( 'notosansvi', $used );
	}

	public function test_a_french_span_in_a_latin_document_changes_nothing() {
		$this->install_face( 'notosansfr', 'DejaVuSans.ttf', [ 'fr' ] );
		$this->install_face( 'lato', 'Chewy.ttf' );

		$used = $this->fonts_used( '<p>English <span lang="fr">français</span></p>', [ 'font' => 'lato' ] );

		$this->assertContains( 'lato', $used );

		/* The map has a `fr` row, and it still fires — this is why the bundled map has no Latin-language rows */
		$this->assertContains( 'notosansfr', $used );
	}

	/* --- The scripts mPDF scavenges rather than maps (§4.4 `backupSubsFont`) --- */

	public function test_cyrillic_stays_in_a_document_font_that_carries_it() {
		$this->install_face( 'roboto', 'DejaVuSans.ttf' );

		$used = $this->fonts_used( '<p>Привет</p>', [ 'font' => 'roboto' ] );

		$this->assertSame( [ 'roboto' ], $used );
	}

	public function test_cyrillic_in_a_latin_only_document_font_is_substituted_from_the_bundled_face() {
		$this->install_face( 'lato', 'Chewy.ttf' );

		$used = $this->fonts_used( '<p>Привет</p>', [ 'font' => 'lato' ] );

		$this->assertContains( 'lato', $used );
		$this->assertContains( Registry::BUNDLED_FONT, $used );
	}

	public function test_an_installed_pack_claiming_cyrillic_takes_it_back_off_substitution() {
		$this->install_face( 'notosanscyrl', 'DejaVuSans.ttf', [ 'und-cyrl' ] );
		$this->install_face( 'lato', 'Chewy.ttf' );

		$this->assertContains( 'notosanscyrl', $this->fonts_used( '<p>Привет</p>', [ 'font' => 'lato' ] ) );
	}

	/* --- What a site that chose no font renders in (§4.8 Default font) --- */

	public function test_a_japanese_site_that_chose_no_font_gets_the_pack_that_answers_for_japanese() {
		global $gfpdf;

		$this->install_face( 'notosansjp', 'DejaVuSans.ttf', [ 'ja' ] );
		$this->language( 'ja' );

		$this->assertSame( 'notosansjp', $gfpdf->get_font_registry()->get_default_font() );
	}

	public function test_a_site_whose_language_no_pack_answers_for_gets_the_bundled_font() {
		global $gfpdf;

		$this->install_face( 'notosansjp', 'DejaVuSans.ttf', [ 'ja' ] );
		$this->language( 'en' );

		$this->assertSame( Registry::BUNDLED_FONT, $gfpdf->get_font_registry()->get_default_font() );
	}

	/* --- The document script, which decides what counts as "the document's own" (§4.4 `baseScript`) --- */

	public function test_the_document_language_sets_the_script_and_both_reach_mpdf() {
		$this->language( 'ja' );

		$mpdf = $this->mpdf_for();

		$this->assertSame( 'ja', $mpdf->default_lang );
		$this->assertSame( \GFPDF_Vendor\Mpdf\Ucdn::SCRIPT_HAN, $mpdf->baseScript );
	}

	public function test_an_explicit_document_script_overrides_the_one_the_language_implies() {
		$this->language( 'ja' );
		GPDFAPI::get_options_class()->update_option( 'document_script', 'LATIN' );

		$this->assertSame( \GFPDF_Vendor\Mpdf\Ucdn::SCRIPT_LATIN, $this->mpdf_for()->baseScript );

		GPDFAPI::get_options_class()->update_option( 'document_script', '' );
	}
}
