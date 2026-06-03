<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Slim_Post extends TestCase {

	public function test_value_parses_pipe_separated_string_into_array() {
		$gf_field       = new GF_Field();
		$gf_field->type = 'slim_post';
		$gf_field->id   = 1;

		$entry = [
			'id'      => 0,
			'form_id' => 0,
			'1'       => 'https://example.com/img.png|:|My Title|:|My Caption|:|My Desc',
		];

		$pdf_field = new Field_Slim_Post( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$value     = $pdf_field->value();

		$this->assertArrayHasKey( 'url', $value );
		$this->assertArrayHasKey( 'title', $value );
		$this->assertSame( 'My Title', $value['title'] );
		$this->assertSame( 'My Caption', $value['caption'] );
	}

	public function test_value_returns_empty_array_when_entry_is_empty() {
		$gf_field       = new GF_Field();
		$gf_field->type = 'slim_post';
		$gf_field->id   = 1;

		$entry = [
			'id'      => 0,
			'form_id' => 0,
			'1'       => '',
		];

		$pdf_field = new Field_Slim_Post( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$value     = $pdf_field->value();

		$this->assertSame( [], $value );
	}

	public function test_html_contains_anchor_and_image_for_valid_url() {
		$gf_field       = new GF_Field();
		$gf_field->type = 'slim_post';
		$gf_field->id   = 1;

		$entry = [
			'id'      => 0,
			'form_id' => 0,
			'1'       => 'https://example.com/img.png|:|Title',
		];

		$pdf_field = new Field_Slim_Post( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( '<a href=', $html );
		$this->assertStringContainsString( '<img src=', $html );
	}
}
