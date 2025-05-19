<?php

namespace GFPDF\Helper\Fields;

use Exception;
use GF_Field_Checkbox;
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
 * @since 4.0
 */
class Field_Tos extends Helper_Abstract_Fields {

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

		if ( ! is_object( $field ) || ! $field instanceof GF_Field_Checkbox ) {
			throw new Exception( '$field needs to be in instance of GF_Field_Checkbox' );
		}

		/* call our parent method */
		parent::__construct( $field, $entry, $gform, $misc );
	}

	/**
	 * Always display this field in the PDF
	 *
	 * @return bool
	 * @since    4.2
	 *
	 */
	public function is_empty() {
		return false;
	}

	/**
	 * Actually check if the field has a value
	 *
	 * @return bool
	 * @since 4.2
	 *
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

		$terms = Kses::parse(
			wpautop(
				$this->gform->process_tags( $this->field->gwtermsofservice_terms, $this->form, $this->entry )
			)
		);
		$value = $this->value();

		$html = "
			<div class='terms-of-service-text'>$terms</div>			
		";

		if ( ! $this->is_field_empty() ) {
			$html .= "<div class='terms-of-service-agreement'><span class='terms-of-service-tick' style='font-family:dejavusans,sans-serif;'>&#10004;</span> $value</div>";
		} else {
			$not_accepted_text = __( 'Not accepted', 'gravity-pdf' );
			$html             .= "<div class='terms-of-service-agreement'><span class='terms-of-service-tick' style='font-family:dejavusans,sans-serif;'>&#10006;</span> $not_accepted_text</div>";
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
