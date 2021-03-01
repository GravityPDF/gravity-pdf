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
 * A simple abstract class controllers can extent to share similar variables
 *
 * @since 4.0
 */
abstract class Helper_Abstract_Model {

	/**
	 * Classes will store a controller object to allow user access
	 *
	 * @var Helper_Abstract_Controller
	 *
	 * @since 4.0
	 */
	private $controller = null;

	/**
	 * Add a controller setter function with type hinting to ensure compatibility
	 *
	 * @param Helper_Abstract_Controller $class The controller class
	 *
	 * @since 4.0
	 */
	final public function setController( Helper_Abstract_Controller $class ) {
		$this->controller = $class;
	}

	/**
	 * Get the controller
	 *
	 * @return Helper_Abstract_Controller
	 * @since 4.0
	 *
	 */
	final public function getController() {
		return $this->controller;
	}
}
