<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Quiz extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}


	protected function quiz_field_by_id( int $field_id ): \GF_Field {
		$form = $this->form( 'all-form-fields' );

		foreach ( $form['fields'] as $field ) {
			if ( (int) $field->id === $field_id ) {
				return $field;
			}
		}

		$this->fail( "Quiz field {$field_id} not found in all-form-fields fixture." );
	}

	public function test_value_returns_matching_choice_text_for_single_answer() {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		$gf_field  = $this->quiz_field_by_id( 24 );
		$pdf_field = new Field_Quiz( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$value = $pdf_field->value();

		$this->assertIsArray( $value );
		$this->assertSame( 'Quiz Dropdown - Second Choice', $value['text'] );
	}

	public function test_value_returns_empty_array_when_no_entry_match() {
		$form      = $this->form( 'all-form-fields' );
		$gf_field  = $this->quiz_field_by_id( 24 );
		$entry     = [ 'id' => 0, 'form_id' => $form['id'] ];
		$pdf_field = new Field_Quiz( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertSame( [], $pdf_field->value() );
	}

	public function test_value_includes_correctness_flag() {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		$gf_field  = $this->quiz_field_by_id( 24 );
		$pdf_field = new Field_Quiz( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$value = $pdf_field->value();

		$this->assertArrayHasKey( 'isCorrect', $value );
		$this->assertArrayHasKey( 'weight', $value );
	}
}
