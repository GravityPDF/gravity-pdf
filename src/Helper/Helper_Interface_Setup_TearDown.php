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
 * A simple interface to standardise how install and delete functionality is executed
 *
 * @since 4.0
 */
interface Helper_Interface_Setup_TearDown {

	/**
	 * This method will be triggered when a new PDF template is installed.
	 * It should contain any additional install code required.
	 *
	 * @return void
	 *
	 * @since 4.1
	 */
	public function setUp();

	/**
	 * This method will be triggered when a PDF template is deleted.
	 * It should contain any additional delete code required, like the removal
	 * of non-core files (i.e anything besides the template, template image and template config)
	 *
	 * @return void
	 *
	 * @since 4.1
	 */
	public function tearDown();
}
