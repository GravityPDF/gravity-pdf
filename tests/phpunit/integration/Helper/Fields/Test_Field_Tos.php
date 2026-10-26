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

	public function test_html_single_agreement_markup_is_unchanged() {
		$expected = "<div id=\"field-1\" class=\"gfpdf-field gfpdf-tos \">\n" .
			"\t\t\t\t\t<div class=\"inner-container\"><div class=\"label\"><strong>Terms</strong></div><div class=\"value\">\n" .
			"\t\t\t<div class=\"terms-of-service-text\"><p>Please agree to our terms.</p>\n" .
			"</div>\t\t\t\n" .
			"\t\t<div class=\"terms-of-service-agreement\"><span class=\"terms-of-service-tick\" style=\"font-family:dejavusans,sans-serif\">&#10004;</span> I agree</div></div></div></div>";

		$this->assertSame( $expected, $this->make_tos_field( 'I agree' )->html() );
	}

	public function test_form_data_includes_every_accepted_agreement() {
		$data = $this->make_tos_field( [ 'First agreement', '', '<b>Third</b>' ] )->form_data()['field'];

		$this->assertSame( 'First agreement', $data[1] );
		$this->assertSame( 'First agreement', $data['1.Terms'] );
		$this->assertSame( 'First agreement', $data['Terms'] );

		$expected = [ 'First agreement', '&lt;b&gt;Third&lt;/b&gt;' ];
		$this->assertSame( $expected, $data['1_agreements'] );
		$this->assertSame( $expected, $data['1.Terms_agreements'] );
		$this->assertArrayNotHasKey( 'Terms_agreements', $data );
	}

	public function test_form_data_standard_keys_keep_first_input_value() {
		$data = $this->make_tos_field( [ '', 'Second agreement' ] )->form_data()['field'];

		$this->assertSame( '', $data[1] );
		$this->assertSame( [ 'Second agreement' ], $data['1_agreements'] );
	}

	public function test_agreements_ignore_values_outside_the_field_inputs() {
		add_filter(
			'gfpdf_field_value',
			function ( $value ) {
				return $value + [ '1.9' => 'Not a checkbox' ];
			}
		);

		$this->assertSame( [ 'I agree' ], $this->make_tos_field( 'I agree' )->form_data()['field']['1_agreements'] );
	}
}
