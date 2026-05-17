<?php

namespace GFPDF\Helper;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 6.15.0
 */
interface Helper_Interface_Extension_Uninstaller {

	/**
	 * Run a custom uninstall action when the Gravity PDF uninstaller is running
	 *
	 * @return void
	 * @since  6.15.0
	 */
	public function uninstall();
}
