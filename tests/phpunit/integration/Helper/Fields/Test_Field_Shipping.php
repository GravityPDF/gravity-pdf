<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field;
use GF_Field_Shipping;
use GFPDF\Tests\Integration\TestCase;


/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Shipping extends TestCase {

	/* Defaulted to 0 so tear_down_after_class is safe on early set_up_before_class failure. */
	private static int $empty_form_id  = 0;
	private static int $empty_entry_id = 0;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		/* Field_Products::value() returns [] for a form with no products, which is what Field_Shipping tests need. */
		$factory              = new \GF_UnitTest_Factory();
		self::$empty_form_id  = $factory->form->create( [], [ 'title' => 'No Products Form', 'fields' => [] ] );
		self::$empty_entry_id = $factory->entry->create( [ 'form_id' => self::$empty_form_id, 'currency' => 'USD' ] );
	}

	public static function tear_down_after_class(): void {
		if ( self::$empty_entry_id ) {
			\GFAPI::delete_entry( self::$empty_entry_id );
		}
		if ( self::$empty_form_id ) {
			\GFAPI::delete_form( self::$empty_form_id );
		}

		parent::tear_down_after_class();
	}

	private function make_empty_products_entry(): array {
		return \GFAPI::get_entry( self::$empty_entry_id );
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
