<?php

namespace GFPDF\Helper\Fields;

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
 * @since 4.5
 */
class Field_Slim extends Helper_Abstract_Fields {

	/**
	 * Display the HTML version of this field
	 *
	 * @param string $value
	 * @param bool   $label
	 *
	 * @return string
	 *
	 * @since 4.5
	 */
	public function html( $value = '', $label = true ) {
		$value = $this->value();
		$url   = $value['url'];

		$image = $value['path'] ?? $value['url'];
		$html  = '<a href="' . esc_url( $url ) . '"><img src="' . ( isset( $value['path'] ) ? esc_attr( $image ) : esc_url( $image ) ) . '" /></a>';

		return parent::html( $html );
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return array
	 *
	 * @since 4.5
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		$value      = $this->get_value();
		$img        = [];
		$img['url'] = $value;

		$path = $this->misc->convert_url_to_path( $value );
		if ( $path && $path !== $value ) {
			$img['path'] = $path;
		}

		$this->cache( $img );

		return $this->cache();
	}
}
