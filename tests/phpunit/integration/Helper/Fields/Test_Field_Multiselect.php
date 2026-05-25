<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_MultiSelect;
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
class Test_Field_Multiselect extends TestCase {

	private function make_field(): GF_Field_MultiSelect {
		$form = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'multiselect' ) {
				return new GF_Field_MultiSelect( $field );
			}
		}
		$this->fail( 'No multiselect field found in all-form-fields fixture' );
	}

	public function test_html_renders_selected_items_as_list() {
		$gf_field = $this->make_field();
		$form     = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

		/* Entry fixture stores comma-separated values for multiselect */
		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'4'       => 'Second Choice,Third Choice',
		];

		$pdf_field = new Field_Multiselect( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( '<ul class="bulleted multiselect">', $html );
		$this->assertStringContainsString( 'Second Choice', $html );
	}

	public function test_is_empty_when_nothing_selected() {
		$gf_field = $this->make_field();
		$form     = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

		$entry     = [ 'id' => 0, 'form_id' => $form['id'] ];
		$pdf_field = new Field_Multiselect( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertTrue( $pdf_field->is_empty() );
	}

	public function test_form_data_contains_value_and_name_keys() {
		$gf_field = $this->make_field();
		$form     = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'4'       => 'Second Choice',
		];

		$pdf_field = new Field_Multiselect( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$form_data = $pdf_field->form_data();

		$this->assertArrayHasKey( 4, $form_data['field'] );
		$this->assertArrayHasKey( '4_name', $form_data['field'] );
	}

	public function test_show_value_filter_renders_values_not_labels() {
		$gf_field = $this->make_field();
		$form     = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

		/*
		 * Choice 2: label = "Multi Select Second Choice", value = "Second Choice".
		 * When the filter is active the value is rendered, not the label.
		 * Assert: the value token appears AND the label-only prefix ("Multi Select ") is absent.
		 */
		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'4'       => 'Second Choice',
		];

		$pdf_field = new Field_Multiselect( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		add_filter( 'gfpdf_show_field_value', '__return_true' );
		$html = $pdf_field->html();
		remove_filter( 'gfpdf_show_field_value', '__return_true' );

		$this->assertStringContainsString( 'Second Choice', $html );
		$this->assertStringNotContainsString( 'Multi Select Second Choice', $html );
	}
}
