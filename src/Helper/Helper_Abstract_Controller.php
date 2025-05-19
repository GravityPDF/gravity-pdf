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
 * A simple abstract class controllers can extent to share similar variables
 *
 * @since 4.0
 */
abstract class Helper_Abstract_Controller {

	/**
	 * Classes will store a model object
	 *
	 * @var object
	 *
	 * @since 4.0
	 */
	public $model = null;

	/**
	 * Classes will store a view object
	 *
	 * @var object
	 *
	 * @since 4.0
	 */
	public $view = null;

	/**
	 * Each controller should have an initialisation function
	 *
	 * @since 4.0
	 */
	abstract public function init();
}
