<?php

namespace GFPDF\Helper\Fields;

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
class Field_Poll extends Helper_Abstract_Fields_Input_Type {

	/**
	 * Return the HTML form data
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function form_data() {

		$data     = [];
		$value    = $this->value();
		$field_id = (int) $this->field->id;
		$label    = $this->get_label();

		if ( isset( $value[0] ) ) {
			$field = [];
			foreach ( $value as $item ) {
				/* For backwards compatibility, we'll wrap these in their own array key */
				$field[0][] = $item['label'];
			}
		} else {
			$field = $value['label'] ?? '';
		}

		$data[ $field_id . '.' . $label ]           = $field;
		$data[ $field_id . '.' . $label . '_name' ] = $field; /* for backwards compatibility */
		$data[ $field_id ]                          = $field;
		$data[ $label ]                             = $field;

		return [ 'field' => $data ];
	}
}
