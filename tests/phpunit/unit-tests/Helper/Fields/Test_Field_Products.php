<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use WP_UnitTestCase;
use GPDFAPI;
use GFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Products extends WP_UnitTestCase {

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

	public function set_up() {
		parent::set_up();

		$this->form  = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$this->entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		$form_id                = GFAPI::add_form( $this->form );
		$this->entry['form_id'] = $form_id;
		$entry_id               = GFAPI::add_entry( $this->entry );
		$this->pdf_field        = new Field_Products( new \GF_Field_Product(), GFAPI::get_entry( $entry_id ), GPDFAPI::get_form_class(), GPDFAPI::get_misc_class() );
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


}
