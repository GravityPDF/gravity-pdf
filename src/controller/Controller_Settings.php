<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;

use Psr\Log\LoggerInterface;

use GFForms;

/**
 * Settings Controller
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
 * Controller_Settings
 * A general class for the global PDF settings
 *
 * @since 4.0
 */
class Controller_Settings extends Helper_Abstract_Controller implements Helper_Interface_Actions, Helper_Interface_Filters
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
	public function __construct( Helper_Abstract_Model $model, Helper_Abstract_View $view, Helper_Abstract_Form $form, LoggerInterface $log, Helper_Notices $notices, Helper_Data $data, Helper_Misc $misc ) {
		
		/* Assign our internal variables */
		$this->form    = $form;
		$this->log     = $log;
		$this->notices = $notices;
		$this->data    = $data;
		$this->misc    = $misc;

		/* Load our model and view */
		$this->model = $model;
		$this->model->setController( $this );

		$this->view  = $view;
		$this->view->setController( $this );
	}

	/**
	 * Initialise our class defaults
	 * @since 4.0
	 * @return void
	 */
	public function init() {

		/*
         * Tell Gravity Forms to initiate our settings page
         * Using the following Class/Model
         */
		 GFForms::add_settings_page( $this->data->short_title, array( $this, 'displayPage' ) );

		 /* Ensure any errors are stored correctly */
		 $this->model->setup_form_settings_errors();

		 /* run actions and filters */
		 $this->add_actions();
		 $this->add_filters();
	}

	/**
	 * Apply any actions needed for the settings page
	 * @since 4.0
	 * @return void
	 */
	public function add_actions() {

		/* Load our settings meta boxes */
		add_action( 'current_screen', array( $this->model, 'add_meta_boxes' ) );

		/* Display our system status on general and tools pages */
		add_action( 'pdf-settings-general', array( $this->view, 'system_status' ) );
		add_action( 'pdf-settings-tools', array( $this->view, 'system_status' ) );

		/* Display the uninstaller if use has the correct permissions */
		if ( $this->form->has_capability( 'gravityforms_uninstall' ) ) {
			add_action( 'pdf-settings-tools', array( $this->view, 'uninstaller' ), 5 );
		}

		/* Process the tool tab actions */
		add_action( 'admin_init', array( $this, 'process_tool_tab_actions' ) );

		/**
		 * Add AJAX Action Endpoints
		 */
		add_action( 'wp_ajax_gfpdf_font_save', array( $this->model, 'save_font' ) );
		add_action( 'wp_ajax_gfpdf_font_delete', array( $this->model, 'delete_font' ) );

	}

	/**
	 * Apply any filters needed for the settings page
	 * @since 4.0
	 * @return void
	 */
	public function add_filters() {

		/* Add tooltips */
		add_filter( 'gform_tooltips', array( $this->view, 'add_tooltips' ) );

		/* If trying to save settings page we'll use this filter to apply any errors passed back from options.php */
		if ( $this->misc->is_gfpdf_page() ) {
			add_filter( 'gfpdf_registered_settings', array( $this->model, 'highlight_errors' ) );
			add_filter( 'admin_notices', 'settings_errors' );
		}

		/* make capability text user friendly */
		add_filter( 'gfpdf_capability_name', array( $this->model, 'style_capabilities' ) );

		/* change capability needed to edit settings page */
		add_filter( 'option_page_capability_gfpdf_settings', array( $this, 'edit_options_cap' ) );
		add_filter( 'gravitypdf_settings_navigation', array( $this, 'disable_tools_on_view_cap' ) );

		/* allow TTF and OTF uploads */
		add_filter( 'upload_mimes', array( $this, 'allow_font_uploads' ) );

		/* Add a sample image of what the template looks like */
		add_filter( 'gfpdf_settings_general', array( $this->model, 'add_template_image' ) );
	}

	/**
	 * Display the settings page for Gravity PDF
	 * @since 4.0
	 * @return void
	 */
	public function displayPage() {
		/**
		 * Determine which settings page to load
		 */
		$page = (isset($_GET['tab'])) ? $_GET['tab'] : 'general';

		switch ( $page ) {
			case 'general':
				$this->view->general();
		  break;

			case 'tools':
				$this->view->tools();
		  break;

			case 'help':
				$this->view->help();
		  break;
		}
	}

	/**
	 * Check our current user has the correct capability
	 * @since 4.0
	 * @return void
	 */
	public function edit_options_cap() {

		/* because current_user_can() doesn't handle Gravity Forms permissions quite correct we'll do our checks here */
		if ( ! $this->form->has_capability( 'gravityforms_edit_settings' ) ) {
			
			$gfpdf->log->addCritical( 'Lack of User Capabilities.', array(
				'user'      => wp_get_current_user(),
				'user_meta' => get_user_meta( get_current_user_id() )
			) );

			wp_die( __( 'Cheatin&#8217; uh?' ), 403 );
		}

		/* the user is authenticated by the above so let's pass in the lowest permissions */
		return 'read';
	}

	/**
	 * Return our custom capability
	 * @param  $nav Array The existing settings navigation
	 * @since 4.0
	 * @return array
	 */
	public function disable_tools_on_view_cap( $nav ) {
		
		if ( ! $this->form->has_capability( 'gravityforms_edit_settings' ) ) {
			$this->log->addNotice( 'Lack of User Capabilities' );

			unset($nav[100]); /* remove tools tab */
		}

		return $nav;
	}

	/**
	 * Check if any of the tool tab actions have been triggered and process
	 * @return void
	 * @since 4.0
	 */
	public function process_tool_tab_actions() {

		/* check if we are on the tools settings page */
		if ( ! $this->misc->is_gfpdf_settings_tab( 'tools' ) ) {
			return;
		}

		/* check if the user has permission to copy the templates */
		if ( ! $this->form->has_capability( 'gravityforms_edit_settings' ) ) {
			
			$this->log->addCritical( 'Lack of User Capabilities.', array(
				'user'      => wp_get_current_user(),
				'user_meta' => get_user_meta( get_current_user_id() )
			) );

			return false;
		}

		$settings = rgpost( 'gfpdf_settings' );

		/* check if we should install the custom templates */
		if ( isset($settings['setup_templates']['name']) ) {
			/* verify the nonce */
			if ( ! wp_verify_nonce( $settings['setup_templates']['nonce'], 'gfpdf_settings[setup_templates]' ) ) {
				 $this->log->addWarning( 'Nonce Verification Failed.' );
				 $this->notices->add_error( __( 'There was a problem installing the PDF templates. Please try again.', 'gravitypdf' ) );
				 return false;
			}

			$this->model->install_templates();
		}
	}

	/**
	 * Add .ttf and .otf to upload whitelist
	 * @param  array $mime_types
	 * @return array
	 * @since 4.0
	 */
	public function allow_font_uploads( $mime_types = array() ) {
		$mime_types['ttf|otf'] = 'application/octet-stream';

		return $mime_types;
	}
}
