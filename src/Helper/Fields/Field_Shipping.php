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
class Field_Shipping extends Helper_Abstract_Field_Products {

	/**
	 * Return the HTML form data
	 *
	 * @return array
	 *
	 * @since 4.3
	 */
	public function form_data() {
		$value = $this->value();

		if ( isset( $value['shipping_formatted'] ) ) {
			$name = ( isset( $value['shipping_name'] ) ) ? $value['shipping_name'] . " ({$value['shipping_formatted']})" : '';
			$name = esc_html( $name );

			$price = $value['shipping'] ?? '';
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

		if ( isset( $value['shipping_formatted'] ) ) {
			$html .= esc_html( $value['shipping_name'] . ' - ' . $value['shipping_formatted'] );
		}

		return parent::html( $html );
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return array
	 *
	 * @since    4.3
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		$data = $this->products->value();

		if ( isset( $data['products_totals']['shipping'] ) ) {
			$this->cache(
				[
					'shipping'           => esc_html( $data['products_totals']['shipping'] ),
					'shipping_formatted' => esc_html( $data['products_totals']['shipping_formatted'] ),
					'shipping_name'      => esc_html( $data['products_totals']['shipping_name'] ),
				]
			);
		} else {
			$this->cache( [] );
		}

		return $this->cache();
	}
}
