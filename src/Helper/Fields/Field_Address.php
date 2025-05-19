<?php

namespace GFPDF\Helper\Fields;

use Exception;
use GF_Field_Address;
use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
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
class Field_Address extends Helper_Abstract_Fields {

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

		if ( ! is_object( $field ) || ! $field instanceof GF_Field_Address ) {
			throw new Exception( '$field needs to be in instance of GF_Field_Address' );
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
		$data    = $this->value(); /* remove any empty fields from the array */
		$address = [];

		/* check if we should display the zip before the city */
		$address_display_format = apply_filters( 'gform_address_display_format', 'default', $this->field );

		/* Start putting our address together */
		if ( ! empty( $data['street'] ) ) {
			$address[] = $data['street'];
		}

		if ( ! empty( $data['street2'] ) ) {
			$address[] = $data['street2'];
		}

		/* display in the standard "city, state zip" format */
		if ( $address_display_format !== 'zip_before_city' ) {
			$zip_string  = $data['city'];
			$zip_string .= ( ! empty( $zip_string ) && ! empty( $data['state'] ) ) ? ", {$data['state']}" : trim( $data['state'] );
			$zip_string .= " {$data['zip']}";

			$zip_string = trim( $zip_string );

			if ( ! empty( $zip_string ) ) {
				$address[] = $zip_string;
			}
		} else { /* display in the "zip, city state" format */
			$zip_string  = trim( $data['zip'] . ' ' . $data['city'] );
			$zip_string .= ( ! empty( $zip_string ) && ! empty( $data['state'] ) ) ? ", {$data['state']}" : trim( $data['state'] );

			if ( ! empty( $zip_string ) ) {
				$address[] = $zip_string;
			}
		}

		/* add country to address, if present */
		if ( ! empty( $data['country'] ) ) {
			$address[] = $data['country'];
		}

		/* display the address in the correct format */
		$html = implode( '<br />', $address );

		/* return the results */

		return parent::html( $html );
	}

	/**
	 * Check if the Address field is empty, based off the active sub-form fields
	 * This prevents problems with addresses showing when country or state fields are disabled but defaults are used
	 *
	 * return boolean Return true if the field is empty, false if it has a value
	 *
	 * @since 4.0
	 */
	public function is_empty() {
		$field = $this->field;
		$value = $this->get_value();

		/* Loop through active fields and determine if field should be classed as empty */
		foreach ( $field->inputs as $item ) {
			if ( ! isset( $item['isHidden'] ) || false === $item['isHidden'] ) {
				/* Now we know item isn't hidden, go through the values and check if there's data */
				$item_value = trim( rgget( (string) $item['id'], $value ) );
				if ( ! empty( $item_value ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		$value = $this->get_value();

		/* check if the returned results are an array */
		if ( ! is_array( $value ) ) {
			$value[ $this->field->id . '.1' ] = $value; /* set to the street value */
		}

		$this->cache(
			[
				'street'  => esc_html( rgget( $this->field->id . '.1', $value ) ),
				'street2' => esc_html( rgget( $this->field->id . '.2', $value ) ),
				'city'    => esc_html( rgget( $this->field->id . '.3', $value ) ),
				'state'   => esc_html( rgget( $this->field->id . '.4', $value ) ),
				'zip'     => esc_html( rgget( $this->field->id . '.5', $value ) ),
				'country' => esc_html( rgget( $this->field->id . '.6', $value ) ),
			]
		);

		return $this->cache();
	}
}
