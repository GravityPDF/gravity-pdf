<?php

namespace GFPDF\Helper\Fields;

use GFCommon;
use GFPDF\Helper\Helper_Abstract_Fields;

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
 * Controls the display and output of Cosmic Giant's Legal Signing Signature field
 *
 * @since 6.10
 */
class Field_Fg_Ls_Signature extends Helper_Abstract_Fields {

	/**
	 * Display the HTML version of this field
	 *
	 * @param string $value
	 * @param bool   $label
	 *
	 * @return string
	 *
	 * @since 6.10
	 */
	public function html( $value = '', $label = true ) {

		$data = $this->value();

		if ( isset( $data['image'] ) ) {
			$html = sprintf(
				'<div class="legalsigning-field-signature__signed-signature"><img src="%1$s" /></div>',
				esc_attr( rgar( $data, 'image' ) )
			);
		} else {
			$width  = $this->field['canvasWidth'] ?? 400;
			$height = $this->field['canvasHeight'] ?? 150;

			$styles = [
				'background-color' => $this->field['backgroundColor'] ?? '#FFF',
				'color'            => $this->field['penColor'] ?? '#000',
				'font-family'      => ( $data['font'] ?? 'caveat' ) . ', cursive',
				'height'           => (int) $height,
				'width'            => $width < 400 ? (int) $width : 400,
			];

			$css = '';
			foreach ( $styles as $property => $val ) {
				$css .= "$property: $val;";
			}

			$html = sprintf(
				'<table style="%2$s">
					<tr>
						<td class="legalsigning-field-signature__signed-signature" style="%2$s">%1$s</td>
					</tr>
				</table>',
				esc_html( rgar( $data, 'name' ) ),
				esc_attr( $css )
			);
		}

		$html = sprintf(
			'<div class="legalsigning-field-signature__signed">
				<div class="legalsigning-field-signature__signed-by">
					<span class="legalsigning-field-signature__signed-by--inner">%2$s</span>
				</div>
				
				<div class="legalsigning-field-signature__signed-wrapper">%1$s</div>
				
				<div class="legalsigning-field-signature__signed-verification">
					<span class="legalsigning-field-signature__signed-verification--inner">%3$s</span>
				</div>
			</div>',
			$html,
			( $this->field['nameType'] ?? '' ) === 'initials' ? esc_html__( 'Initialed By', 'forgravity_legalsigning' ) : esc_html__( 'Signed By', 'forgravity_legalsigning' ), /* phpcs:ignore WordPress.WP.I18n.TextDomainMismatch */
			esc_html__( 'Signed using Legal Signing', 'forgravity_legalsigning' ) /* phpcs:ignore WordPress.WP.I18n.TextDomainMismatch */
		);

		return parent::html( $html );
	}

	/**
	 * Get form data array for current field
	 *
	 * @return array
	 *
	 * @since 6.10
	 */
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

	/**
	 * Get the standard GF value of this field
	 *
	 * @return string|array
	 *
	 * @since 6.10
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		$value = $this->get_value();
		$value = \GFCommon::is_json( $value ) ? json_decode( $value, true ) : [ 'image' => $value ];
		$this->cache( $value );

		return $this->cache();
	}
}
