<?php

namespace GFPDF\Helper\Fields;

use Exception;
use GF_Field_Radio;
use GFCommon;
use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Statics\Kses;

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
class Field_Radio extends Helper_Abstract_Fields {

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

		if ( ! is_object( $field ) || ! $field instanceof GF_Field_Radio ) {
			throw new Exception( '$field needs to be in instance of GF_Field_Radio' );
		}

		/* call our parent method */
		parent::__construct( $field, $entry, $gform, $misc );
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
		$items      = $this->value();
		$show_value = apply_filters( 'gfpdf_show_field_value', false, $this->field, $items ); /* Set to `true` to show a field's value instead of the label */
		$output     = $show_value ? $items['value'] : $items['label'];

		return parent::html( $output );
	}

	/**
	 * Checks if the selected Radio button value is defined by the site owner (standard radio options)
	 * or by the end user (through the "other" option).
	 *
	 * @param string $value The user-selected radio button value
	 *
	 * @return bool Returns true if value is user-defined, or false otherwise
	 *
	 * @since 4.0.1
	 */
	protected function is_user_defined_value( $value ) {

		/* Check if the field has the "Other" choice enabled */
		if ( ! isset( $this->field->enableOtherChoice ) || true !== $this->field->enableOtherChoice ) {
			return false;
		}

		/* Loop through the values and check if we have a match */
		foreach ( $this->field->choices as $item ) {
			if ( wp_specialchars_decode( $value, ENT_QUOTES ) === $item['value'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if the value is empty. Optional filter to check both the value and the label
	 *
	 * @return bool
	 *
	 * @since 6.1
	 */
	public function is_empty() {
		$value = $this->value();

		if ( apply_filters( 'gfpdf_field_is_empty_value_instead_of_label', true, $value, $this->field, $this->entry, $this->form, $this ) ) {
			return strlen( trim( $value['value'] ) ) === 0;
		}

		return parent::is_empty();
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

		/* Standardised Format */
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
	 * @return array
	 *
	 * @since 4.0
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		$label = esc_html( GFCommon::selection_display( $this->get_value(), $this->field, '', true ) );
		$value = esc_html( GFCommon::selection_display( $this->get_value(), $this->field ) );

		/* Allow HTML if the radio value isn't the "other" option */
		if ( ! $this->is_user_defined_value( $value ) ) {
			$value = Kses::parse( $this->gform->process_tags( wp_specialchars_decode( $value, ENT_QUOTES ), $this->form, $this->entry ) );
			$label = Kses::parse( $this->gform->process_tags( wp_specialchars_decode( $label, ENT_QUOTES ), $this->form, $this->entry ) );
		}

		/* return value / label as an array */
		$this->cache(
			[
				'value' => $value,
				'label' => $label,
			]
		);

		return $this->cache();
	}
}
