<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Email;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Email extends TestCase {

	public function test_html_wraps_valid_email_in_mailto_anchor() {
		$gf_field = new GF_Field_Email( [ 'id' => 1 ] );

		$entry = [
			'id'      => 0,
			'form_id' => 0,
			'1'       => 'support@gravitypdf.com',
		];

		$pdf_field = new Field_Email( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'href="mailto:support@gravitypdf.com"', $html );
		$this->assertStringContainsString( 'support@gravitypdf.com', $html );
	}

	public function test_html_esc_htmls_non_email_string() {
		$gf_field = new GF_Field_Email( [ 'id' => 1 ] );

		$entry = [
			'id'      => 0,
			'form_id' => 0,
			'1'       => 'not-an-email<script>',
		];

		$pdf_field = new Field_Email( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$html      = $pdf_field->html();

		$this->assertStringNotContainsString( 'mailto:', $html );
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( 'not-an-email', $html );
	}

	public function test_is_empty_when_no_value() {
		$gf_field  = new GF_Field_Email( [ 'id' => 1 ] );
		$entry     = [ 'id' => 0, 'form_id' => 0 ];
		$pdf_field = new Field_Email( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertTrue( $pdf_field->is_empty() );
	}
}
