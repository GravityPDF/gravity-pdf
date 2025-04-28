<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Field_Products;
use GFPDF\Statics\Kses;

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

			$price = $value['price_unformatted'] ?? '';
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
		if ( isset( $value['subtotal_formatted'] ) ) {
			if ( in_array( $this->field->get_input_type(), [ 'radio', 'select' ], true ) ) {
				$html .= Kses::parse( $value['name'] . ': ' );
			}

			if ( $value['quantity'] > 1 ) {
				$html .= esc_html( sprintf( '%s x %s = %s', $value['unit_price_formatted'], $value['quantity'], $value['subtotal_formatted'] ) );
			} else {
				$html .= esc_html( sprintf( '%s x %s', $value['subtotal_formatted'], $value['quantity'] ) );
			}
		}

		return parent::html( $html );
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
