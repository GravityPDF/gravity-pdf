<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Abstract_Fields;

use GF_Field_Checkbox;
use GFCommon;

use Exception;

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
class Field_Checkbox extends Helper_Abstract_Fields {

	/**
	 * Check the appropriate variables are parsed in send to the parent construct
	 *
	 * @param object                             $field The GF_Field_* Object
	 * @param array                              $entry The Gravity Forms Entry
	 *
	 * @param \GFPDF\Helper\Helper_Abstract_Form $gform
	 * @param \GFPDF\Helper\Helper_Misc          $misc
	 *
	 * @throws Exception
	 *
	 * @since 4.0
	 */
	public function __construct( $field, $entry, Helper_Abstract_Form $gform, Helper_Misc $misc ) {

		if ( ! is_object( $field ) || ! $field instanceof GF_Field_Checkbox ) {
			throw new Exception( '$field needs to be in instance of GF_Field_Checkbox' );
		}

		/* call our parent method */
		parent::__construct( $field, $entry, $gform, $misc );
	}

	/**
	 * Return the HTML form data
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function form_data() {

		$value = $this->value();
		$label = $this->get_label();
		$data  = [];

		/* Backwards compatibility support for v3 */
		if ( 0 === sizeof( $value ) ) {
			$data['field'][ $this->field->id . '.' . $label ] = '';
			$data['field'][ $this->field->id ]                = '';
			$data['field'][ $label ]                          = '';

			/* Name Format */
			$data['field'][ $this->field->id . '.' . $label . '_name' ] = '';
			$data['field'][ $this->field->id . '_name' ]                = '';
			$data['field'][ $label . '_name' ]                          = '';
		}

		foreach ( $value as $item ) {

			/* Standadised Format */
			$data['field'][ $this->field->id . '.' . $label ][] = $item['value'];
			$data['field'][ $this->field->id ][]                = $item['value'];
			$data['field'][ $label ][]                          = $item['value'];

			/* Name Format */
			$data['field'][ $this->field->id . '.' . $label . '_name' ][] = $item['label'];
			$data['field'][ $this->field->id . '_name' ][]                = $item['label'];
			$data['field'][ $label . '_name' ][]                          = $item['label'];
		}

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

		$value = apply_filters( 'gfpdf_show_field_value', false ); /* Set to `true` to show a field's value instead of the label */
		$items = $this->value();
		$html  = '';

		/* Generate our drop down list */
		if ( sizeof( $items ) > 0 ) {
			$html = '<ul class="bulleted checkbox">';
			$i    = 1;
			foreach ( $items as $item ) {
				$sanitized_option = ( $value ) ? $item['value'] : $item['label'];
				$html .= '<li id="field-' . $this->field->id . '-option-' . $i . '">' . $sanitized_option . '</li>';
				$i++;
			}

			$html .= '</ul>';
		}

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

		$value = $this->get_value();

		/* if not an array, make it so */
		if ( ! is_array( $value ) ) {
			$value = [ $value ];
		}

		/* remove any unselected fields */
		$value = array_filter( $value );

		/* loop through results and get checkbox 'labels' */
		$items = [];

		foreach ( $value as $key => $item ) {
			$label = esc_html( GFCommon::selection_display( $item, $this->field, '', true ) );
			$label = wp_kses_post( $this->gform->process_tags( wp_specialchars_decode( $label, ENT_QUOTES ), $this->form, $this->entry ) );

			$value = esc_html( GFCommon::selection_display( $item, $this->field ) );
			$value = wp_kses_post( $this->gform->process_tags( wp_specialchars_decode( $value, ENT_QUOTES ), $this->form, $this->entry ) );

			$items[] = [
				'value' => $value,
				'label' => $label,
			];
		}

		$this->cache( $items );

		return $this->cache();
	}
}
