<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_QueryPath;

/**
 * Gravity Forms Field
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
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
 * @since 4.0
 */
class Field_v3_Products extends Field_Products {


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
		$html = parent::html( $value, $label );

		/* Format the order label correctly */
		$label = apply_filters( 'gform_order_label', esc_html__( 'Order', 'gravityforms' ), $this->form->id );
		$label = apply_filters( 'gform_order_label_' . $this->form->id, $label, $this->form->id );

		$heading = '<h2 class="default entry-view-section-break">' . $label . '</h2>';

		/* Pull out the .entry-products table from the HTML using querypath */
		$qp    = new Helper_QueryPath();
		$table = $qp->html5( $html, 'div.inner-container' )->innerHTML5();

		$html = $heading;
		$html .= $table;

		return $html;
	}

}
