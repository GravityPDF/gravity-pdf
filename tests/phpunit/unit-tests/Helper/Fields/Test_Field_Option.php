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
class Test_Field_Option extends WP_UnitTestCase {

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

	public function set_up() {
		parent::set_up();
		$this->form  = $GLOBALS['GFPDF_Test']->form['non-group-products-form'];
		$this->entry = $GLOBALS['GFPDF_Test']->entries['non-group-products-form'][0];

		$form_id                = \GFAPI::add_form( $this->form );
		$this->entry['form_id'] = $form_id;
		$entry_id = \GFAPI::add_entry( $this->entry);
		$this->pdf_field  = new Field_Option( new \GF_Field_Option( $this->form['fields'][4] ), \GFAPI::get_entry( $entry_id ), \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
	}

	public function test_get_option_html() {
		$options = [ [ 'option_name' => 'Option 1' ], [ 'option_name' => 'Option 2' ], [ 'option_name' => 'Option 3' ] ];
		$html    = $this->pdf_field->get_option_html( $options );

		$this->assertStringContainsString( '<li>Option 1</li><li>Option 2</li><li>Option 3</li>', $html );
	}
}
