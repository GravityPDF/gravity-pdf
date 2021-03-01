<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Field_Products;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 4.3
 */
class Field_Product extends Helper_Abstract_Field_Products {

	/**
	 * Return the HTML form data
	 *
	 * @return array
	 *
	 * @since 4.3
	 */
	public function form_data() {
		$value = $this->value();

		if ( isset( $value['price'] ) ) {
			$name = ( isset( $value['name'] ) && isset( $value['price'] ) ) ? $value['name'] . " ({$value['price']})" : '';
			$name = esc_html( $name );

			$price = ( isset( $value['price_unformatted'] ) ) ? $value['price_unformatted'] : '';
			$price = esc_html( $price );

			return $this->set_form_data( $name, $price );
		}

		return $this->set_form_data( '', '' );
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
		$value = $this->value();
		$html  = '';

		if ( isset( $value['price'] ) ) {
			if ( in_array( $this->field->get_input_type(), [ 'radio', 'select' ], true ) ) {
				$html .= $value['name'] . ' - ' . $value['price'];
			} else {
				$html .= $value['price'];
			}
		}

		return parent::html( esc_html( $html ) );
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return array
	 *
	 * @since    4.3
	 *
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		$data = $this->products->value();

		if ( isset( $data['products'][ $this->field->id ] ) ) {
			$this->cache( $data['products'][ $this->field->id ] );
		} else {
			$this->cache( [] );
		}

		return $this->cache();
	}
}
