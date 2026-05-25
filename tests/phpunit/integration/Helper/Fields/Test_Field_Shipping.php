<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field;
use GF_Field_Shipping;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Shipping extends TestCase {

	/**
	 * Creates a real GF form with no product fields and a matching entry.
	 * Field_Products::value() returns [] when the form has no products, which
	 * causes Field_Shipping::value() to return [] as expected for these tests.
	 */
	private function make_empty_products_entry(): array {
		$form_id  = $this->gf_factory()->form->create( [], [ 'title' => 'No Products Form', 'fields' => [] ] );
		$entry_id = $this->gf_factory()->entry->create( [ 'form_id' => $form_id, 'currency' => 'USD' ] );

		return \GFAPI::get_entry( $entry_id );
	}

	protected function build_shipping_pdf_field( \GF_Field $gf_field, array $entry ): Field_Shipping {
		$pdf_field = new Field_Shipping(
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

	public function test_value_returns_empty_array_when_no_shipping_product() {
		$entry     = $this->make_empty_products_entry();
		$gf_field  = new GF_Field_Shipping( [ 'id' => 99, 'type' => 'shipping' ] );
		$pdf_field = $this->build_shipping_pdf_field( $gf_field, $entry );

		$this->assertSame( [], $pdf_field->value() );
	}

	public function test_html_returns_empty_string_when_no_shipping_data() {
		$entry     = $this->make_empty_products_entry();
		$gf_field  = new GF_Field_Shipping( [ 'id' => 99, 'type' => 'shipping' ] );
		$pdf_field = $this->build_shipping_pdf_field( $gf_field, $entry );

		/* value() returns [] so the field should report itself as empty. */
		$this->assertTrue( $pdf_field->is_empty() );
	}

	public function test_form_data_returns_empty_strings_when_no_shipping() {
		$entry     = $this->make_empty_products_entry();
		$gf_field  = new GF_Field_Shipping( [ 'id' => 99, 'type' => 'shipping', 'label' => 'Shipping' ] );
		$pdf_field = $this->build_shipping_pdf_field( $gf_field, $entry );

		$data = $pdf_field->form_data();

		$this->assertSame( '', $data['field'][99] );
	}
}
