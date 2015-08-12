<?php

namespace GFPDF\Helper;

use GFPDF\Helper\Helper_Abstract_Model;

use WP_Error;

/**
 * Abstract Helper View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

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
 * A simple abstract class views can extent to share similar variables
 * @since 4.0
 */
abstract class Helper_Abstract_View extends Helper_Abstract_Model {
	/**
	 * Each object should have a view name
	 * @var String
	 * @since 4.0
	 */
	protected $ViewType = null;

	/**
	 * Enable a private data cache we can set and retrive information from
	 * @var array
	 * @since 4.0
	 */
	protected $data = array();


	/**
	 * Triggered when invoking inaccessible methods in an object context
	 * Use it to load in our views
	 * @param  String $name     Template name to load
	 * @param  Array  $arguments Pass in additional parameters to the template view if needed
	 * @return void
	 * @since 4.0
	 */
	final public function __call( $name, $arguments ) {
		/* check if we have any arguments */
		$vars = $this->data;
		if ( isset($arguments[0]) && is_array( $arguments[0] ) ) {
			$vars = array_merge( $arguments[0], $this->data );
		}

		/* load the about page view */
		return $this->load( $name, $vars );
	}

	/**
	 * Load a view file based on the filename and type
	 * @param  String  $filename The filename to load
	 * @param  Array   $args Variables to pass to the included file
	 * @param  Boolean $output Whether to automatically display the included file or return it's output as a String
	 * @return String/Object           The loaded file, or WP_ERROR
	 * @since 4.0
	 */
	final protected function load( $filename, $args = array(), $output = true ) {
		$path = PDF_PLUGIN_DIR . 'src/views/html/' . $this->ViewType . '/' . $filename . '.php';

		if ( is_readable( $path ) ) {
			/* for backwards compatibility extract the $args variable */
			extract( $args, EXTR_SKIP ); /* skip any arguments that would clash - i.e filename, args, output, path, this */

			if ( $output ) {
				include $path;
				return true;
			} else {
				return $this->buffer( $path, $args );
			}
		}
		return new WP_Error( 'invalid_path', sprintf( __( 'Cannot find file %s', 'gravitypdf' ), $filename ) );
	}

	/**
	 * Store output of included file in a buffer and return
	 * @param  String $path File path to include
	 * @param  Array  $args Variables to pass to the included file
	 * @return String       The contents of the included file
	 * @since 4.0
	 */
	final private function buffer( $path, $args = array() ) {
		/* for backwards compatibility extract the $args variable */
		extract( $args, EXTR_SKIP ); /* skip any arguments that would clash - i.e filename, args, output, path, this */

		ob_start();
		include $path;
		return ob_get_clean();
	}
}
