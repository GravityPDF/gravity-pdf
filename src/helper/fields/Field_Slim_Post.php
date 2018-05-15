<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Fields;
use GF_Field_FileUpload;

/**
 * Gravity Forms Field
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.5
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * Controls the display and output of a Gravity Form field
 *
 * @since 4.5
 */
class Field_Slim_Post extends Helper_Abstract_Fields {

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

		$image = ( isset( $value['path'] ) ) ? $value['path'] : $value['url'];
		$html  = '<a href="' . esc_url( $url ) . '"><img src="' . $image . '" /></a>';

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

		$value = $this->get_value();
		$img   = [];

		if ( strlen( $value ) > 0 ) {
			$value = explode( '|:|', $this->get_value() );

			$img['url']         = ( isset( $value[0] ) ) ? esc_url( $value[0] ) : '';
			$img['path']        = ( isset( $value[0] ) ) ? esc_url( $value[0] ) : '';
			$img['title']       = ( isset( $value[1] ) ) ? esc_html( $value[1] ) : '';
			$img['caption']     = ( isset( $value[2] ) ) ? esc_html( $value[2] ) : '';
			$img['description'] = ( isset( $value[3] ) ) ? esc_html( $value[3] ) : '';

			$path = ( isset( $value[0] ) ) ? $this->misc->convert_url_to_path( $value[0] ) : '';
			if ( $path != $img['url'] ) {
				$img['path'] = $path;
			}
		}

		$this->cache( $img );

		return $this->cache();
	}
}
