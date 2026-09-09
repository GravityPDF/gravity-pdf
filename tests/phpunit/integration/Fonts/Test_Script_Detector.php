<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * What a document says it needs
 *
 * The tags asserted here are mPDF's, not ours, so the surprising ones are pinned rather than corrected: both kana
 * give `ja`, and Han on its own gives `und-Hans` whatever the characters are.
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Script_Detector extends TestCase {

	use HasCatalogRows;
	use HasFontRows;

	/**
	 * @var Script_Detector
	 */
	public $detector;

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$gfpdf->get_font_repository()->ensure_ready();
		$this->drop_catalog_rows();

		$this->detector = $gfpdf->get_script_detector();
	}

	public function tear_down(): void {
		$this->drop_catalog_rows();
		$this->remove_font_rows();

		parent::tear_down();
	}

	/**
	 * A catalogue claiming every tag the table asks about, so a miss is the detector's and not the fixture's
	 */
	protected function seed_catalogue(): void {
		$this->insert_catalog_row(
			'packs',
			'everything',
			[
				'coverage'  => 1,
				'font_keys' => 'notinstalled',
				'scripts'   => 'ja,ko,und-hans,und-arab,th,he,hi,und-zsye,chr',
			]
		);
	}

	/**
	 * Hebrew is in the table because Arimo carries it: the pack claims `he`, and the gate still refuses to look
	 */
	public function provider_scripts(): array {
		return [
			'kana resolve as Japanese'            => [ 'ひらがな', [ 'ja' ] ],
			'katakana resolve as Japanese too'    => [ 'カタカナ', [ 'ja' ] ],
			'Han alone resolves as Simplified'    => [ '漢字', [ 'und-hans' ] ],
			'Hangul resolves as Korean'           => [ '한국어', [ 'ko' ] ],
			'Arabic'                              => [ 'مرحبا', [ 'und-arab' ] ],
			'Thai'                                => [ 'สวัสดี', [ 'th' ] ],
			'Hebrew is in Arimo already'          => [ 'שלום', [] ],
			'Devanagari'                          => [ 'नमस्ते', [ 'hi' ] ],
			'Cherokee keeps its language code'    => [ 'ᏣᎳᎩ', [ 'chr' ] ],
			'Latin is covered and never detected' => [ 'Hello world', [] ],
			'Greek and Cyrillic too'              => [ 'Καλημέρα Привет', [] ],
			'accented Latin too'                  => [ 'Ünïcödé façade', [] ],
			'whitespace is not a script'          => [ "one\ntwo\tthree ", [] ],
		];
	}

	/**
	 * @dataProvider provider_scripts
	 */
	public function test_a_documents_scripts( string $text, array $expected ) {
		$this->seed_catalogue();

		$this->assertSame( $expected, $this->detector->detect( $text ) );
	}

	public function test_a_mixed_document_names_each_script_once() {
		$this->seed_catalogue();

		$this->assertSame(
			[ 'ja', 'und-hans' ],
			$this->detector->detect( 'Hello', 'ひらがな です', '漢字', 'かな' )
		);
	}

	/**
	 * The whole point of the gate: a site with nothing left to install must not walk a document's code points
	 */
	public function test_a_script_the_site_already_has_a_font_for_is_not_reported() {
		$this->insert_catalog_row(
			'packs',
			'japanese',
			[
				'coverage'  => 1,
				'font_keys' => 'notosansjp',
				'scripts'   => 'ja',
			]
		);

		$this->assertSame( [ 'ja' ], $this->detector->detect( 'ひらがな' ) );

		$this->install_font_row( 'notosansjp' );

		$this->assertSame( [], $this->detector->detect( 'ひらがな' ) );
	}

	public function test_a_script_no_catalogue_entry_claims_is_not_reported() {
		$this->insert_catalog_row(
			'packs',
			'japanese',
			[
				'coverage'  => 1,
				'font_keys' => 'notosansjp',
				'scripts'   => 'ja',
			]
		);

		$this->assertSame( [], $this->detector->detect( 'مرحبا' ) );
	}

	/**
	 * `preg_match_all` fails rather than matches on a broken subject, and a document nobody can read needs nothing
	 */
	public function test_text_that_is_not_utf8_asks_for_nothing() {
		$this->seed_catalogue();

		$this->assertSame( [], $this->detector->detect( "\xC3\x28" ) );
	}

	/**
	 * The pre-render scan runs on every submission, so the gate has to stay affordable on a large Latin document
	 */
	public function test_a_two_hundred_field_latin_entry_costs_under_two_milliseconds() {
		$this->seed_catalogue();

		$values = array_fill( 0, 200, 'The quick brown fox jumps over the lazy dog, 0123456789.' );

		$start = microtime( true );
		$this->detector->detect( ...$values );
		$elapsed = ( microtime( true ) - $start ) * 1000;

		$this->assertLessThan( 2, $elapsed );
	}
}
