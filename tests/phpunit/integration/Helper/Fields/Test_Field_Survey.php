<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

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
class Test_Field_Survey extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}


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

		$this->form = $this->form( 'all-form-fields' );

		foreach ( $this->form['fields'] as $field ) {
			if ( $field->type === 'survey' ) {
				$this->gf_field = $field;
				break;
			}
		}

		$entry = $this->entry( 'all-form-fields' );

		$this->pdf_field = new Field_Survey( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
	}

	public function test_html() {
		$html    = $this->pdf_field->html();
		$form_id = $this->form( 'all-form-fields' )['id'];

		$this->assertStringContainsString( "<table aria-label='Likert Survey Field' class='gsurvey-likert' id='input_{$form_id}_26'>", $html );
		$this->assertStringContainsString( "<input name='input_26' type='radio' value='glikertcol2636762f85' checked='checked' id='choice_{$form_id}_26_1' />", $html );
	}
}
