<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Fg_Ls_Signature extends TestCase {

	private function make_gf_field( array $extra = [] ): GF_Field {
		$gf_field       = new GF_Field( array_merge( [ 'id' => 1, 'type' => 'fg_ls_signature' ], $extra ) );
		$gf_field->type = 'fg_ls_signature';

		return $gf_field;
	}

	public function test_html_renders_img_tag_when_image_key_present() {
		$gf_field = $this->make_gf_field();

		$data  = json_encode( [ 'image' => 'https://example.org/signature.png' ] );
		$entry = [ 'id' => 0, 'form_id' => 0, '1' => $data ];

		$pdf_field = new Field_Fg_Ls_Signature( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( '<img src="https://example.org/signature.png"', $html );
		$this->assertStringContainsString( 'legalsigning-field-signature__signed', $html );
	}

	public function test_html_renders_name_in_table_when_no_image() {
		$gf_field = $this->make_gf_field();

		$data  = json_encode( [ 'name' => 'Jane Doe', 'font' => 'caveat' ] );
		$entry = [ 'id' => 0, 'form_id' => 0, '1' => $data ];

		$pdf_field = new Field_Fg_Ls_Signature( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'Jane Doe', $html );
		$this->assertStringContainsString( '<table', $html );
	}

	public function test_value_decodes_json_entry() {
		$gf_field = $this->make_gf_field();

		$data  = json_encode( [ 'image' => 'https://example.org/sig.png', 'name' => 'John' ] );
		$entry = [ 'id' => 0, 'form_id' => 0, '1' => $data ];

		$pdf_field = new Field_Fg_Ls_Signature( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$value     = $pdf_field->value();

		$this->assertIsArray( $value );
		$this->assertArrayHasKey( 'image', $value );
		$this->assertSame( 'https://example.org/sig.png', $value['image'] );
	}

	public function test_value_wraps_plain_url_in_image_key() {
		$gf_field = $this->make_gf_field();

		$entry = [ 'id' => 0, 'form_id' => 0, '1' => 'https://example.org/plain.png' ];

		$pdf_field = new Field_Fg_Ls_Signature( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$value     = $pdf_field->value();

		$this->assertArrayHasKey( 'image', $value );
		$this->assertSame( 'https://example.org/plain.png', $value['image'] );
	}
}
