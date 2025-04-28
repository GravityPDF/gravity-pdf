<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Field_Products;

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
 * @since 4.3
 */
class Field_Quantity extends Helper_Abstract_Field_Products {

	/**
	 * Return the HTML form data
	 *
	 * @return array
	 *
	 * @since 4.3
	 */
	public function form_data() {
		$value = esc_html( $this->value() );

		return $this->set_form_data( $value, $value );
	}

	/**
	 * Display the HTML version of this field
	 *
	 * @param string $value
	 * @param bool   $label
	 *
	 * @return string
	 *
	 * @since 4.3
	 */
	public function html( $value = '', $label = true ) {
		$html = esc_html( $this->value() );

		return parent::html( $html );
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return string
	 *
	 * @since    4.3
	 *
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		$data = $this->products->value();

		if ( isset( $data['products'][ $this->field->productField ]['quantity'] ) ) {
			$this->cache( $data['products'][ $this->field->productField ]['quantity'] );
		} else {
			$this->cache( '' );
		}

		return $this->cache();
	}
}
