<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_FileUpload;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Fileupload extends TestCase {

	public function test_html_contains_anchor_for_single_file() {
		$form     = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$gf_field = null;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'fileupload' && ! $field->multipleFiles ) {
				$gf_field = new GF_Field_FileUpload( $field );
				break;
			}
		}

		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'18'      => 'http://example.org/wp-content/uploads/gravity_forms/test.pdf',
		];

		$pdf_field = new Field_Fileupload( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( '<a href=', $html );
		$this->assertStringContainsString( 'test.pdf', $html );
		$this->assertStringContainsString( '<ul class="bulleted fileupload">', $html );
	}

	public function test_html_is_empty_wrapper_when_no_file() {
		$form     = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$gf_field = null;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'fileupload' && ! $field->multipleFiles ) {
				$gf_field = new GF_Field_FileUpload( $field );
				break;
			}
		}

		$entry     = [ 'id' => 0, 'form_id' => $form['id'] ];
		$pdf_field = new Field_Fileupload( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertTrue( $pdf_field->is_empty() );
	}

	public function test_html_renders_multiple_files() {
		$form     = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$gf_field = null;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'fileupload' && $field->multipleFiles ) {
				$gf_field = new GF_Field_FileUpload( $field );
				break;
			}
		}

		$urls  = [
			'http://example.org/wp-content/uploads/gravity_forms/1/CPC-JAKE.docx',
			'http://example.org/wp-content/uploads/gravity_forms/1/Tent-Cards.pdf',
		];
		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'19'      => json_encode( $urls ),
		];

		$pdf_field = new Field_Fileupload( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'CPC-JAKE.docx', $html );
		$this->assertStringContainsString( 'Tent-Cards.pdf', $html );
	}
}
