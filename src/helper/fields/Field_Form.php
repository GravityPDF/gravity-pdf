<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Field_Container;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Abstract_Fields;

use GP_Field_Nested_Form;

use Exception;

/**
 * Gravity Forms Field
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.1
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
 * @since 5.1
 */
class Field_Form extends Helper_Abstract_Fields {

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
	 * @since 5.1
	 */
	public function __construct( $field, $entry, Helper_Abstract_Form $gform, Helper_Misc $misc ) {

		if ( ! is_object( $field ) || ! $field instanceof GP_Field_Nested_Form ) {
			throw new Exception( '$field needs to be in instance of GP_Field_Nested_Form' );
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
	 *
	 * @since 5.1
	 */
	public function html( $value = '', $label = true ) {
		/* Get the Nested Form */
		$form = $this->gform->get_form( $this->field->gpnfForm );
		if ( is_wp_error( $form ) ) {
			return parent::html( '' );
		}

		$html = '';

		/* Get the Nested Form Entries */
		$value = explode( ',', $this->value() );
		foreach ( $value as $id ) {
			$entry = $this->gform->get_entry( (int) trim( $id ) );
			if ( is_wp_error( $entry ) ) {
				continue;
			}

			/* Output the entry HTML mark-up */
			$html .= parent::html( $this->get_repeater_html( $form, $entry ) );
		}

		return $html;
	}

	/**
	 * Output a nested form entry
	 *
	 * @param array $form
	 * @param array $entry
	 *
	 * @return false|string
	 *
	 * @since 5.1
	 */
	public function get_repeater_html( $form, $entry ) {
		ob_start();

		$container = new Helper_Field_Container( [ 'class_map' => [] ] );
		$pdf_model = \GPDFAPI::get_mvc_class( 'Model_PDF' );
		$products  = new Field_Products( new \GF_Field(), $this->entry, $this->gform, $this->misc );

		/* Loop through the Repeater fields */
		foreach ( $form['fields'] as $field ) {
			/* Output a field using the standard method if not empty */
			$class = $pdf_model->get_field_class( $field, $form, $entry, $products );
			if ( ! $class->is_empty() ) {
				$field->cssClass = '';
				$container->generate( $field );
				echo $class->html();
				$container->close( $field );
			}
		}

		return ob_get_clean();
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return string|array
	 *
	 * @since 5.1
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		$this->cache( $this->get_value() );

		return $this->cache();
	}
}