<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_HTML;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Html extends TestCase {

	public function test_html_renders_field_content() {
		$form     = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$gf_field = null;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'html' ) {
				$gf_field = new GF_Field_HTML( $field );
				break;
			}
		}

		$entry = [ 'id' => 0, 'form_id' => $form['id'] ];

		$pdf_field = new Field_Html( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'This is a HTML block', $html );
	}

	public function test_html_omits_label_wrapper() {
		$gf_field          = new GF_Field_HTML( [ 'id' => 9, 'content' => '<p>No label here</p>' ] );
		$entry             = [ 'id' => 0, 'form_id' => 0 ];
		$pdf_field         = new Field_Html( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();

		/* Field_Html calls parent::html($html, false) — the label <div> must not appear */
		$this->assertStringNotContainsString( '<div class="label">', $html );
		$this->assertStringContainsString( 'No label here', $html );
	}

	public function test_form_data_exposes_html_content() {
		$gf_field  = new GF_Field_HTML( [ 'id' => 9, 'content' => 'My <strong>content</strong>' ] );
		$entry     = [ 'id' => 0, 'form_id' => 0 ];
		$pdf_field = new Field_Html( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$form_data = $pdf_field->form_data();

		$this->assertArrayHasKey( 'html', $form_data );
		$this->assertArrayHasKey( 'html_id', $form_data );
		$this->assertArrayHasKey( 9, $form_data['html_id'] );
	}
}
