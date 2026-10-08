<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Checkbox;
use GFPDF\Tests\Integration\TestCase;


/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Checkbox extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ] );
	}


	private function make_field(): GF_Field_Checkbox {
		return new GF_Field_Checkbox( $this->field_from_fixture( 'checkbox' ) );
	}

	public function test_html_renders_checked_choices_as_list() {
		$gf_field = $this->make_field();
		$form     = $this->form( 'all-form-fields' );

		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'6.2'     => 'Checkbox Choice 2',
			'6.3'     => 'Checkbox Choice 3',
		];

		$pdf_field = new Field_Checkbox( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();
		$this->assertStringContainsString( '<ul class="bulleted checkbox">', $html );
		$this->assertStringContainsString( 'Checkbox Choice 2', $html );
		$this->assertStringContainsString( 'Checkbox Choice 3', $html );
	}

	public function test_html_is_empty_wrapper_when_nothing_checked() {
		$gf_field = $this->make_field();
		$form     = $this->form( 'all-form-fields' );

		$entry     = [ 'id' => 0, 'form_id' => $form['id'] ];
		$pdf_field = new Field_Checkbox( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertTrue( $pdf_field->is_empty() );
	}

	public function test_form_data_contains_value_and_name_keys() {
		$gf_field = $this->make_field();
		$form     = $this->form( 'all-form-fields' );

		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'6.2'     => 'Checkbox Choice 2',
		];

		$pdf_field = new Field_Checkbox( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$form_data = $pdf_field->form_data();

		$this->assertArrayHasKey( 6, $form_data['field'] );
		$this->assertArrayHasKey( '6_name', $form_data['field'] );
		$this->assertIsArray( $form_data['field'][6] );
	}

	public function test_show_value_filter_uses_value_instead_of_label() {
		$gf_field = $this->make_field();
		$form     = $this->form( 'all-form-fields' );

		/*
		 * Choice 2: label = "Checkbox Choice 2 Text", value = "Checkbox Choice 2".
		 * When the filter is active the value is rendered, not the label.
		 * Assert: the value token appears AND the label-only suffix (" Text") is absent.
		 */
		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'6.2'     => 'Checkbox Choice 2',
		];

		$pdf_field = new Field_Checkbox( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		add_filter( 'gfpdf_show_field_value', '__return_true' );
		$html = $pdf_field->html();
		remove_filter( 'gfpdf_show_field_value', '__return_true' );

		$this->assertStringContainsString( 'Checkbox Choice 2', $html );
		$this->assertStringNotContainsString( 'Checkbox Choice 2 Text', $html );
	}

	public function test_merge_tags_only_processed_in_choices() {
		$form_id = $this->gf_factory()->form->create( [], [ 'title' => 'Checkbox Tags', 'fields' => [ new GF_Field_Checkbox( [
			'id'      => 1,
			'choices' => [ [ 'text' => 'Choice {form_id}', 'value' => 'a' ] ],
			'inputs'  => [ [ 'id' => '1.1' ], [ 'id' => '1.2' ] ],
		] ) ] ] );

		$entry = [ 'id' => 0, 'form_id' => $form_id, '1.1' => 'a', '1.2' => 'Posted {form_id}' ];
		$items = ( new Field_Checkbox( \GFAPI::get_form( $form_id )['fields'][0], $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) )->value();

		$this->assertSame( [ "Choice $form_id", 'Posted {form_id}' ], array_column( $items, 'label' ) );
	}

	public function test_choice_with_html_still_processed_as_gravity_forms_saved_it() {
		$form_id = $this->gf_factory()->form->create( [], [ 'title' => 'Checkbox Html', 'fields' => [ new GF_Field_Checkbox( [
			'id'      => 1,
			'choices' => [ [ 'text' => 'A', 'value' => '<b>a</b> & co' ] ],
			'inputs'  => [ [ 'id' => '1.1' ] ],
		] ) ] ] );

		/* Gravity Forms saves the choice's value sanitised */
		$entry = [ 'id' => 0, 'form_id' => $form_id, '1.1' => '<b>a</b> &amp; co' ];
		$items = ( new Field_Checkbox( \GFAPI::get_form( $form_id )['fields'][0], $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) )->value();

		/* Its HTML is kept */
		$this->assertSame( '<b>a</b> &amp; co', $items[0]['value'] );
	}
}
