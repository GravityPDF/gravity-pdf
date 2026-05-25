<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Default extends TestCase {

	public function test_html_renders_known_value() {
		$gf_field       = new GF_Field();
		$gf_field->id   = 1;
		$gf_field->type = 'text';

		$entry = [
			'id'      => 0,
			'form_id' => 0,
			'1'       => 'Hello World',
		];

		$pdf_field = new Field_Default( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'Hello World', $html );
	}

	public function test_value_esc_htmls_string_input() {
		$gf_field       = new GF_Field();
		$gf_field->id   = 1;
		$gf_field->type = 'text';

		$entry = [
			'id'      => 0,
			'form_id' => 0,
			'1'       => 'Safe <b>text</b>',
		];

		$pdf_field = new Field_Default( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$value     = $pdf_field->value();

		$this->assertStringContainsString( 'Safe', $value );
		$this->assertStringNotContainsString( '<b>', $value );
	}
}
