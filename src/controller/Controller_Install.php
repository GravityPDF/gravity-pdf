<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;

use Psr\Log\LoggerInterface;

/**
 * Install Update Controller
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
 * Controller_Installer
 * Controls the installation and uninstallation of Gravity PDF
 *
 * @since 4.0
 */
class Controller_Install extends Helper_Abstract_Controller implements Helper_Interface_Actions, Helper_Interface_Filters
{

	/**
	 * Holds abstracted functions related to the forms plugin
	 * @var Object
	 * @since 4.0
	 */
	protected $form;

	/**
	 * Holds our log class
	 * @var Object
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 * @var Object Helper_Notices
	 * @since 4.0
	 */
	protected $notices;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 * @var Object
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 * @var Object
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Load our model and view and required actions
	 */
	public function __construct( Helper_Abstract_Model $model, Helper_Abstract_Form $form, LoggerInterface $log, Helper_Notices $notices, Helper_Data $data, Helper_Misc $misc ) {
		
		/* Assign our internal variables */
		$this->form    = $form;
		$this->log     = $log;
		$this->notices = $notices;
		$this->data    = $data;
		$this->misc    = $misc;

		/* Load our model and view */
		$this->model = $model;
		$this->model->setController( $this );
	}

	/**
	 * Initialise our class defaults
	 * @since 4.0
	 * @return void
	 */
	public function init() {
		 $this->add_actions();
		 $this->add_filters();
	}

	/**
	 * Apply any actions needed for the settings page
	 * @since 4.0
	 * @return void
	 */
	public function add_actions() {
		add_action( 'admin_init', array( $this, 'maybe_uninstall' ) );
		add_action( 'wp_loaded', array( $this, 'check_install_status' ), 9999 );

		/* rewrite endpoints */
		add_action( 'init', array( $this->model, 'register_rewrite_rules' ) );
	}

	/**
	 * Apply any filters needed for the settings page
	 * @since 4.0
	 * @return void
	 */
	public function add_filters() {
		/* rewrite filters */
		add_filter( 'query_vars', array( $this->model, 'register_rewrite_tags' ) );
	}

	/**
	 * Set up data related to the plugin setup and installation
	 * @return void
	 * @since 4.0
	 */
	public function setup_defaults() {

		$this->data->is_installed   = $this->model->is_installed();
		$this->data->permalink      = $this->model->get_permalink_regex();
		$this->data->working_folder = $this->model->get_working_directory();
		$this->data->settings_url   = $this->model->get_settings_url();

		$upload_details             = $this->misc->get_upload_details();
		$this->data->upload_dir     = $upload_details['path'];
		$this->data->upload_dir_url = $upload_details['url'];

		$this->model->setup_template_location();
		$this->model->setup_multisite_template_location();
		$this->model->create_folder_structures();
	}

	/**
	 * Check the software has been installed on this website before and
	 * the version numbers are in sync
	 * @return void
	 * @since 4.0
	 */
	public function check_install_status() {

		 if( ! $this->data->is_installed ) {
		 	$this->model->install_plugin();
		 }

		 if( PDF_EXTENDED_VERSION != get_option( 'gfpdf_current_version' ) ) {
		 	update_option( 'gfpdf_current_version', PDF_EXTENDED_VERSION );
		 }
	}

	/**
	 * Determine if we should be saving the PDF settings
	 * @return void
	 * @since 4.0
	 */
	public function maybe_uninstall() {

		/* check if we should be uninstalling */
		if ( rgpost( 'gfpdf_uninstall' ) ) {

			/* Check Nonce is valid */
			if ( ! wp_verify_nonce( rgpost( 'gfpdf-uninstall-plugin' ), 'gfpdf-uninstall-plugin' ) ) {
				 $this->notices->add_error( __( 'There was a problem removing Gravity PDF. Please try again.', 'gravitypdf' ) );
				 $this->log->addWarning( __CLASS__ . '::' . __METHOD__ . '(): ' . 'Nonce Verification Failed.' );
				 return false;
			}

			/* check if user has permission to uninstall the plugin */
			if ( ! $this->form->has_capability( 'gravityforms_uninstall' ) ) {
	
				$this->log->addCritical( __CLASS__ . '::' . __METHOD__ . '(): ' . 'Lack of User Capabilities.', array(
					'user'      => wp_get_current_user(),
					'user_meta' => get_user_meta( get_current_user_id() )
				) );

				wp_die( __( 'Cheatin&#8217; uh?' ), 403 );
			}

			$this->model->uninstall_plugin();
		}
	}
}
