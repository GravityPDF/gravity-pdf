<?php

namespace GFPDF\Helper\Fields;

use Exception;
use GF_Field_MultiSelect;
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
class Field_Multiselect extends Helper_Abstract_Fields {

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

		if ( ! is_object( $field ) || ! $field instanceof GF_Field_MultiSelect ) {
			throw new Exception( '$field needs to be in instance of GF_Field_MultiSelect' );
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
		if ( 0 === count( $value ) ) {
			$data['field'][ $this->field->id . '.' . $label ] = '';
			$data['field'][ $this->field->id ]                = '';
			$data['field'][ $label ]                          = '';

			/* Name Format */
			$data['field'][ $this->field->id . '.' . $label . '_name' ] = '';
			$data['field'][ $this->field->id . '_name' ]                = '';
			$data['field'][ $label . '_name' ]                          = '';
		}

		foreach ( $value as $item ) {

			/* Standardised Format */
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

		$items = $this->value();
		$value = apply_filters( 'gfpdf_show_field_value', false, $this->field, $items ); /* Set to `true` to show a field's value instead of the label */
		$html  = '';

		if ( count( $items ) > 0 ) {
			$i    = 1;
			$html = '<ul class="bulleted multiselect">';

			foreach ( $items as $item ) {
				$sanitized_value  = $item['value'];
				$sanitized_option = ( $value ) ? $sanitized_value : $item['label'];

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

		/* Add support for JSON stored multiselect fields (added to GF2.2) */
		if ( $this->field->storageType === 'json' ) {
			$value = json_decode( $value, true );
			$value = ( $value === null ) ? [] : $value;
		}

		/* split value into an array */
		if ( ! is_array( $value ) ) {
			$value = explode( ',', $value );
		}

		/* remove any empty / unselected fields */
		$value = array_filter( $value );

		/* loop through array and get the correct selection display value */
		$items = [];
		foreach ( $value as $item ) {
			$label = esc_html( GFCommon::selection_display( $item, $this->field, '', true ) );
			$value = esc_html( GFCommon::selection_display( $item, $this->field ) );

			$items[] = [
				'value' => $value,
				'label' => $label,
			];
		}

		$this->cache( $items );

		return $this->cache();
	}
}
