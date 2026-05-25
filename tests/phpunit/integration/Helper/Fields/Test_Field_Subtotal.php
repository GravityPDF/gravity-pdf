<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Subtotal extends TestCase {

	protected function build_subtotal_pdf_field( array $field_data, array $entry ): Field_Subtotal {
		$gf_field  = new GF_Field( $field_data );
		$pdf_field = new Field_Subtotal(
			$gf_field,
			$entry,
			\GPDFAPI::get_form_class(),
			\GPDFAPI::get_misc_class()
		);
		$pdf_field->set_products(
			new Field_Products( new GF_Field(), $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() )
		);

		return $pdf_field;
	}

	public function test_value_returns_array_with_total_and_formatted_keys() {
		$entry     = [ 'id' => 0, 'form_id' => 0, 'currency' => 'USD' ];
		$pdf_field = $this->build_subtotal_pdf_field( [ 'id' => 1, 'type' => 'subtotal' ], $entry );

		$value = $pdf_field->value();

		$this->assertArrayHasKey( 'total', $value );
		$this->assertArrayHasKey( 'total_formatted', $value );
	}

	public function test_html_contains_formatted_total() {
		$entry     = [ 'id' => 0, 'form_id' => 0, 'currency' => 'USD', '1' => '25.00' ];
		$pdf_field = $this->build_subtotal_pdf_field( [ 'id' => 1, 'type' => 'subtotal' ], $entry );

		$html = $pdf_field->html();

		$this->assertStringContainsString( '$25.00', $html );
	}

	public function test_is_empty_returns_true_when_field_lacks_get_subtotal_method() {
		$entry     = [ 'id' => 0, 'form_id' => 0, 'currency' => 'USD' ];
		$pdf_field = $this->build_subtotal_pdf_field( [ 'id' => 1, 'type' => 'subtotal' ], $entry );

		$this->assertTrue( $pdf_field->is_empty() );
	}
}
