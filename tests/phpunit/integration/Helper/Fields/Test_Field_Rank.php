<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Rank extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}


	protected function rank_field(): \GF_Field {
		$form = $this->form( 'all-form-fields' );
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'survey' && $field->inputType === 'rank' ) {
				return $field;
			}
		}
		$this->fail( 'Rank survey field not found in all-form-fields fixture.' );
	}

	public function test_value_returns_array_of_choice_labels_in_entry_order() {
		$entry     = $this->entry( 'all-form-fields' );
		$pdf_field = new Field_Rank( $this->rank_field(), $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$value = $pdf_field->value();

		$this->assertIsArray( $value );
		$this->assertNotEmpty( $value );
		$this->assertContains( 'Rank Fourth Choce', $value );
	}

	public function test_value_first_element_matches_entry_first_ranked_choice() {
		$entry     = $this->entry( 'all-form-fields' );
		$pdf_field = new Field_Rank( $this->rank_field(), $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$value = $pdf_field->value();

		$this->assertSame( 'Rank Fourth Choce', $value[0] );
	}

	public function test_form_data_nests_value_under_survey_rank_key() {
		$entry     = $this->entry( 'all-form-fields' );
		$gf_field  = $this->rank_field();
		$pdf_field = new Field_Rank( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$data = $pdf_field->form_data();

		$this->assertArrayHasKey( 'survey', $data );
		$this->assertArrayHasKey( 'rank', $data['survey'] );
		$this->assertArrayHasKey( $gf_field->id, $data['survey']['rank'] );
	}
}
