<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Section;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_V3_Section extends TestCase {

	public function test_html_contains_h2_with_section_title() {
		$form = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'section' ) {
				$gf_field = new GF_Field_Section( $field );
				break;
			}
		}

		$entry     = [ 'id' => 0, 'form_id' => $form['id'] ];
		$pdf_field = new Field_V3_Section( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();

		$this->assertStringContainsString( 'entry-view-section-break', $html );
		$this->assertStringContainsString( 'Section Break', $html );
		$this->assertStringContainsString( '<h2', $html );
	}

	public function test_html_omits_description_div_when_value_param_is_empty() {
		$form = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'section' ) {
				$gf_field = new GF_Field_Section( $field );
				break;
			}
		}

		$entry     = [ 'id' => 0, 'form_id' => $form['id'] ];
		$pdf_field = new Field_V3_Section( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html( '' );

		$this->assertStringNotContainsString( 'entry-view-section-break-content', $html );
	}

	public function test_html_includes_description_div_when_value_param_is_non_empty() {
		$form = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'section' ) {
				$gf_field             = new GF_Field_Section( $field );
				$gf_field->description = 'Section notes here';
				break;
			}
		}

		$entry     = [ 'id' => 0, 'form_id' => $form['id'] ];
		$pdf_field = new Field_V3_Section( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html( 'non-empty' );

		$this->assertStringContainsString( 'entry-view-section-break-content', $html );
	}
}
