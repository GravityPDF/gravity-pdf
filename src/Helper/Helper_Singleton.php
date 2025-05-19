<?php

namespace GFPDF\Helper;

/*
 * A Sudo-Singleton Helper Class designed to hold our MVC classes
 * The main benefit is it more easily allows users to remove filters/actions Gravity PDF sets
 *
 * This isn't considered an actual `Singleton` pattern as we're not modifying our classes in any way (no static methods
 * or disabling of the __construct), but it has the same objectives.
 */

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
 * @since 4.0
 */
class Helper_Singleton {

	/**
	 * Location for the classes
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	private $classes = [];

	/**
	 * Get the class name without the namespace
	 *
	 * @param object $class_object
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	private function get_class_name( $class_object ) {
		$class_name = get_class( $class_object );
		$pos        = strrpos( $class_name, '\\' );

		if ( $pos !== false ) {
			return substr( $class_name, $pos + 1 );
		}

		return $class_name;
	}

	/**
	 * Add the already-initialised class to our singleton data store for later use
	 *
	 * @param object $class_object
	 *
	 * @since 4.0
	 */
	public function add_class( $class_object ) {
		$class_name = $this->get_class_name( $class_object );

		$this->classes[ $class_name ] = $class_object;
	}

	/**
	 * Retrieve the desired class
	 *
	 * @param string $name
	 *
	 * @return object|bool
	 *
	 * @since 4.0
	 *
	 */
	public function get_class( $name ) {
		if ( isset( $this->classes[ $name ] ) ) {
			return $this->classes[ $name ];
		}

		return false;
	}
}
