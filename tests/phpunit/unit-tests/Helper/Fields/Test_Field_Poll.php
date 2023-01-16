<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Poll extends WP_UnitTestCase {

	/**
	 * @var array
	 */
	public $form;

	/**
	 * @var \GF_Field_Poll
	 */
	public $gf_field;

	/**
	 * @var Field_Poll
	 */
	public $pdf_field;

	public function set_up() {
		parent::set_up();

		$this->form = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

		foreach ( $this->form['fields'] as $field ) {
			if ( $field->type === 'poll' ) {
				$this->gf_field = $field;
				break;
			}
		}

		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		$this->pdf_field = new Field_Poll( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
	}

	public function test_html() {
		$html = $this->pdf_field->html();

		$this->assertStringContainsString( '<div id="field-22" class="gfpdf-field gfpdf-select ">', $html );
		$this->assertStringContainsString( '<div class="value">Poll Dropdown - First Choice</div>', $html );
	}
}
