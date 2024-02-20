<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Section extends WP_UnitTestCase {

	public $form;

	public $gf_field;

	public $pdf_field;

	public function set_up() {
		parent::set_up();

		$this->form = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

		foreach ( $this->form['fields'] as $field ) {
			if ( $field->type === 'section' ) {
				$this->gf_field = new \GF_Field_Section( $field );
				break;
			}
		}

		$entry = [
			'form_id' => $this->form['id'],
		];

		$this->pdf_field = new Field_Section( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
	}

	public function test_exclude_description_markup_if_empty() {
		$this->gf_field->description = 'Contents';
		$field                       = new Field_Section( $this->gf_field, [ 'form_id' => $this->form['id'] ], \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringContainsString( 'gfpdf-section-description', $field->html( true ) );

		$this->gf_field->description = '';
		$field                       = new Field_Section( $this->gf_field, [ 'form_id' => $this->form['id'] ], \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringNotContainsString( 'gfpdf-section-description', $field->html( true ) );
	}
}
