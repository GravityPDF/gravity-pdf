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

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'non-group-products-form' ], [ 'non-group-products-form' ] );
	}


	/**
	 * Returns the live fixture entry for non-group-products-form. The fixture
	 * form + entry are already in the DB (created in set_up_before_class via
	 * load_fixtures()), so the entry is suitable for Field_Products::value()
	 * which needs a real form_id to call GFCommon::get_product_fields().
	 */
	private function make_real_entry(): array {
		return $this->entry( 'non-group-products-form' );
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

	/**
	 * Returns the public-property config for the quantity field with id $field_id
	 * in the non-group-products-form fixture. Extracts properties explicitly because
	 * casting a GF_Field to array exposes null-byte-prefixed private property names.
	 */
	private function quantity_field_config( int $field_id ): array {
		foreach ( $this->form( 'non-group-products-form' )['fields'] as $field ) {
			if ( $field->type === 'quantity' && (int) $field->id === $field_id ) {
				return [
					'id'           => $field->id,
					'type'         => 'quantity',
					'productField' => $field->productField,
				];
			}
		}

		$this->fail( "Quantity field {$field_id} not found in non-group-products-form fixture" );
	}

	public function test_value_returns_quantity_for_linked_product() {
		$entry            = $this->make_real_entry();
		$qty_field_config = $this->quantity_field_config( 6 );

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
		$entry            = $this->make_real_entry();
		$qty_field_config = $this->quantity_field_config( 6 );

		$pdf_field = $this->build_quantity_pdf_field( $qty_field_config, $entry );

		$this->assertStringContainsString( '42', $pdf_field->html() );
	}
}
