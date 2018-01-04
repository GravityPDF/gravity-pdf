<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Fields;

use GFCommon;

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
class Field_Likert extends Helper_Abstract_Fields {

	/**
	 * Used to check if the current field has a value
	 *
	 * @since    4.0
	 */
	public function is_empty() {

		$value = $this->value();

		if ( isset( $value['row'] ) ) { /* Check for single row likerts */
			if ( sizeof( array_filter( $value['row'] ) ) === 0 ) { /* if empty */
				return true;
			}
		} else { /*
			multi row likert */
			/* loop through the results and check if they are all empty */
			$empty = true;

			foreach ( $value['rows'] as $row ) {
				if ( sizeof( array_filter( $row ) ) > 0 ) {
					$empty = false;
					break;
				}
			}

			return $empty;
		}

		return false;
	}

	/**
	 * Return the HTML form data
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function form_data() {

		$data  = [];
		$value = $this->value();

		$data['survey']['likert'][ $this->field->id ] = $value;

		return $data;
	}

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

		$html = GFCommon::get_lead_field_display( $this->field, $this->get_value(), $this->entry['currency'] );
		$html = apply_filters( 'gform_entry_field_value', $html, $this->field, $this->entry, $this->form );

		return parent::html( $html );
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return string|array
	 *
	 * @since 4.0
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		/*
         * Process Single and Multi Column Likerts
         */
		$likert = [];

		/*
         * Get the column names
         */
		foreach ( $this->field->choices as $column ) {
			$likert['col'][ $column['value'] ] = esc_html( $column['text'] );
		}

		/**
		 * Build our Likert Array
		 */
		if ( is_array( $this->field->inputs ) && sizeof( $this->field->inputs ) > 0 ) { /* Handle our multirow likert */

			/* loop through each row */
			foreach ( $this->field->inputs as $row ) {
				/* loop through each column */
				foreach ( $likert['col'] as $id => $text ) {
					/* check if user selected this likert value */
					$data = rgar( $this->entry, $row['id'] );

					$likert['rows'][ $row['label'] ][ $text ] = ( ( $row['name'] . ':' . $id ) == $data ) ? 'selected' : '';
				}
			}
		} else { /* Handle our single-row likert */

			/* Get the value from the entry */
			$data = rgar( $this->entry, $this->field->id );
			foreach ( $likert['col'] as $id => $text ) {
				/* check if user selected this likert value */
				$likert['row'][ $text ] = ( $id == $data ) ? 'selected' : '';
			}
		}

		$this->cache( $likert );

		return $this->cache();
	}
}
