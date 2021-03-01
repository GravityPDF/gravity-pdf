<?php

namespace GFPDF\Helper;

use WP_Error;

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
 * A simple abstract class view can extent to share similar variables
 *
 * @since 4.0
 */
abstract class Helper_Abstract_View extends Helper_Abstract_Model {

	/**
	 * Each object should have a view name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_type = null;

	/**
	 * Enable a private data cache we can set and retrieve information from
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	protected $data_cache = [];

	/**
	 * Automatically define our constructor which will set our data cache
	 *
	 * @param array $data An array of data to pass to the view
	 *
	 * @since 4.0
	 */
	public function __construct( $data = [] ) {
		$this->data_cache = $data;
	}

	/**
	 * Triggered when invoking inaccessible methods in an object context
	 * Use it to load in our view
	 *
	 * @param string $name      Template name to load
	 * @param array  $arguments Pass in additional parameters to the template view if needed
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	final public function __call( $name, $arguments ) {
		/* check if we have any arguments */
		$vars = $this->data_cache;
		if ( isset( $arguments[0] ) && is_array( $arguments[0] ) ) {
			$vars = array_merge( $arguments[0], $vars );
		}

		/* load the about page view */

		return $this->load( $name, $vars );
	}

	/**
	 * Get the full path to the current view
	 *
	 * @return string The path
	 *
	 * @since 4.0.1
	 */
	final public function get_view_dir_path() {
		return PDF_PLUGIN_DIR . 'src/View/html/' . $this->view_type . '/';
	}

	/**
	 * Load a view file based on the filename and type
	 *
	 * @param string  $filename The filename to load
	 * @param array   $args     Variables to pass to the included file
	 * @param boolean $output   Whether to automatically display the included file or return it's output as a String
	 *
	 * @return string|WP_Error           The loaded file, or WP_ERROR
	 *
	 * @since 4.0
	 */
	final protected function load( $filename, $args = [], $output = true ) {
		$path = $this->get_view_dir_path() . $filename . '.php';
		$args = array_merge( $this->data_cache, $args );

		if ( is_readable( $path ) ) {

			if ( $output ) {

				/* Include our $gfpdf object automatically */
				global $gfpdf;

				/*
				 * For backwards compatibility extract the $args variable
				 * phpcs:disable -- disable warning about `extract`
				 */
				extract( $args, EXTR_SKIP ); /* skip any arguments that would clash - i.e filename, args, output, path, this */
				/* phpcs:enable */

				include $path;

				return true;
			} else {
				return $this->buffer( $path, $args );
			}
		}

		return new WP_Error( 'invalid_path', sprintf( esc_html__( 'Cannot find file %s', 'gravity-forms-pdf-extended' ), $filename ) );
	}

	/**
	 * Store output of included file in a buffer and return
	 *
	 * @param string $path File path to include
	 * @param array  $args Variables to pass to the included file
	 *
	 * @return string       The contents of the included file
	 *
	 * @since 4.0
	 */
	private function buffer( $path, $args = [] ) {
		/*
		 * for backwards compatibility extract the $args variable
		 * phpcs:disable -- disable warning about `extract`
		 */
		extract( $args, EXTR_SKIP ); /* skip any arguments that would clash - i.e filename, args, output, path, this */
		/* phpcs:enable */

		/* Include our $gfpdf object automatically */
		global $gfpdf;

		ob_start();
		include $path;

		return ob_get_clean();
	}
}
