<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Phone;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Phone extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}


	public function test_html_contains_phone_number() {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'phone' ) {
				$gf_field = new GF_Field_Phone( $field );
				break;
			}
		}

		$pdf_field = new Field_Phone( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringContainsString( '(555) 678-1210', $pdf_field->html() );
	}

	public function test_value_returns_entry_phone_number() {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'phone' ) {
				$gf_field = new GF_Field_Phone( $field );
				break;
			}
		}

		$pdf_field = new Field_Phone( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertSame( '(555) 678-1210', $pdf_field->value() );
	}

	public function test_empty_entry_produces_empty_value() {
		$form  = $this->form( 'all-form-fields' );
		$entry = [ 'id' => 0, 'form_id' => $form['id'] ];

		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'phone' ) {
				$gf_field = new GF_Field_Phone( $field );
				break;
			}
		}

		$pdf_field = new Field_Phone( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertSame( '', $pdf_field->value() );
	}
}
