<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Controller\Controller_Mergetags;
use GFPDF\Controller\Controller_Shortcodes;
use GFPDF\Helper\Helper_Url_Signer;
use GPDFAPI;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Model_Mergetags
 *
 * @package   GFPDF\Model
 *
 * @group     model
 * @group     tags
 */
class Test_Model_Mergetags extends TestCase {

	/**
	 * @var Controller_Shortcodes
	 */
	public $controller;

	/**
	 * @var Model_Shortcodes
	 */
	public $model;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		/* Unhook the plugin's own instance, or both would grant trust for each tag and only one would use it */
		$plugin_model = GPDFAPI::get_mvc_class( 'Model_Mergetags' );
		remove_filter( 'gform_pre_replace_merge_tags', [ $plugin_model, 'mark_trusted_pdf_mergetags' ] );
		remove_filter( 'gform_replace_merge_tags', [ $plugin_model, 'process_pdf_mergetags' ] );

		/* Setup our test classes */
		$this->model      = new Model_Mergetags( $gfpdf->options, GPDFAPI::get_mvc_class( 'Model_PDF' ), $gfpdf->log, $gfpdf->misc, new Helper_Url_Signer() );
		$this->controller = new Controller_Mergetags( $this->model );
		$this->controller->init();
	}

	/**
	 * Test the appropriate filters are set up
	 */
	public function test_filters() {
		$this->assertSame( 10, has_filter( 'gform_replace_merge_tags', [ $this->model, 'process_pdf_mergetags' ] ) );
		$this->assertSame( 10, has_filter( 'gform_custom_merge_tags', [ $this->model, 'add_pdf_mergetags' ] ) );
		$this->assertSame( 9999, has_filter( 'gform_entry_id_pre_save_lead', [ $this->model, 'start_resolving_administrative_defaults' ] ) );
		$this->assertSame( 1, has_action( 'gform_entry_created', [ $this->model, 'stop_resolving_administrative_defaults' ] ) );
	}

	public function test_pdf_mergetag_without_an_entry_is_kept_only_while_resolving_defaults() {
		$tag = '{Label:pdf:556690c67856b}';

		$this->assertSame( 'PDF: ', $this->model->process_pdf_mergetags( "PDF: $tag", false, false, false ) );

		$this->assertSame( 7, $this->model->start_resolving_administrative_defaults( 7 ) );
		$this->assertSame( "PDF: $tag", $this->model->process_pdf_mergetags( "PDF: $tag", false, false, false ) );

		$this->model->stop_resolving_administrative_defaults();
		$this->assertSame( 'PDF: ', $this->model->process_pdf_mergetags( "PDF: $tag", false, false, false ) );
	}

	public function test_submitted_entry_keeps_pdf_mergetags_in_an_administrative_default() {
		$form_id = $this->gf_factory()->form->create(
			[],
			[
				'title'  => 'Administrative Default',
				'fields' => [
					new \GF_Field_Text( [ 'id' => 1, 'label' => 'Name' ] ),
					new \GF_Field_Textarea( [ 'id' => 2, 'label' => 'Links', 'visibility' => 'administrative' ] ),
				],
			]
		);

		$pdf_id = $this->gf_factory()->pdf->set_form_id( $form_id )->create();
		$tags   = "{Document:pdf:$pdf_id}\n{Document:pdf:$pdf_id:signed}";

		$form                           = \GFAPI::get_form( $form_id );
		$form['fields'][1]->defaultValue = $tags;
		\GFAPI::update_form( $form );

		$result = \GFAPI::submit_form( $form_id, [ 'input_1' => 'Jake' ] );

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( $tags, \GFAPI::get_entry( $result['entry_id'] )['2'] );
		$this->assertFalse( GPDFAPI::get_mvc_class( 'Model_Signed_Url_Trust' )->is_resolving_defaults() );
	}

	/**
	 * Check we correctly load the form's PDF mergetags in the correct format
	 */
	public function test_add_mergetags() {
		$form = $this->form( 'all-form-fields' );

		$tags = $this->model->add_pdf_mergetags( [], $form['id'] );

		$this->assertCount( 3, $tags );

		$this->assertSame( 'PDF: My First PDF Template', $tags[0]['label'] );
		$this->assertSame( '{My First PDF Template:pdf:555ad84787d7e}', $tags[0]['tag'] );

		$this->assertSame( '{My First PDF Template (copy):pdf:556690c67856b}', $tags[1]['tag'] );
	}

	public function provider_standard_pdf_mergetags_no_permalinks(): array {
		return [

			/* Valid single tag */
			[
				'expected' => 'http://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1',
				'text'     => '{My First PDF Template (Copy):pdf:556690c67856b}',
			],

			[
				'expected' => 'http://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1',
				'text'     => '{:pdf:556690c67856b}',
			],

			[
				'expected' => 'This is my content<br> It is awesome<br><br>http://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1',
				'text'     => 'This is my content<br> It is awesome<br><br>{:pdf:556690c67856b}',
			],

			[
				'expected' => 'This is my content<br> It is awesome<br><br>http://example.org/?gpdf=1&pid=556690c67856b&lid=1',
				'text'     => 'This is my content<br> It is awesome<br><br>{:pdf:556690c67856b}',
				'encode'   => false,
			],

			[
				'expected' => "This is my content\n It is awesome\n\nhttp://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1",
				'text'     => "This is my content\n It is awesome\n\n{:pdf:556690c67856b}",
			],

			/* Invalid format */
			[
				'expected' => ':pdf:556690c67856b}',
				'text'     => ':pdf:556690c67856b}',
			],

			[
				'expected' => '{:pdf:556690c67856b',
				'text'     => '{:pdf:556690c67856b',
			],

			[
				'expected' => ':pdf:556690c67856b',
				'text'     => ':pdf:556690c67856b',
			],

			/* PDF Config not active */
			[
				'expected' => '',
				'text'     => '{Not Active:pdf:556690c8d7f82}',
			],

			[
				'expected' => 'My content ',
				'text'     => 'My content {Not Active:pdf:556690c8d7f82}',
			],

			[
				'expected' => 'My content<br><br>Other Stuff',
				'text'     => 'My content<br><br>{Not Active:pdf:556690c8d7f82}<br>Other Stuff',
			],

			[
				'expected' => 'My content<br /><br>Other Stuff',
				'text'     => 'My content<br /><br>{Not Active:pdf:556690c8d7f82}<br>Other Stuff',
			],

			[
				'expected' => "My content\n\nOther Stuff",
				'text'     => "My content\n\n{Not Active:pdf:556690c8d7f82}\nOther Stuff",
			],

			/* Conditional logic failed */
			[
				'expected' => '',
				'text'     => '{Conditional Failed:pdf:555ad84787d7e}',
			],

			/* Multiple tags */
			[
				'expected' => "My Content goes here\n\nhttp://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1\n",
				'text'     => "My Content goes here\n\n{Not Active:pdf:556690c8d7f82}\n{My First PDF Template (Copy):pdf:556690c67856b}\n{Conditional Failed:pdf:555ad84787d7e}",
			],
		];
	}

	public function provider_modifier_pdf_mergetags_no_permalinks(): array {
		return [

			/* Download */
			[
				'expected' => 'http://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1&#038;action=download',
				'text'     => '{Label:pdf:556690c67856b:download}',
			],

			/* Print */
			[
				'expected' => 'http://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1&#038;print=1',
				'text'     => '{Label:pdf:556690c67856b:print}',
			],

			/* Print and Download (any order) */
			[
				'expected' => 'http://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1&#038;action=download&#038;print=1',
				'text'     => '{Label:pdf:556690c67856b:download:print}',
			],

			[
				'expected' => 'http://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1&#038;action=download&#038;print=1',
				'text'     => '{Label:pdf:556690c67856b:print:download}',
			],
		];
	}

	/**
	 * Check we correctly convert our PDF mergetag with permalinks enabled
	 *
	 * @param string $expected
	 * @param string $text
	 * @param bool   $encode
	 *
	 * @dataProvider provider_standard_pdf_mergetags_permalinks
	 * @dataProvider provider_modifier_pdf_mergetags_permalinks
	 */
	public function test_process_pdf_mergetags_permalink( $expected, $text, $encode = true ) {
		global $wp_rewrite;

		$old_permalink_structure = get_option( 'permalink_structure' );
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules();

		$this->test_process_pdf_mergetags( $expected, $text, $encode );

		$wp_rewrite->set_permalink_structure( $old_permalink_structure );
		flush_rewrite_rules();
	}

	/**
	 * Check we correctly convert our PDF mergetag with permalinks disabled
	 *
	 * @param string $expected
	 * @param string $text
	 * @param bool   $encode
	 *
	 * @dataProvider provider_standard_pdf_mergetags_no_permalinks
	 * @dataProvider provider_modifier_pdf_mergetags_no_permalinks
	 */
	public function test_process_pdf_mergetags( $expected, $text, $encode = true ) {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		// Provider strings use the legacy entry-ID slot (lid=1 or /1/). Substitute
		// with the per-class fixture's actual entry ID before comparing.
		$expected = strtr( $expected, [ 'lid=1' => "lid={$entry['id']}", '/1/' => "/{$entry['id']}/" ] );

		$results = $this->model->process_pdf_mergetags( $text, $form, $entry, $encode );

		$this->assertSame( $expected, $results );
	}

	public function provider_standard_pdf_mergetags_permalinks(): array {
		return [

			/* Valid single tag */
			[
				'expected' => 'http://example.org/pdf/556690c67856b/1/',
				'text'     => '{My First PDF Template (Copy):pdf:556690c67856b}',
			],

			[
				'expected' => 'http://example.org/pdf/556690c67856b/1/',
				'text'     => '{:pdf:556690c67856b}',
			],

			[
				'expected' => 'This is my content<br> It is awesome<br><br>http://example.org/pdf/556690c67856b/1/',
				'text'     => 'This is my content<br> It is awesome<br><br>{:pdf:556690c67856b}',
			],

			[
				'expected' => 'This is my content<br> It is awesome<br><br>http://example.org/pdf/556690c67856b/1/',
				'text'     => 'This is my content<br> It is awesome<br><br>{:pdf:556690c67856b}',
				'encode'   => false,
			],

			[
				'expected' => "This is my content\n It is awesome\n\nhttp://example.org/pdf/556690c67856b/1/",
				'text'     => "This is my content\n It is awesome\n\n{:pdf:556690c67856b}",
			],

			/* Invalid format */
			[
				'expected' => ':pdf:556690c67856b}',
				'text'     => ':pdf:556690c67856b}',
			],

			[
				'expected' => '{:pdf:556690c67856b',
				'text'     => '{:pdf:556690c67856b',
			],

			[
				'expected' => ':pdf:556690c67856b',
				'text'     => ':pdf:556690c67856b',
			],

			/* PDF Config not active */
			[
				'expected' => '',
				'text'     => '{Not Active:pdf:556690c8d7f82}',
			],

			[
				'expected' => 'My content ',
				'text'     => 'My content {Not Active:pdf:556690c8d7f82}',
			],

			[
				'expected' => 'My content<br><br>Other Stuff',
				'text'     => 'My content<br><br>{Not Active:pdf:556690c8d7f82}<br>Other Stuff',
			],

			[
				'expected' => 'My content<br /><br>Other Stuff',
				'text'     => 'My content<br /><br>{Not Active:pdf:556690c8d7f82}<br>Other Stuff',
			],

			[
				'expected' => "My content\n\nOther Stuff",
				'text'     => "My content\n\n{Not Active:pdf:556690c8d7f82}\nOther Stuff",
			],

			/* Conditional logic failed */
			[
				'expected' => '',
				'text'     => '{Conditional Failed:pdf:555ad84787d7e}',
			],

			/* Multiple tags */
			[
				'expected' => "My Content goes here\n\nhttp://example.org/pdf/556690c67856b/1/\n",
				'text'     => "My Content goes here\n\n{Not Active:pdf:556690c8d7f82}\n{My First PDF Template (Copy):pdf:556690c67856b}\n{Conditional Failed:pdf:555ad84787d7e}",
			],
		];
	}

	public function provider_modifier_pdf_mergetags_permalinks(): array {
		return [

			/* Download */
			[
				'expected' => 'http://example.org/pdf/556690c67856b/1/download/',
				'text'     => '{Label:pdf:556690c67856b:download}',
			],

			/* Print */
			[
				'expected' => 'http://example.org/pdf/556690c67856b/1/?print=1',
				'text'     => '{Label:pdf:556690c67856b:print}',
			],

			/* Print and Download (any order) */
			[
				'expected' => 'http://example.org/pdf/556690c67856b/1/download/?print=1',
				'text'     => '{Label:pdf:556690c67856b:download:print}',
			],

			[
				'expected' => 'http://example.org/pdf/556690c67856b/1/download/?print=1',
				'text'     => '{Label:pdf:556690c67856b:print:download}',
			],
		];
	}

	/**
	 * Check for signed URL when processing merge tag
	 *
	 * @param string $text
	 *
	 * @dataProvider provider_signed_modifier_pdf_mergetags
	 */
	public function test_process_pdf_mergetags_signed_no_permalink( $text ) {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		$this->model->mark_trusted_pdf_mergetags( $text, $form, $entry );
		$results = $this->model->process_pdf_mergetags( $text, $form, $entry, false );

		$this->assertStringContainsString( '&signature=', $results );
		$this->assertStringContainsString( '&expires=', $results );

		if ( strpos( $text, 'download' ) !== false ) {
			$this->assertStringContainsString( 'action=download', $results );
		}

		if ( strpos( $text, 'print' ) !== false ) {
			$this->assertStringContainsString( '&print=1', $results );
		}
	}

	/**
	 * Check for signed URL when processing merge tag
	 *
	 * @param string $text
	 *
	 * @dataProvider provider_signed_modifier_pdf_mergetags
	 */
	public function test_process_pdf_mergetags_signed_permalink( $text ) {
		global $wp_rewrite;

		$old_permalink_structure = get_option( 'permalink_structure' );
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules();

		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		$this->model->mark_trusted_pdf_mergetags( $text, $form, $entry );
		$results = $this->model->process_pdf_mergetags( $text, $form, $entry, false );

		$this->assertStringContainsString( 'signature=', $results );
		$this->assertStringContainsString( 'expires=', $results );

		if ( strpos( $text, 'download' ) !== false ) {
			$this->assertStringContainsString( '/download/', $results );
		}

		if ( strpos( $text, 'print' ) !== false ) {
			$this->assertStringContainsString( '?print=1', $results );
		}

		$wp_rewrite->set_permalink_structure( $old_permalink_structure );
		flush_rewrite_rules();
	}

	public function provider_signed_modifier_pdf_mergetags(): array {
		return [
			[ '{Label:pdf:556690c67856b:signed}' ],
			[ '{Label:pdf:556690c67856b:signed,1 day}' ],
			[ '{Label:pdf:556690c67856b:signed,3 weeks}' ],
			[ '{Label:pdf:556690c67856b:signed,5 months}' ],
			[ '{Label:pdf:556690c67856b:download:signed}' ],
			[ '{Label:pdf:556690c67856b:print:signed}' ],
			[ '{Label:pdf:556690c67856b:download:print:signed}' ],
			[ '{Label:pdf:556690c67856b:print:download:signed}' ],
			[ '{Label:pdf:556690c67856b:download:signed,3 weeks}' ],
			[ '{Label:pdf:556690c67856b:print:signed,1 day}' ],
			[ '{Label:pdf:556690c67856b:download:print:signed,5 months}' ],
			[ '{Label:pdf:556690c67856b:print:download:signed,1 year}' ],
			[ '{Label:pdf:556690c67856b:signed:download}' ],
			[ '{Label:pdf:556690c67856b:signed,1 day:download}' ],
			[ '{Label:pdf:556690c67856b:signed,3 weeks:print}' ],
			[ '{Label:pdf:556690c67856b:signed,5 months:print:download}' ],
			[ '{Label:pdf:556690c67856b:signed,5 months:download:print}' ],
		];
	}

	public function test_signed_pdf_mergetag_needs_trust_or_a_user_who_can_view_it() {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );
		$tag   = '{Label:pdf:556690c67856b:signed}';
		wp_set_current_user( 0 );

		$this->assertStringNotContainsString( 'signature=', $this->model->process_pdf_mergetags( $tag, $form, $entry, false ) );

		/* Each trusted tag signs once */
		$this->model->mark_trusted_pdf_mergetags( $tag, $form, $entry );
		$this->assertStringContainsString( 'signature=', $this->model->process_pdf_mergetags( $tag, $form, $entry, false ) );
		$this->assertStringNotContainsString( 'signature=', $this->model->process_pdf_mergetags( $tag, $form, $entry, false ) );

		/* Trust is per entry */
		$this->model->mark_trusted_pdf_mergetags( $tag, $form, [ 'id' => $entry['id'] + 1 ] );
		$this->assertStringNotContainsString( 'signature=', $this->model->process_pdf_mergetags( $tag, $form, $entry, false ) );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$this->assertStringContainsString( 'signature=', $this->model->process_pdf_mergetags( $tag, $form, $entry, false ) );

		wp_set_current_user( 0 );
	}

	public function test_signed_pdf_mergetag_in_a_field_value_is_not_trusted() {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );
		$tag   = '{Label:pdf:556690c67856b:signed}';
		wp_set_current_user( 0 );

		$this->assertStringContainsString( 'signature=', \GFCommon::replace_variables( $tag, $form, $entry ) );

		/* A tag in a field value isn't trusted */
		$field               = $this->get_text_field( $form );
		$entry[ $field->id ] = $tag;
		foreach ( [ 'standard' => '{all_fields}', 'administrative' => '{all_fields:admin}' ] as $visibility => $all_fields ) {
			$field->visibility = $visibility;

			$output = \GFCommon::replace_variables( $all_fields, $form, $entry );
			$this->assertStringContainsString( 'pid=556690c67856b', $output );
			$this->assertStringNotContainsString( 'signature=', $output );
		}

		$untrusted = GPDFAPI::get_mvc_class( 'Model_Signed_Url_Trust' )->run_untrusted(
			function () use ( $tag, $form, $entry ) {
				return \GFCommon::replace_variables( $tag, $form, $entry );
			}
		);
		$this->assertStringNotContainsString( 'signature=', $untrusted );
	}

	private function get_text_field( $form ) {
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'text' ) {
				return $field;
			}
		}

		$this->fail( 'No text field in the form' );
	}

	public function test_add_field_map_choices() {
		$form          = $this->form( 'all-form-fields' );
		$test_fields[] = [
			'label'   => 'Entry Properties',
			'choices' => [],
		];
		$fields        = $this->model->add_field_map_choices( $test_fields, $form['id'], [], [] );
		$pdfs          = $fields[1]['choices'];

		$this->assertCount( 12, $pdfs );

		$this->assertContainsEquals( 'My First PDF Template', $pdfs[0] );
		$this->assertContainsEquals( '{My First PDF Template:pdf:555ad84787d7e}', $pdfs[0] );

		$this->assertContainsEquals( 'My First PDF Template Signed (+1 week)', $pdfs[1] );
		$this->assertContainsEquals( '{My First PDF Template:pdf:555ad84787d7e:signed,1 week}', $pdfs[1] );

		$this->assertContainsEquals( 'My First PDF Template Signed (+1 month)', $pdfs[2] );
		$this->assertContainsEquals( '{My First PDF Template:pdf:555ad84787d7e:signed,1 month}', $pdfs[2] );

		$this->assertContainsEquals( 'My First PDF Template Signed (+1 year)', $pdfs[3] );
		$this->assertContainsEquals( '{My First PDF Template:pdf:555ad84787d7e:signed,12 months}', $pdfs[3] );
	}

	public function test_process_field_value() {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		$this->assertEmpty( $this->model->process_field_value( '', $form, $entry, '{My First PDF Template:pdf:555ad84787d7e}' ) );

		$this->assertNotFalse( filter_var( $this->model->process_field_value( '', $form, $entry, '{My First PDF Template (copy):pdf:556690c67856b}', FILTER_VALIDATE_URL ) ) );
		$this->assertNotFalse( filter_var( $this->model->process_field_value( '', $form, $entry, '{My First PDF Template (copy):pdf:556690c67856b:signed,1 week}', FILTER_VALIDATE_URL ) ) );
		$this->assertNotFalse( filter_var( $this->model->process_field_value( '', $form, $entry, '{My First PDF Template (copy):pdf:556690c67856b:signed,1 month}', FILTER_VALIDATE_URL ) ) );
		$this->assertNotFalse( filter_var( $this->model->process_field_value( '', $form, $entry, '{My First PDF Template (copy):pdf:556690c67856b:signed,12 months}', FILTER_VALIDATE_URL ) ) );

		$this->assertStringNotContainsString( 'signature=', $this->model->process_field_value( '', $form, $entry, '{My First PDF Template (copy):pdf:556690c67856b}' ) );
		$this->assertStringContainsString( 'signature=', $this->model->process_field_value( '', $form, $entry, '{My First PDF Template (copy):pdf:556690c67856b:signed,1 week}' ) );
		$this->assertStringContainsString( 'signature=', $this->model->process_field_value( '', $form, $entry, '{My First PDF Template (copy):pdf:556690c67856b:signed,1 month}' ) );
		$this->assertStringContainsString( 'signature=', $this->model->process_field_value( '', $form, $entry, '{My First PDF Template (copy):pdf:556690c67856b:signed,12 months}' ) );

	}

	/* Test if fields does not contain Entry Properties label. */
	public function test_empty_field_map_choices() {
		$form   = $this->form( 'all-form-fields' );
		$fields = $this->model->add_field_map_choices( [], $form['id'], [], [] );
		$this->assertEmpty( $fields );
	}

	/* Test if there are no pdf template included on the form . */
	public function test_no_pdf_template() {
		$form          = $this->form( 'all-form-fields' );
		$test_fields[] = [
			'label'   => 'Entry Properties',
			'choices' => [],
		];

		foreach ( \GPDFAPI::get_form_pdfs( $form['id'] ) as $pdf ) {
			\GPDFAPI::delete_pdf( $form['id'], $pdf['id'] );
		}

		$fields = $this->model->add_field_map_choices( $test_fields, $form['id'], [], [] );
		$this->assertCount( 1, $fields );
	}

}
