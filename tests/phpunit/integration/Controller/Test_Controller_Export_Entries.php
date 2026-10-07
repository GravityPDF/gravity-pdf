<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Tests\Integration\TestCase;

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
class Test_Controller_Export_Entries extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}

	public function test_add_pdfs_to_export_fields() {
		$form = apply_filters( 'gform_export_fields', $this->form( 'all-form-fields' ) );

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
		$form_id  = $this->form( 'all-form-fields' )['id'];
		$entry    = $this->entry( 'all-form-fields' );
		$field_id = 'gpdf_555ad84787d7e';
		$this->assertEmpty( apply_filters( 'gform_export_field_value', 'item', $form_id, $field_id, $entry ) );
	}

	public function test_get_export_field_pdf_value() {
		$form_id  = $this->form( 'all-form-fields' )['id'];
		$entry    = $this->entry( 'all-form-fields' );
		$field_id = 'gpdf_556690c67856b';
		$this->assertStringContainsString( 'http://example.org/?gpdf=1', apply_filters( 'gform_export_field_value', 'item', $form_id, $field_id, $entry ) );
	}

	public function test_get_export_field_empty_value() {
		$form_id  = $this->form( 'all-form-fields' )['id'];
		$field_id = 'gpdf_555ad84787d7e';
		$value    = 'item';
		$this->assertSame( $value, apply_filters( 'gform_export_field_value', $value, $form_id, $field_id, [] ) );
	}

	public function test_signed_export_url_is_signed_only_for_the_exported_entry() {
		wp_set_current_user( 0 );
		$form_id  = $this->form( 'all-form-fields' )['id'];
		$entry    = $this->entry( 'all-form-fields' );
		$field_id = 'gpdf_556690c67856b';

		$entry_id = $entry['id'];
		$signed   = function ( $shortcode ) use ( &$entry_id ) {
			return sprintf( '[gravitypdf id="556690c67856b" entry="%d" raw="1" signed="1"]', $entry_id );
		};
		add_filter( 'gfpdf_export_pdf_shortcode', $signed );

		$this->assertStringContainsString( 'signature=', apply_filters( 'gform_export_field_value', 'item', $form_id, $field_id, $entry ) );

		/* The trust is only for the exported entry, not one a filter swaps in */
		$entry_id = $this->gf_factory()->entry->create( [ 'form_id' => $form_id ] );
		$this->assertStringNotContainsString( 'signature=', apply_filters( 'gform_export_field_value', 'item', $form_id, $field_id, $entry ) );
	}
}
