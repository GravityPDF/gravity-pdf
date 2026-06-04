<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Product extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'non-group-products-form' ], [ 'non-group-products-form' ] );
	}


	/**
	 * @var array
	 */
	public $form;

	/**
	 * @var array
	 */
	public $entry;

	/**
	 * @var Field_Product
	 */
	public $pdf_field;

	public function set_up(): void {
		parent::set_up();

		$this->form  = $this->form( 'non-group-products-form' );
		$this->entry = $this->entry( 'non-group-products-form' );
	}

	public function test_grouped_default_html() {
		$pdf_field = $this->set_products( $this->form['fields'][0] );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'class="gfpdf-field gfpdf-singleproduct ', $html );
		$this->assertStringContainsString( '<div class="value">$1.00 x 50 = $50.00</div>', $html );
	}

	public function test_grouped_disabled_qty_html() {
		$pdf_field = $this->set_products( $this->form['fields'][1] );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'class="gfpdf-field gfpdf-singleproduct ', $html );
		$this->assertStringContainsString( '<div class="value">$2.00 x 1</div>', $html );
	}

	public function test_grouped_linked_quantity_html() {
		$pdf_field = $this->set_products( $this->form['fields'][2] );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'class="gfpdf-field gfpdf-singleproduct ', $html );
		$this->assertStringContainsString( '<div class="value">$3.00 x 42 = $126.00</div>', $html );
	}

	public function test_grouped_option_html() {
		$pdf_field = $this->set_products( $this->form['fields'][4] );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'class="gfpdf-field gfpdf-singleproduct ', $html );
		$this->assertStringContainsString( '<div class="value">$4.00 x 32 = $128.00</div>', $html );
	}

	protected function set_products( $field ) {
		$pdf_field = new Field_Product( new \GF_Field_Product( $field ), $this->entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$pdf_field->set_products( new Field_Products( new \GF_Field(), $this->entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) );
		return $pdf_field;
	}

}
