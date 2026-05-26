<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Rating extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}


	protected function rating_field(): \GF_Field {
		$form = $this->form( 'all-form-fields' );
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'survey' && $field->inputType === 'rating' ) {
				return $field;
			}
		}
		$this->fail( 'Rating survey field not found in all-form-fields fixture.' );
	}

	public function test_value_returns_array_with_matching_choice_label() {
		$entry     = $this->entry( 'all-form-fields' );
		$pdf_field = new Field_Rating( $this->rating_field(), $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$value = $pdf_field->value();

		$this->assertIsArray( $value );
		$this->assertNotEmpty( $value );
		$this->assertContains( 'Pretty good', $value );
	}

	public function test_form_data_nests_value_under_survey_rating_key() {
		$entry     = $this->entry( 'all-form-fields' );
		$gf_field  = $this->rating_field();
		$pdf_field = new Field_Rating( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$data = $pdf_field->form_data();

		$this->assertArrayHasKey( 'survey', $data );
		$this->assertArrayHasKey( 'rating', $data['survey'] );
		$this->assertArrayHasKey( $gf_field->id, $data['survey']['rating'] );
	}

	public function test_empty_entry_produces_empty_value_array() {
		$form      = $this->form( 'all-form-fields' );
		$entry     = [ 'id' => 0, 'form_id' => $form['id'] ];
		$pdf_field = new Field_Rating( $this->rating_field(), $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertSame( [], $pdf_field->value() );
	}
}
