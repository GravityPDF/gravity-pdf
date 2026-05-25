<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Slim extends TestCase {

	public function test_html_wraps_url_in_anchor_and_img_tags() {
		$gf_field       = new GF_Field();
		$gf_field->type = 'slim';
		$gf_field->id   = 1;

		$entry = [
			'id'      => 0,
			'form_id' => 0,
			'1'       => 'https://example.com/signature.png',
		];

		$pdf_field = new Field_Slim( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();

		$this->assertStringContainsString( '<a href="https://example.com/signature.png">', $html );
		$this->assertStringContainsString( '<img src=', $html );
	}

	public function test_value_returns_url_key() {
		$gf_field       = new GF_Field();
		$gf_field->type = 'slim';
		$gf_field->id   = 1;

		$entry = [
			'id'      => 0,
			'form_id' => 0,
			'1'       => 'https://example.com/sig.png',
		];

		$pdf_field = new Field_Slim( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$value     = $pdf_field->value();

		$this->assertArrayHasKey( 'url', $value );
		$this->assertSame( 'https://example.com/sig.png', $value['url'] );
	}
}
