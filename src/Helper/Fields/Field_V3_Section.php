<?php

namespace GFPDF\Helper\Fields;

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
class Field_V3_Section extends Field_Section {

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

		$html = '<h2 class="default entry-view-section-break" id="' . esc_attr( 'field-' . $this->field->id ) . '">' . esc_html( $section['title'] ) . '</h2>';

		if ( ! empty( $value ) ) {
			$html .= '<div class="default entry-view-section-break entry-view-section-break-content">' . Kses::parse( $section['description'] ) . '</div>';
		}

		if ( $this->get_output() ) {
			Kses::output( $html );
		}

		return $html;
	}
}
