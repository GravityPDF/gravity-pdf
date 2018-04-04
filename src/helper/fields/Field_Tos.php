<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Abstract_Fields;

use GF_Field_Checkbox;

use Exception;

/**
 * Gravity Forms Field
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
 * Controls the display and output of a Gravity Form field
 *
 * @since 4.0
 */
class Field_Tos extends Helper_Abstract_Fields {

	/**
	 * Check the appropriate variables are parsed in send to the parent construct
	 *
	 * @param object                             $field The GF_Field_* Object
	 * @param array                              $entry The Gravity Forms Entry
	 *
	 * @param \GFPDF\Helper\Helper_Abstract_Form $gform
	 * @param \GFPDF\Helper\Helper_Misc          $misc
	 *
	 * @throws Exception
	 *
	 * @since 4.0
	 */
	public function __construct( $field, $entry, Helper_Abstract_Form $gform, Helper_Misc $misc ) {

		if ( ! is_object( $field ) || ! $field instanceof GF_Field_Checkbox ) {
			throw new Exception( '$field needs to be in instance of GF_Field_Checkbox' );
		}

		/* call our parent method */
		parent::__construct( $field, $entry, $gform, $misc );
	}

	/**
	 * Always display this field in the PDF
	 *
	 * @since    4.2
	 *
	 * @return bool
	 */
	public function is_empty() {
		return false;
	}

	/**
	 * Actually check if the field has a value
	 *
	 * @since 4.2
	 *
	 * @return bool
	 */
	public function is_field_empty() {
		return parent::is_empty();
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

		$terms = wp_kses_post(
			wpautop(
				$this->gform->process_tags( $this->field->gwtermsofservice_terms , $this->form, $this->entry )
			)
		);
		$value = $this->value();

		$html = "
			<div class='terms-of-service-text'>$terms</div>			
		";

		if ( ! $this->is_field_empty() ) {
			$html .= "<div class='terms-of-service-agreement'><span class='terms-of-service-tick' style='font-family:dejavusans;'>&#10004;</span> $value</div>";
		} else {
			$not_accepted_text = __( 'Not accepted', 'gravity-forms-pdf-extended' );
			$html              .= "<div class='terms-of-service-agreement'><span class='terms-of-service-tick' style='font-family:dejavusans;'>&#10006;</span> $not_accepted_text</div>";
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

		$value_array = $this->get_value();
		$value       = esc_html( $value_array[ $this->field->id . '.1' ] );

		$this->cache( $value );

		return $this->cache();
	}
}