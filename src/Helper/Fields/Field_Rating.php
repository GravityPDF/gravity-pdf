<?php

namespace GFPDF\Helper\Fields;

use GFCommon;
use GFPDF\Helper\Helper_Abstract_Fields;

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
class Field_Rating extends Helper_Abstract_Fields {

	/**
	 * Return the HTML form data
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function form_data() {

		$data  = [];
		$value = $this->value();

		$data['survey']['rating'][ $this->field->id ] = $value;

		return $data;
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

		$html = GFCommon::get_lead_field_display( $this->field, $this->get_value(), $this->entry['currency'] );
		$html = apply_filters( 'gform_entry_field_value', $html, $this->field, $this->entry, $this->form );

		return parent::html( $html );
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return string|array
	 *
	 * @since 4.0
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		/* Process field */
		$items = explode( ',', $this->get_value() );

		$value = [];

		/* Loop through each of the user-selected items */
		foreach ( $items as $rating ) {

			/* Loop through the total choices */
			foreach ( $this->field->choices as $choice ) {
				if ( trim( $choice['value'] ) === trim( $rating ) ) {
					$value[] = esc_html( $choice['text'] );
					break; /* exit inner loop as soon as found */
				}
			}
		}

		$this->cache( $value ); /* for backwards compatibility we'll wrap it in an array */

		return $this->cache();
	}
}
