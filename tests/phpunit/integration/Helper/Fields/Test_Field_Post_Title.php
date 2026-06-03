<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Post_Title;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Post_Title extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}


	public function test_html_contains_title() {
		$entry    = $this->entry( 'all-form-fields' );
		$gf_field = new GF_Field_Post_Title( $this->field_from_fixture( 'post_title' ) );

		$pdf_field = new Field_Post_Title( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringContainsString( 'My Post Title', $pdf_field->html() );
	}

	public function test_value_returns_escaped_string() {
		$gf_field = new GF_Field_Post_Title( [ 'id' => 1 ] );
		$entry    = [ 'id' => 0, 'form_id' => 0, '1' => 'My <Title>' ];

		$pdf_field = new Field_Post_Title( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringNotContainsString( '<Title>', $pdf_field->value() );
		$this->assertStringContainsString( 'My', $pdf_field->value() );
	}

	public function test_empty_entry_produces_empty_value() {
		$gf_field = new GF_Field_Post_Title( [ 'id' => 1 ] );
		$entry    = [ 'id' => 0, 'form_id' => 0 ];

		$pdf_field = new Field_Post_Title( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertSame( '', $pdf_field->value() );
	}
}
