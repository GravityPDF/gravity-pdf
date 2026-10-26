<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Mpdf;

use GFPDF_Vendor\Mpdf\HTMLParserMode;
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

	protected function get_config(): array {
		return [
			'mode'    => 'c',
			'tempDir' => sys_get_temp_dir(),
		];
	}

	protected function get_pdf(): Mpdf {
		return new Mpdf( $this->get_config() );
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

	/**
	 * @since 6.18.0
	 */
	public function test_floats_buried_by_a_new_float_are_dropped() {
		$pdf = $this->get_pdf();
		$this->write_floated_rows( $pdf, 50 );

		$this->assertCount( 3, $pdf->floatDivs );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_dropping_buried_floats_leaves_the_pages_unchanged() {
		$pruned = $this->get_pdf();
		$this->write_floated_rows( $pruned, 50 );

		$unpruned = new class( $this->get_config() ) extends Mpdf {
			public function addFloatDiv( array $floatDiv ) {
				$this->floatDivs[] = $floatDiv;
			}
		};
		$this->write_floated_rows( $unpruned, 50 );

		$this->assertSame( $unpruned->page, $pruned->page );
		$this->assertSame( $unpruned->pages, $pruned->pages );
	}

	/**
	 * Writes cleared rows of three floated cells, one WriteHTML() call per row, as PDF for GravityView does
	 */
	private function write_floated_rows( Mpdf $pdf, int $rows ): void {
		$pdf->WriteHTML( '<div style="clear: both;">Head</div>', HTMLParserMode::HTML_BODY, true, false );

		for ( $i = 1; $i <= $rows; $i++ ) {
			$pdf->WriteHTML(
				sprintf(
					'<div style="clear: both;"><div style="float: left; width: 30%%;">Row %1$d, left</div><div style="float: left; width: 30%%;">Row %1$d, middle</div><div style="float: left; width: 30%%;">Row %1$d, right</div></div>',
					$i
				),
				HTMLParserMode::HTML_BODY,
				false,
				false
			);
		}

		$pdf->WriteHTML( '<div style="clear: both;">Foot</div>', HTMLParserMode::HTML_BODY, false, true );
	}

}
