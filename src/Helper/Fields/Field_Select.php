<?php

namespace GFPDF\Helper\Fields;

use Exception;
use GF_Field_Select;
use GFCommon;
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
class Field_Select extends Helper_Abstract_Fields {

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

		if ( ! is_object( $field ) || ! $field instanceof GF_Field_Select ) {
			throw new Exception( '$field needs to be in instance of GF_Field_Select' );
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
		$items  = $this->value();
		$value  = apply_filters( 'gfpdf_show_field_value', false, $this->field, $items ); /* Set to `true` to show a field's value instead of the label */
		$output = ( $value ) ? $items['value'] : $items['label'];

		return parent::html( $output );
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
