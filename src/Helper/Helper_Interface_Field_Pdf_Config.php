<?php

namespace GFPDF\Helper;

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
 * An interface for PDF Fields to support storing and retrieving the current PDF configuration
 *
 * @since 6.9
 */
interface Helper_Interface_Field_Pdf_Config {

	/**
	 * Set the current PDF configuration
	 *
	 * @param array $config Should contain the keys 'meta' and 'settings'
	 *
	 * @return void
	 *
	 * @since 6.9
	 */
	public function set_pdf_config( $config );

	/**
	 * Get the current PDF configuration
	 *
	 * @return array
	 *
	 * @since 6.9
	 */
	public function get_pdf_config();
}
