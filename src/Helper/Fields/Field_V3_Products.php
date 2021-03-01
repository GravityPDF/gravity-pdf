<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_QueryPath;
use GFPDF_Vendor\QueryPath\Exception;

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
class Field_V3_Products extends Field_Products {


	/**
	 * Display the HTML version of this field
	 *
	 * @param string $value
	 * @param bool   $label
	 *
	 * @return string
	 *
	 * @throws Exception
	 * @since 4.0
	 */
	public function html( $value = '', $label = true ) {
		$html = parent::html( $value, $label );

		/* Format the order label correctly */
		$label = apply_filters( 'gform_order_label', esc_html__( 'Order', 'gravityforms' ), $this->form->id );
		$label = apply_filters( 'gform_order_label_' . $this->form->id, $label, $this->form->id );

		$heading = '<h2 class="default entry-view-section-break">' . $label . '</h2>';

		/* Pull out the .entry-products table from the HTML using querypath */
		$qp    = new Helper_QueryPath();
		$table = $qp->html5( $html, 'div.inner-container' )->innerHTML5();

		$html  = $heading;
		$html .= $table;

		return $html;
	}

}
