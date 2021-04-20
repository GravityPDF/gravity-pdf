<?php

namespace GFPDF\Helper\Fields;

use Exception;
use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
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
class Field_Survey extends Helper_Abstract_Fields {

	/**
	 * Check the appropriate variables are parsed in send to the parent construct
	 *
	 * @param object               $field The GF_Field_* Object
	 * @param array                $entry The Gravity Forms Entry
	 *
	 * @param Helper_Abstract_Form $gform
	 * @param Helper_Misc          $misc
	 *
	 * @throws Exception
	 *
	 * @since 4.0
	 */
	public function __construct( $field, $entry, Helper_Abstract_Form $gform, Helper_Misc $misc ) {

		/* call our parent method */
		parent::__construct( $field, $entry, $gform, $misc );

		/*
		 * Survey Field can be any of the following:
		 * single line text, paragraph, dropdown, select, checkbox,
		 * likert, rank or rating
		 */
		$class = $this->misc->get_field_class( $field->inputType );

		try {
			/* check load our class */
			if ( class_exists( $class ) ) {

				/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_field_class/ for more details about these filters */
				$this->fieldObject = apply_filters( 'gfpdf_field_class', new $class( $field, $entry, $gform, $misc ), $field, $entry, $this->form );
				$this->fieldObject = apply_filters( 'gfpdf_field_class_' . $field->inputType, $this->fieldObject, $field, $entry, $this->form );
			} else {
				throw new Exception();
			}
		} catch ( Exception $e ) {
			/* Exception thrown. Load generic field loader */
			$this->fieldObject = apply_filters( 'gfpdf_field_default_class', new Field_Default( $field, $entry, $gform, $misc ), $field, $entry, $this->form );
		}

		/* force the fieldObject value cache */
		$this->value();
	}

	/**
	 * Get the $form_data object
	 * Survey field uses multiple field types so we need to account for that
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	private function get_form_data() {
		if ( method_exists( $this->fieldObject, 'form_data' ) ) {
			return $this->fieldObject->form_data();
		}

		return parent::form_data();
	}

	/**
	 * Used to check if the current field has a value
	 *
	 * @since    4.0
	 */
	public function is_empty() {
		return $this->fieldObject->is_empty();
	}

	/**
	 * Return the HTML form data
	 *
	 * @return array
	 *
	 * @since 4.0
	 *
	 */
	public function form_data() {

		$data     = [];
		$field_id = (int) $this->field->id;

		/* Provide backwards compatibility fixes to certain fields */
		switch ( $this->field->inputType ) {
			case 'radio':
			case 'select':
				$data  = $this->get_form_data();
				$value = $data['field'][ $this->field->id . '_name' ];

				/* Overriding survey radio values with name */
				array_walk(
					$data['field'],
					function( &$item, $key, $value ) {
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
				$data = $this->get_form_data();
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
			return parent::html( '' );
		}

		return $this->fieldObject->html();
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return string|array
	 * @since 4.0
	 */
	public function value() {
		if ( $this->fieldObject->has_cache() ) {
			return $this->fieldObject->cache();
		}

		$value = $this->fieldObject->value();

		$this->fieldObject->cache( $value );

		return $this->fieldObject->cache();
	}
}
