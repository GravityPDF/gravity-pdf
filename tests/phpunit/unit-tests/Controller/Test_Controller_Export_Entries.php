<?php

namespace GFPDF\Controller;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
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
	public function set_up() {
		parent::set_up();

		/* Signing grants are request-scoped, so give each test a fresh registry */
		$GLOBALS['gfpdf']->singleton->add_class( new \GFPDF\Model\Model_Signed_Url_Trust() );
	}

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

	/**
	 * @dataProvider provider_export_field_pdf_error
	 */
	public function test_get_export_field_empty_pdf_value_if_error( $pdf_id, $debug ) {
		$options  = $GLOBALS['gfpdf']->options;
		$original = $options->get_option( 'debug_mode', 'No' );
		$options->update_option( 'debug_mode', $debug );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$form_id = $GLOBALS['GFPDF_Test']->form['all-form-fields']['id'];
		$entry   = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];
		$value   = apply_filters( 'gform_export_field_value', 'item', $form_id, 'gpdf_' . $pdf_id, $entry );

		$options->update_option( 'debug_mode', $original );

		$this->assertSame( '', $value );
	}

	public function provider_export_field_pdf_error() {
		return [
			'failed conditional logic'            => [ '555ad84787d7e', 'No' ],
			'failed conditional logic, debug on' => [ '555ad84787d7e', 'Yes' ],
			'inactive PDF, debug on'              => [ '556690c8d7f82', 'Yes' ],
			'invalid PDF, debug on'               => [ 'abc123', 'Yes' ],
		];
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
		$value    = 'item';
		$this->assertSame( $value, apply_filters( 'gform_export_field_value', $value, $form_id, $field_id, [] ) );
	}

	public function test_signed_export_url_is_signed_only_for_the_exported_entry() {
		wp_set_current_user( 0 );
		$form_id  = $GLOBALS['GFPDF_Test']->form['all-form-fields']['id'];
		$entry    = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];
		$field_id = 'gpdf_556690c67856b';

		$entry_id = $entry['id'];
		$signed   = function ( $shortcode ) use ( &$entry_id ) {
			return sprintf( '[gravitypdf id="556690c67856b" entry="%d" raw="1" signed="1"]', $entry_id );
		};
		add_filter( 'gfpdf_export_pdf_shortcode', $signed );

		$this->assertStringContainsString( 'signature=', apply_filters( 'gform_export_field_value', 'item', $form_id, $field_id, $entry ) );

		/* Only the exported entry is vouched for, not one a filter swaps in */
		$entry_id = \GFAPI::add_entry( [ 'form_id' => $form_id ] );
		$this->assertStringNotContainsString( 'signature=', apply_filters( 'gform_export_field_value', 'item', $form_id, $field_id, $entry ) );
	}
}
