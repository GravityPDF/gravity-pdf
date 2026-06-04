<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Post_Excerpt;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Post_Excerpt extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}


	public function test_html_contains_excerpt_text() {
		$entry    = $this->entry( 'all-form-fields' );
		$gf_field = new GF_Field_Post_Excerpt( $this->field_from_fixture( 'post_excerpt' ) );

		$pdf_field = new Field_Post_Excerpt( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringContainsString( 'My Post Excerpt', $pdf_field->html() );
	}

	public function test_value_returns_escaped_string() {
		$gf_field = new GF_Field_Post_Excerpt( [ 'id' => 1 ] );
		$entry    = [ 'id' => 0, 'form_id' => 0, '1' => '<b>Rich</b>' ];

		$pdf_field = new Field_Post_Excerpt( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringNotContainsString( '<b>', $pdf_field->value() );
		$this->assertStringContainsString( 'Rich', $pdf_field->value() );
	}

	public function test_empty_entry_produces_empty_value() {
		$gf_field = new GF_Field_Post_Excerpt( [ 'id' => 1 ] );
		$entry    = [ 'id' => 0, 'form_id' => 0 ];

		$pdf_field = new Field_Post_Excerpt( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertSame( '', $pdf_field->value() );
	}
}
