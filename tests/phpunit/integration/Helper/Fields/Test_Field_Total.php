<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field;
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
class Test_Field_Total extends TestCase {

	/**
	 * Creates a real GF form with no product fields and a matching entry.
	 * Field_Products::value() returns [] when the form has no products, which
	 * causes Field_Total::value() to return [] as expected for the empty tests.
	 */
	private function make_empty_products_entry(): array {
		$form_id  = $this->gf_factory()->form->create( [], [ 'title' => 'No Products Form', 'fields' => [] ] );
		$entry_id = $this->gf_factory()->entry->create( [ 'form_id' => $form_id, 'currency' => 'USD' ] );

		return \GFAPI::get_entry( $entry_id );
	}

	protected function build_total_pdf_field( array $entry ): Field_Total {
		$gf_field  = new GF_Field( [ 'id' => 40, 'type' => 'total', 'label' => 'Total' ] );
		$pdf_field = new Field_Total(
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

	public function test_value_returns_empty_array_when_no_products_in_entry() {
		$entry     = $this->make_empty_products_entry();
		$pdf_field = $this->build_total_pdf_field( $entry );

		$this->assertSame( [], $pdf_field->value() );
	}

	public function test_html_is_empty_string_when_no_products() {
		$entry     = $this->make_empty_products_entry();
		$pdf_field = $this->build_total_pdf_field( $entry );

		/* value() returns [] so the field should report itself as empty. */
		$this->assertTrue( $pdf_field->is_empty() );
	}

	public function test_value_contains_total_and_total_formatted_with_products() {
		/* Create a form+entry from the non-group-products-form fixture so real products exist. */
		$form_data             = $this->form( 'non-group-products-form' );
		$form_id               = $this->gf_factory()->form->create( [], $form_data );
		$entry_data            = $GLOBALS['GFPDF_Test']->entries['non-group-products-form'][0];
		$entry_data['form_id'] = $form_id;
		$entry_id              = $this->gf_factory()->entry->create( $entry_data );
		$entry                 = \GFAPI::get_entry( $entry_id );

		$pdf_field = $this->build_total_pdf_field( $entry );

		$value = $pdf_field->value();

		$this->assertArrayHasKey( 'total', $value );
		$this->assertArrayHasKey( 'total_formatted', $value );
		$this->assertGreaterThan( 0, $value['total'] );
		$this->assertStringContainsString( '$', $value['total_formatted'] );
	}
}
