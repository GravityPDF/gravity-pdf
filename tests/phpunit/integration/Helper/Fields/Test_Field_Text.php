<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Text;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Text extends TestCase {

	public function test_html_contains_entry_value() {
		$form  = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'text' ) {
				$gf_field = new GF_Field_Text( $field );
				break;
			}
		}

		$pdf_field = new Field_Text( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringContainsString( 'My Single Line Response', $pdf_field->html() );
	}

	public function test_value_returns_escaped_string() {
		$gf_field = new GF_Field_Text( [ 'id' => 1 ] );
		$entry    = [ 'id' => 0, 'form_id' => 0, '1' => '<script>xss</script>' ];

		$pdf_field = new Field_Text( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringNotContainsString( '<script>', $pdf_field->value() );
	}

	public function test_empty_entry_produces_empty_value() {
		$gf_field  = new GF_Field_Text( [ 'id' => 1 ] );
		$entry     = [ 'id' => 0, 'form_id' => 0 ];
		$pdf_field = new Field_Text( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertSame( '', $pdf_field->value() );
	}
}
