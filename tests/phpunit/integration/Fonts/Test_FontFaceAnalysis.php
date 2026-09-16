<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_FontFaceAnalysis
 *
 * Covers what a font file says about itself, and the two upload questions that answers.
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_FontFaceAnalysis extends TestCase {

	use HasFontRows;

	public function tear_down(): void {
		$this->remove_font_files();

		parent::tear_down();
	}

	/**
	 * Copy one of the bundled faces into the fonts directory under the name a slot would have given it
	 */
	protected function place( string $bundled, string $as ): array {
		copy( PDF_PLUGIN_DIR . 'fonts/' . $bundled, $this->font_dir() . $as );

		return [ 'name' => $as ];
	}

	protected function analysis(): FontFaceAnalysis {
		return new FontFaceAnalysis( $this->font_dir() );
	}

	public function test_a_face_reports_its_family_and_style() {
		$this->place( 'Arimo-BoldItalic.ttf', 'x-bi.ttf' );

		$face = $this->analysis()->analyse( 'x-bi.ttf' );

		$this->assertSame( 'Arimo', $face['family'] );
		$this->assertTrue( $face['bold'] );
		$this->assertTrue( $face['italic'] );
	}

	public function test_a_file_that_will_not_parse_is_not_this_class_to_report() {
		file_put_contents( $this->font_dir() . 'junk.ttf', 'not a font' );

		/* `TtfFontValidation` refuses the upload; advice about a file that never arrived would be noise */
		$this->assertNull( $this->analysis()->analyse( 'junk.ttf' ) );
	}

	public function test_four_faces_in_their_own_slots_earn_no_warning() {
		$files = [
			'regular'     => $this->place( 'Arimo-Regular.ttf', 'a-r.ttf' ),
			'italics'     => $this->place( 'Arimo-Italic.ttf', 'a-i.ttf' ),
			'bold'        => $this->place( 'Arimo-Bold.ttf', 'a-b.ttf' ),
			'bolditalics' => $this->place( 'Arimo-BoldItalic.ttf', 'a-bi.ttf' ),
		];

		$this->assertSame( [], $this->analysis()->warnings( $files ) );
	}

	/**
	 * Nothing stops the bold file going into the italic slot, and nothing about the PDF says so afterwards: it
	 * renders upright where italics were asked for. The style bits are the only thing that can tell an
	 * administrator before they find it in a document.
	 */
	public function test_a_face_in_the_wrong_slot_is_called_out() {
		$files = [
			'regular' => $this->place( 'Arimo-Regular.ttf', 'a-r.ttf' ),
			'italics' => $this->place( 'Arimo-Bold.ttf', 'wrong.ttf' ),
		];

		$warnings = $this->analysis()->warnings( $files );

		$this->assertCount( 1, $warnings );
		$this->assertStringContainsString( 'wrong.ttf', $warnings[0] );
		$this->assertStringContainsString( 'Italic', $warnings[0] );
		$this->assertStringContainsString( 'Bold', $warnings[0] );
	}

	public function test_faces_from_two_families_are_called_out() {
		$files = [
			'regular' => $this->place( 'Arimo-Regular.ttf', 'a-r.ttf' ),
			'bold'    => $this->place( 'DejaVuSansSymbols.ttf', 'd-b.ttf' ),
		];

		$warnings = $this->analysis()->warnings( $files );

		/* Two things are wrong with that pair, and both are worth saying */
		$this->assertStringContainsString( 'more than one font family', implode( "\n", $warnings ) );
		$this->assertStringContainsString( 'Arimo', implode( "\n", $warnings ) );
		$this->assertStringContainsString( 'DejaVu Sans', implode( "\n", $warnings ) );
	}

	/**
	 * Kashida is Arabic justification. Every OTL font used to get it, which on a font with no RTL coverage is a
	 * setting that can never fire — `Otl::shapeArabic()` writes the markers `GetJspacing()` needs, and nothing
	 * else does. Across the packs installed here the gate drops it from 57 of 66.
	 *
	 * The flag is coarse, and Arimo is the proof: a Latin UI face answering true because its cmap reaches into
	 * Hebrew. Harmless either way, for the reason `has_rtl()` records, so this asserts the aggregation and not a
	 * precision the flag does not claim.
	 */
	public function test_rtl_is_what_separates_a_font_kashida_is_for() {
		$none = [ 'regular' => $this->place( 'DejaVuSansSymbols.ttf', 'symbols.ttf' ) ];

		$this->assertFalse( $this->analysis()->has_rtl( $none ) );

		$covers = [ 'regular' => $this->place( 'Arimo-Regular.ttf', 'a-r.ttf' ) ];

		$this->assertTrue( $this->analysis()->has_rtl( $covers ) );

		/* One face is enough: the family is registered as a unit and Kashida is set on the row, not the face */
		$this->assertTrue( $this->analysis()->has_rtl( $none + [ 'bold' => $covers['regular'] ] ) );
	}
}
