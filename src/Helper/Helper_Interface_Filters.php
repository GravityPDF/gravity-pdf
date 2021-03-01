<?php

namespace GFPDF\Helper;

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
 * A simple interface to standardise how actions and filters should be applied in classes
 *
 * @since 4.0
 */
interface Helper_Interface_Filters {

	/**
	 * Apply any actions needed for the settings page
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function add_filters();
}
