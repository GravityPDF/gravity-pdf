<?php

namespace GFPDF\Helper\Fields;

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

		$html = '<h2 class="default entry-view-section-break" id="field-' . $this->field->id . '">' . $section['title'] . '</h2>';

		if ( ! empty( $value ) ) {
			$html .= '<div class="default entry-view-section-break entry-view-section-break-content">' . $section['description'] . '</div>';
		}

		return $html;
	}

}
