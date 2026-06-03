<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_List;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_V3_List extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}


	protected function list_field_by_id( int $field_id ): GF_Field_List {
		$form = $this->form( 'all-form-fields' );
		foreach ( $form['fields'] as $field ) {
			if ( (int) $field->id === $field_id ) {
				return new GF_Field_List( $field );
			}
		}
		$this->fail( "List field {$field_id} not found in all-form-fields fixture." );
	}

	public function test_single_column_html_renders_bullet_list() {
		$gf_field  = $this->list_field_by_id( 20 );
		$entry     = $this->entry( 'all-form-fields' );
		$pdf_field = new Field_V3_List( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();

		$this->assertStringContainsString( 'bulleted single-column-list', $html );
		$this->assertStringContainsString( 'List Item Response 1', $html );
	}

	public function test_multi_column_html_falls_through_to_parent_table() {
		$gf_field  = $this->list_field_by_id( 21 );
		$entry     = $this->entry( 'all-form-fields' );
		$pdf_field = new Field_V3_List( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();

		$this->assertStringContainsString( 'gfield_list', $html );
		$this->assertStringContainsString( 'List Response Col 1', $html );
	}

	public function test_empty_list_returns_empty_html() {
		$gf_field  = $this->list_field_by_id( 20 );
		$entry     = [ 'id' => 0, 'form_id' => $this->form( 'all-form-fields' )['id'] ];
		$pdf_field = new Field_V3_List( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();

		$this->assertStringNotContainsString( '<li>', $html );
	}
}
