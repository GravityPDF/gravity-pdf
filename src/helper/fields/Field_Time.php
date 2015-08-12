<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Fields;

use GF_Field_Time;

use Exception;

/**
 * Gravity Forms Field
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

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
 * @since 4.0
 */
class Field_Time extends Helper_Fields
{

	/**
	 * Check the appropriate variables are parsed in send to the parent construct
	 * @param Object $field The GF_Field_* Object
	 * @param Array  $entry The Gravity Forms Entry
	 * @since 4.0
	 */
	public function __construct( $field, $entry ) {
		if ( ! is_object( $field ) || ! $field instanceof GF_Field_Time ) {
			throw new Exception( '$field needs to be in instance of GF_Field_Time' );
		}

		/* call our parent method */
		parent::__construct( $field, $entry );
	}

	/**
	 * Display the HTML version of this field
	 * @return String
	 * @since 4.0
	 */
	public function html( $value = '', $label = true ) {
		$value = esc_html( $this->value() );

		return parent::html( $value );
	}

	/**
	 * Get the standard GF value of this field
	 * @return String/Array
	 * @since 4.0
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		$this->cache( $this->get_value() );

		return $this->cache();
	}
}
