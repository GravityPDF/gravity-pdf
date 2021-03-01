<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;

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
 * Controls the Gravity PDF Shortcode View / Display
 *
 * @since 4.0
 */
class View_Shortcodes extends Helper_Abstract_View {

	/**
	 * Set the view's name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_type = 'Shortcodes';

	/**
	 * Shortcode Error: Entry ID not passed through the shortcode - directly or through the URL.
	 *
	 * @return string The error message
	 *
	 * @since 4.0
	 */
	public function no_entry_id() {
		return $this->load( 'no_entry_id', [], false );
	}

	/**
	 * Shortcode Error: Entry ID, Form ID or PDF ID mismatch. Cannot get PDF configuration.
	 *
	 * @return string The error message
	 *
	 * @since 4.0
	 */
	public function invalid_pdf_config() {
		return $this->load( 'invalid_pdf_config', [], false );
	}

	/**
	 * Shortcode Error: PDF configuration not active
	 *
	 * @return string The error message
	 *
	 * @since 4.0
	 */
	public function pdf_not_active() {
		return $this->load( 'pdf_not_active', [], false );
	}

	/**
	 * Shortcode Error: PDF Conditional Logic not met
	 *
	 * @return string The error message
	 *
	 * @since 4.0
	 */
	public function conditional_logic_not_met() {
		return $this->load( 'conditional_logic_not_met', [], false );
	}

	/**
	 * Generate the Gravity PDF link
	 *
	 * @param array $attr The parameters
	 *
	 * @return string       The Shortcode Markup
	 *
	 * @since 4.0
	 */
	public function display_gravitypdf_shortcode( $attr ) {
		return $this->load( 'gravitypdf', $attr, false );
	}
}
