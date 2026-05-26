<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Website;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Website extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}


	public function test_html_wraps_valid_url_in_anchor_tag() {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'website' ) {
				$gf_field = new GF_Field_Website( $field );
				break;
			}
		}

		$pdf_field = new Field_Website( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();

		$this->assertStringContainsString( '<a href="https://gravitypdf.com"', $html );
		$this->assertStringContainsString( 'target="_blank"', $html );
		$this->assertStringContainsString( 'gravitypdf.com', $html );
	}

	public function test_value_returns_escaped_url_string() {
		$gf_field  = new GF_Field_Website( [ 'id' => 1 ] );
		$entry     = [ 'id' => 0, 'form_id' => 0, '1' => 'https://example.com' ];
		$pdf_field = new Field_Website( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertSame( 'https://example.com', $pdf_field->value() );
	}

	public function test_html_returns_plain_text_for_invalid_url() {
		$gf_field  = new GF_Field_Website( [ 'id' => 1 ] );
		$entry     = [ 'id' => 0, 'form_id' => 0, '1' => 'not-a-url' ];
		$pdf_field = new Field_Website( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();

		$this->assertStringNotContainsString( '<a href', $html );
		$this->assertStringContainsString( 'not-a-url', $html );
	}

	public function test_html_is_empty_when_no_url_in_entry() {
		$gf_field  = new GF_Field_Website( [ 'id' => 1 ] );
		$entry     = [ 'id' => 0, 'form_id' => 0 ];
		$pdf_field = new Field_Website( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringNotContainsString( '<a', $pdf_field->html() );
	}
}
