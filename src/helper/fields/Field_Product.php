<?php

namespace GFPDF\Helper\Fields;

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
 * Controls the individual display and output of:
 * product, option, shipping, quantity and total fields
 *
 * If you just want the complete product list / HTML table use the Field_Products class
 *
 * @since 4.0
 */
class Field_Product extends Helper_Abstract_Fields
{

	/**
	 * Our products class which handles all Gravity Form products fields in bulk
	 * @var Class
	 */
	private $products;

	/**
	 * Check the appropriate variables are parsed in send to the parent construct
	 * @param Object $field The GF_Field_* Object
	 * @param Array  $entry The Gravity Forms Entry
	 * @param Object $products A class that gets the full breakdown of products for the form
	 * @since 4.0
	 */
	public function __construct( $field, $entry, Helper_Abstract_Fields $products ) {

		/* call our parent method */
		parent::__construct( $field, $entry );

		/* store our products class */
		$this->products = $products;
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

		switch ( $this->field->type ) {
			case 'product':
				$name  = $value['name'] . " ({$value['price']})";
				$price = $value['price_unformatted'];
			break;

			case 'option':
				if ( sizeof( $value['options'] ) > 1 ) {
					foreach ( $value['options'] as $option ) {
						$name[]  = $option['option_name'] . " ({$option['price_formatted']})";
						$price[] = $option['price'];
					}
				} else {
					$name  = $value['options'][0]['option_name'] . " ({$value['options'][0]['price_formatted']})";
					$price = $value['options'][0]['price'];
				}
			break;

			case 'shipping':
				$name  = $value['shipping_name'] . " ({$value['shipping_formatted']})";
				$price = $value['shipping'];
			break;

			case 'quantity':
			default:
				$name  = $value;
				$price = $value;
			break;
		}

		/* Standadised Format */
		$data['field'][ $this->field->id . '.' . $label ]           = $name;
		$data['field'][ $this->field->id ]                          = $name;
		$data['field'][ $label ]                                    = $name;

		/* Name Format */
		$data['field'][ $this->field->id . '.' . $label . '_name' ] = $name;
		$data['field'][ $this->field->id . '_name' ]                = $name;
		$data['field'][ $label . '_name' ]                          = $name;

		/* Value */
		$data['field'][ $this->field->id . '.' . $label . '_value' ] = $price;
		$data['field'][ $this->field->id . '_value' ]                = $price;
		$data['field'][ $label . '_value' ]                          = $price;

		return $data;
	}

	/**
	 * Display the HTML version of this field
	 * @return String
	 * @since 4.0
	 */
	public function html( $value = '', $label = true ) {
		$value = $this->value();
		$hmlt  = '';

		switch ( $this->field->type ) {
			case 'product':
				$html .= $value['name'] . ' - ' . $value['price'];
				$html .= $this->get_option_html( $value['options'] );
			break;

			case 'option':
				$html .= $this->get_option_html( $value['options'] );
			break;

			case 'quantity':
				$html .= $value;
			break;

			case 'shipping':
				$html .= $value['shipping_formatted'];
			break;

			case 'total':
				$html .= $value['total_formatted'];
			break;
		}

		return parent::html( $html );
	}

	/**
	 * Get a HTML list of the product's selected options
	 * @param  Array  $options A list of the selected products
	 * @param  string $html   Pass in an existing HTML, or default to blank
	 * @return string         The finalised HTML
	 */
	public function get_option_html( $options, $html = '' ) {
		if ( is_array( $options ) ) {
			$html .= '<ul class="product_options">';

			foreach ( $options as $option ) {
				$html .= '<li>' . $option['option_name'] . ' - ' . $option['price_formatted'] . '</li>';
			}

			$html .= '</ul>';
		}

		return $html;
	}

	/**
	 * Get the standard GF value of this field
	 * @return String/Array
	 * @since 4.0
	 * @internal We won't use a cache here because it's being handled in the Field_Products class, which is linked to this class through a static object
	 */
	public function value() {
		/* Get the full products array */
		$data = $this->products->value();

		/* Filter out the product information we require */
		if ( $this->field->type == 'product' && isset($data['products'][$this->field->id]) ) {
			return $data['products'][$this->field->id];
		}

		/* Filter out the options information we require */
		if ( $this->field->type == 'option' && isset($data['products'][$this->field->productField]['options']) ) {
			return array( 'options' => $data['products'][$this->field->productField]['options'] );
		}

		/* Filter out the quantity field */
		if ( $this->field->type == 'quantity' && isset($data['products'][$this->field->productField]['quantity']) ) {
			return $data['products'][$this->field->productField]['quantity'];
		}

		/* Filter out the shipping field */
		if ( $this->field->type == 'shipping' && isset($data['products_totals']['shipping']) ) {
			return array(
				'shipping'           => $data['products_totals']['shipping'],
				'shipping_formatted' => $data['products_totals']['shipping_formatted'],
				'shipping_name'      => $data['products_totals']['shipping_name'],
			);
		}

		/* Filter out the total field */
		if ( $this->field->type == 'total' && isset($data['products_totals']['total']) ) {
			return array(
				'total'           => $data['products_totals']['total'],
				'total_formatted' => $data['products_totals']['total_formatted'],
			);
		}

		return array();
	}
}
