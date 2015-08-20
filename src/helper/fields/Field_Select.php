<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Abstract_Fields;

use GFFormsModel;
use GF_Field_Select;
use GFCommon;

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
class Field_Select extends Helper_Abstract_Fields
{

	/**
	 * Check the appropriate variables are parsed in send to the parent construct
	 * @param Object $field The GF_Field_* Object
	 * @param Array  $entry The Gravity Forms Entry
	 * @since 4.0
	 */
	public function __construct( $field, $entry, Helper_Abstract_Form $form, Helper_Misc $misc ) {
		
		if ( ! is_object( $field ) || ! $field instanceof GF_Field_Select ) {
			throw new Exception( '$field needs to be in instance of GF_Field_Select' );
		}

		/* call our parent method */
		parent::__construct( $field, $entry, $form, $misc );
	}

	/**
	 * Return the HTML form data
	 * @return Array
	 * @since 4.0
	 */
	public function form_data() {

		$value = $this->value();
		$label = GFFormsModel::get_label( $this->field );
		$data  = array();

		/* Standadised Format */
		$data['field'][ $this->field->id . '.' . $label ]           = $value['value'];
		$data['field'][ $this->field->id ]                          = $value['value'];
		$data['field'][ $label ]                                    = $value['value'];

		/* Name Format */
		$data['field'][ $this->field->id . '.' . $label . '_name' ] = $value['label'];
		$data['field'][ $this->field->id . '_name' ]                = $value['label'];
		$data['field'][ $label . '_name' ]                          = $value['label'];

		return $data;
	}

	/**
	 * Display the HTML version of this field
	 * @return String
	 * @since 4.0
	 */
	public function html( $value = '', $label = true ) {
		$data   = $this->value();

		$output = ($value) ? $data['value'] : $data['label'];
		$output = esc_html( $output );

		return parent::html( $output );
	}

	/**
	 * Get the standard GF value of this field
	 * @return Array
	 * @since 4.0
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		$label = trim( GFCommon::selection_display( $this->get_value(), $this->field, '', true ) );
		$value = trim( GFCommon::selection_display( $this->get_value(), $this->field ) );

		/* if both fields are blank return an empty array */
		if ( strlen( $label ) === 0 && strlen( $value ) === 0 ) {
			return array();
		}

		/* return value / label as an array */
		$this->cache(array(
			'value' => $value,
			'label' => $label,
		));

		return $this->cache();
	}
}
