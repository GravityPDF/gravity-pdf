<?php

namespace GFPDF\Helper;

use GFPDF\Helper\Fields\Field_Products;

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
 * Helper fields can be extended to allow each Gravity Form field type to be displayed correctly
 * We found the default GF display functionality isn't quite up to par for the Gravity PDF requirements
 *
 * @since 4.0
 */
abstract class Helper_Abstract_Field_Products extends Helper_Abstract_Fields {
	/**
	 * Our products class which handles all Gravity Form products fields in bulk
	 *
	 * @var Field_Products
	 */
	protected $products;

	/**
	 * Store our products class for later user
	 *
	 * @param Field_Products $products
	 *
	 * @since 4.3
	 */
	public function set_products( Field_Products $products ) {
		$this->products = $products;
	}

	/**
	 * @return Field_Products
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

		/* Backwards Compatible – Standardised Format */
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
