<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Abstract_Fields;

use GFFormsModel;

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
class Field_Poll extends Helper_Abstract_Fields
{

	/**
	 * Check the appropriate variables are parsed in send to the parent construct
	 * @param Object $field The GF_Field_* Object
	 * @param Array  $entry The Gravity Forms Entry
	 * @since 4.0
	 */
	public function __construct( $field, $entry, Helper_Abstract_Form $form, Helper_Misc $misc ) {

		/* call our parent method */
		parent::__construct( $field, $entry, $form, $misc );

		/*
         * Custom Field can be any of the following field types:
         * single line text, paragraph, dropdown, select, number, checkbox, radio, hidden,
         * date, time, phone, website, email, file upload or list
         */
		$class = $this->misc->get_field_class( $field->inputType );

		try {
			/* check load our class */
			if ( class_exists( $class ) ) {
				$this->fieldObject = new $class( $field, $entry, $form, $misc );
			} else {
				throw new Exception( 'Class not found' );
			}
		} catch (Exception $e) {
			/* Exception thrown. Load generic field loader */
			$this->fieldObject = new Field_Default( $field, $entry, $form, $misc );
		}
	}

	/**
	 * Used to check if the current field has a value
	 * @since 4.0
	 * @internal Child classes can override this method when dealing with a specific use case
	 */
	public function is_empty() {
		return $this->fieldObject->is_empty();
	}

	/**
	 * Return the HTML form data
	 * @return Array
	 * @since 4.0
	 */
	public function form_data() {

		$data = array();
		$value = $this->value();
		$label = GFFormsModel::get_label( $this->field );

		if ( isset($value[0]) ) {

			$field = array();
			$fieldValue = array();

			foreach ( $value as $item ) {
				/* For backwards compatibility, we'll wrap these in their own array key */
				$field[0][]      = $item['label'];
				$fieldValue[0][] = $item['value'];
			}
		} else {
			$field      = ( isset($value['label']) ) ? $value['label'] : '';
			$fieldValue = ( isset($value['value']) ) ? $value['value'] : '';
		}

		$data[ $this->field->id . '.' . $label ] = $field;
		$data[ $this->field->id . '.' . $label . '_name' ] = $field; /* for backwards compatibility */
		$data[ $this->field->id ]                = $field;
		$data[ $label ]                          = $field;

		return array( 'field' => $data );
	}

	/**
	 * Display the HTML version of this field
	 * @return String
	 * @since 4.0
	 */
	public function html( $value = '', $label = true ) {
		echo $this->fieldObject->html();
	}

	/**
	 * Get the standard GF value of this field
	 * @return String/Array
	 * @since 4.0
	 */
	public function value() {
		if ( $this->fieldObject->has_cache() ) {
			return $this->cache();
		}

		$value = $this->fieldObject->value();

		$this->fieldObject->cache( $value );

		return $this->fieldObject->cache();
	}
}
