<?php

namespace GFPDF\Helper\Fields;

use GFCommon;
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
class Field_Subtotal extends Helper_Abstract_Field_Products {

	/**
	 * @return bool
	 *
	 * @since 4.3
	 */
	public function is_empty() {
		if ( ! method_exists( $this->field, 'get_subtotal' ) ) {
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

		if ( isset( $value['total_formatted'] ) ) {
			$name  = $value['total_formatted'];
			$price = $value['total'];

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

		if ( isset( $value['total_formatted'] ) ) {
			$html = $value['total_formatted'];
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

		$subtotal = $this->get_value();

		$this->cache(
			[
				'total'           => esc_html( $subtotal ),
				'total_formatted' => esc_html( GFCommon::to_money( $subtotal, $this->entry['currency'] ) ),
			]
		);

		return $this->cache();
	}
}
