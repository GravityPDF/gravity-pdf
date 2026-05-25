<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Checkbox;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Tos extends TestCase {

	protected function make_tos_field( string $checked_value, string $terms = 'Please agree to our terms.' ): Field_Tos {
		$gf_field                          = new GF_Field_Checkbox( [
			'id'      => 1,
			'type'    => 'checkbox',
			'inputs'  => [
				[ 'id' => '1.1', 'label' => 'I agree' ],
			],
			'choices' => [
				[ 'text' => 'I agree', 'value' => 'I agree' ],
			],
		] );
		$gf_field->gwtermsofservice_terms = $terms;

		$entry = [
			'id'      => 0,
			'form_id' => 0,
			'1.1'     => $checked_value,
		];

		return new Field_Tos( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
	}

	public function test_is_empty_always_returns_false() {
		$pdf_field = $this->make_tos_field( '' );
		$this->assertFalse( $pdf_field->is_empty() );
	}

	public function test_html_contains_terms_text() {
		$pdf_field = $this->make_tos_field( 'I agree', 'You must accept the terms.' );
		$this->assertStringContainsString( 'You must accept the terms.', $pdf_field->html() );
	}

	public function test_html_shows_tick_when_accepted() {
		$pdf_field = $this->make_tos_field( 'I agree' );
		$this->assertStringContainsString( 'terms-of-service-tick', $pdf_field->html() );
		$this->assertStringContainsString( '&#10004;', $pdf_field->html() );
	}

	public function test_html_shows_cross_when_not_accepted() {
		$pdf_field = $this->make_tos_field( '' );
		$this->assertStringContainsString( '&#10006;', $pdf_field->html() );
	}
}
