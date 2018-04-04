<?php

namespace GFPDF\Helper;

use GFFormsModel;
use GF_Field;
use GFCache;
use GFCommon;

use Exception;

/**
 * Abstract Helper Fields
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

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
 * Helper fields can be extended to allow each Gravity Form field type to be displayed correctly
 * We found the default GF display functionality isn't quite up to par for the Gravity PDF requirements
 *
 * @since 4.0
 */
abstract class Helper_Abstract_Field_Products extends Helper_Abstract_Fields {
	/**
	 * Our products class which handles all Gravity Form products fields in bulk
	 *
	 * @var \GFPDF\Helper\Helper_Abstract_Fields
	 */
	protected $products;

	/**
	 * Store our products class for later user
	 *
	 * @param \GFPDF\Helper\Helper_Abstract_Fields $products
	 *
	 * @since 4.3
	 */
	public function set_products( Helper_Abstract_Fields $products ) {
		$this->products = $products;
	}

	/**
	 * @return Helper_Abstract_Field_Products
	 *
	 * @since 4.3
	 */
	public function get_products() {
		return $this->products;
	}

	/**
	 * Prepare the product field form data fields
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return array
	 *
	 * @since 4.3
	 */
	protected function set_form_data( $name, $value ) {
		$data = [
			'field' => [],
		];

		$label    = $this->get_label();
		$field_id = (int) $this->field->id;

		/* Backwards Compatible – Standadised Format */
		$data['field'][ $field_id . '.' . $label ] = $name;
		$data['field'][ $field_id ]                = $name;
		$data['field'][ $label ]                   = $name;

		/* Name Format */
		$data['field'][ $field_id . '.' . $label . '_name' ] = $name;
		$data['field'][ $field_id . '_name' ]                = $name;
		$data['field'][ $label . '_name' ]                   = $name;

		/* New to v4 $form_data format to include the prices */
		$data['field'][ $field_id . '.' . $label . '_value' ] = $value;
		$data['field'][ $field_id . '_value' ]                = $value;
		$data['field'][ $label . '_value' ]                   = $value;

		return $data;
	}
}
