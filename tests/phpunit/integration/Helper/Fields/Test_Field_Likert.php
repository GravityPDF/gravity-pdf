<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Likert extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ] );
	}


	private function make_likert_field( bool $multi_row = false ): array {
		$form = $this->form( 'all-form-fields' );

		foreach ( $form['fields'] as $field ) {
			if ( $field->type !== 'survey' || ! isset( $field->inputType ) || $field->inputType !== 'likert' ) {
				continue;
			}

			if ( $multi_row ? ! empty( $field->inputs ) : empty( $field->inputs ) ) {
				return [ $field, $form ];
			}
		}

		$this->fail( sprintf( 'No %s likert field found in all-form-fields fixture', $multi_row ? 'multi-row' : 'single-row' ) );
	}

	public function test_value_returns_col_and_row_keys_for_single_row_likert() {
		[ $gf_field, $form ] = $this->make_likert_field();

		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			(string) $gf_field->id => 'glikertcol2636762f85',
		];

		$pdf_field = new Field_Likert( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$value     = $pdf_field->value();

		$this->assertArrayHasKey( 'col', $value );
		$this->assertArrayHasKey( 'row', $value );
		$this->assertContains( 'selected', $value['row'] );
	}

	public function test_is_empty_when_no_choice_selected() {
		[ $gf_field, $form ] = $this->make_likert_field();

		$entry     = [ 'id' => 0, 'form_id' => $form['id'] ];
		$pdf_field = new Field_Likert( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertTrue( $pdf_field->is_empty() );
	}

	public function test_form_data_uses_survey_likert_key() {
		[ $gf_field, $form ] = $this->make_likert_field();

		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			(string) $gf_field->id => 'glikertcol2636762f85',
		];

		$pdf_field = new Field_Likert( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$form_data = $pdf_field->form_data();

		$this->assertArrayHasKey( 'survey', $form_data );
		$this->assertArrayHasKey( 'likert', $form_data['survey'] );
		$this->assertArrayHasKey( $gf_field->id, $form_data['survey']['likert'] );
	}

	public function test_value_multi_row_contains_rows_key() {
		[ $gf_field, $form ] = $this->make_likert_field( true );

		$entry = [ 'id' => 0, 'form_id' => $form['id'] ];

		$pdf_field = new Field_Likert( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$value     = $pdf_field->value();

		$this->assertArrayHasKey( 'rows', $value );
	}
}
