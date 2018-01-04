<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Abstract_Fields;

use GFCommon;
use GF_Field_Section;
use GF_Field;

use GPDFAPI;

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
class Field_Section extends Helper_Abstract_Fields {

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

		if ( ! is_object( $field ) || ! $field instanceof GF_Field_Section ) {
			throw new Exception( '$field needs to be in instance of GF_Field_Section' );
		}

		/* call our parent method */
		parent::__construct( $field, $entry, $gform, $misc );
	}

	/**
	 * Used to check if the current field has a value
	 *
	 * @since 4.0
	 */
	public function is_empty() {

		/* Run default checks to see if section break contains fields with values */
		if ( GFCommon::is_section_empty( $this->field, $this->form, $this->entry ) ) {
			return true;
		}

		/* Run our own checks against the fields to test for issues */
		$fields    = GFCommon::get_section_fields( $this->form, $this->field->id );
		$PDF_model = GPDFAPI::get_mvc_class( 'Model_PDF' );
		$products  = new Field_Products( new GF_Field(), $this->entry, $this->gform, $this->misc );

		$empty = true;
		foreach ( $fields as $field ) {
			if ( 'section' !== $field->type ) {
				$class = $PDF_model->get_field_class( $field, $this->form, $this->entry, $products );
				if ( ! $class->is_empty() ) {
					$empty = false;
					break;
				}
			}
		}

		/* Our custom empty checks determined the fields in the section break are infact empty */
		if ( $empty ) {
			return true;
		}

		return false;
	}

	/**
	 * Return the HTML form data
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function form_data() {

		$data = [];

		$data['section_break'][ $this->field->id ] = $this->value();

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
		/* sanitize the HTML */
		$section = $this->value(); /* allow the same HTML as per the post editor */

		$html = '<div id="field-' . $this->field->id . '" class="gfpdf-section-title gfpdf-field '. $this->field->cssClass .'">';
		$html .= '<h3>' . $section['title'] . '</h3>';

		if ( ! empty( $value ) ) {
			$html .= '<div id="field-' . $this->field->id . '-desc" class="gfpdf-section-description gfpdf-field">' . $section['description'] . '</div>';
		}

		$html .= '</div>';

		/**
		 * See https://gravitypdf.com/documentation/v4/gfpdf_field_section_break_html/ for more information about this filter
		 *
		 * @since 4.1
		 */
		return apply_filters( 'gfpdf_field_section_break_html',
			$html,
			$section['title'],
			$section['description'],
			$value,
			$this->field,
			$this->form,
			$this->entry,
			$this
		);
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

		$this->cache( [
			'title'       => esc_html( $this->field->label ),
			'description' => wp_kses_post(
				$this->gform->process_tags( $this->field->description, $this->form, $this->entry )
			),
		] );

		return $this->cache();
	}
}
