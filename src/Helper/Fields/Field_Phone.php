<?php

namespace GFPDF\Helper\Fields;

use Exception;
use GF_Field_Phone;
use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controls the display and output of a Gravity Form field
 *
 * @since 4.0
 */
class Field_Phone extends Helper_Abstract_Fields {

	/**
	 * Check the appropriate variables are parsed in send to the parent construct
	 *
	 * @param object               $field The GF_Field_* Object
	 * @param array                $entry The Gravity Forms Entry
	 *
	 * @param Helper_Abstract_Form $gform
	 * @param Helper_Misc          $misc
	 *
	 * @throws Exception
	 *
	 * @since 4.0
	 */
	public function __construct( $field, $entry, Helper_Abstract_Form $gform, Helper_Misc $misc ) {

		if ( ! is_object( $field ) || ! ( $field instanceof GF_Field_Phone ) ) {
			throw new Exception( '$field needs to be in instance of GF_Field_Phone' );
		}

		/* call our parent method */
		parent::__construct( $field, $entry, $gform, $misc );
	}

	/**
	 * Display the HTML version of this field
	 *
	 * @param string $value
	 * @param bool   $label
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function html( $value = '', $label = true ) {
		return parent::html( $this->get_display_value() );
	}

	/**
	 * Standardised method for returning the field's correct $form_data['field'] keys
	 *
	 * The International (formatted) number is also exposed, in its individual parts, under $form_data['phone']
	 *
	 * @return array
	 *
	 * @since 6.16.0
	 */
	public function form_data() {
		$value    = $this->get_display_value();
		$label    = $this->get_label();
		$field_id = (int) $this->field->id;

		$data = [
			'field' => [
				$field_id . '.' . $label => $value,
				$field_id                => $value,
				$label                   => $value,
			],
		];

		$phone = $this->value();
		if ( is_array( $phone ) ) {
			$data['phone'][ $field_id ] = $phone;
		}

		return $data;
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * Returns the individual parts of the number when the entry holds an International (formatted) number, otherwise
	 * the number is returned as a string
	 *
	 * @return string|array
	 *
	 * @since 4.0
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		$value = $this->get_value();
		$phone = $this->get_international_phone( $value );

		$this->cache( $phone ?? esc_html( $value ) );

		return $this->cache();
	}

	/**
	 * Get the number as it should be displayed in the PDF
	 *
	 * International (formatted) numbers are prefixed with the ISO country code, and the dial code when the field's
	 * "Show country dial code" setting is enabled
	 *
	 * @return string
	 *
	 * @since 6.16.0
	 */
	protected function get_display_value() {
		$phone = $this->value();

		if ( ! is_array( $phone ) ) {
			return $phone;
		}

		$parts = [ $phone['country'] ];

		if ( $this->field->showCountryCode ?? true ) {
			$parts[] = $phone['dial_code'];
		}

		$parts[] = $phone['formatted'];

		return implode( ' ', array_filter( $parts ) );
	}

	/**
	 * Get the individual parts of a Gravity Forms 3.0 International (formatted) number, which is stored as JSON
	 *
	 * The stored value is tested, and not the field's format setting, so entries saved before the format was changed
	 * still display correctly
	 *
	 * @param string $value The raw field value from the entry
	 *
	 * @return array|null Null when the value isn't an International (formatted) number
	 *
	 * @since 6.16.0
	 */
	protected function get_international_phone( $value ) {
		$phone = is_string( $value ) ? json_decode( $value, true ) : null;

		if ( ! is_array( $phone ) || ( ! isset( $phone['formatted'] ) && ! isset( $phone['e164'] ) ) ) {
			return null;
		}

		/* Entries added through the API bypass the Gravity Forms sanitiser, so discard anything that isn't a scalar */
		$parts = [];
		foreach ( [ 'country', 'national', 'formatted', 'e164' ] as $key ) {
			$part          = $phone[ $key ] ?? null;
			$parts[ $key ] = is_scalar( $part ) ? esc_html( (string) $part ) : '';
		}

		return [
			'country'   => strtoupper( $parts['country'] ),
			'dial_code' => $this->get_dial_code( $parts['e164'], $parts['national'] ),
			'national'  => $parts['national'],
			'formatted' => $parts['formatted'],
			'e164'      => $parts['e164'],
		];
	}

	/**
	 * Derive the country dial code by removing the national number from the tail of the E.164 number
	 *
	 * Gravity Forms only ships its dial code list to the browser, so it cannot be looked up in PHP
	 *
	 * @param string $e164     The E.164 number, eg. +12015551232
	 * @param string $national The national number, eg. 2015551232
	 *
	 * @return string The dial code, eg. +1, or an empty string when it cannot be derived
	 *
	 * @since 6.16.0
	 */
	protected function get_dial_code( $e164, $national ) {
		$e164_digits     = preg_replace( '/\D/', '', $e164 );
		$national_digits = preg_replace( '/\D/', '', $national );
		$length          = strlen( $national_digits );

		if ( $length === 0 || substr( $e164_digits, - $length ) !== $national_digits ) {
			return '';
		}

		$dial_code = substr( $e164_digits, 0, - $length );

		return $dial_code !== '' ? '+' . $dial_code : '';
	}
}
