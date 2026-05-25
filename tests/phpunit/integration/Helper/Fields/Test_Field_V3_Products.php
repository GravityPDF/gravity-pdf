<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_V3_Products extends TestCase {

	protected function build_v3_products( array $entry ): Field_V3_Products {
		return new Field_V3_Products(
			new GF_Field(),
			$entry,
			\GPDFAPI::get_form_class(),
			\GPDFAPI::get_misc_class()
		);
	}

	public function test_html_contains_order_heading() {
		$form  = $GLOBALS['GFPDF_Test']->form['non-group-products-form'];
		$entry = $GLOBALS['GFPDF_Test']->entries['non-group-products-form'][0];

		$form_id          = $this->gf_factory()->form->create( [], $form );
		$entry['form_id'] = $form_id;
		$entry_id         = $this->gf_factory()->entry->create( $entry );
		$live_entry       = \GFAPI::get_entry( $entry_id );

		$pdf_field = $this->build_v3_products( $live_entry );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'entry-view-section-break', $html );
		$this->assertStringContainsString( 'Order', $html );
	}

	public function test_html_contains_products_table_content() {
		$form  = $GLOBALS['GFPDF_Test']->form['non-group-products-form'];
		$entry = $GLOBALS['GFPDF_Test']->entries['non-group-products-form'][0];

		$form_id          = $this->gf_factory()->form->create( [], $form );
		$entry['form_id'] = $form_id;
		$entry_id         = $this->gf_factory()->entry->create( $entry );
		$live_entry       = \GFAPI::get_entry( $entry_id );

		$pdf_field = $this->build_v3_products( $live_entry );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'entry-products', $html );
	}
}
