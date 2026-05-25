<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Consent;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Fg_Ls_Consent extends TestCase {

	private function make_field_and_entry(): array {
		$form = $GLOBALS['GFPDF_Test']->form['repeater-consent-form'];

		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'consent' ) {
				$gf_field = new GF_Field_Consent( $field );
				break;
			}
		}

		$entry = [
			'id'                            => '0',
			'form_id'                       => $form['id'],
			$gf_field->id . '.1'            => '1',
			$gf_field->id . '.2'            => 'I agree to the privacy policy.',
			$gf_field->id . '.3'            => '6',
		];

		return [ $gf_field, $entry ];
	}

	public function test_html_shows_consent_accepted_when_consented() {
		[ $gf_field, $entry ] = $this->make_field_and_entry();

		$pdf_field = new Field_Fg_Ls_Consent( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringContainsString( 'consent-accepted-label', $pdf_field->html() );
	}

	public function test_form_data_includes_value_and_dot_2_keys() {
		[ $gf_field, $entry ] = $this->make_field_and_entry();

		$pdf_field = new Field_Fg_Ls_Consent( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$form_data = $pdf_field->form_data();

		$field_id = (int) $gf_field->id;

		$this->assertArrayHasKey( 'field', $form_data );
		$this->assertArrayHasKey( $field_id, $form_data['field'] );
		$this->assertArrayHasKey( $field_id . '.2', $form_data['field'] );
	}

	public function test_form_data_dot_2_key_contains_consent_array() {
		[ $gf_field, $entry ] = $this->make_field_and_entry();

		$pdf_field = new Field_Fg_Ls_Consent( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$form_data = $pdf_field->form_data();

		$field_id = (int) $gf_field->id;
		$dot2     = $form_data['field'][ $field_id . '.2' ];

		$this->assertIsArray( $dot2 );
		$this->assertArrayHasKey( 'value', $dot2 );
	}
}
