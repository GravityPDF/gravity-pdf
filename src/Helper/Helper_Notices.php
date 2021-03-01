<?php

namespace GFPDF\Helper;

use GFCommon;
use GFForms;

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
 * @since 4.0
 *
 * @todo  Implement Transient Support so errors and notices can be saved
 */
class Helper_Notices implements Helper_Interface_Actions {

	/**
	 * Holds any notices that we've triggered
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	private $notices = [];

	/**
	 * Holds any errors that we've triggered
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	private $errors = [];

	/**
	 * Initialise our class defaults
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function init() {
		$this->add_actions();
	}

	/**
	 * Apply any actions needed to implement notices
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function add_actions() {
		add_action( $this->get_notice_type(), [ $this, 'process' ] );
	}

	/**
	 * Determine which notice should be triggered
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	private function get_notice_type() {
		if ( is_multisite() && is_network_admin() ) {
			return 'network_admin_notices';
		}

		return 'admin_notices';
	}

	/**
	 * Public endpoint for adding a new notice
	 *
	 * @param string $notice The message to be queued
	 * @param string $class  The class that should be included with the notice box
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function add_notice( $notice, $class = '' ) {

		if ( GFForms::is_gravity_page() ) {
			GFCommon::add_message( $notice );

			return;
		}

		if ( empty( $class ) ) {
			$this->notices[] = $notice;
		} else {
			$this->notices[ $class ] = $notice;
		}
	}

	/**
	 * Public endpoint for adding a new notice
	 *
	 * @param string $error The error message that should be added
	 * @param string $class Any class names that should apply to the error
	 *
	 * @internal param string $notice The message to be queued
	 *
	 * @since    4.0
	 */
	public function add_error( $error, $class = '' ) {

		if ( GFForms::is_gravity_page() ) {
			GFCommon::add_error_message( $error );

			return;
		}

		if ( empty( $class ) ) {
			$this->errors[] = $error;
		} else {
			$this->errors[ $class ] = $error;
		}
	}

	/**
	 * Check if we currently have a notice
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function has_notice() {
		if ( count( $this->notices ) > 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if we currently have an error
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function has_error() {
		if ( count( $this->errors ) > 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Remove all notices / errors
	 *
	 * @param string $type Switch to remove all messages, errors or just notices. Valid arguments are 'all', 'notices', 'errors'
	 *
	 * @since 4.0
	 */
	public function clear( $type = 'all' ) {

		if ( 'errors' === $type || 'all' === $type ) {
			$this->errors = [];
		}

		if ( 'notices' === $type || 'all' === $type ) {
			$this->notices = [];
		}
	}

	/**
	 * Process our admin notice and error messages
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function process() {
		foreach ( $this->notices as $class => $notice ) {
			$include_class = ( ! is_int( $class ) ) ? $class : '';
			$this->html( $notice, 'updated ' . $include_class );
		}

		foreach ( $this->errors as $class => $error ) {
			$include_class = ( ! is_int( $class ) ) ? $class : '';
			$this->html( $error, 'error ' . $include_class );
		}
	}

	/**
	 * Generate the HTML used to display the notice / error
	 *
	 * @param string $text  The message to be displayed
	 * @param string $class The class name (updated / error)
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	private function html( $text, $class = 'updated' ) {
		?>
		<div class="<?= $class; ?> notice">
			<p><?= $text; ?></p>
		</div>
		<?php
	}
}
