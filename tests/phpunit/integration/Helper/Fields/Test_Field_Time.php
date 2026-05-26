<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Time;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Time extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}


	public function test_html_contains_time_value_from_fixture() {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'time' ) {
				$gf_field = new GF_Field_Time( $field );
				break;
			}
		}

		$pdf_field = new Field_Time( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringContainsString( '10:30 am', $pdf_field->html() );
	}

	public function test_value_returns_escaped_string() {
		$gf_field = new GF_Field_Time( [ 'id' => 1 ] );
		$entry    = [ 'id' => 0, 'form_id' => 0, '1' => '02:45 pm' ];

		$pdf_field = new Field_Time( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertSame( '02:45 pm', $pdf_field->value() );
	}

	public function test_empty_entry_produces_empty_value() {
		$gf_field  = new GF_Field_Time( [ 'id' => 1 ] );
		$entry     = [ 'id' => 0, 'form_id' => 0 ];
		$pdf_field = new Field_Time( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertSame( '', $pdf_field->value() );
	}
}
