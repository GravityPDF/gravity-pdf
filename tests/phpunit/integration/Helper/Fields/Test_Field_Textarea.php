<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Textarea;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Textarea extends TestCase {

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

	/**
	 * @dataProvider provider_encode_tags
	 */
	public function test_encode_tags( $value, $expected ) {
		$pdf_field = new Field_Textarea( new GF_Field_Textarea( [ 'id' => 1 ] ), [ 'id' => 0, 'form_id' => 0 ], \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertSame( $expected, $pdf_field->encode_tags( $value ) );
		$this->assertSame( $expected, $pdf_field->encode_tags( $expected ), 'Encoding twice changes nothing' );

		/* No merge tag matches after kses: Gravity PDF's, or Gravity Forms' field tag from GFCommon::replace_variables() */
		$parsed = \GFPDF\Statics\Kses::parse( $expected );
		$this->assertSame( 0, preg_match( \GFPDF\Model\Model_Mergetags::PDF_MERGETAG_REGEX, $parsed ) );
		$this->assertSame( 0, preg_match( '/{[^{]*?:(\d+(\.\w+)?)(:(.*?))?}/mi', $parsed ) );
	}

	public function provider_encode_tags() {
		return [
			'no tags'                    => [ 'https://example.org/ <b>Bold</b>', 'https://example.org/ <b>Bold</b>' ],
			'text'                       => [ '[gravitypdf id="1"] {Text:1}', "[\u{FEFF}gravitypdf id=\"1\"] {\u{FEFF}Text:\u{FEFF}1}" ],
			'text that looks like a tag' => [ 'I <3 {admin_email} >_<', "I <3 {\u{FEFF}admin_email} >_<" ],
			'attribute name'             => [ '<b {Text:1}>', "<b {\u{FEFF}Text:\u{FEFF}1}>" ],
			'attribute values'           => [ '<a href="https://example.org/?a=[b]" title="{all_fields}">[c]</a>', "<a href=\"https://example.org/?a=%5Bb%5D\" title=\"%7Ball_fields%7D\">[\u{FEFF}c]</a>" ],
			'attribute containing >'     => [ '<a title="1>0" href="https://example.org/">{c}</a>', "<a title=\"1>0\" href=\"https://example.org/\">{\u{FEFF}c}</a>" ],
			'tag ending in an attribute' => [ '{x <b title=":3:pdf:abc">}', "{\u{FEFF}x <b title=\":\u{FEFF}3:\u{FEFF}pdf:\u{FEFF}abc\">}" ],
			'tag ending in a URL'        => [ '{x <a href="https://example.org/:3:pdf:abc">}', "{\u{FEFF}x <a href=\"https://example.org/%3A3%3Apdf%3Aabc\">}" ],
			'tag ending in a style'      => [ '{x <b style="color:red:3:pdf:abc">}', "{\u{FEFF}x <b style=\"color:\u{FEFF}red:\u{FEFF}3:\u{FEFF}pdf:\u{FEFF}abc\">}" ],
			'tag ending in a property'   => [ '{x <b style="color:3:y">}', "{\u{FEFF}x <b style=\"color:\u{FEFF}3:\u{FEFF}y\">}" ],
			'style declarations'         => [ '{c} <b style="color: red; text-align:center">', "{\u{FEFF}c} <b style=\"color: red; text-align:center\">" ],
		];
	}

	public function test_default_tags_are_restored_in_attributes() {
		$field = new GF_Field_Textarea( [
			'id'           => 1,
			'visibility'   => 'administrative',
			'defaultValue' => '{user:display_name}',
		] );

		$pdf_field = new Field_Textarea( $field, [ 'id' => 0, 'form_id' => 0 ], \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$value     = '<a href="https://example.org/?u={user:display_name}" title="{user:display_name}">{all_fields}</a>';

		$this->assertSame( "<a href=\"https://example.org/?u={user:display_name}\" title=\"{user:display_name}\">{\u{FEFF}all_fields}</a>", $pdf_field->encode_value_tags( $value ) );
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
		$this->assertStringContainsString( "Jake [gravitypdf id=abc] [\u{FEFF}gravitypdf id=xyz] {\u{FEFF}all_fields}", $mixed );

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

	public function test_rich_text_administrative_default_shortcode_is_left_for_the_pdf_to_run() {
		$default = '[gravitypdf id="abc"]';
		$form    = $this->gf_factory()->form->create( [], [ 'title' => 'Rich Default', 'fields' => [ new GF_Field_Textarea( [ 'id' => 1, 'useRichTextEditor' => true, 'visibility' => 'administrative', 'defaultValue' => $default ] ) ] ] );
		$field   = \GFAPI::get_form( $form )['fields'][0];
		$entry   = [ 'id' => 5, 'form_id' => $form, '1' => $default ];

		$html = ( new Field_Textarea( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) )->html();

		$this->assertStringContainsString( $default, $html );
		$this->assertStringNotContainsString( 'entry=', $html );
	}

	public function test_rich_text_merge_tags_only_processed_when_trusted_or_opted_in() {
		$form  = $this->gf_factory()->form->create( [], [ 'title' => 'Merge Tags', 'fields' => [ new GF_Field_Textarea( [ 'id' => 1, 'useRichTextEditor' => true ] ) ] ] );
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
		$this->assertStringContainsString( "Form {\u{FEFF}form_id}", $value() );
	}

	public function test_opted_in_merge_tags_never_sign_a_pdf_url() {
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
		wp_set_current_user( 0 );

		$entry = $this->entry( 'all-form-fields' );
		$form  = $this->form( 'all-form-fields' );
		$field = new GF_Field_Textarea( [ 'id' => 999, 'formId' => $form['id'], 'useRichTextEditor' => true ] );

		$entry['999'] = '{Label:pdf:556690c67856b:signed}';

		add_filter( 'gfpdf_process_merge_tags_in_submitted_rich_text', '__return_true' );
		$value = ( new Field_Textarea( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) )->value();
		remove_filter( 'gfpdf_process_merge_tags_in_submitted_rich_text', '__return_true' );

		$this->assertStringContainsString( 'pid=556690c67856b', $value );
		$this->assertStringNotContainsString( 'signature=', $value );
	}
}
