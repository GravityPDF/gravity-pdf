<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Fields;

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
class Field_Default extends Helper_Abstract_Fields {

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
		$property = version_compare( \GFForms::$version, '2.9.29', '>=' ) ? $this->entry : $this->entry['currency'];

		$html = $this->field->get_value_entry_detail( $this->get_value(), $property );
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

		$value = $this->get_value();
		$value = is_array( $value ) ? array_map( 'esc_html', $value ) : esc_html( $value );
		$this->cache( $value );

		return $this->cache();
	}
}
