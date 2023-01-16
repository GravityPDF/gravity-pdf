<?php

namespace GFPDF\Controller;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Controller_Export_Entries
 *
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   export
 */
class Test_Controller_Export_Entries extends WP_UnitTestCase {
	public function test_add_pdfs_to_export_fields() {
		$form = apply_filters( 'gform_export_fields', $GLOBALS['GFPDF_Test']->form['all-form-fields'] );

		$field_ids = array_column( $form['fields'], 'id' );

		$this->assertContains( 'gpdf_555ad84787d7e', $field_ids );
		$this->assertContains( 'gpdf_556690c67856b', $field_ids );
		$this->assertContains( 'gpdf_fawf90c678523b', $field_ids );
	}

	public function test_no_add_pdfs_to_export_fields() {
		$form = [ 'id' => 0 ];

		$this->assertSame( $form, apply_filters( 'gform_export_fields', $form ) );
	}

	public function test_get_export_field_unrelated_value() {
		$value = 'item';
		$this->assertSame( $value, apply_filters( 'gform_export_field_value', $value, 1, '', [] ) );
	}

	public function test_get_export_field_empty_pdf_value_if_failed_conditional_logic() {
		$form_id  = $GLOBALS['GFPDF_Test']->form['all-form-fields']['id'];
		$entry    = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];
		$field_id = 'gpdf_555ad84787d7e';
		$this->assertEmpty( apply_filters( 'gform_export_field_value', 'item', $form_id, $field_id, $entry ) );
	}

	public function test_get_export_field_pdf_value() {
		$form_id  = $GLOBALS['GFPDF_Test']->form['all-form-fields']['id'];
		$entry    = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];
		$field_id = 'gpdf_556690c67856b';
		$this->assertStringContainsString( 'http://example.org/?gpdf=1', apply_filters( 'gform_export_field_value', 'item', $form_id, $field_id, $entry ) );
	}

	public function test_get_export_field_empty_value() {
		$form_id  = $GLOBALS['GFPDF_Test']->form['all-form-fields']['id'];
		$field_id = 'gpdf_555ad84787d7e';
		$this->assertEmpty( apply_filters( 'gform_export_field_value', 'item', $form_id, $field_id, [] ) );
	}
}
