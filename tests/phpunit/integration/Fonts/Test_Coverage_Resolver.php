<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * What a site is told to install, and why
 *
 * The catalogue speaks mPDF's language tags because the pipeline generates the column from mPDF's own
 * `LanguageToFont`; WordPress speaks locales. Most of these cases are that translation and its one asymmetry —
 * `zh-tw` is Traditional where bare `zh` is Simplified.
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Coverage_Resolver extends TestCase {

	use HasCatalogRows;
	use HasFontRows;

	/**
	 * @var Coverage_Resolver
	 */
	public $resolver;

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$gfpdf->get_font_repository()->ensure_ready();
		$this->drop_catalog_rows();

		$this->resolver = $gfpdf->get_coverage_resolver();
	}

	public function tear_down(): void {
		GPDFAPI::get_options_class()->update_option( 'default_pdf_language', '' );

		$this->drop_catalog_rows();
		$this->remove_font_rows();

		parent::tear_down();
	}

	/**
	 * One coverage entry, columns only
	 *
	 * No entry document: the resolver names entries, never their files, so a fixture carrying one would be
	 * asserting against a shape it cannot read.
	 */
	protected function seed_pack( string $entry, array $overrides = [] ): void {
		$this->insert_catalog_row( 'packs', $entry, array_merge( [ 'coverage' => 1 ], $overrides ) );
	}

	/**
	 * The four packs the locale table turns on, plus the one that is always wanted
	 */
	protected function seed_catalogue(): void {
		$this->seed_pack( 'emoji', [ 'always' => 1 ] );
		$this->seed_pack( 'japanese', [ 'languages' => 'ja', 'font_keys' => 'notosansjp' ] );
		$this->seed_pack( 'chinese-simplified', [ 'languages' => 'zh', 'font_keys' => 'notosanssc' ] );
		$this->seed_pack( 'chinese-traditional', [ 'languages' => 'zh-tw,zh-hk', 'font_keys' => 'notosanstc' ] );
		$this->seed_pack( 'arabic', [ 'languages' => 'ar,fa,ur', 'font_keys' => 'xbriyaz' ] );
	}

	/**
	 * @return string[] The entry ids a request list names, the always pack last
	 */
	protected function requested( array $requests ): array {
		return array_column( $requests, 'entry' );
	}

	public function test_a_regional_chinese_locale_installs_traditional_rather_than_the_bare_language() {
		$this->seed_catalogue();

		$this->assertSame(
			[ 'packs/chinese-traditional', 'packs/emoji' ],
			$this->requested( $this->resolver->for_locales( [ 'zh_TW' ] ) )
		);
	}

	public function test_a_locale_with_no_exact_row_falls_back_to_its_primary_subtag() {
		$this->seed_catalogue();

		$this->assertSame(
			[ 'packs/chinese-simplified', 'packs/emoji' ],
			$this->requested( $this->resolver->for_locales( [ 'zh_CN' ] ) )
		);
	}

	public function test_every_installed_translation_is_asked_for_at_once() {
		$this->seed_catalogue();

		$this->assertSame(
			[ 'packs/japanese', 'packs/arabic', 'packs/emoji' ],
			$this->requested( $this->resolver->for_locales( [ 'ja', 'en_US', 'ar' ] ) )
		);
	}

	public function test_two_locales_claiming_one_pack_ask_for_it_once() {
		$this->seed_catalogue();

		$this->assertSame(
			[ 'packs/arabic', 'packs/emoji' ],
			$this->requested( $this->resolver->for_locales( [ 'ar', 'fa_IR' ] ) )
		);
	}

	public function test_a_request_names_the_entry_and_leaves_its_files_to_the_queue() {
		$this->seed_pack( 'arabic', [ 'languages' => 'ar' ] );

		$this->assertSame( [ [ 'entry' => 'packs/arabic' ] ], $this->resolver->for_locales( [ 'ar' ] ) );
	}

	public function test_an_always_pack_the_admin_removed_is_not_asked_for_again() {
		$this->seed_catalogue();
		$this->catalog_repository()->mark_removed( 'packs', 'emoji' );

		$this->assertSame( [ 'packs/japanese' ], $this->requested( $this->resolver->for_locales( [ 'ja' ] ) ) );
	}

	public function test_a_script_subtag_row_is_reached_before_the_bare_language() {
		$this->seed_pack( 'emoji', [ 'always' => 1 ] );
		$this->seed_pack( 'chinese-simplified', [ 'languages' => 'zh' ] );
		$this->seed_pack( 'traditional-by-script', [ 'languages' => 'und-hant' ] );

		$this->assertSame(
			[ 'packs/traditional-by-script', 'packs/emoji' ],
			$this->requested( $this->resolver->for_locales( [ 'zh_Hant' ] ) )
		);
	}

	public function test_saving_a_pack_font_on_a_pdf_installs_the_pack_that_owns_it() {
		$this->seed_catalogue();

		$this->assertSame(
			[ 'packs/chinese-traditional', 'packs/emoji' ],
			$this->requested( $this->resolver->for_settings( [ 'font' => 'notosanstc' ] ) )
		);
	}

	public function test_saving_a_pdf_language_installs_the_pack_covering_it() {
		$this->seed_catalogue();

		$this->assertSame(
			[ 'packs/arabic', 'packs/emoji' ],
			$this->requested( $this->resolver->for_settings( [ 'pdf_language' => 'ar' ] ) )
		);
	}

	public function test_saving_a_pdf_that_needs_nothing_still_answers_the_always_pack() {
		$this->seed_catalogue();

		$this->assertSame(
			[ 'packs/emoji' ],
			$this->requested( $this->resolver->for_settings( [ 'font' => 'gfpdf-arimo', 'pdf_language' => 'en' ] ) )
		);
	}

	/**
	 * `default_pdf_language` has no trigger of its own, so the PDF save is what notices a change to it
	 */
	public function test_a_pdf_with_no_language_of_its_own_asks_for_the_sites() {
		$this->seed_catalogue();
		GPDFAPI::get_options_class()->update_option( 'default_pdf_language', 'ar' );

		$this->assertSame(
			[ 'packs/arabic', 'packs/emoji' ],
			$this->requested( $this->resolver->for_settings( [ 'font' => 'gfpdf-arimo' ] ) )
		);
	}

	public function test_a_detected_script_installs_the_pack_claiming_it() {
		$this->seed_pack( 'emoji', [ 'always' => 1 ] );
		$this->seed_pack( 'japanese', [ 'scripts' => 'ja', 'font_keys' => 'notosansjp' ] );
		$this->seed_pack( 'arabic', [ 'scripts' => 'und-arab', 'font_keys' => 'xbriyaz' ] );

		$this->assertSame(
			[ 'packs/japanese', 'packs/emoji' ],
			$this->requested( $this->resolver->for_scripts( [ 'ja' ] ) )
		);
	}

	/**
	 * The reason is what lets the queue take the Regular faces inline; without it a request means "all of it,
	 * in the background", which is every other trigger
	 */
	public function test_a_script_request_says_which_scripts_matched_and_the_always_pack_does_not() {
		$this->seed_pack( 'emoji', [ 'always' => 1 ] );
		$this->seed_pack( 'japanese', [ 'scripts' => 'ja,und-hani' ] );

		$this->assertSame(
			[
				[
					'entry'   => 'packs/japanese',
					'scripts' => [ 'ja' ],
				],
				[ 'entry' => 'packs/emoji' ],
			],
			$this->resolver->for_scripts( [ 'ja' ] )
		);
	}

	public function test_a_script_nothing_claims_asks_for_nothing_but_the_always_pack() {
		$this->seed_catalogue();

		$this->assertSame( [ 'packs/emoji' ], $this->requested( $this->resolver->for_scripts( [ 'und-brai' ] ) ) );
	}

	public function test_the_scripts_still_worth_looking_for_are_the_ones_no_installed_font_covers() {
		$this->seed_pack( 'japanese', [ 'scripts' => 'ja', 'font_keys' => 'notosansjp' ] );
		$this->seed_pack( 'arabic', [ 'scripts' => 'und-arab', 'font_keys' => 'xbriyaz' ] );

		$this->assertSame(
			[ 'und-arab' => true, 'ja' => true ],
			$this->resolver->uninstalled_scripts()
		);

		$this->install_font_row( 'notosansjp' );

		$this->assertSame( [ 'und-arab' => true ], $this->resolver->uninstalled_scripts() );
	}

	/**
	 * A pack short of one of its keys still has files to fetch, so its scripts stay on the list
	 */
	public function test_a_partly_installed_pack_is_still_worth_looking_for() {
		$this->seed_pack( 'cjk', [ 'scripts' => 'ja,ko', 'font_keys' => 'notosansjp,notosanskr' ] );
		$this->install_font_row( 'notosansjp' );

		$this->assertSame( [ 'ja' => true, 'ko' => true ], $this->resolver->uninstalled_scripts() );
	}

	public function test_a_removed_pack_is_not_worth_looking_for() {
		$this->seed_pack( 'emoji', [ 'always' => 1, 'scripts' => 'und-zsye' ] );
		$this->catalog_repository()->mark_removed( 'packs', 'emoji' );

		$this->assertSame( [], $this->resolver->uninstalled_scripts() );
	}

	/**
	 * A font the 6.x upgrade adopted, routed the way `Legacy_Font_Adopter` routes one
	 */
	protected function adopt_legacy( string $font_key, array $languages ): void {
		$this->install_font_row( $font_key, [ 'source' => 'imported', 'meta' => [ 'legacy' => true, 'languages' => $languages ] ] );
	}

	public function test_a_script_a_legacy_font_answers_is_not_a_gap() {
		$this->seed_catalogue();
		$this->seed_pack( 'korean', [ 'languages' => 'ko', 'scripts' => 'ko', 'font_keys' => 'notosanskr' ] );
		$this->adopt_legacy( 'unbatang', [ 'ko', 'kor' ] );

		$this->assertSame( [ 'packs/emoji' ], $this->requested( $this->resolver->for_scripts( [ 'ko' ] ) ) );
	}

	public function test_a_locale_a_legacy_font_answers_downloads_nothing() {
		$this->seed_catalogue();
		$this->seed_pack( 'korean', [ 'languages' => 'ko', 'font_keys' => 'notosanskr' ] );
		$this->adopt_legacy( 'unbatang', [ 'ko', 'kor' ] );

		$this->assertSame( [ 'packs/emoji' ], $this->requested( $this->resolver->for_locales( [ 'ko_KR' ] ) ) );
	}

	public function test_only_a_legacy_row_counts_as_coverage() {
		$this->seed_catalogue();
		$this->seed_pack( 'korean', [ 'languages' => 'ko', 'font_keys' => 'notosanskr' ] );

		/* A generic pack answering `ko` must not stop the Korean pack arriving to outrank it */
		$this->install_font_row( 'open-sans', [ 'meta' => [ 'languages' => [ 'ko' ], 'generic' => true ] ] );

		$this->assertContains( 'packs/korean', $this->requested( $this->resolver->for_locales( [ 'ko_KR' ] ) ) );
	}

	public function test_a_rung_more_specific_than_the_legacy_answer_still_installs() {
		$this->seed_catalogue();
		$this->adopt_legacy( 'sun-exta', [ 'zh' ] );

		/* 6.x answered bare `zh` and nothing narrower, so Traditional is still a gap the ladder reaches first */
		$this->assertContains( 'packs/chinese-traditional', $this->requested( $this->resolver->for_locales( [ 'zh_TW' ] ) ) );
		$this->assertNotContains( 'packs/chinese-simplified', $this->requested( $this->resolver->for_locales( [ 'zh_CN' ] ) ) );
	}

	public function test_a_script_a_legacy_font_answers_is_not_worth_looking_for() {
		$this->seed_pack( 'korean', [ 'languages' => 'ko', 'scripts' => 'ko', 'font_keys' => 'notosanskr' ] );
		$this->adopt_legacy( 'unbatang', [ 'ko', 'kor' ] );

		$this->assertSame( [], $this->resolver->uninstalled_scripts() );
	}

	public function test_deleting_the_legacy_font_makes_its_scripts_a_gap_again() {
		$this->seed_pack( 'korean', [ 'languages' => 'ko', 'scripts' => 'ko', 'font_keys' => 'notosanskr' ] );
		$this->adopt_legacy( 'unbatang', [ 'ko', 'kor' ] );

		$this->font_repository()->delete( 'unbatang' );

		$this->assertSame( [ 'ko' => true ], $this->resolver->uninstalled_scripts() );
	}

	public function test_the_pack_a_legacy_font_stands_in_for_is_named_once_it_is_the_only_route() {
		$this->seed_pack( 'korean', [ 'languages' => 'ko', 'scripts' => 'ko', 'font_keys' => 'notosanskr' ] );
		$this->adopt_legacy( 'unbatang', [ 'ko', 'kor' ] );

		$covered = $this->resolver->legacy_covered_entries();

		$this->assertCount( 1, $covered );
		$this->assertSame( 'korean', $covered[0]['entry'] );
		$this->assertSame( [ 'ko' ], $covered[0]['legacy_tags'] );
		$this->assertSame( [ 'unbatang' ], $covered[0]['legacy_fonts'] );
	}

	public function test_a_pack_that_is_already_installed_is_not_stood_in_for() {
		$this->seed_pack( 'korean', [ 'languages' => 'ko', 'scripts' => 'ko', 'font_keys' => 'notosanskr' ] );
		$this->adopt_legacy( 'unbatang', [ 'ko', 'kor' ] );
		$this->install_entry_row( 'notosanskr', 'korean' );

		$this->assertSame( [], $this->resolver->legacy_covered_entries() );
	}

	public function test_an_entry_outside_the_coverage_set_is_never_asked_for() {
		$this->seed_pack( 'popular-sans', [ 'coverage' => 0, 'font_keys' => 'roboto', 'languages' => 'en' ] );

		$this->assertSame( [], $this->resolver->for_settings( [ 'font' => 'roboto' ] ) );
		$this->assertSame( [], $this->resolver->for_locales( [ 'en_US' ] ) );
	}
}
