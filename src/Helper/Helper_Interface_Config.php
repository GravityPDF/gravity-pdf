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
interface Helper_Interface_Config {

	/**
	 * Classes should return a key => value array with the template settings
	 * The array should be multidimensional with the top-level keys being either "core" or "fields"
	 * The "core" array will allow boolean values to be passed to enable core features, such as "headers", "footers" or "backgrounds"
	 * The "fields" array allows a template to load in custom fields. It is based on our \GFPDF\Helper\Helper_Abstract_Options Settings API
	 * See the Helper_Options_Fields::register_settings() method for the exact fields that can be passed in
	 *
	 * @return array
	 * @since 4.0
	 *
	 */
	public function configuration();
}
