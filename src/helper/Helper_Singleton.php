<?php

namespace GFPDF\Helper;

/**
 * A Sudo-Singleton Helper Class designed to hold our MVC classes
 * The main benefit is it more easily allows users to remove filters/actions Gravity PDF sets
 *
 * This isn't considered an actual `Singleton` pattern as we're not modifying our classes in any way (no static methods / disabling of the __construct), but it has the same objectives
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

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
	 * @param object $class
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	private function get_class_name( $class ) {
		$class_name = get_class( $class );

		if ( $pos = strrpos( $class_name, '\\' ) ) {
			return substr( $class_name, $pos + 1 );
		}

		return $class_name;
	}

	/**
	 * Add the already-initialised class to our singleton data store for later use
	 *
	 * @param object $class
	 *
	 * @since 4.0
	 */
	public function add_class( $class ) {
		$class_name = $this->get_class_name( $class );

		$this->classes[ $class_name ] = $class;
	}

	/**
	 * Retreive the desired class
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
