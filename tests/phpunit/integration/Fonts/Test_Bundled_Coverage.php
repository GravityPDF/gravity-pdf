<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * The generated coverage class against the fonts it was generated from
 *
 * `Script_Detector`'s gate is only as honest as this class: a stale one either hides real misses or fires on every
 * render. Nothing else notices, since a wrong class still compiles and still matches something.
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Bundled_Coverage extends TestCase {

	/**
	 * @var string
	 */
	public $tools;

	public function set_up(): void {
		parent::set_up();

		$this->tools = PDF_PLUGIN_DIR . 'tools/fonts/';

		require_once $this->tools . 'bundled-coverage.php';
	}

	public function test_the_committed_class_matches_the_bundled_fonts() {
		$ranges = gfpdf_bundled_coverage_ranges( PDF_PLUGIN_DIR . 'fonts' );

		$this->assertSame(
			gfpdf_bundled_coverage_class( $ranges ),
			file_get_contents( PDF_PLUGIN_DIR . 'src/Fonts/Bundled_Coverage.php' ),
			'Run `php tools/fonts/generate-bundled-coverage.php` — the bundled fonts have changed'
		);
	}

	/**
	 * The block-name approximation this class exists to avoid: Arimo carries neither of these
	 */
	public function test_the_gate_fires_on_arabic_presentation_forms_and_halfwidth_katakana() {
		$this->assertSame( 1, preg_match( Bundled_Coverage::UNCOVERED, "\u{FB50}" ) );
		$this->assertSame( 1, preg_match( Bundled_Coverage::UNCOVERED, "\u{FF76}" ) );
	}

	/**
	 * A PDF never draws these, so covering them is what keeps the gate quiet on an ordinary document
	 */
	public function test_the_gate_ignores_whitespace_and_formatting_characters() {
		$this->assertSame( 0, preg_match( Bundled_Coverage::UNCOVERED, "line\r\n\tone \u{00A0}\u{200B}\u{FEFF}" ) );
	}
}
