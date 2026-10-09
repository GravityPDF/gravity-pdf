<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Textarea;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Textarea extends WP_UnitTestCase {

	public function test_rich_text_html() {
		$field = new GF_Field_Textarea( [
			'id' => 1,
			'useRichTextEditor' => true,
		] );

		$entry = [
			'id' => 0,
			'form_id' => 0,
			'1'       => '<div class="a b c d e f g h i j k l m n o p q r s t u v w x y z">Hi <ul id="list"><li>Item 1</li><li class="1 2 3 4 5 6 7 8 9 10 11 12 13">Item 2</li><li>Item 3</li></ul></div><p class="a b c">My paragraph</p>',
		];

		$pdf_field = new Field_Textarea( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$value = $pdf_field->html();
		$this->assertStringContainsString('<div class="a b c d e f g h">Hi <ul id="list"><li>Item 1</li><li class="1 2 3 4 5 6 7 8">Item 2</li><li>Item 3</li></ul></div><p class="a b c">My paragraph</p>', str_replace( [ "\n", "\t" ], '', $value ) );
	}

	public function test_empty_rich_text_value_raises_no_deprecation() {
		$field = new GF_Field_Textarea( [ 'id' => 1, 'useRichTextEditor' => true ] );
		$entry = [ 'id' => 0, 'form_id' => 0, '1' => '' ];

		$deprecations = [];
		set_error_handler( function ( $errno, $errstr ) use ( &$deprecations ) {
			$deprecations[] = $errstr;

			return true;
		}, E_DEPRECATED );

		try {
			/* An empty value parses to no elements, so QueryPath returns a null class */
			( new Field_Textarea( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) )->html();
		} finally {
			restore_error_handler();
		}

		$this->assertSame( [], $deprecations );
	}

	public function test_administrative_value_is_encoded_unless_it_is_the_default() {
		$field = new GF_Field_Textarea( [
			'id'           => 1,
			'visibility'   => 'administrative',
			'defaultValue' => '[gravitypdf id=abc]',
		] );

		$html = function ( $field, $value ) {
			$entry = [ 'id' => 0, 'form_id' => 0, '1' => $value ];

			return ( new Field_Textarea( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) )->html();
		};

		$this->assertStringContainsString( '[gravitypdf id=abc]', $html( $field, '[gravitypdf id=abc]' ) );

		$posted = $html( $field, '[gravitypdf id=xyz]' );
		$this->assertStringContainsString( 'gravitypdf id=xyz', $posted );
		$this->assertStringNotContainsString( '[gravitypdf id=xyz]', $posted );

		/* Default tags are kept wherever they appear */
		$field->defaultValue = '{user:display_name} [gravitypdf id=abc]';
		$mixed               = $html( $field, 'Jake [gravitypdf id=abc] [gravitypdf id=xyz] {all_fields}' );
		$this->assertStringContainsString( 'Jake [gravitypdf id=abc] &#091;gravitypdf id=xyz&#093; &#123;all_fields&#125;', $mixed );

		$field->visibility = 'visible';
		$this->assertStringNotContainsString( '[gravitypdf id=abc]', $html( $field, '[gravitypdf id=abc]' ) );
	}

	public function test_administrative_default_keeps_shortcode_quotes() {
		$default = '"Hi" [gravitypdf id="abc" text=\'dl\']';
		$field   = new GF_Field_Textarea( [ 'id' => 1, 'visibility' => 'administrative', 'defaultValue' => $default ] );
		$entry   = [ 'id' => 0, 'form_id' => 0, '1' => $default ];

		$html = ( new Field_Textarea( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) )->html();

		$this->assertStringContainsString( '&quot;Hi&quot; [gravitypdf id="abc" text=\'dl\']', $html );
	}

	public function test_rich_text_merge_tags_only_processed_when_trusted_or_opted_in() {
		$form  = ( new \GF_UnitTest_Factory() )->form->create( [], [ 'title' => 'Merge Tags', 'fields' => [ new GF_Field_Textarea( [ 'id' => 1, 'useRichTextEditor' => true ] ) ] ] );
		$field = \GFAPI::get_form( $form )['fields'][0];

		$value = function () use ( $field, $form ) {
			$entry = [ 'id' => 0, 'form_id' => $form, '1' => 'Form {form_id}' ];

			return ( new Field_Textarea( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) )->value();
		};

		$this->assertStringContainsString( 'Form {form_id}', $value() );

		add_filter( 'gfpdf_process_merge_tags_in_submitted_rich_text', '__return_true' );
		$this->assertStringContainsString( "Form $form", $value() );
		remove_filter( 'gfpdf_process_merge_tags_in_submitted_rich_text', '__return_true' );

		$field->visibility   = 'administrative';
		$field->defaultValue = 'Form {form_id}';
		$this->assertStringContainsString( "Form $form", $value() );

		/* A tag that isn't in the default is left encoded */
		$field->defaultValue = '{form_id} {user:display_name}';
		$this->assertStringContainsString( "Form $form", $value() );
		$field->defaultValue = '{entry_id}';
		$this->assertStringContainsString( 'Form &#123;form_id&#125;', $value() );
	}

	public function test_opted_in_merge_tags_never_sign_a_pdf_url() {
		wp_set_current_user( 0 );

		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];
		$form  = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$field = new GF_Field_Textarea( [ 'id' => 999, 'formId' => $form['id'], 'useRichTextEditor' => true ] );

		$entry['999'] = '{Label:pdf:556690c67856b:signed}';

		add_filter( 'gfpdf_process_merge_tags_in_submitted_rich_text', '__return_true' );
		$value = ( new Field_Textarea( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) )->value();
		remove_filter( 'gfpdf_process_merge_tags_in_submitted_rich_text', '__return_true' );

		$this->assertStringContainsString( 'pid=556690c67856b', $value );
		$this->assertStringNotContainsString( 'signature=', $value );
	}
}
