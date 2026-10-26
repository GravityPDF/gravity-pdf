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
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
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
	 * @var string[]|null
	 */
	private $agreements;

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
		return $this->agreements() === [];
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
		$html  = "
			<div class='terms-of-service-text'>$terms</div>
		";

		foreach ( $this->agreements() as $agreement ) {
			$html .= $this->get_agreement_markup( '&#10004;', $agreement );
		}

		if ( $this->is_field_empty() ) {
			$html .= $this->get_agreement_markup( '&#10006;', __( 'Not accepted', 'gravity-pdf' ) );
		}

		return parent::html( $html );
	}

	/**
	 * Include every accepted agreement alongside the standard keys, which keep the first one
	 *
	 * @return array
	 *
	 * @since 7.0
	 */
	public function form_data() {
		$data       = parent::form_data();
		$label      = $this->get_label();
		$field_id   = (int) $this->field->id;
		$agreements = $this->agreements();

		$data['field'][ $field_id . '.' . $label . '_agreements' ] = $agreements;
		$data['field'][ $field_id . '_agreements' ]                = $agreements;
		$data['field'][ $label . '_agreements' ]                   = $agreements;

		return $data;
	}

	/**
	 * Get the first accepted agreement
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function value() {
		return $this->agreements()[0] ?? '';
	}

	/**
	 * Get every accepted agreement, as the Gravity Wiz perk supports more than one checkbox per field
	 *
	 * @return string[]
	 *
	 * @since 7.0
	 */
	public function agreements(): array {
		if ( $this->agreements === null ) {
			$values = array_filter(
				(array) $this->get_value(),
				function ( $value ) {
					return is_string( $value ) && trim( $value ) !== '';
				}
			);

			$this->agreements = array_map( 'esc_html', array_values( $values ) );
		}

		return $this->agreements;
	}

	/**
	 * @param string $tick  The tick or cross HTML entity
	 * @param string $label Escaped agreement text
	 *
	 * @return string
	 *
	 * @since 7.0
	 */
	protected function get_agreement_markup( string $tick, string $label ): string {
		return "<div class='terms-of-service-agreement'><span class='terms-of-service-tick' style='font-family:dejavusans,sans-serif;'>$tick</span> $label</div>";
	}
}
