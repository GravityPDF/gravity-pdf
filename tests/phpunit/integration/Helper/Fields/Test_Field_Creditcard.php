<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_CreditCard;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Creditcard extends TestCase {

	/**
	 * Returns a GF_Field_CreditCard with the standard 5-input configuration
	 * that GF expects (card number, exp month, exp year, card type, cardholder name).
	 * Without inputs, GF_Field_CreditCard::get_entry_inputs() crashes iterating null.
	 */
	private function make_cc_field( int $id = 1 ): GF_Field_CreditCard {
		return new GF_Field_CreditCard( [
			'id'     => $id,
			'inputs' => [
				[ 'id' => "{$id}.1", 'label' => 'Card Number' ],
				[ 'id' => "{$id}.2", 'label' => 'Expiration Month' ],
				[ 'id' => "{$id}.3", 'label' => 'Expiration Year' ],
				[ 'id' => "{$id}.4", 'label' => 'Card Type' ],
				[ 'id' => "{$id}.5", 'label' => 'Cardholder Name' ],
			],
		] );
	}

	public function test_html_renders_masked_number_and_card_type() {
		$gf_field = $this->make_cc_field();

		/*
		 * GF stores only the last-four digits and the card type in the entry after
		 * payment processing; the full PAN is never persisted. The subfield keys
		 * are 1.1 (masked number) and 1.4 (card type).
		 */
		$entry = [
			'id'      => 0,
			'form_id' => 0,
			'1.1'     => 'XXXX XXXX XXXX 1234',
			'1.4'     => 'Visa',
		];

		$pdf_field = new Field_Creditcard( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();
		$this->assertStringContainsString( 'XXXX XXXX XXXX 1234', $html );
		$this->assertStringContainsString( 'Visa', $html );
	}

	public function test_value_returns_type_and_number_keys() {
		$gf_field = $this->make_cc_field();

		$entry = [
			'id'      => 0,
			'form_id' => 0,
			'1.1'     => 'XXXX XXXX XXXX 5678',
			'1.4'     => 'Mastercard',
		];

		$pdf_field = new Field_Creditcard( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$value     = $pdf_field->value();

		$this->assertArrayHasKey( 'type', $value );
		$this->assertArrayHasKey( 'number', $value );
		$this->assertSame( 'Mastercard', $value['type'] );
		$this->assertSame( 'XXXX XXXX XXXX 5678', $value['number'] );
	}

	public function test_html_omits_empty_subfields() {
		$gf_field  = $this->make_cc_field();
		$entry     = [ 'id' => 0, 'form_id' => 0, '1.4' => 'Visa' ];
		$pdf_field = new Field_Creditcard( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();
		$this->assertStringContainsString( 'Visa', $html );
		$this->assertStringNotContainsString( '<br>', $html );
	}
}
