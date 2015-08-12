<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Helper\Helper_Abstract_Model;

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
	 * Load our model and view and required actions
	 */
	public function __construct( Helper_Abstract_Model $model ) {
		/* load our model and view */
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
	 * @dependancy $gfpdf GFPDF\Router
	 */
	public function setup_defaults() {
		global $gfpdf;

		$gfpdf->data->is_installed   = $this->model->is_installed();
		$gfpdf->data->permalink      = $this->model->get_permalink_regex();
		$gfpdf->data->working_folder = $this->model->get_working_directory();
		$gfpdf->data->settings_url   = $this->model->get_settings_url();

		$upload_details              = $gfpdf->misc->get_upload_details();
		$gfpdf->data->upload_dir     = $upload_details['path'];
		$gfpdf->data->upload_dir_url = $upload_details['url'];

		$this->model->setup_template_location();
		$this->model->setup_multisite_template_location();
		$this->model->create_folder_structures();
	}

	/**
	 * Determine if we should be saving the PDF settings
	 * @return void
	 * @since 4.0
	 */
	public function maybe_uninstall() {
		global $gfpdf;

		/* check if we should be uninstalling */
		if ( rgpost( 'gfpdf_uninstall' ) ) {

			/* Check Nonce is valid */
			if ( ! wp_verify_nonce( rgpost( 'gfpdf-uninstall-plugin' ), 'gfpdf-uninstall-plugin' ) ) {
				 $gfpdf->notices->add_error( __( 'There was a problem removing Gravity PDF. Please try again.', 'gravitypdf' ) );
				 return false;
			}

			/* check if user has permission to uninstall the plugin */
			if ( ! $gfpdf->form->has_capability( 'gravityforms_uninstall' ) ) {
				wp_die( __( 'Cheatin&#8217; uh?' ), 403 );
			}

			$this->model->uninstall_plugin();
		}
	}
}
