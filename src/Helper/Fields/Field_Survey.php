<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Fields;
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
class Field_Survey extends Helper_Abstract_Fields_Input_Type {

	/**
	 * Return the field form data
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function form_data() {

		$data     = [];
		$field_id = (int) $this->field->id;

		/* Provide backwards compatibility fixes to certain fields */
		switch ( $this->field->inputType ) {
			case 'radio':
			case 'select':
				$data  = parent::form_data();
				$value = $data['field'][ $this->field->id . '_name' ];

				/* Overriding survey radio values with name */
				array_walk(
					$data['field'],
					function ( &$item, $key, $value ) {
						$item = esc_html( $value );
					},
					$value
				);
				break;

			case 'checkbox':
				$value = $this->get_value();

				/* Convert survey ID to real value */
				foreach ( $this->field->choices as $choice ) {

					$key = array_search( $choice['value'], $value, true );
					if ( $key !== false ) {
						$value[ $key ] = esc_html( $choice['text'] );
					}
				}

				$value = [ $value ];
				$label = $this->get_label();

				/* Gravity PDF v3 backwards compatibility. Check if nothing is selected and return blank */
				if ( 0 === count( array_filter( $value[0] ) ) ) {
					$value = '';
				}

				$data[ $field_id . '.' . $label ] = $value;
				$data[ $field_id ]                = $value;
				$data[ $label ]                   = $value;

				$data = [ 'field' => $data ];
				break;

			default:
				$data = parent::form_data();
				break;
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
	 * @since 4.0
	 */
	public function html( $value = '', $label = true ) {

		/* Return early to prevent unwanted details being displayed when the plugin isn't enabled */
		if ( ! class_exists( 'GFSurvey' ) ) {
			return Helper_Abstract_Fields::html( '' );
		}

		if ( $this->get_output() ) {
			$this->fieldObject->enable_output();
		}

		return $this->fieldObject->html();
	}
}
