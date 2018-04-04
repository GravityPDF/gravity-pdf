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
 * Controller_Settings
 * A general class for the global PDF settings
 *
 * @since 4.0
 */
class Controller_Settings extends Helper_Abstract_Controller implements Helper_Interface_Actions, Helper_Interface_Filters {

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var \GFPDF\Helper\Helper_Form
	 *
	 * @since 4.0
	 */
	protected $gform;

	/**
	 * Holds our log class
	 *
	 * @var \Monolog\Logger|LoggerInterface
	 *
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 *
	 * @var \GFPDF\Helper\Helper_Notices
	 *
	 * @since 4.0
	 */
	protected $notices;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var \GFPDF\Helper\Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var \GFPDF\Helper\Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Setup our class by injecting all our dependancies
	 *
	 * @param Helper_Abstract_Model|\GFPDF\Model\Model_Settings $model   Our Settings Model the controller will manage
	 * @param Helper_Abstract_View|\GFPDF\View\View_Settings    $view    Our Settings View the controller will manage
	 * @param \GFPDF\Helper\Helper_Abstract_Form                $gform   Our abstracted Gravity Forms helper functions
	 * @param \Monolog\Logger|LoggerInterface                   $log     Our logger class
	 * @param \GFPDF\Helper\Helper_Notices                      $notices Our notice class used to queue admin messages and errors
	 * @param \GFPDF\Helper\Helper_Data                         $data    Our plugin data store
	 * @param \GFPDF\Helper\Helper_Misc                         $misc    Our miscellaneous class
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Model $model, Helper_Abstract_View $view, Helper_Abstract_Form $gform, LoggerInterface $log, Helper_Notices $notices, Helper_Data $data, Helper_Misc $misc ) {

		/* Assign our internal variables */
		$this->gform   = $gform;
		$this->log     = $log;
		$this->notices = $notices;
		$this->data    = $data;
		$this->misc    = $misc;

		/* Load our model and view */
		$this->model = $model;
		$this->model->setController( $this );

		$this->view = $view;
		$this->view->setController( $this );
	}

	/**
	 * Initialise our class defaults
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function init() {

		/*
         * Tell Gravity Forms to initiate our settings page
         * Using the following Class/Model
         */
		GFForms::add_settings_page( $this->data->short_title, [ $this, 'display_page' ] );

		/* Ensure any errors are stored correctly */
		$this->model->setup_form_settings_errors();

		/* run actions and filters */
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Apply any actions needed for the settings page
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function add_actions() {

		/* Display our system status on general and tools pages */
		add_action( 'gfpdf_post_general_settings_page', [ $this->view, 'system_status' ] );
		add_action( 'gfpdf_post_tools_settings_page', [ $this->view, 'system_status' ] );

		/**
		 * Display the uninstaller if use has the correct permissions
		 *
		 * If not a multisite any user with the GF uninstaller permission can remove it (usually just admins)
		 *
		 * If multisite only the super admin can uninstall the software. This is due to how the plugin shares similar directory structures across networked sites
		 */
		if ( ( ! is_multisite() && $this->gform->has_capability( 'gravityforms_uninstall' ) ) ||
		     ( is_multisite() && is_super_admin() )
		) {
			add_action( 'gfpdf_post_tools_settings_page', [ $this->view, 'uninstaller' ], 5 );
		}

		/* Process the tool tab actions */
		add_action( 'admin_init', [ $this, 'process_tool_tab_actions' ] );

		/**
		 * Add AJAX Action Endpoints
		 */
		add_action( 'wp_ajax_gfpdf_font_save', [ $this->model, 'save_font' ] );
		add_action( 'wp_ajax_gfpdf_font_delete', [ $this->model, 'delete_font' ] );
		add_action( 'wp_ajax_gfpdf_has_pdf_protection', [ $this->model, 'check_tmp_pdf_security' ] );
		add_action( 'wp_ajax_gfpdf_deactivate_license', [ $this->model, 'process_license_deactivation' ] );
	}

	/**
	 * Apply any filters needed for the settings page
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function add_filters() {

		/* Add tooltips */
		add_filter( 'gform_tooltips', [ $this->view, 'add_tooltips' ] );

		/* If trying to save settings page we'll use this filter to apply any errors passed back from options.php */
		if ( $this->misc->is_gfpdf_page() ) {
			add_filter( 'gfpdf_registered_fields', [ $this->model, 'highlight_errors' ] );
			add_filter( 'admin_notices', 'settings_errors' );
			add_filter( 'gfpdf_localised_script_array', array( $this->model, 'get_template_data' ) );
		}

		/* make capability text user friendly */
		add_filter( 'gfpdf_capability_name', [ $this->model, 'style_capabilities' ] );

		/* change capability needed to edit settings page */
		add_filter( 'option_page_capability_gfpdf_settings', [ $this, 'edit_options_cap' ] );
		add_filter( 'gravitypdf_settings_navigation', [ $this, 'disable_tools_on_view_cap' ] );

		/* allow TTF uploads */
		add_filter( 'upload_mimes', [ $this, 'allow_font_uploads' ] );

		/* Register add-ons for licensing page */
		add_filter( 'gfpdf_settings_licenses', [ $this->model, 'register_addons_for_licensing' ] );
		add_filter( 'gfpdf_settings_license_sanitize', [ $this->model, 'maybe_active_licenses' ] );
	}

	/**
	 * Display the settings page for Gravity PDF
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function display_page() {

		$page = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : 'general';

		switch ( $page ) {
			case 'general':
				$this->view->general();
			break;

			case 'tools':
				$this->view->tools();
			break;

			case 'license':
				$this->view->license();
			break;

			case 'extensions':
				$this->view->extensions();
			break;

			case 'help':
				$this->view->help();
			break;
		}
	}

	/**
	 * Check our current user has the correct capability
	 *
	 * @since 4.0
	 *
	 * @return string
	 */
	public function edit_options_cap() {

		/* because current_user_can() doesn't handle Gravity Forms permissions quite correct we'll do our checks here */
		if ( ! $this->gform->has_capability( 'gravityforms_edit_settings' ) ) {

			$this->log->addCritical( 'Lack of User Capabilities.', [
				'user'      => wp_get_current_user(),
				'user_meta' => get_user_meta( get_current_user_id() ),
			] );

			wp_die( esc_html__( 'Access Denied' ), 403 );
		}

		/* the user is authenticated by the above so let's pass in the lowest permissions */

		return 'read';
	}

	/**
	 * Return our custom capability
	 *
	 * @param array $nav The existing settings navigation
	 *
	 * @since 4.0
	 *
	 * @return array
	 */
	public function disable_tools_on_view_cap( $nav ) {

		if ( ! $this->gform->has_capability( 'gravityforms_edit_settings' ) ) {
			$this->log->addNotice( 'Lack of User Capabilities' );

			unset( $nav[100] ); /* remove tools tab */
		}

		return $nav;
	}

	/**
	 * Check if any of the tool tab actions have been triggered and process
	 *
	 * @return void|boolean
	 *
	 * @since 4.0
	 */
	public function process_tool_tab_actions() {

		/* check if we are on the tools settings page */
		if ( ! $this->misc->is_gfpdf_settings_tab( 'tools' ) ) {
			return null;
		}

		/* check if the user has permission to copy the templates */
		if ( ! $this->gform->has_capability( 'gravityforms_edit_settings' ) ) {

			$this->log->addCritical( 'Lack of User Capabilities.', [
				'user'      => wp_get_current_user(),
				'user_meta' => get_user_meta( get_current_user_id() ),
			] );

			return null;
		}

		$settings = rgpost( 'gfpdf_settings' );

		/* Only run checks if the gfpdf_settings POST data exists */
		if ( empty( $settings ) ) {
			return null;
		}

		/* check if we should install the custom templates */
		if ( isset( $settings['setup_templates']['name'] ) && isset( $settings['setup_templates']['nonce'] ) ) {
			/* verify the nonce */
			if ( ! wp_verify_nonce( $settings['setup_templates']['nonce'], 'gfpdf_settings[setup_templates]' ) ) {
				$this->log->addWarning( 'Nonce Verification Failed.' );
				$this->notices->add_error( esc_html__( 'There was a problem installing the PDF templates. Please try again.', 'gravity-forms-pdf-extended' ) );

				return null;
			}

			return $this->model->install_templates();
		}

		/* See https://gravitypdf.com/documentation/v4/gfpdf_tool_tab_actions/ for more details about this action */
		do_action( 'gfpdf_tool_tab_actions', $settings );
	}

	/**
	 * Add .ttf to upload whitelist
	 *
	 * @param  array $mime_types
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function allow_font_uploads( $mime_types = [] ) {
		$mime_types['ttf'] = 'application/x-font-ttf';

		return $mime_types;
	}
}
