<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Products extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}


	/**
	 * @var array
	 */
	public $form;

	/**
	 * @var Field_Product
	 */
	public $pdf_field;

	/**
	 * @var array
	 */
	public $entry;

	public function set_up(): void {
		parent::set_up();

		$this->form      = $this->form( 'all-form-fields' );
		$this->entry     = $this->entry( 'all-form-fields' );
		$this->pdf_field = new Field_Products( new \GF_Field_Product(), $this->entry, GPDFAPI::get_form_class(), GPDFAPI::get_misc_class() );
	}

	public function test_value() {
		$value = $this->pdf_field->value();

		$this->assertNotEmpty( $value['products'] );
		$this->assertArrayHasKey( 'name', $value['products'][34] );
		$this->assertArrayHasKey( 'price', $value['products'][34] );
		$this->assertArrayHasKey( 'price_unformatted', $value['products'][34] );
		$this->assertArrayHasKey( 'options', $value['products'][34] );
		$this->assertArrayHasKey( 'quantity', $value['products'][34] );
		$this->assertArrayHasKey( 'subtotal', $value['products'][34] );
		$this->assertArrayHasKey( 'subtotal_formatted', $value['products'][34] );
	}

	public function test_html() {
		$html = $this->pdf_field->html();

		$this->assertStringContainsString( '<li>Product Options for Basic Product: Option 2</li>', $html );
		$this->assertStringContainsString( 'Calculation Price', $html );
		$this->assertStringContainsString( '<li>Option for Calculation Price: Cal - Option 1</li>', $html );
		$this->assertStringContainsString( '<td class="grandtotal_amount totals">$860.25</td>', $html );
	}

	public function test_labels_in_html() {
		$products = \GFCommon::get_product_fields( $this->form, $this->entry );
		$products['products'][34]['name'] = '<em>Product Basic</em>';
		$products['products'][34]['options'][0]['option_label'] = '<img src="#"> Option 2';

		$use_choice_text = true;
		$use_admin_label = false;
		gform_update_meta( $this->pdf_field->entry['id'], "gform_product_info_{$use_choice_text}_{$use_admin_label}", $products, $this->form['id'] );

		$html = $this->pdf_field->html();

		$this->assertStringContainsString( '<em>Product Basic</em>', $html );
		$this->assertStringContainsString( '<li><img src="#"> Option 2</li>', $html );
		$this->assertStringContainsString( 'Calculation Price', $html );
		$this->assertStringContainsString( '<li>Option for Calculation Price: Cal - Option 1</li>', $html );
		$this->assertStringContainsString( '<td class="grandtotal_amount totals">$860.25</td>', $html );
	}

}
