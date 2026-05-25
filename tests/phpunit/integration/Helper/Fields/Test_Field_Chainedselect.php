<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields {

	use GFPDF\Tests\Integration\TestCase;

	/**
	 * @group   helper
	 * @group   fields
	 */
	class Test_Field_Chainedselect extends TestCase {

		private function make_gf_field( int $id = 5, string $label = 'Location' ): \GF_Chained_Field_Select {
			return new \GF_Chained_Field_Select( [
				'id'      => $id,
				'label'   => $label,
				'choices' => [
					[
						'text'    => 'Australia',
						'value'   => 'Australia',
						'choices' => [
							[ 'text' => 'NSW', 'value' => 'NSW', 'choices' => [] ],
						],
					],
				],
				'inputs'  => [
					[ 'id' => '5.1', 'label' => 'Level 1' ],
					[ 'id' => '5.2', 'label' => 'Level 2' ],
				],
			] );
		}

		public function test_form_data_keys_contain_field_id_and_label() {
			$gf_field = $this->make_gf_field();
			$entry    = [
				'id'      => 0,
				'form_id' => 0,
				'5.1'     => 'Australia',
				'5.2'     => 'NSW',
			];

			$pdf_field = new Field_Chainedselect( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
			$form_data = $pdf_field->form_data();

			$this->assertArrayHasKey( 'field', $form_data );
			$this->assertArrayHasKey( 5, $form_data['field'] );
			$this->assertArrayHasKey( 'Location', $form_data['field'] );
		}

		public function test_value_contains_selected_items() {
			$gf_field = $this->make_gf_field();
			$entry    = [
				'id'      => 0,
				'form_id' => 0,
				'5.1'     => 'Australia',
				'5.2'     => 'NSW',
			];

			$pdf_field = new Field_Chainedselect( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
			$value     = $pdf_field->value();

			$this->assertIsArray( $value );
			$this->assertContains( 'Australia', $value );
		}

		public function test_is_empty_when_no_selections() {
			$gf_field  = $this->make_gf_field();
			$entry     = [ 'id' => 0, 'form_id' => 0 ];
			$pdf_field = new Field_Chainedselect( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

			$this->assertTrue( $pdf_field->is_empty() );
		}
	}
}

namespace {

	if ( ! class_exists( 'GF_Chained_Field_Select' ) ) {
		class GF_Chained_Field_Select extends \GF_Field {
			public $type = 'chainedselect';
		}
	}
}
