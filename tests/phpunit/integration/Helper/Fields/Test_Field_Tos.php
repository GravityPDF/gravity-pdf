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

	/**
	 * @param string|string[] $checked_values One entry value per checkbox, starting at input 1.1
	 */
	protected function make_tos_field( $checked_values, string $terms = 'Please agree to our terms.' ): Field_Tos {
		$inputs = [];
		$entry  = [
			'id'      => 0,
			'form_id' => 0,
		];

		foreach ( array_values( (array) $checked_values ) as $i => $checked_value ) {
			$input_id           = '1.' . ( $i + 1 );
			$inputs[]           = [ 'id' => $input_id, 'label' => "Agreement $input_id" ];
			$entry[ $input_id ] = $checked_value;
		}

		$gf_field                         = new GF_Field_Checkbox( [
			'id'     => 1,
			'type'   => 'tos',
			'label'  => 'Terms',
			'inputs' => $inputs,
		] );
		$gf_field->gwtermsofservice_terms = $terms;

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

	public function test_html_shows_every_accepted_agreement() {
		$html = $this->make_tos_field( [ 'First agreement', '', 'Third agreement' ] )->html();

		$this->assertSame( 2, substr_count( $html, '&#10004;' ) );
		$this->assertStringContainsString( 'First agreement', $html );
		$this->assertStringContainsString( 'Third agreement', $html );
		$this->assertStringNotContainsString( '&#10006;', $html );
	}

	public function test_html_shows_later_agreement_when_first_not_accepted() {
		$pdf_field = $this->make_tos_field( [ '', 'Second agreement' ] );

		$this->assertFalse( $pdf_field->is_field_empty() );
		$this->assertStringContainsString( 'Second agreement', $pdf_field->html() );
		$this->assertStringNotContainsString( '&#10006;', $pdf_field->html() );
	}

	public function test_html_shows_cross_when_no_agreement_accepted() {
		$pdf_field = $this->make_tos_field( [ '', '' ] );

		$this->assertTrue( $pdf_field->is_field_empty() );
		$this->assertStringContainsString( '&#10006;', $pdf_field->html() );
	}

	public function test_agreements_are_escaped() {
		$this->assertSame( [ '&lt;b&gt;Agree&lt;/b&gt;' ], $this->make_tos_field( [ '<b>Agree</b>' ] )->agreements() );
	}

	public function test_form_data_includes_every_accepted_agreement() {
		$data = $this->make_tos_field( [ 'First agreement', '', 'Third agreement' ] )->form_data()['field'];

		$this->assertSame( 'First agreement', $data[1] );
		$this->assertSame( 'First agreement', $data['1.Terms'] );
		$this->assertSame( 'First agreement', $data['Terms'] );

		$expected = [ 'First agreement', 'Third agreement' ];
		$this->assertSame( $expected, $data['1_agreements'] );
		$this->assertSame( $expected, $data['1.Terms_agreements'] );
		$this->assertSame( $expected, $data['Terms_agreements'] );
	}
}
