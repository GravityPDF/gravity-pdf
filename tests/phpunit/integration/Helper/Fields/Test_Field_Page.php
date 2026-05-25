<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Page extends TestCase {

	public function test_html_contains_page_number_id() {
		$gf_field             = new GF_Field();
		$gf_field->type       = 'page';
		$gf_field->id         = 5;
		$gf_field->pageNumber = 2;
		$gf_field->content    = 'Step Two';

		$entry     = [ 'id' => 0, 'form_id' => 0 ];
		$pdf_field = new Field_Page( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();

		$this->assertStringContainsString( 'id="page-no-2"', $html );
	}

	public function test_html_contains_page_label() {
		$gf_field             = new GF_Field();
		$gf_field->type       = 'page';
		$gf_field->id         = 5;
		$gf_field->pageNumber = 1;
		$gf_field->content    = 'Personal Information';

		$entry     = [ 'id' => 0, 'form_id' => 0 ];
		$pdf_field = new Field_Page( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringContainsString( 'Personal Information', $pdf_field->html() );
	}

	public function test_value_returns_page_content() {
		$gf_field             = new GF_Field();
		$gf_field->type       = 'page';
		$gf_field->id         = 3;
		$gf_field->pageNumber = 3;
		$gf_field->content    = 'Final Page';

		$entry     = [ 'id' => 0, 'form_id' => 0 ];
		$pdf_field = new Field_Page( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertSame( 'Final Page', $pdf_field->value() );
	}
}
