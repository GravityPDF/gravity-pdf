<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Product extends WP_UnitTestCase {

	/**
	 * @var array
	 */
	public $form;

	/**
	 * @var array
	 */
	public $entry_id;

	/**
	 * @var array
	 */
	public $entry;

	/**
	 * @var Field_Product
	 */
	public $pdf_field;

	public function set_up() {
		$this->form = $GLOBALS['GFPDF_Test']->form['non-group-products-form'];
		$entry      = $GLOBALS['GFPDF_Test']->entries['non-group-products-form'][0];

		$form_id    = \GFAPI::add_form( $this->form );
		$entry['form_id'] = $form_id;
		$this->entry_id   = \GFAPI::add_entry( $entry );
		$this->entry      = $entry;

		parent::set_up();
	}

	public function test_grouped_default_html() {
		$pdf_field = $this->set_products( $this->form['fields'][0] );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'class="gfpdf-field gfpdf-singleproduct ">', $html );
		$this->assertStringContainsString( '<div class="value">$1.00 x 50 = $50.00</div>', $html );
	}

	public function test_grouped_disabled_qty_html() {
		$pdf_field = $this->set_products( $this->form['fields'][1] );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'class="gfpdf-field gfpdf-singleproduct ">', $html );
		$this->assertStringContainsString( '<div class="value">$2.00 x 1</div>', $html );
	}

	public function test_grouped_linked_quantity_html() {
		$pdf_field = $this->set_products( $this->form['fields'][2] );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'class="gfpdf-field gfpdf-singleproduct ">', $html );
		$this->assertStringContainsString( '<div class="value">$3.00 x 42 = $126.00</div>', $html );
	}

	public function test_grouped_option_html() {
		$pdf_field = $this->set_products( $this->form['fields'][4] );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'class="gfpdf-field gfpdf-singleproduct ">', $html );
		$this->assertStringContainsString( '<div class="value">$4.00 x 32 = $128.00</div>', $html );
	}

	protected function set_products( $field ) {
		$pdf_field = new Field_Product( new \GF_Field_Product( $field ), \GFAPI::get_entry( $this->entry_id ), \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$pdf_field->set_products( new Field_Products( new \GF_Field(), $this->entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) );
		return $pdf_field;
	}

}
