<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field;
use GF_Field_Quantity;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Quantity extends TestCase {

	/**
	 * Creates a real GF form+entry in the DB from the non-group-products-form fixture
	 * and returns the live entry array. Field_Products requires a non-null form to
	 * call GFCommon::get_product_fields(), so tests must use a real form_id.
	 */
	private function make_real_entry(): array {
		$form_data             = $this->form( 'non-group-products-form' );
		$form_id               = $this->gf_factory()->form->create( [], $form_data );
		$entry_data            = $GLOBALS['GFPDF_Test']->entries['non-group-products-form'][0];
		$entry_data['form_id'] = $form_id;
		$entry_id              = $this->gf_factory()->entry->create( $entry_data );

		return \GFAPI::get_entry( $entry_id );
	}

	protected function build_quantity_pdf_field( array $quantity_field_data, array $entry ): Field_Quantity {
		$pdf_field = new Field_Quantity(
			new GF_Field_Quantity( $quantity_field_data ),
			$entry,
			\GPDFAPI::get_form_class(),
			\GPDFAPI::get_misc_class()
		);

		$pdf_field->set_products(
			new Field_Products( new GF_Field(), $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() )
		);

		return $pdf_field;
	}

	public function test_value_returns_quantity_for_linked_product() {
		$entry = $this->make_real_entry();
		$form  = $this->form( 'non-group-products-form' );

		$qty_field_config = null;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'quantity' && (int) $field->id === 6 ) {
				/* Extract public properties explicitly — casting a GF_Field to array
				 * exposes null-byte-prefixed private property names and causes errors. */
				$qty_field_config = [
					'id'           => $field->id,
					'type'         => 'quantity',
					'productField' => $field->productField,
				];
				break;
			}
		}

		$this->assertNotNull( $qty_field_config, 'Quantity field 6 not found in fixture' );

		$pdf_field = $this->build_quantity_pdf_field( $qty_field_config, $entry );

		$this->assertSame( '42', (string) $pdf_field->value() );
	}

	public function test_value_returns_empty_string_when_no_matching_product() {
		$entry = $this->make_real_entry();

		/*
		 * productField 9999 does not exist in the fixture form, so
		 * Field_Quantity::value() finds no matching product and returns ''.
		 * A real entry is still required so Field_Products can resolve the form.
		 */
		$qty_field_config = [
			'id'           => 99,
			'type'         => 'quantity',
			'productField' => 9999,
		];

		$pdf_field = $this->build_quantity_pdf_field( $qty_field_config, $entry );

		$this->assertSame( '', $pdf_field->value() );
	}

	public function test_html_contains_quantity_value() {
		$entry = $this->make_real_entry();
		$form  = $this->form( 'non-group-products-form' );

		$qty_field_config = null;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'quantity' && (int) $field->id === 6 ) {
				$qty_field_config = [
					'id'           => $field->id,
					'type'         => 'quantity',
					'productField' => $field->productField,
				];
				break;
			}
		}

		$this->assertNotNull( $qty_field_config, 'Quantity field 6 not found in fixture' );

		$pdf_field = $this->build_quantity_pdf_field( $qty_field_config, $entry );

		$this->assertStringContainsString( '42', $pdf_field->html() );
	}
}
