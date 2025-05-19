<?php

namespace GFPDF\Helper;

use Exception;
use GFPDF\Helper\Fields\GF_Field;

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
 * A helper class to display Gravity Forms fields that can masquerade as different field types
 *
 * @since 6.12
 */
abstract class Helper_Abstract_Fields_Input_Type extends Helper_Abstract_Fields {

	/**
	 * @param GF_Field             $field The GF_Field_* Object
	 * @param array                $entry The Gravity Forms Entry
	 *
	 * @param Helper_Abstract_Form $gform
	 * @param Helper_Misc          $misc
	 *
	 * @throws Exception
	 *
	 * @since 6.12
	 */
	public function __construct( $field, $entry, Helper_Abstract_Form $gform, Helper_Misc $misc ) {
		parent::__construct( $field, $entry, $gform, $misc );

		/* Try and load the masquerading field */
		$class = $this->misc->get_field_class( $field->inputType );

		try {
			/* check load our class */
			if ( class_exists( $class ) ) {

				/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_field_class/ for more details about these filters */
				$this->fieldObject = apply_filters( 'gfpdf_field_class', new $class( $field, $entry, $gform, $misc ), $field, $entry, $this->form );
				$this->fieldObject = apply_filters( 'gfpdf_field_class_' . $field->inputType, $this->fieldObject, $field, $entry, $this->form );
			} else {
				throw new Exception( 'Class not found' );
			}
		} catch ( Exception $e ) {
			/* Exception thrown. Load generic field loader */
			$this->fieldObject = apply_filters( 'gfpdf_field_default_class', new Field_Default( $field, $entry, $gform, $misc ), $field, $entry, $this->form );
		}

		if ( $this->fieldObject instanceof \GFPDF\Helper\Helper_Interface_Field_Pdf_Config ) {
			$this->fieldObject->set_pdf_config( $this->get_pdf_config() );
		}

		/* force the fieldObject value cache */
		$this->value();
	}

	/**
	 * Used to check if the current field has a value
	 *
	 * @since 6.12
	 */
	public function is_empty() {
		return $this->fieldObject->is_empty();
	}

	/**
	 * Display the HTML version of this field
	 *
	 * @param string $value
	 * @param bool   $label
	 *
	 * @return string
	 * @since 6.12
	 */
	public function html( $value = '', $label = true ) {
		if ( $this->get_output() ) {
			$this->fieldObject->enable_output();
		}

		return $this->fieldObject->html();
	}

	/**
	 * Return the correct form data information for the selected fields
	 *
	 * @return array
	 *
	 * @since 6.12
	 */
	public function form_data() {
		if ( method_exists( $this->fieldObject, 'form_data' ) ) {
			return $this->fieldObject->form_data();
		}

		return parent::form_data();
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return string|array
	 *
	 * @since 6.12
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
