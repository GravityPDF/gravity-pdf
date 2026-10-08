<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Post_Content;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Post_Content extends TestCase {

	public function test_html_contains_plain_text_value() {
		$gf_field = new GF_Field_Post_Content( [ 'id' => 1 ] );
		$entry    = [ 'id' => 0, 'form_id' => 0, '1' => 'Post body text here' ];

		$pdf_field = new Field_Post_Content( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringContainsString( 'Post body text here', $pdf_field->html() );
	}

	public function test_plain_text_value_is_escaped() {
		$gf_field = new GF_Field_Post_Content( [ 'id' => 1 ] );
		$entry    = [ 'id' => 0, 'form_id' => 0, '1' => '<script>evil()</script>' ];

		$pdf_field = new Field_Post_Content( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringNotContainsString( '<script>', $pdf_field->html() );
	}

	public function test_rich_text_editor_preserves_allowed_tags() {
		$gf_field                    = new GF_Field_Post_Content( [ 'id' => 1 ] );
		$gf_field->useRichTextEditor = true;
		$entry                       = [ 'id' => 0, 'form_id' => 0, '1' => '<strong>Bold</strong>' ];

		$pdf_field = new Field_Post_Content( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringContainsString( '<strong>Bold</strong>', $pdf_field->value() );
	}

	public function test_rich_text_merge_tags_only_processed_when_opted_in() {
		$form_id  = $this->gf_factory()->form->create( [], [ 'title' => 'Post Content Tags', 'fields' => [ new GF_Field_Post_Content( [ 'id' => 1, 'useRichTextEditor' => true ] ) ] ] );
		$gf_field = \GFAPI::get_form( $form_id )['fields'][0];
		$entry    = [ 'id' => 0, 'form_id' => $form_id, '1' => 'Form {form_id}' ];

		$value = function () use ( $gf_field, $entry ) {
			return ( new Field_Post_Content( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) )->value();
		};

		$this->assertStringContainsString( 'Form {form_id}', $value() );

		add_filter( 'gfpdf_field_process_merge_tags', '__return_true' );
		$this->assertStringContainsString( "Form $form_id", $value() );
		remove_filter( 'gfpdf_field_process_merge_tags', '__return_true' );
	}
}
