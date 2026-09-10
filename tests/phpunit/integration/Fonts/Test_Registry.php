<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Mpdf\Ucdn;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Registry
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Registry extends TestCase {

	use HasFontRows;

	/**
	 * @var Registry
	 */
	public $registry;

	/**
	 * @var Font_Repository
	 */
	public $repository;

	/**
	 * @var string
	 */
	public $font_dir;

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$this->registry   = $gfpdf->get_font_registry();
		$this->repository = $this->font_repository();
		$this->font_dir   = $this->font_dir();
	}

	public function tear_down(): void {
		$this->remove_font_rows();

		$options = GPDFAPI::get_options_class();
		$options->update_option( 'font_language_overrides', [] );
		$options->update_option( 'default_font', '' );
		$options->update_option( 'default_pdf_language', '' );
		$options->update_option( 'document_script', '' );

		parent::tear_down();
	}

	protected function install( string $font_key, array $overrides = [] ): int {
		return $this->install_font_row( $font_key, $overrides );
	}

	public function test_the_bundled_layer_registers_arimo_and_the_symbol_supplement() {
		$fonts = $this->registry->bundled_package()->getFonts();

		$this->assertSame( 'Arimo-Regular.ttf', $fonts['gfpdf-arimo']['R'] );
		$this->assertSame( 'Arimo-BoldItalic.ttf', $fonts['gfpdf-arimo']['BI'] );

		/* Arimo carries no legacy kern table, so GPOS is its only route to kerning */
		$this->assertSame( 0xFF, $fonts['gfpdf-arimo']['useOTL'] );

		$this->assertSame( 'DejaVuSansSymbols.ttf', $fonts['gfpdf-dejavu-symbols']['R'] );
		$this->assertSame( 0, $fonts['gfpdf-dejavu-symbols']['useOTL'] );
	}

	public function test_the_bundled_directory_is_the_plugin_fonts_folder() {
		$directory = $this->registry->bundled_package()->getDirectory();

		$this->assertFileExists( $directory . '/Arimo-Regular.ttf' );

		/* FontFileFinder joins with '/', so a trailing slash would double it */
		$this->assertStringEndsNotWith( '/', $directory );
	}

	public function test_installed_rows_register_with_their_flags() {
		$this->install( 'alpha', [ 'use_otl' => 255, 'use_kashida' => 75 ] );

		$fonts = $this->registry->installed_package()->getFonts();

		$this->assertSame( 'test-alpha.ttf', $fonts['alpha']['R'] );
		$this->assertSame( 255, $fonts['alpha']['useOTL'] );
		$this->assertSame( 75, $fonts['alpha']['useKashida'] );
	}

	public function test_a_missing_face_is_left_out_without_touching_the_disk() {
		$id = $this->install( 'beta' );
		$this->repository->insert_file( $id, 'B', [ 'path' => 'test-beta-b.ttf', 'size' => 3, 'missing' => 1 ] );

		$fonts = $this->registry->installed_package()->getFonts();

		$this->assertArrayHasKey( 'R', $fonts['beta'] );
		$this->assertArrayNotHasKey( 'B', $fonts['beta'] );
	}

	public function test_a_row_with_no_readable_face_registers_nothing() {
		$id = $this->install( 'gamma' );
		$this->repository->set_missing( 'test-gamma.ttf', true );

		$this->assertArrayNotHasKey( 'gamma', $this->registry->installed_package()->getFonts() );
	}

	public function test_meta_drives_the_fallback_arrays() {
		$this->install( 'delta', [ 'meta' => [ 'backup_subs' => true, 'bmp' => true, 'family_substitution' => [ 'serif_fonts' ] ] ] );

		$package = $this->registry->installed_package();

		$this->assertContains( 'delta', $package->getBackupSubsFonts() );
		$this->assertContains( 'delta', $package->getBmpFonts() );
		$this->assertContains( 'delta', $package->getFontFamilySubstitution()['serif_fonts'] );
	}

	public function test_bundled_is_read_before_installed() {
		$registry = $this->registry->build_font_registry();
		$packages = array_values( $registry->getAll() );

		/* add() prepends, so the last one added is read first */
		$this->assertSame( 'gfpdf-bundled', $packages[0]->getId() );
		$this->assertSame( 'gfpdf-installed', $packages[1]->getId() );
	}

	public function test_the_language_map_starts_with_latin_only() {
		$this->assertSame( [ 'und-latn' => 'gfpdf-arimo' ], $this->registry->default_language_map() );
	}

	public function test_installed_rows_overlay_the_bundled_map() {
		$this->install( 'epsilon', [ 'meta' => [ 'languages' => [ 'th', 'und-Thai' ] ] ] );

		$map = $this->registry->default_language_map();

		$this->assertSame( 'epsilon', $map['th'] );
		$this->assertSame( 'epsilon', $map['und-thai'] );
		$this->assertSame( 'gfpdf-arimo', $map['und-latn'] );
	}

	public function provider_install_orders(): array {
		return [
			'the generic pack first'  => [ [ 'open-sans', 'taameydavidclm' ] ],
			'the specific pack first' => [ [ 'taameydavidclm', 'open-sans' ] ],
		];
	}

	/**
	 * @dataProvider provider_install_orders
	 */
	public function test_a_specific_pack_outranks_a_generic_one_in_either_install_order( array $order ) {
		$meta = [
			'open-sans'      => [ 'languages' => [ 'he' ], 'generic' => true, 'position' => 0 ],
			'taameydavidclm' => [ 'languages' => [ 'he' ], 'position' => 12 ],
		];

		foreach ( $order as $font_key ) {
			$this->install( $font_key, [ 'meta' => $meta[ $font_key ] ] );
		}

		$this->assertSame( 'taameydavidclm', $this->registry->default_language_map()['he'] );
	}

	public function test_position_orders_two_rows_of_the_same_tier() {
		$this->install( 'caveat', [ 'meta' => [ 'languages' => [ 'ru' ], 'generic' => true, 'position' => 3 ] ] );
		$this->install( 'roboto', [ 'meta' => [ 'languages' => [ 'ru' ], 'generic' => true, 'position' => 0 ] ] );

		$this->assertSame( 'roboto', $this->registry->default_language_map()['ru'] );
	}

	public function test_the_font_key_breaks_a_tie_between_two_equal_rows() {
		$this->install( 'second', [ 'meta' => [ 'languages' => [ 'ko' ], 'position' => 5 ] ] );
		$this->install( 'first', [ 'meta' => [ 'languages' => [ 'ko' ], 'position' => 5 ] ] );

		$this->assertSame( 'first', $this->registry->default_language_map()['ko'] );
	}

	public function test_an_override_replaces_a_mapped_font() {
		$this->install( 'zeta', [ 'meta' => [ 'languages' => [ 'ar' ] ] ] );
		$this->install( 'eta' );

		GPDFAPI::get_options_class()->update_option( 'font_language_overrides', [ 'ar' => 'eta' ] );

		$this->assertSame( 'eta', $this->registry->effective_language_map()['ar'] );
	}

	public function test_the_star_sentinel_removes_a_code() {
		$this->install( 'theta', [ 'meta' => [ 'languages' => [ 'ar' ] ] ] );

		GPDFAPI::get_options_class()->update_option( 'font_language_overrides', [ 'ar' => '*' ] );

		$this->assertArrayNotHasKey( 'ar', $this->registry->effective_language_map() );
	}

	public function test_an_override_naming_an_uninstalled_font_is_dropped() {
		$this->install( 'iota', [ 'meta' => [ 'languages' => [ 'ar' ] ] ] );

		GPDFAPI::get_options_class()->update_option( 'font_language_overrides', [ 'ar' => 'deleted-font' ] );

		/* Falls through to the row rather than sending the run into mPDF's substitution */
		$this->assertSame( 'iota', $this->registry->effective_language_map()['ar'] );
	}

	public function test_the_map_resolves_a_tag_by_script_then_primary_subtag() {
		$this->install( 'kappa', [ 'meta' => [ 'languages' => [ 'und-arab', 'ja' ] ] ] );

		$map = $this->registry->language_to_font();

		$this->assertSame( 'kappa', $map->getLanguageOptions( 'und-Arab', false ) );
		$this->assertSame( 'kappa', $map->getLanguageOptions( 'ar-Arab-eg', false ) );
		$this->assertSame( 'kappa', $map->getLanguageOptions( 'ja-jp', false ) );
		$this->assertSame( '', $map->getLanguageOptions( 'und-Deva', false ) );
	}

	public function test_the_default_font_prefers_the_pdf_then_the_option_then_arimo() {
		$this->install( 'lambda' );

		GPDFAPI::get_options_class()->update_option( 'default_font', 'lambda' );

		$this->assertSame( 'lambda', $this->registry->get_default_font( [ 'font' => 'lambda' ] ) );
		$this->assertSame( 'lambda', $this->registry->get_default_font() );
	}

	public function test_a_font_that_is_not_installed_falls_back_to_arimo() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'never-installed' );

		$this->assertSame( 'gfpdf-arimo', $this->registry->get_default_font() );
		$this->assertSame( 'gfpdf-arimo', $this->registry->get_default_font( [ 'font' => 'also-gone' ] ) );
	}

	public function test_the_document_script_follows_the_language() {
		$options = GPDFAPI::get_options_class();

		$options->update_option( 'default_pdf_language', 'ja' );
		$this->assertSame( Ucdn::SCRIPT_HAN, $this->registry->get_document_script() );

		$options->update_option( 'default_pdf_language', 'ar-eg' );
		$this->assertSame( Ucdn::SCRIPT_ARABIC, $this->registry->get_document_script() );

		$options->update_option( 'default_pdf_language', 'en-gb' );
		$this->assertSame( Ucdn::SCRIPT_LATIN, $this->registry->get_document_script() );
	}

	public function test_the_document_script_setting_overrides_the_language() {
		$options = GPDFAPI::get_options_class();
		$options->update_option( 'default_pdf_language', 'en' );
		$options->update_option( 'document_script', 'SCRIPT_THAI' );

		$this->assertSame( Ucdn::SCRIPT_THAI, $this->registry->get_document_script() );
	}

	public function test_a_per_pdf_language_beats_the_global_one() {
		GPDFAPI::get_options_class()->update_option( 'default_pdf_language', 'en' );

		$this->assertSame( 'th', $this->registry->get_document_language( [ 'pdf_language' => 'th' ] ) );
		$this->assertSame( 'en', $this->registry->get_document_language() );
	}

	public function test_the_default_language_falls_back_to_the_site_locale() {
		$this->assertSame( Language_To_Font::locale_to_language( get_locale() ), $this->registry->get_default_language() );
	}

	public function test_locale_to_language_swaps_separators_and_case() {
		$this->assertSame( 'ja-jp', Language_To_Font::locale_to_language( 'ja_JP' ) );
		$this->assertSame( 'zh-cn', Language_To_Font::locale_to_language( 'zh_CN' ) );
		$this->assertSame( 'en', Language_To_Font::locale_to_language( 'en' ) );
	}

	public function test_the_alias_defers_to_a_row_claiming_the_name() {
		$this->assertSame( 'gfpdf-arimo', $this->registry->bundled_package()->getFontAliases()['arial'] );

		$this->install( 'arial' );

		$this->assertArrayNotHasKey( 'arial', $this->registry->bundled_package()->getFontAliases() );
	}

	public function test_grouped_fonts_separate_packs_from_uploads() {
		$this->install( 'mu' );
		$this->install( 'nu', [ 'source' => 'packs', 'entry' => 'thai', 'coverage' => 1 ] );

		$groups = $this->registry->get_grouped_fonts();

		$this->assertSame( 'Arimo', $groups['Bundled Fonts']['gfpdf-arimo'] );
		$this->assertArrayHasKey( 'mu', $groups['User-Defined Fonts'] );
		$this->assertArrayHasKey( 'nu', $groups['Language Packs'] );
	}
}
