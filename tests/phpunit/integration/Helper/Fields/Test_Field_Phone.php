<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Phone;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Phone extends TestCase {

	/**
	 * A US number in the Gravity Forms 3.0 International (formatted) format
	 */
	private const US_NUMBER = '{"country":"US","national":"2015551232","formatted":"(201) 555-1232","e164":"+12015551232"}';

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}

	/**
	 * Build a Field_Phone holding the passed entry value
	 *
	 * @param string $value          The raw value stored in the entry
	 * @param array  $field_settings Additional GF_Field_Phone settings, eg. phoneFormat or showCountryCode
	 *
	 * @return Field_Phone
	 */
	protected function get_field( $value, $field_settings = [] ) {
		$field = new GF_Field_Phone(
			array_merge(
				[
					'id'          => 1,
					'label'       => 'Phone',
					'phoneFormat' => 'formatted',
				],
				$field_settings
			)
		);

		$entry = [
			'id'      => 0,
			'form_id' => 0,
			'1'       => $value,
		];

		return new Field_Phone( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
	}

	public function test_html_contains_phone_number() {
		$entry    = $this->entry( 'all-form-fields' );
		$gf_field = new GF_Field_Phone( $this->field_from_fixture( 'phone' ) );

		$pdf_field = new Field_Phone( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringContainsString( '(555) 678-1210', $pdf_field->html() );
	}

	public function test_value_returns_entry_phone_number() {
		$entry    = $this->entry( 'all-form-fields' );
		$gf_field = new GF_Field_Phone( $this->field_from_fixture( 'phone' ) );

		$pdf_field = new Field_Phone( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertSame( '(555) 678-1210', $pdf_field->value() );
	}

	public function test_empty_entry_produces_empty_value() {
		$form  = $this->form( 'all-form-fields' );
		$entry = [ 'id' => 0, 'form_id' => $form['id'] ];

		$gf_field = new GF_Field_Phone( $this->field_from_fixture( 'phone' ) );

		$pdf_field = new Field_Phone( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertSame( '', $pdf_field->value() );
	}

	/**
	 * The International (formatted) number is stored as JSON, and is broken into its individual parts
	 */
	public function test_value_international() {
		$field = $this->get_field( self::US_NUMBER );

		$this->assertSame(
			[
				'country'   => 'US',
				'dial_code' => '+1',
				'national'  => '2015551232',
				'formatted' => '(201) 555-1232',
				'e164'      => '+12015551232',
			],
			$field->value()
		);
	}

	/**
	 * The dial code isn't available to PHP, so it's derived from the E.164 and national numbers
	 */
	public function test_value_international_multi_digit_dial_code() {
		$field = $this->get_field( '{"country":"gb","national":"2079460958","formatted":"020 7946 0958","e164":"+442079460958"}' );

		$value = $field->value();

		$this->assertSame( 'GB', $value['country'] );
		$this->assertSame( '+44', $value['dial_code'] );
	}

	/**
	 * Entries saved while the field was International (formatted) still display correctly after the field is swapped
	 * to a format that doesn't store JSON
	 */
	public function test_value_international_after_format_swap() {
		$field = $this->get_field( self::US_NUMBER, [ 'phoneFormat' => 'international' ] );

		$this->assertStringContainsString( '<div class="value">US +1 (201) 555-1232</div>', $field->html() );
		$this->assertSame( '+12015551232', $field->form_data()['phone'][1]['e164'] );
	}

	/**
	 * A number that isn't stored as JSON is passed through as a string
	 */
	public function test_value_standard() {
		$field = $this->get_field( '(555) 678-1210', [ 'phoneFormat' => 'standard' ] );

		$this->assertSame( '(555) 678-1210', $field->value() );
	}

	/**
	 * The ISO country code and dial code are shown in front of the number
	 */
	public function test_html_shows_country_and_dial_code() {
		$field = $this->get_field( self::US_NUMBER, [ 'showCountryCode' => true ] );

		$this->assertStringContainsString( '<div class="value">US +1 (201) 555-1232</div>', $field->html() );
	}

	/**
	 * Only the ISO country code is shown when the field's dial code setting is disabled
	 */
	public function test_html_hides_dial_code() {
		$field = $this->get_field( self::US_NUMBER, [ 'showCountryCode' => false ] );

		$this->assertStringContainsString( '<div class="value">US (201) 555-1232</div>', $field->html() );
	}

	/**
	 * The dial code is shown by default, matching how Gravity Forms renders the field
	 */
	public function test_html_shows_dial_code_by_default() {
		$field = $this->get_field( self::US_NUMBER );

		$this->assertStringContainsString( '<div class="value">US +1 (201) 555-1232</div>', $field->html() );
	}

	/**
	 * $form_data['field'] holds the displayed number, and $form_data['phone'] the individual parts
	 */
	public function test_form_data_international() {
		$field = $this->get_field( self::US_NUMBER );

		$form_data = $field->form_data();

		$this->assertSame( 'US +1 (201) 555-1232', $form_data['field'][1] );
		$this->assertSame( 'US +1 (201) 555-1232', $form_data['field']['1.Phone'] );
		$this->assertSame( 'US +1 (201) 555-1232', $form_data['field']['Phone'] );

		$this->assertSame( '+12015551232', $form_data['phone'][1]['e164'] );
		$this->assertSame( 'US', $form_data['phone'][1]['country'] );
	}

	/**
	 * The phone key is only added for International (formatted) numbers
	 */
	public function test_form_data_standard() {
		$field = $this->get_field( '(555) 678-1210', [ 'phoneFormat' => 'standard' ] );

		$form_data = $field->form_data();

		$this->assertSame( '(555) 678-1210', $form_data['field'][1] );
		$this->assertSame( '(555) 678-1210', $form_data['field']['1.Phone'] );
		$this->assertSame( '(555) 678-1210', $form_data['field']['Phone'] );

		$this->assertArrayNotHasKey( 'phone', $form_data );
	}

	/**
	 * JSON that isn't an International (formatted) number is left alone
	 */
	public function test_value_unrecognised_json() {
		$field = $this->get_field( '{"foo":"bar"}' );

		$this->assertSame( '{&quot;foo&quot;:&quot;bar&quot;}', $field->value() );
	}

	/**
	 * An empty field is still reported as empty
	 */
	public function test_is_empty() {
		$this->assertTrue( $this->get_field( '' )->is_empty() );
		$this->assertFalse( $this->get_field( self::US_NUMBER )->is_empty() );
	}
}
