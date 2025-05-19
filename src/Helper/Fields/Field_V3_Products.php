<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_QueryPath;
use GFPDF\Statics\Kses;
use GFPDF_Vendor\QueryPath\Exception;

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
		$output_enabled = false;
		if ( $this->get_output() ) {
			$this->disable_output();
			$output_enabled = true;
		}

		$html = parent::html( $value, $label );

		if ( $output_enabled ) {
			$this->enable_output();
		}

		/* Format the order label correctly */
		$label = apply_filters( 'gform_order_label', esc_html__( 'Order', 'gravityforms' ), $this->form['id'] );
		$label = apply_filters( 'gform_order_label_' . $this->form['id'], $label, $this->form['id'] );

		$heading = '<h2 class="default entry-view-section-break">' . esc_html( $label ) . '</h2>';

		/* Pull out the .entry-products table from the HTML using querypath */
		$qp    = new Helper_QueryPath();
		$table = $qp->html5( $html, 'div.inner-container' )->innerHTML();

		$html  = $heading;
		$html .= $table;

		if ( $this->get_output() ) {
			Kses::output( $html );
		}

		return Kses::parse( $html );
	}
}
