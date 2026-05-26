<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Number;
use GFPDF\Tests\Integration\TestCase;


/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Number extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ] );
	}


	public function test_value_applies_decimal_dot_format() {
		$form     = $this->form( 'all-form-fields' );
		$gf_field = null;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'number' ) {
				$gf_field = new GF_Field_Number( $field );
				break;
			}
		}

		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'5'       => '1234.56',
			'currency' => 'USD',
		];

		$pdf_field = new Field_Number( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$value     = $pdf_field->value();

		/*
		 * decimal_dot with no thousands separator: GFCommon::format_number outputs "1234.56".
		 * assertSame catches any mangling (e.g. wrong decimal/thousands sep) that substring
		 * checks on the raw stored value would not detect.
		 */
		$this->assertSame( '1234.56', $value );
	}

	public function test_html_contains_formatted_number() {
		$gf_field = new GF_Field_Number( [ 'id' => 1, 'numberFormat' => 'decimal_dot' ] );

		$entry = [
			'id'      => 0,
			'form_id' => 0,
			'1'       => '9999.99',
			'currency' => 'USD',
		];

		$pdf_field = new Field_Number( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( '9999', $html );
	}

	public function test_is_empty_when_no_value() {
		$gf_field  = new GF_Field_Number( [ 'id' => 1, 'numberFormat' => 'decimal_dot' ] );
		$entry     = [ 'id' => 0, 'form_id' => 0, 'currency' => 'USD' ];
		$pdf_field = new Field_Number( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertTrue( $pdf_field->is_empty() );
	}
}
