<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Mpdf;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   mpdf
 */
class Test_Mpdf extends WP_UnitTestCase {

	protected function get_pdf(): Mpdf {
		return new Mpdf(
			[
				'mode'    => 'c',
				'tempDir' => sys_get_temp_dir(),
			]
		);
	}

	/**
	 * The call every pre-1.0.3 Business Plus template makes, which reached strtoupper() as an array and, on PHP 8,
	 * ended the request with a TypeError
	 *
	 * The size is still discarded, exactly as PHP 7 discarded it — the page keeps the size it already had. Only the
	 * fatal goes away.
	 */
	public function test_add_page_discards_an_array_second_argument_instead_of_fataling() {
		$pdf = $this->get_pdf();
		$pdf->AddPage( 'P', [ 200, 100 ] );

		$this->assertEqualsWithDelta( 210, $pdf->w, 0.01 );
		$this->assertEqualsWithDelta( 297, $pdf->h, 0.01 );
	}

	/**
	 * A template asking for a page of a source PDF it doesn't have used to reach mPDF's "Invalid page format"
	 * exception; discarding the array means it never gets there
	 */
	public function test_add_page_discards_an_array_of_nulls() {
		$pdf = $this->get_pdf();
		$pdf->AddPage( 'P', [ null, null ] );

		$this->assertEqualsWithDelta( 210, $pdf->w, 0.01 );
		$this->assertEqualsWithDelta( 297, $pdf->h, 0.01 );
	}

	/**
	 * Only the array is emptied, so a page size mPDF does accept still applies
	 */
	public function test_add_page_still_honours_an_explicit_page_size() {
		$pdf = $this->get_pdf();
		$pdf->AddPage( 'P', [ 200, 100 ], '', '', '', '', '', '', '', '', '', '', '', '', '', 0, 0, 0, 0, '', [ 300, 150 ] );

		$this->assertEqualsWithDelta( 300, $pdf->w, 0.01 );
		$this->assertEqualsWithDelta( 150, $pdf->h, 0.01 );
	}

	/**
	 * The parameter mPDF actually declares there still has to work
	 */
	public function test_add_page_leaves_a_condition_string_alone() {
		$pdf = $this->get_pdf();
		$pdf->AddPage( 'P', 'NEXT-ODD' );

		$this->assertEqualsWithDelta( 210, $pdf->w, 0.01 );
		$this->assertEqualsWithDelta( 297, $pdf->h, 0.01 );
	}

	public function test_add_page_defaults_to_the_document_page_size() {
		$pdf = $this->get_pdf();
		$pdf->AddPage();

		$this->assertEqualsWithDelta( 210, $pdf->w, 0.01 );
		$this->assertEqualsWithDelta( 297, $pdf->h, 0.01 );
	}

}
