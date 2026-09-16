<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\HasCatalogRows;
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

	use HasCatalogRows;
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
		$this->drop_catalog_rows();

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

	public function test_a_licence_file_is_recorded_without_becoming_a_face() {
		$id = $this->install( 'epsilon' );

		/* mPDF is handed roles, so a licence text reaching `AddFont()` would throw where a font would render */
		$this->repository->insert_file( $id, 'LICENSE', [ 'path' => 'test-epsilon-licence.txt', 'size' => 3 ] );

		$fonts = $this->registry->installed_package()->getFonts();

		$this->assertSame( [ 'R', 'useOTL', 'useKashida' ], array_keys( $fonts['epsilon'] ) );
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

	public function test_the_backup_substitution_list_keeps_the_order_the_rows_arrive_in() {
		foreach ( [ 'dejavusanscondensed', 'freesans', 'sun-exta' ] as $font_key ) {
			$this->install( $font_key, [ 'meta' => [ 'legacy' => true, 'backup_subs' => true ] ] );
		}

		/* 6.x's own three, and mPDF reads the bundled pair ahead of them (see the layer order below) */
		$this->assertSame(
			[ 'dejavusanscondensed', 'freesans', 'sun-exta' ],
			$this->registry->installed_package()->getBackupSubsFonts()
		);
		$this->assertSame(
			[ 'gfpdf-arimo', 'gfpdf-dejavu-symbols' ],
			$this->registry->bundled_package()->getBackupSubsFonts()
		);
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

	/**
	 * @dataProvider provider_install_orders_with_legacy
	 */
	public function test_a_pack_outranks_a_legacy_row_in_either_install_order( array $order ) {
		$meta = [
			'notosanskr' => [ 'languages' => [ 'ko' ], 'position' => 4 ],
			'unbatang'   => [ 'languages' => [ 'ko' ], 'legacy' => true ],
		];

		foreach ( $order as $font_key ) {
			$this->install( $font_key, [ 'meta' => $meta[ $font_key ] ] );
		}

		$this->assertSame( 'notosanskr', $this->registry->default_language_map()['ko'] );
	}

	public function provider_install_orders_with_legacy(): array {
		return [
			'the legacy row first' => [ [ 'unbatang', 'notosanskr' ] ],
			'the pack first'       => [ [ 'notosanskr', 'unbatang' ] ],
		];
	}

	public function test_a_legacy_row_answers_a_code_no_pack_claims() {
		$this->install( 'unbatang', [ 'meta' => [ 'legacy' => true, 'languages' => [ 'ko', 'kor' ] ] ] );

		$this->assertSame( 'unbatang', $this->registry->default_language_map()['ko'] );
	}

	public function test_a_legacy_row_takes_back_the_latin_code_it_had_in_six() {
		$this->install( 'dejavusanscondensed', [ 'meta' => [ 'legacy' => true, 'languages' => [ 'und-latn' ] ] ] );

		$this->assertSame( 'dejavusanscondensed', $this->registry->default_language_map()['und-latn'] );
	}

	public function test_deleting_a_legacy_row_returns_its_codes_to_the_bundled_map() {
		$this->install( 'dejavusanscondensed', [ 'meta' => [ 'legacy' => true, 'languages' => [ 'und-latn', 'ru' ] ] ] );

		$this->repository->delete( 'dejavusanscondensed' );

		$map = $this->registry->default_language_map();

		$this->assertSame( 'gfpdf-arimo', $map['und-latn'] );
		$this->assertArrayNotHasKey( 'ru', $map );
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

		/* What the Font Manager stores, and what mPDF itself calls the same script */
		foreach ( [ 'THAI', 'SCRIPT_THAI' ] as $stored ) {
			$options->update_option( 'document_script', $stored );

			$this->assertSame( Ucdn::SCRIPT_THAI, $this->registry->get_document_script(), $stored );
		}
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

	/**
	 * WordPress has no `en` locale — the Site Language option stores `''` for English (United States) and
	 * `get_locale()` turns that into `en_US` — so `en-us` is the default path, not an edge case (§11 D21)
	 */
	public function test_a_regional_locale_is_named_by_the_language_above_it() {
		$this->assertSame( 'English', Language_To_Font::label_for( 'en-us' ) );
		$this->assertSame( 'Portuguese', Language_To_Font::label_for( 'pt-br' ) );

		/* A tag the table names outright is its own answer, regional or not, and a script tag is reached the same way */
		$this->assertSame( 'Chinese (Taiwan)', Language_To_Font::label_for( 'zh-tw' ) );
		$this->assertSame( 'Japanese', Language_To_Font::label_for( 'ja' ) );
		$this->assertSame( 'Han (Simplified)', Language_To_Font::label_for( 'zh-Hans' ) );
	}

	public function test_a_code_no_rung_names_is_shown_as_itself() {
		$this->assertSame( 'qqq-zz', Language_To_Font::label_for( 'qqq-zz' ) );
		$this->assertSame( '', Language_To_Font::label_for( '' ) );
	}

	/**
	 * The claim is what installs a pack and the map is what a render resolves through, and nothing on a site
	 * compares them: `chinese-traditional` claims `zh-tw`, routes nothing, and `zh-tw` widens to Simplified's
	 * `zh` (§11 D22)
	 */
	public function test_a_claim_no_route_answers_is_reported() {
		$this->assertSame(
			[ 'zh-hk', 'zh-tw' ],
			Language_To_Font::unrouted_claims( [ 'languages' => 'zh-hk,zh-tw' ], [] )
		);

		/* Most specific first: a regional claim is answered by the bare language it widens to, and by its script */
		$this->assertSame( [], Language_To_Font::unrouted_claims( [ 'languages' => [ 'zh-tw' ] ], [ 'zh' ] ) );
		$this->assertSame( [], Language_To_Font::unrouted_claims( [ 'languages' => [ 'zh-hant' ] ], [ 'und-Hant' ] ) );

		$this->assertSame(
			[ 'ko' ],
			Language_To_Font::unrouted_claims( [ 'scripts' => [ 'ja' ], 'languages' => [ 'ko' ] ], [ 'ja', 'jpn' ] )
		);
	}

	/* `central-asian` withholds a route for `mn` on purpose, whose tag doubles as a language, and says so */
	public function test_a_claim_the_pack_declares_it_will_not_route_is_not_reported() {
		$claim = [ 'scripts' => 'mn,und-Yiii' ];

		$this->assertSame( [ 'mn' ], Language_To_Font::unrouted_claims( $claim, [ 'und-Yiii' ] ) );
		$this->assertSame( [], Language_To_Font::unrouted_claims( $claim, [ 'und-Yiii' ], [ 'mn' ] ) );
	}

	/* An `always` pack routes nothing by design: `emoji` reaches text through backup-subs, not the language map */
	public function test_an_always_pack_claims_without_routing_by_design() {
		$this->assertSame(
			[],
			Language_To_Font::unrouted_claims( [ 'scripts' => 'und-zsye', 'always' => 1 ], [] )
		);
	}

	public function test_the_alias_defers_to_a_row_claiming_the_name() {
		$this->assertSame( 'gfpdf-arimo', $this->registry->bundled_package()->getFontAliases()['arial'] );

		$this->install( 'arial' );

		$this->assertArrayNotHasKey( 'arial', $this->registry->bundled_package()->getFontAliases() );
	}

	public function test_a_rows_own_aliases_resolve_to_it() {
		$this->install( 'ocrb', [ 'meta' => [ 'legacy' => true, 'aliases' => [ 'ocr-b', 'ocr-b10bt' ] ] ] );

		$aliases = $this->registry->bundled_package()->getFontAliases();

		$this->assertSame( 'ocrb', $aliases['ocr-b'] );
		$this->assertSame( 'ocrb', $aliases['ocr-b10bt'] );
	}

	public function test_an_alias_a_row_claims_by_name_is_dropped() {
		$this->install( 'ocrb', [ 'meta' => [ 'legacy' => true, 'aliases' => [ 'ocr-b' ] ] ] );
		$this->install( 'ocr-b' );

		$this->assertArrayNotHasKey( 'ocr-b', $this->registry->bundled_package()->getFontAliases() );
	}

	public function test_the_generic_sans_list_carries_the_css_generic_names() {
		$sans = $this->registry->bundled_package()->getFontFamilySubstitution()['sans_fonts'];

		$this->assertSame( 'gfpdf-arimo', $sans[0] );
		$this->assertContains( 'cursive', $sans );
		$this->assertContains( 'fantasy', $sans );
	}

	public function test_grouped_fonts_separate_packs_from_uploads() {
		$this->insert_catalog_row( 'packs', 'thai', [ 'coverage' => 1, 'label' => 'Thai' ] );

		$this->install( 'mu' );
		$this->install( 'nu', [ 'source' => 'packs', 'entry' => 'thai', 'coverage' => 1 ] );

		$fonts = $this->registry->get_grouped_fonts();

		$this->assertSame( 'gfpdf-arimo', $fonts['bundled'][0]['id'] );
		$this->assertSame( [ 'mu' ], array_column( $fonts['custom'], 'id' ) );

		$this->assertCount( 1, $fonts['groups'] );
		$this->assertSame( 'Thai', $fonts['groups'][0]['label'] );
		$this->assertSame( 'packs', $fonts['groups'][0]['source'] );
		$this->assertSame( [ 'nu' ], array_column( $fonts['groups'][0]['fonts'], 'id' ) );
	}

	public function test_a_catalogue_display_family_is_grouped_with_the_uploads() {
		$this->insert_catalog_row( 'google', 'lato' );

		/* An admin thinks of a font they chose and named the same way whether it came from a CDN or their desktop */
		$this->install( 'latolight', [ 'source' => 'google', 'entry' => 'lato' ] );

		$fonts = $this->registry->get_grouped_fonts();

		$this->assertSame( [], $fonts['groups'] );
		$this->assertSame( [ 'latolight' ], array_column( $fonts['custom'], 'id' ) );
	}

	public function test_the_groups_run_in_the_catalogues_order_not_the_rows() {
		$this->insert_catalog_row( 'packs', 'korean', [ 'coverage' => 1, 'position' => 9 ] );
		$this->insert_catalog_row( 'packs', 'arabic', [ 'coverage' => 1, 'position' => 2 ] );

		$this->install( 'nanum', [ 'source' => 'packs', 'entry' => 'korean', 'coverage' => 1 ] );
		$this->install( 'xbriyaz', [ 'source' => 'packs', 'entry' => 'arabic', 'coverage' => 1 ] );

		/* The sidebar and the browser list packs in one order, and this is where that order comes from */
		$this->assertSame( [ 'arabic', 'korean' ], array_column( $this->registry->get_grouped_fonts()['groups'], 'entry' ) );
	}

	public function test_a_pack_the_catalogue_has_dropped_keeps_its_group() {
		$this->install( 'nanum', [ 'source' => 'packs', 'entry' => 'korean', 'coverage' => 1, 'label' => 'Nanum Gothic' ] );

		$group = $this->registry->get_grouped_fonts()['groups'][0];

		/* The PDFs naming it still render, so hiding it from the dropdown would be the only thing that broke */
		$this->assertSame( 'Nanum Gothic', $group['label'] );
		$this->assertSame( 'korean', $group['entry'] );
		$this->assertSame( 1, $group['files'] );
	}

	public function test_a_pack_label_is_translated_on_the_way_out() {
		$this->insert_catalog_row( 'packs', 'japanese', [ 'coverage' => 1, 'label' => 'Japanese (raw)' ] );
		$this->install( 'notosansjp', [ 'source' => 'packs', 'entry' => 'japanese', 'coverage' => 1 ] );

		$this->assertSame( 'Japanese', $this->registry->get_grouped_fonts()['groups'][0]['label'] );
	}

	public function test_every_face_carries_what_a_preview_needs() {
		$this->install( 'mu' );

		$file = $this->registry->get_grouped_fonts()['custom'][0]['files']['R'];

		$this->assertSame( 'R', $file['role'] );
		$this->assertSame( 'test-mu.ttf', $file['path'] );
		$this->assertStringEndsWith( '/fonts/test-mu.ttf', (string) $file['url'] );
		$this->assertSame( 0, $file['missing'] );

		/* How the installer decides whether to re-fetch a file, and no business of the browser's */
		$this->assertArrayNotHasKey( 'sha256', $file );
	}

	public function test_a_face_that_is_not_on_disk_is_given_no_url() {
		$id = $this->install( 'mu' );

		$this->repository->insert_file( $id, 'B', [ 'path' => 'test-gone.ttf', 'missing' => 1 ] );

		$this->assertNull( $this->registry->get_grouped_fonts()['custom'][0]['files']['B']['url'] );
	}

	public function test_the_bundled_faces_are_served_from_the_plugin_directory() {
		$bundled = $this->registry->get_grouped_fonts()['bundled'][0];

		$this->assertSame( 'bundled', $bundled['source'] );
		$this->assertTrue( $bundled['enabled'] );
		$this->assertStringEndsWith( '/fonts/Arimo-Regular.ttf', (string) $bundled['files']['R']['url'] );
		$this->assertGreaterThan( 0, $bundled['files']['BI']['size'] );
	}

	public function test_only_an_always_entry_with_nothing_installed_is_reported_missing() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1, 'always' => 1, 'size' => 943718 ] );
		$this->insert_catalog_row( 'packs', 'indic', [ 'coverage' => 1, 'always' => 1 ] );
		$this->insert_catalog_row( 'packs', 'thai', [ 'coverage' => 1 ] );

		$this->install( 'freesans', [ 'source' => 'packs', 'entry' => 'indic', 'coverage' => 1 ] );

		$this->assertSame(
			[
				[
					'source' => 'packs',
					'entry'  => 'emoji',
					'label'  => 'Emoji',
					'size'   => 943718,
				],
			],
			$this->registry->missing_always_entries()
		);
	}

	/**
	 * The settings screen's view of the map, keyed by group id
	 *
	 * @return array<string, array>
	 */
	protected function language_map(): array {
		return array_column( $this->registry->language_map(), null, 'group' );
	}

	/**
	 * One group's rows, keyed by language code
	 *
	 * @return array<string, array>
	 */
	protected function rows_of( string $group ): array {
		return array_column( $this->language_map()[ $group ]['rows'] ?? [], null, 'code' );
	}

	public function test_a_fresh_site_maps_one_code_and_files_it_under_the_bundled_font() {
		$map = $this->registry->language_map();

		$this->assertCount( 1, $map );
		$this->assertSame( 'bundled', $map[0]['group'] );
		$this->assertSame( 'Bundled · Arimo', $map[0]['label'] );
		$this->assertSame(
			[
				'code'         => 'und-latn',
				'label'        => 'Latin script',
				'default_font' => 'gfpdf-arimo',
				'font'         => 'gfpdf-arimo',
			],
			$map[0]['rows'][0]
		);
	}

	public function test_an_installed_packs_codes_group_under_the_pack_that_claims_them() {
		$this->install_pack( 'japanese', 'notosansjp', [ 'ja', 'jpn' ] );

		$rows = $this->rows_of( 'packs/japanese' );

		$this->assertSame( [ 'ja', 'jpn' ], array_keys( $rows ) );
		$this->assertSame( 'Japanese', $rows['ja']['label'] );
		$this->assertSame( 'notosansjp', $rows['ja']['default_font'] );
		$this->assertSame( 'notosansjp', $rows['ja']['font'] );

		/* The pack's group label, not the font's: one label source with the sidebar (§4.4) */
		$this->assertSame( 'Japanese', $this->language_map()['packs/japanese']['label'] );
	}

	public function test_an_override_moves_the_font_but_not_the_group() {
		$this->install_pack( 'japanese', 'notosansjp', [ 'ja' ] );
		$this->install( 'brandsans' );

		GPDFAPI::get_options_class()->update_option( 'font_language_overrides', [ 'ja' => 'brandsans' ] );

		$row = $this->rows_of( 'packs/japanese' )['ja'];

		$this->assertSame( 'notosansjp', $row['default_font'] );
		$this->assertSame( 'brandsans', $row['font'] );
	}

	public function test_a_code_overridden_to_the_sentinel_reads_as_no_font_rather_than_vanishing() {
		$this->install_pack( 'japanese', 'notosansjp', [ 'ja' ] );

		GPDFAPI::get_options_class()->update_option( 'font_language_overrides', [ 'ja' => '*' ] );

		$row = $this->rows_of( 'packs/japanese' )['ja'];

		$this->assertSame( 'notosansjp', $row['default_font'] );
		$this->assertSame( '*', $row['font'] );
	}

	public function test_a_code_no_map_routes_becomes_an_other_row_with_no_default() {
		$this->install( 'brandsans' );

		GPDFAPI::get_options_class()->update_option( 'font_language_overrides', [ 'sw' => 'brandsans' ] );

		$this->assertSame(
			[
				'code'         => 'sw',
				'label'        => 'Swahili',
				'default_font' => '*',
				'font'         => 'brandsans',
			],
			$this->rows_of( 'other' )['sw']
		);
	}

	public function test_the_codes_a_6x_upgrade_adopted_group_with_the_override_only_rows() {
		$this->install( 'unbatang', [ 'source' => 'imported', 'meta' => [ 'legacy' => true, 'languages' => [ 'ko' ] ] ] );

		$this->assertSame( 'unbatang', $this->rows_of( 'other' )['ko']['default_font'] );
	}

	public function test_a_code_no_label_names_falls_back_to_the_code_itself() {
		$this->install( 'brandsans', [ 'meta' => [ 'languages' => [ 'xx-zz' ] ] ] );

		$this->assertSame( 'xx-zz', $this->rows_of( 'other' )['xx-zz']['label'] );
	}

	public function test_rows_are_ordered_by_name_rather_than_by_the_order_the_entry_listed_them() {
		$this->install_pack( 'west-asian', 'notosanssyriac', [ 'syr', 'hy', 'ka' ] );

		$this->assertSame(
			[ 'Armenian', 'Georgian', 'Syriac' ],
			array_column( $this->language_map()['packs/west-asian']['rows'], 'label' )
		);
	}

	public function test_a_group_with_no_codes_is_left_out_entirely() {
		$this->install_pack( 'popular-sans', 'roboto' );

		$this->assertArrayNotHasKey( 'packs/popular-sans', $this->language_map() );
	}

	public function test_an_override_naming_a_font_that_is_gone_leaves_the_row_on_its_default() {
		$this->install_pack( 'japanese', 'notosansjp', [ 'ja' ] );

		GPDFAPI::get_options_class()->update_option( 'font_language_overrides', [ 'ja' => 'deletedfont' ] );

		$this->assertSame( 'notosansjp', $this->rows_of( 'packs/japanese' )['ja']['font'] );
	}

	/**
	 * The one code set that is knowable without a catalogue: what a 6.x upgrade adopts off disk
	 *
	 * A pack claiming a code no label names degrades to the raw code, which is the right failure and is pinned
	 * above. This set cannot degrade quietly, because it is frozen in the repository and can be checked.
	 */
	public function test_every_language_a_6x_upgrade_carries_has_a_name() {
		$labels  = Language_To_Font::labels();
		$unnamed = [];

		foreach ( Legacy_Installer_Files::FAMILIES as $family ) {
			foreach ( (array) ( $family['languages'] ?? [] ) as $code ) {
				if ( ! isset( $labels[ strtolower( (string) $code ) ] ) ) {
					$unnamed[] = $code;
				}
			}
		}

		$this->assertSame( [], array_values( array_unique( $unnamed ) ) );
	}

	/**
	 * A 6.x site has years of these on disk: until 2026-09-15 `settings_sanitize()` wrote `[]` for any unanswered
	 * select, so the readers have to survive one even though nothing writes one any more
	 */
	public function test_a_pdf_setting_stored_as_an_empty_array_reads_as_no_choice() {
		$this->install( 'brandsans' );

		GPDFAPI::get_options_class()->update_option( 'default_pdf_language', 'ja' );

		$this->assertSame( Registry::BUNDLED_FONT, $this->registry->get_default_font( [ 'font' => [] ] ) );
		$this->assertSame( 'ja', $this->registry->get_document_language( [ 'pdf_language' => [] ] ) );
	}

	/**
	 * `switch_to_locale()` is how a notification email renders in the recipient's language, and it moves what
	 * `determine_locale()` answers — driven here through the filter that function consults, since the real call
	 * refuses a locale with no translations installed and the test environment has none
	 */
	public function test_the_label_table_is_rebuilt_when_the_locale_changes() {
		$french    = static function () {
			return 'fr_FR';
		};
		$translate = static function ( $translation, $text, $domain ) {
			return $domain === 'gravity-pdf' && determine_locale() === 'fr_FR' ? 'fr:' . $text : $translation;
		};

		add_filter( 'gettext', $translate, 10, 3 );

		$before = Language_To_Font::labels()['ja'];

		add_filter( 'pre_determine_locale', $french );
		$after = Language_To_Font::labels()['ja'];
		remove_filter( 'pre_determine_locale', $french );

		remove_filter( 'gettext', $translate, 10 );

		/* A memo keyed on anything but the locale hands the email the site's own language instead */
		$this->assertSame( 'Japanese', $before );
		$this->assertSame( 'fr:Japanese', $after );
	}

	public function test_every_label_is_a_string_keyed_by_a_lowercase_code() {
		$labels = Language_To_Font::labels();

		$this->assertSame( 'Japanese', $labels['ja'] );
		$this->assertSame( 'Han (Simplified)', $labels['und-hans'] );

		foreach ( $labels as $code => $label ) {
			$this->assertSame( strtolower( (string) $code ), (string) $code );
			$this->assertNotSame( '', $label );
		}
	}
}
