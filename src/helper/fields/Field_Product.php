<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Fields;

use GFFormsModel;
use GFCommon;

/**
 * Gravity Forms Field
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2016, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2016, Blue Liquid Designs

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
 * Controls the individual display and output of:
 * product, option, shipping, quantity and total fields
 *
 * If you just want the complete product list / HTML table use the Field_Products class
 *
 * @since 4.0
 */
class Field_Product extends Helper_Abstract_Fields {

	/**
	 * Our products class which handles all Gravity Form products fields in bulk
	 *
	 * @var \GFPDF\Helper\Helper_Abstract_fields
	 */
	private $products;

	/**
	 * Store our products class for later user
	 *
	 * @param \GFPDF\Helper\Helper_Abstract_Fields $products
	 *
	 * @since 4.0
	 */
	public function set_products( Helper_Abstract_Fields $products ) {
		$this->products = $products;
	}

	/**
	 * Return the HTML form data
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function form_data() {

		$value    = $this->value();
		$field    = $this->field;
		$label    = GFFormsModel::get_label( $field );
		$field_id = (int) $field->id;
		$data     = [];
		$name     = $price = '';

		switch ( $field->type ) {
			case 'product':
				$name = ( isset( $value['name'] ) && isset( $value['price'] ) ) ? $value['name'] . " ({$value['price']})" : '';
				$name = esc_html( $name );

				$price = ( isset( $value['price_unformatted'] ) ) ? $value['price_unformatted'] : '';
				$price = esc_html( $price );
			break;

			case 'option':

				/**
				 * Gravity Forms doesn't currently store the option field ID with the standard product information.
				 * However, it does allow multiple fields to be an option for a single product.
				 * This becomes problematic when you have multiple option fields that contains the same name and are
				 * trying to determine which field it was selected from.
				 *
				 * To get around this limitation we'll process the entry option fields and make this data available.
				 */

				/* Get the current option value for this field */
				$option_value = GFFormsModel::get_lead_field_value( $this->entry, $field );

				/* Ensure this variable is an array */
				$option_value = ( ! is_array( $option_value ) ) ? [ $option_value ] : $option_value;

				/* Reset the array keys and remove any empty values */
				$option_value = array_values( $option_value );
				$option_value = array_filter( $option_value );

				/* Get the field name ( */
				$name = array_map( function ( $value ) use ( $field ) {
					$option_info = GFCommon::get_option_info( $value, $field, false );

					return esc_html( $option_info['name'] );
				}, $option_value );

				/* Get the field value (the price) */
				$price = array_map( function ( $value ) use ( $field ) {
					$option_info = GFCommon::get_option_info( $value, $field, false );

					return esc_html( $option_info['price'] );
				}, $option_value );

				/**
				 * Valid option fields can only be radio, checkbox and select boxes
				 * To ensure backwards compatibility we'll remove the array if not a checkbox value
				 */
				if ( $field->inputType !== 'checkbox' ) {
					$name  = array_shift( $name );
					$price = array_shift( $price );
				}

			break;

			case 'shipping':
				$name = ( isset( $value['shipping_name'] ) ) ? $value['shipping_name'] . " ({$value['shipping_formatted']})" : '';
				$name = esc_html( $name );

				$price = ( isset( $value['shipping'] ) ) ? $value['shipping'] : '';
				$price = esc_html( $price );
			break;

			case 'quantity':
			default:
				$name  = $value;
				$price = $value;
			break;
		}

		/* Backwards Compatible – Standadised Format */
		$data['field'][ $field_id . '.' . $label ] = $name;
		$data['field'][ $field_id ]                = $name;
		$data['field'][ $label ]                   = $name;

		/* Name Format */
		$data['field'][ $field_id . '.' . $label . '_name' ] = $name;
		$data['field'][ $field_id . '_name' ]                = $name;
		$data['field'][ $label . '_name' ]                   = $name;

		/* New to v4 $form_data format to include the prices */
		$data['field'][ $field_id . '.' . $label . '_value' ] = $price;
		$data['field'][ $field_id . '_value' ]                = $price;
		$data['field'][ $label . '_value' ]                   = $price;

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
		$value = $this->value();
		$html  = '';

		switch ( $this->field->type ) {
			case 'product':
				if ( isset( $value['name'] ) ) {
					$html .= esc_html( $value['name'] . ' - ' . $value['price'] );
					$html .= $this->get_option_html( $value['options'] );
				}
			break;

			case 'option':
				if ( isset( $value['options'] ) ) {
					$html .= $this->get_option_html( $value['options'] );
				}
			break;

			case 'quantity':
				$html .= esc_html( $value );
			break;

			case 'shipping':
				if ( isset( $value['shipping_formatted'] ) ) {
					$html .= $value['shipping_formatted'];
				}
			break;

			case 'total':
				if ( isset( $value['total_formatted'] ) ) {
					$html .= $value['total_formatted'];
				}
			break;
		}

		return parent::html( $html );
	}

	/**
	 * Get a HTML list of the product's selected options
	 *
	 * @param  array  $options A list of the selected products
	 * @param  string $html    Pass in an existing HTML, or default to blank
	 *
	 * @return string         The finalised HTML
	 *
	 * @since 4.0
	 */
	public function get_option_html( $options, $html = '' ) {
		if ( is_array( $options ) ) {
			$html .= '<ul class="product_options">';

			foreach ( $options as $option ) {
				$html .= '<li>' . esc_html( $option['option_name'] . ' - ' . $option['price_formatted'] ) . '</li>';
			}

			$html .= '</ul>';
		}

		return $html;
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return string|array
	 *
	 * @since    4.0
	 *
	 * @internal We won't use a cache here because it's being handled in the Field_Products class, which is linked to this class through a static object
	 */
	public function value() {

		/* Get the full products array */
		$data = $this->products->value();

		/* Filter out the product information we require */
		if ( $this->field->type == 'product' && isset( $data['products'][ $this->field->id ] ) ) {
			return $data['products'][ $this->field->id ];
		}

		/* Filter out the options information we require */
		if ( $this->field->type == 'option' && isset( $data['products'][ $this->field->productField ]['options'] ) ) {
			return [ 'options' => $data['products'][ $this->field->productField ]['options'] ];
		}

		/* Filter out the quantity field */
		if ( $this->field->type == 'quantity' ) {
			return ( isset( $data['products'][ $this->field->productField ]['quantity'] ) ) ? $data['products'][ $this->field->productField ]['quantity'] : '';
		}

		/* Filter out the shipping field */
		if ( $this->field->type == 'shipping' && isset( $data['products_totals']['shipping'] ) ) {
			return [
				'shipping'           => esc_html( $data['products_totals']['shipping'] ),
				'shipping_formatted' => esc_html( $data['products_totals']['shipping_formatted'] ),
				'shipping_name'      => esc_html( $data['products_totals']['shipping_name'] ),
			];
		}

		/* Filter out the total field */
		if ( $this->field->type == 'total' && isset( $data['products_totals']['total'] ) ) {
			return [
				'total'           => esc_html( $data['products_totals']['total'] ),
				'total_formatted' => esc_html( $data['products_totals']['total_formatted'] ),
			];
		}

		return [];
	}
}
