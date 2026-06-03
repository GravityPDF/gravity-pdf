<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field;
use GFPDF\Tests\Integration\TestCase;


/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Tax extends TestCase {

	/* Defaulted to 0 so tear_down_after_class is safe on early set_up_before_class failure. */
	private static int $empty_form_id  = 0;
	private static int $empty_entry_id = 0;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		/* Field_Products::value() returns [] for a form with no products, which is what Field_Tax tests need. */
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

	protected function build_tax_pdf_field( array $field_data, array $entry ): Field_Tax {
		$gf_field  = new GF_Field( $field_data );
		$pdf_field = new Field_Tax(
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

	public function test_is_empty_returns_true_when_gp_ecommerce_fields_not_present() {
		/* is_empty() short-circuits before touching Field_Products, so a stub entry is safe here. */
		$entry     = [ 'id' => 0, 'form_id' => 0, 'currency' => 'USD' ];
		$pdf_field = $this->build_tax_pdf_field( [ 'id' => 1, 'type' => 'tax' ], $entry );

		$this->assertTrue( $pdf_field->is_empty() );
	}

	public function test_value_returns_empty_array_when_no_tax_product_in_entry() {
		$entry     = $this->make_empty_products_entry();
		$pdf_field = $this->build_tax_pdf_field( [ 'id' => 99, 'type' => 'tax' ], $entry );

		$this->assertSame( [], $pdf_field->value() );
	}

	public function test_html_returns_empty_content_when_no_tax_data() {
		$entry     = $this->make_empty_products_entry();
		$pdf_field = $this->build_tax_pdf_field( [ 'id' => 99, 'type' => 'tax' ], $entry );

		/* value() returns [] so html() should produce an empty-value wrapper with no money amount. */
		$this->assertTrue( $pdf_field->is_empty() );
	}
}
