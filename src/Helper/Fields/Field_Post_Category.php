<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Fields_Input_Type;

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
 * @since 4.0
 */
class Field_Post_Category extends Helper_Abstract_Fields_Input_Type {

	/**
	 * Return the form data
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function form_data() {

		$field_value = $this->value();
		$label       = $this->get_label();
		$data        = [];
		$value       = [
			'value' => '',
			'label' => '',
		];

		/* If Radio of Select boxes */
		if ( ! isset( $field_value[0] ) ) {

			/* Set up our basic values */
			$value['value'] = ( isset( $field_value['value'] ) && ! is_array( $field_value['value'] ) ) ? $field_value['value'] : '';
			$value['label'] = ( isset( $field_value['label'] ) && ! is_array( $field_value['label'] ) ) ? $field_value['label'] : '';

		} else { /* If Checkboxes or Multiselects */

			$value['value'] = [];
			$value['label'] = [];

			/* Loop through the results and store in array */
			foreach ( $field_value as $item ) {
				$value['value'][] = $item['value'];
				$value['label'][] = $item['label'];
			}
		}

		$data['field'][ $this->field->id . '.' . $label ] = $value['value'];
		$data['field'][ $this->field->id ]                = $value['value'];
		$data['field'][ $label ]                          = $value['value'];

		/* Name Format */
		$data['field'][ $this->field->id . '.' . $label . '_name' ] = $value['label'];
		$data['field'][ $this->field->id . '_name' ]                = $value['label'];
		$data['field'][ $label . '_name' ]                          = $value['label'];

		return $data;
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return string|array
	 *
	 * @since 4.0
	 */
	public function value() {
		if ( $this->fieldObject->has_cache() ) {
			return $this->fieldObject->cache();
		}

		/* get the value from the correct field object */
		$items = $this->fieldObject->value();

		/*
		 * Standardise the $items format
		 * The Radio / Select box will return a single-dimensional array,
		 * while checkbox and multiselect will not.
		 */
		$single_dimension = false;
		if ( isset( $items['label'] ) ) {
			$items            = [ $items ];
			$single_dimension = true;
		}

		/* Loop through standardised array and convert the label / value to their appropriate category */
		foreach ( $items as &$val ) {
			$cat = explode( ':', $val['label'] );

			$val['label'] = count( $cat ) > 0 ? esc_html( $cat[0] ) : '';
			$val['value'] = count( $cat ) > 1 ? $cat[1] : $cat[0];
		}

		unset( $val );

		/* Select / Radio Buttons will not have a multidimensional array  */
		if ( $single_dimension ) {
			$items = $items[0];
		}

		/* force the fieldObject cache to be set so it doesn't run their 'value' method directly */
		$this->fieldObject->cache( $items );

		return $this->fieldObject->cache();
	}
}
