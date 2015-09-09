<?php

namespace GFPDF\Helper;

use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;

/**
 * Give a standardised format to queue admin notices
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
 * @since 4.0
 * @todo Impliment Transient Support so errors and notices can be saved
 */
class Helper_Notices implements Helper_Interface_Actions {

	/**
	 * Holds any notices that we've triggered
	 * @var array
	 * @since 4.0
	 */
	private $notices = array();

	/**
	 * Holds any errors that we've triggered
	 * @var array
	 * @since 4.0
	 */
	private $errors = array();

	/**
	 * Initialise our class defaults
	 * @since 4.0
	 * @return void
	 */
	public function init() {
		$this->add_actions();
	}

	/**
	 * Apply any actions needed to impliment notices
	 * @since 4.0
	 * @return void
	 */
	public function add_actions() {
		add_action( $this->get_notice_type(), array( $this, 'process' ) );
	}

	/**
	 * Determine which notice should be triggered
	 * @return String
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
	 * @param String $notice The message to be queued
	 * @return void
	 * @since 4.0
	 */
	public function add_notice( $notice ) {
		$this->notices[] = $notice;
	}

	/**
	 * Public endpoint for adding a new notice
	 * @param String $notice The message to be queued
	 * @return void
	 * @since 4.0
	 */
	public function add_error( $error ) {
		$this->errors[] = $error;
	}

	/**
	 * Check if we currently have a notice
	 * @return boolean
	 * @since 4.0
	 */
	public function has_notice() {
		if ( sizeof( $this->notices ) > 0 ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if we currently have an error
	 * @return boolean
	 * @since 4.0
	 */
	public function has_error() {
		if ( sizeof( $this->errors ) > 0 ) {
			return true;
		}
		return false;
	}

	/**
	 * Remove all notices / errors
	 * @param  string $type Switch to remove all messages, errors or just notices. Valid arguments are 'all', 'notices', 'errors'
	 * @since 4.0
	 */
	public function clear( $type = 'all' ) {

		if( 'errors' === $type || 'all' === $type ) {
			$this->errors = array();
		}

		if( 'notices' === $type || 'all' === $type ) {
			$this->notices = array();
		}
	}

	/**
	 * Process our admin notice and error messages
	 * @return void
	 * @since 4.0
	 */
	public function process() {
		foreach ( $this->notices as $notice ) {
			$this->html( $notice );
		}

		foreach ( $this->errors as $error ) {
			$this->html( $error, 'error' );
		}
	}

	/**
	 * Generate the HTML used to display the notice / error
	 * @param  String $text  The message to be displayed
	 * @param  String $class The class name (updated / error)
	 * @return void
	 * @since 4.0
	 */
	private function html( $text, $class = 'updated' ) {
		?>
            <div class="<?php echo $class; ?> notice">
                <p><?php echo $text; ?></p>
            </div>
        <?php
	}
}
