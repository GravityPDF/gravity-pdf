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
 * Class Helper_Interface_Config_Settings
 *
 * @package GFPDF\Helper
 */
interface Helper_Interface_Config_Settings {

	/**
	 * Setter
	 *
	 * @since 6.0
	 */
	public function set_settings( array $settings );

	/**
	 * Getter
	 *
	 * @since 6.0
	 */
	public function get_settings(): array;
}
