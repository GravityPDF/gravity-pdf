<?php

namespace GFPDF\Helper\Fields;

use Exception;
use GF_Field_Consent;
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
 * @since 6.10
 */
class Field_Fg_Ls_Consent extends Field_Consent {
	public function form_data() {
		$label    = $this->get_label();
		$field_id = (int) $this->field->id;

		$data = [];

		/* Maintain backwards compatibility */
		$value                            = $this->get_value();
		$data[ $field_id . '.' . $label ] = $value;
		$data[ $field_id ]                = $value;
		$data[ $label ]                   = $value;

		$value                                   = $this->value();
		$data[ $field_id . '.' . $label . '.2' ] = $value;
		$data[ $field_id . '.2' ]                = $value;
		$data[ $label . '.2' ]                   = $value;

		return [ 'field' => $data ];
	}
}
