<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Post_Tags;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Post_Tags extends TestCase {

	public function test_html_contains_all_tags() {
		$form  = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'post_tags' ) {
				$gf_field = new GF_Field_Post_Tags( $field );
				break;
			}
		}

		$pdf_field = new Field_Post_Tags( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();
		$this->assertStringContainsString( 'tag1', $html );
		$this->assertStringContainsString( 'tag2', $html );
		$this->assertStringContainsString( 'tag3', $html );
	}

	public function test_value_returns_trimmed_array() {
		$gf_field = new GF_Field_Post_Tags( [ 'id' => 1 ] );
		$entry    = [ 'id' => 0, 'form_id' => 0, '1' => 'alpha, beta, gamma' ];

		$pdf_field = new Field_Post_Tags( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$value     = $pdf_field->value();

		$this->assertSame( [ 'alpha', 'beta', 'gamma' ], $value );
	}

	public function test_form_data_joins_tags_as_string() {
		$gf_field = new GF_Field_Post_Tags( [ 'id' => 1 ] );
		$entry    = [ 'id' => 0, 'form_id' => 0, '1' => 'foo, bar' ];

		$pdf_field = new Field_Post_Tags( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$data      = $pdf_field->form_data();

		$this->assertSame( 'foo, bar', $data['field'][1] );
	}
}
