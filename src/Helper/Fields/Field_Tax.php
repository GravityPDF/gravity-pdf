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
class Field_Tax extends Helper_Abstract_Field_Products {

	/**
	 * @return bool
	 *
	 * @since 4.3
	 */
	public function is_empty() {
		if ( ! class_exists( 'GP_Ecommerce_Fields' ) ) {
			return true;
		}

		return parent::is_empty();
	}

	/**
	 * Return the HTML form data
	 *
	 * @return array
	 *
	 * @since 4.3
	 */
	public function form_data() {
		$value = $this->value();

		if ( isset( $value['name'] ) ) {
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

		if ( isset( $value['name'] ) ) {
			$html .= esc_html( $value['price'] );
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
