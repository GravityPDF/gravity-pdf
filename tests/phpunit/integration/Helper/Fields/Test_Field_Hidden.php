<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Hidden;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Hidden extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ] );
	}


	public function test_html_renders_stored_value() {
		$form     = $this->form( 'all-form-fields' );
		$gf_field = null;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'hidden' ) {
				$gf_field = new GF_Field_Hidden( $field );
				break;
			}
		}

		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'8'       => 'hidden field value',
		];

		$pdf_field = new Field_Hidden( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'hidden field value', $html );
	}

	public function test_value_esc_htmls_the_entry_value() {
		$gf_field  = new GF_Field_Hidden( [ 'id' => 8 ] );
		$entry     = [ 'id' => 0, 'form_id' => 0, '8' => 'raw <b>value</b>' ];
		$pdf_field = new Field_Hidden( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$value = $pdf_field->value();
		$this->assertStringContainsString( 'raw', $value );
		$this->assertStringNotContainsString( '<b>', $value );
	}

	public function test_is_empty_when_no_value_stored() {
		$gf_field  = new GF_Field_Hidden( [ 'id' => 8 ] );
		$entry     = [ 'id' => 0, 'form_id' => 0 ];
		$pdf_field = new Field_Hidden( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertTrue( $pdf_field->is_empty() );
	}
}
