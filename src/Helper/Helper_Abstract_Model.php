<?php

namespace GFPDF\Helper;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A simple abstract class controlers can extent to share similar variables
 *
 * @since 4.0
 */
abstract class Helper_Abstract_Model {

	/**
	 * Classes will store a controler object to allow user access
	 *
	 * @var object
	 *
	 * @since 4.0
	 */
	private $controller = null;

	/**
	 * Add a controller setter function with type hinting to ensure compatiiblity
	 *
	 * @param \GFPDF\Helper\Helper_Abstract_Controller $class The controller class
	 *
	 * @since 4.0
	 */
	final public function setController( Helper_Abstract_Controller $class ) {
		$this->controller = $class;
	}

	/**
	 * Get the controller
	 *
	 * @since 4.0
	 *
	 * @return \GFPDF\Helper\Helper_Abstract_Controller
	 */
	final public function getController() {
		return $this->controller;
	}
}
