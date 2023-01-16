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
class Test_Field_Survey extends WP_UnitTestCase {

	/**
	 * @var array
	 */
	public $form;

	/**
	 * @var \GF_Field_Survey
	 */
	public $gf_field;

	/**
	 * @var Field_Survey
	 */
	public $pdf_field;

	public function set_up() {
		parent::set_up();

		$this->form = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

		foreach ( $this->form['fields'] as $field ) {
			if ( $field->type === 'survey' ) {
				$this->gf_field = $field;
				break;
			}
		}

		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		$this->pdf_field = new Field_Survey( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
	}

	public function test_html() {
		$html = $this->pdf_field->html();

		$this->assertStringContainsString( "<table class='gsurvey-likert' id='input_1_26'>", $html );
		$this->assertStringContainsString( "<input name='input_26' type='radio' value='glikertcol2636762f85' checked='checked' id='choice_1_26_1' />", $html );
	}
}
