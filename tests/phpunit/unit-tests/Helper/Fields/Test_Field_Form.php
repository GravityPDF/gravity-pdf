<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields {

	use WP_UnitTestCase;

	/**
	 * @package     Gravity PDF
	 * @copyright   Copyright (c) 2024, Blue Liquid Designs
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 */

	/**
	 * @package GFPDF\Helper\Fields
	 *
	 * @group   helper
	 * @group   fields
	 */
	class Test_Field_Form extends WP_UnitTestCase {

		public $form;

		public $gf_field;

		public $pdf_field;

		public function set_up() {
			parent::set_up();

			$this->form = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

			$entry = [
				'id'      => 0,
				'form_id' => $this->form['id'],
				'1'       => implode( ',', array_column( $GLOBALS['GFPDF_Test']->entries['all-form-fields'], 'id' ) ),
			];

			$this->gf_field = new \GP_Field_Nested_Form( [
				'id'       => 1,
				'gpnfForm' => $this->form['id'],
			] );

			$this->pdf_field = new Field_Form( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		}

		public function test_unique_ids() {
			$html = $this->pdf_field->html();

			$this->assertStringNotContainsString( 'id="field-1"', $html );
			$this->assertStringContainsString( 'id="field-1-0"', $html );
			$this->assertStringContainsString( 'id="field-1-1"', $html );

			$this->assertStringNotContainsString( 'id="field-3"', $html );
			$this->assertStringContainsString( 'id="nested-field-3-0"', $html );
			$this->assertStringContainsString( 'id="nested-field-3-1"', $html );

			$this->assertStringNotContainsString( 'id="field-41-option-1"', $html);
			$this->assertStringContainsString( 'id="nested-field-41-option-1-0"', $html );
			$this->assertStringContainsString( 'id="nested-field-41-option-1-1"', $html );
		}

	}
}

namespace {

	class GP_Field_Nested_Form extends \GF_Field {
		public $type = 'form';
	}

}