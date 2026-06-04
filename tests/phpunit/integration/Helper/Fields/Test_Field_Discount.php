<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Discount extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'non-group-products-form' ], [ 'non-group-products-form' ] );
	}


	/**
	 * Builds a Field_Discount whose Field_Products companion uses the fixture
	 * entry already in the DB. Field id 99 is used so it has no matching
	 * product in the fixture → value() returns [].
	 */
	private function make_pdf_field_with_real_entry(): Field_Discount {
		$entry = $this->entry( 'non-group-products-form' );

		$gf_field       = new GF_Field();
		$gf_field->id   = 99;
		$gf_field->type = 'discount';

		$pdf_field = new Field_Discount( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$pdf_field->set_products( new Field_Products( new GF_Field(), $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) );

		return $pdf_field;
	}

	public function test_is_empty_when_gp_ecommerce_fields_absent() {
		$gf_field       = new GF_Field();
		$gf_field->id   = 1;
		$gf_field->type = 'discount';

		$entry     = [ 'id' => 0, 'form_id' => 0 ];
		$pdf_field = new Field_Discount( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$pdf_field->set_products( new Field_Products( new GF_Field(), $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) );

		/*
		 * GP_Ecommerce_Fields is not present in the test environment so
		 * Field_Discount::is_empty() short-circuits and returns true.
		 */
		$this->assertTrue( $pdf_field->is_empty() );
	}

	public function test_value_returns_empty_array_when_no_matching_product() {
		$pdf_field = $this->make_pdf_field_with_real_entry();
		$value     = $pdf_field->value();

		$this->assertIsArray( $value );
		$this->assertEmpty( $value );
	}

	public function test_form_data_returns_empty_strings_when_no_discount() {
		$pdf_field = $this->make_pdf_field_with_real_entry();
		$form_data = $pdf_field->form_data();

		$this->assertArrayHasKey( 'field', $form_data );

		foreach ( $form_data['field'] as $v ) {
			$this->assertSame( '', $v );
		}
	}
}
