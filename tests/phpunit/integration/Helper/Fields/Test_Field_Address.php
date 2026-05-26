<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Address;
use GFPDF\Tests\Integration\TestCase;


/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Address extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ] );
	}


	public function test_html_renders_street_and_city() {
		$form     = $this->form( 'all-form-fields' );
		$gf_field = null;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'address' ) {
				$gf_field = new GF_Field_Address( $field );
				break;
			}
		}

		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'15.1'    => '12 Address St',
			'15.2'    => 'Line 2',
			'15.3'    => 'Cityville',
			'15.4'    => 'Statesman',
			'15.5'    => '5000',
			'15.6'    => 'Chad',
		];

		$pdf_field = new Field_Address( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$html = $pdf_field->html();
		$this->assertStringContainsString( '12 Address St', $html );
		$this->assertStringContainsString( 'Cityville', $html );
		$this->assertStringContainsString( 'Chad', $html );
		$this->assertStringContainsString( '<br />', $html );
	}

	public function test_value_returns_keyed_array() {
		$form     = $this->form( 'all-form-fields' );
		$gf_field = null;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'address' ) {
				$gf_field = new GF_Field_Address( $field );
				break;
			}
		}

		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'15.1'    => '5 Main Rd',
			'15.3'    => 'Townsville',
			'15.6'    => 'Australia',
		];

		$pdf_field = new Field_Address( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$value     = $pdf_field->value();

		$this->assertArrayHasKey( 'street', $value );
		$this->assertArrayHasKey( 'city', $value );
		$this->assertArrayHasKey( 'country', $value );
		$this->assertSame( '5 Main Rd', $value['street'] );
		$this->assertSame( 'Townsville', $value['city'] );
		$this->assertSame( 'Australia', $value['country'] );
	}

	public function test_is_empty_when_all_inputs_blank() {
		$form     = $this->form( 'all-form-fields' );
		$gf_field = null;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'address' ) {
				$gf_field = new GF_Field_Address( $field );
				break;
			}
		}

		$entry     = [ 'id' => 0, 'form_id' => $form['id'] ];
		$pdf_field = new Field_Address( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertTrue( $pdf_field->is_empty() );
	}

	public function test_zip_before_city_format() {
		$form     = $this->form( 'all-form-fields' );
		$gf_field = null;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'address' ) {
				$gf_field = new GF_Field_Address( $field );
				break;
			}
		}

		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'15.3'    => 'Berlin',
			'15.5'    => '10115',
		];

		$pdf_field = new Field_Address( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		add_filter( 'gform_address_display_format', fn() => 'zip_before_city' );
		$html = $pdf_field->html();
		remove_all_filters( 'gform_address_display_format' );

		$this->assertStringContainsString( '10115', $html );
		$this->assertStringContainsString( 'Berlin', $html );
		$this->assertLessThan( strpos( $html, 'Berlin' ), strpos( $html, '10115' ), 'ZIP should appear before city in zip_before_city format' );
	}
}
