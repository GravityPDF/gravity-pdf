<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

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
class Test_Field_Multi_Choice extends TestCase {
	/**
	 * Class-scoped form: structure is identical per test; only entries vary.
	 * Tracking the per-test entry IDs in tear_down() prevents orphan entry rows.
	 */
	private static $shared_form;

	private array $created_entry_ids = [];

	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		$choices = self::build_choices();

		self::$shared_form = ( new \GF_UnitTest_Factory() )->form->create_and_get( [
			'fields' => [
				[
					'id'                => 1,
					'label'             => 'Radio Multi Choice',
					'type'              => 'multi_choice',
					'inputType'         => 'radio',
					'choices'           => $choices[1]['choices'],
					'inputs'            => $choices[1]['inputs'],
					'enableOtherChoice' => true,
				],

				[
					'id'                => 2,
					'label'             => 'Checkbox Multi Choice',
					'type'              => 'multi_choice',
					'inputType'         => 'checkbox',
					'choices'           => $choices[2]['choices'],
					'inputs'            => $choices[2]['inputs'],
					'enableOtherChoice' => true,
				],
			],
		] );
	}

	public static function tear_down_after_class(): void {
		if ( self::$shared_form ) {
			\GFAPI::delete_form( self::$shared_form['id'] );
			self::$shared_form = null;
		}

		parent::tear_down_after_class();
	}

	private static function build_choices(): array {
		return [
			1 => self::get_choices( 1 ),
			2 => self::get_choices( 2 ),
		];
	}

	/**
	 * @var array
	 */
	protected $form;

	public function set_up(): void {
		parent::set_up();

		$this->form = self::$shared_form;
	}

	public function tear_down(): void {
		foreach ( $this->created_entry_ids as $id ) {
			\GFAPI::delete_entry( $id );
		}
		$this->created_entry_ids = [];

		parent::tear_down();
	}

	private function create_entry( array $data ): array {
		$id                        = $this->gf_factory()->entry->create( $data );
		$this->created_entry_ids[] = $id;

		return \GFAPI::get_entry( $id );
	}

	protected static function get_choices( $id ) {
		$choices = [
			[
				'text'  => 'Option 1',
				'value' => 'o1',
				'key'   => 'abc',
			],

			[
				'text'  => 'Option 2',
				'value' => 'o2',
				'key'   => 'def',
			],

			[
				'text'  => '<strong>Option</strong> 3',
				'value' => 'o3',
				'key'   => 'ghi',
			],

			[
				'text'  => 'Select an option',
				'value' => '',
				'key'   => 'jkl',
			],
		];

		$inputs = [
			[
				'id'    => $id . '.1',
				'label' => 'Option 1',
				'key'   => 'abc',
			],

			[
				'id'    => $id . '.2',
				'label' => 'Option 2',
				'key'   => 'def',
			],

			[
				'id'    => $id . '.3',
				'label' => 'Option 3',
				'key'   => 'ghi',
			],

			[
				'id'    => $id . '.4',
				'label' => 'Select an option',
				'key'   => 'jkl',
			],
		];

		return [
			'choices' => $choices,
			'inputs'  => $inputs,
		];
	}

	public function test_radio_html() {
		$entry = $this->create_entry( [
			'form_id' => $this->form['id'],
			'1'       => 'o2',
		] );

		$field     = $this->form['fields'][0];
		$pdf_field = new Field_Multi_Choice( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();
		$this->assertStringContainsString( '<div id="field-1" class="gfpdf-field gfpdf-radio ', $html );
		$this->assertStringContainsString( '<div class="label"><strong>Radio Multi Choice</strong></div><div class="value">Option 2</div>', $html );
	}

	public function test_radio_html_with_markup() {
		$entry = $this->create_entry( [
			'form_id' => $this->form['id'],
			'1'       => 'o3',
		] );

		$field     = $this->form['fields'][0];
		$pdf_field = new Field_Multi_Choice( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();
		$this->assertStringContainsString( '<div id="field-1" class="gfpdf-field gfpdf-radio ', $html );
		$this->assertStringContainsString( '<div class="label"><strong>Radio Multi Choice</strong></div><div class="value"><strong>Option</strong> 3</div>', $html );

		/* pass user-defined string and verify response is escaped in the PDF */
		$entry = $this->create_entry( [
			'form_id' => $this->form['id'],
			'1'       => '<em>My answer</em>',
		] );

		$pdf_field = new Field_Multi_Choice( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();
		$this->assertStringContainsString( '<div id="field-1" class="gfpdf-field gfpdf-radio ', $html );
		$this->assertStringContainsString( '<div class="label"><strong>Radio Multi Choice</strong></div><div class="value">&lt;em&gt;My answer&lt;/em&gt;</div>', $html );
	}

	public function test_radio_html_with_empty_value_but_not_label() {
		$entry = $this->create_entry( [
			'form_id' => $this->form['id'],
			'1'       => '',
		] );

		$field     = $this->form['fields'][0];
		$pdf_field = new Field_Multi_Choice( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();
		$this->assertStringContainsString( '<div id="field-1" class="gfpdf-field gfpdf-radio ', $html );
		$this->assertStringContainsString( '<div class="label"><strong>Radio Multi Choice</strong></div><div class="value">Select an option</div>', $html );
	}

	public function test_radio_form_data() {
		$entry = $this->create_entry( [
			'form_id' => $this->form['id'],
			'1'       => 'o2',
		] );

		$field     = $this->form['fields'][0];
		$pdf_field = new Field_Multi_Choice( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$form_data = $pdf_field->form_data();

		$this->assertSame( 'o2', $form_data['field'][1] );
		$this->assertSame( 'o2', $form_data['field']['1.Radio Multi Choice'] );
		$this->assertSame( 'o2', $form_data['field']['Radio Multi Choice'] );

		$this->assertSame( 'Option 2', $form_data['field']['1_name'] );
		$this->assertSame( 'Option 2', $form_data['field']['1.Radio Multi Choice_name'] );
		$this->assertSame( 'Option 2', $form_data['field']['Radio Multi Choice_name'] );
	}

	public function test_checkbox_html() {
		$entry = $this->create_entry( [
			'form_id' => $this->form['id'],
			'2.1'     => 'o1',
			'2.2'     => '',
			'2.3'     => 'o3',
		] );

		$field     = $this->form['fields'][1];
		$pdf_field = new Field_Multi_Choice( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();
		$this->assertStringContainsString( '<div id="field-2" class="gfpdf-field gfpdf-checkbox ', $html );
		$this->assertStringContainsString( '<div class="label"><strong>Checkbox Multi Choice</strong></div><div class="value"><ul class="bulleted checkbox"><li id="field-2-option-1">Option 1</li><li id="field-2-option-2"><strong>Option</strong> 3</li></ul></div>', $html );
	}

	public function test_checkbox_form_data() {
		$entry = $this->create_entry( [
			'form_id' => $this->form['id'],
			'2.1'     => 'o1',
			'2.2'     => '',
			'2.3'     => 'o3',
		] );

		$field     = $this->form['fields'][1];
		$pdf_field = new Field_Multi_Choice( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$form_data = $pdf_field->form_data();

		$this->assertSame( 'o1', $form_data['field'][2][0] );
		$this->assertSame( 'o3', $form_data['field'][2][1] );
		$this->assertSame( 'o1', $form_data['field']['2.Checkbox Multi Choice'][0] );
		$this->assertSame( 'o3', $form_data['field']['2.Checkbox Multi Choice'][1] );
		$this->assertSame( 'o1', $form_data['field']['Checkbox Multi Choice'][0] );
		$this->assertSame( 'o3', $form_data['field']['Checkbox Multi Choice'][1] );

		$this->assertSame( 'Option 1', $form_data['field']['2_name'][0] );
		$this->assertSame( '<strong>Option</strong> 3', $form_data['field']['2_name'][1] );
		$this->assertSame( 'Option 1', $form_data['field']['2.Checkbox Multi Choice_name'][0] );
		$this->assertSame( '<strong>Option</strong> 3', $form_data['field']['2.Checkbox Multi Choice_name'][1] );
		$this->assertSame( 'Option 1', $form_data['field']['Checkbox Multi Choice_name'][0] );
		$this->assertSame( '<strong>Option</strong> 3', $form_data['field']['Checkbox Multi Choice_name'][1] );
	}
}
