<?php

namespace GFPDF\Controller;

use GFForms;
use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Form;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Model\Model_Settings;
use GFPDF\View\View_Settings;
use Psr\Log\LoggerInterface;

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
 * Controller_Settings
 * A general class for the global PDF settings
 *
 * @since 4.0
 */
class Controller_Settings extends Helper_Abstract_Controller implements Helper_Interface_Actions, Helper_Interface_Filters {

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var Helper_Form
	 *
	 * @since 4.0
	 */
	protected $gform;

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 *
	 * @var Helper_Notices
	 *
	 * @since 4.0
	 */
	protected $notices;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Setup our class by injecting all our dependencies
	 *
	 * @param Helper_Abstract_Model|Model_Settings $model   Our Settings Model the controller will manage
	 * @param Helper_Abstract_View|View_Settings   $view    Our Settings View the controller will manage
	 * @param Helper_Abstract_Form                 $gform   Our abstracted Gravity Forms helper functions
	 * @param LoggerInterface                      $log     Our logger class
	 * @param Helper_Notices                       $notices Our notice class used to queue admin messages and errors
	 * @param Helper_Data                          $data    Our plugin data store
	 * @param Helper_Misc                          $misc    Our miscellaneous class
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
	 * @return void
	 * @since 4.0
	 *
	 */
	public function init() {

		/*
		 * Tell Gravity Forms to initiate our settings page
		 * Using the following Class/Model
		 */
		GFForms::add_settings_page(
			[
				'name'    => $this->data->short_title,
				'icon'    => 'dashicons-media-document',
				'handler' => [ $this, 'display_page' ],
			],
			''
		);

		/* run actions and filters */
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Apply any actions needed for the settings page
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function add_actions() {

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

		/*
		 * Add AJAX Action Endpoints
		 */
		add_action( 'wp_ajax_gfpdf_deactivate_license', [ $this->model, 'process_license_deactivation' ] );
	}

	/**
	 * Apply any filters needed for the settings page
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function add_filters() {

		/* Add tooltips */
		add_filter( 'gform_tooltips', [ $this->view, 'add_tooltips' ] );

		/* If trying to save settings page we'll use this filter to apply any errors passed back from options.php */
		if ( $this->misc->is_gfpdf_page() ) {
			add_filter( 'gfpdf_registered_fields', [ $this->model, 'highlight_errors' ] );
			add_filter( 'gfpdf_localised_script_array', [ $this->model, 'get_template_data' ] );
		}

		/* make capability text user friendly */
		add_filter( 'gfpdf_capability_name', [ $this->model, 'style_capabilities' ] );

		/* change capability needed to edit settings page */
		add_filter( 'option_page_capability_gfpdf_settings', [ $this, 'edit_options_cap' ] );
		add_filter( 'gravitypdf_settings_navigation', [ $this, 'disable_tools_on_view_cap' ] );

		/* Register add-ons for licensing page */
		add_filter( 'gfpdf_settings_licenses', [ $this->model, 'register_addons_for_licensing' ] );
		add_filter( 'gfpdf_settings_license_sanitize', [ $this->model, 'maybe_active_licenses' ] );
	}

	/**
	 * Display the settings page for Gravity PDF
	 *
	 * @return void
	 * @since 4.0
	 *
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
	 * @return string
	 * @since 4.0
	 *
	 */
	public function edit_options_cap() {

		/* because current_user_can() doesn't handle Gravity Forms permissions quite correct we'll do our checks here */
		if ( ! $this->gform->has_capability( 'gravityforms_edit_settings' ) ) {

			$this->log->critical(
				'Lack of User Capabilities.',
				[
					'user'      => wp_get_current_user(),
					'user_meta' => get_user_meta( get_current_user_id() ),
				]
			);

			wp_die( esc_html__( 'Access Denied', 'default' ), 403 );
		}

		/* the user is authenticated by the above so let's pass in the lowest permissions */

		return 'read';
	}

	/**
	 * Return our custom capability
	 *
	 * @param array $nav The existing settings navigation
	 *
	 * @return array
	 * @since 4.0
	 *
	 */
	public function disable_tools_on_view_cap( $nav ) {

		if ( ! $this->gform->has_capability( 'gravityforms_edit_settings' ) ) {
			$this->log->notice( 'Lack of User Capabilities' );

			unset( $nav[100] ); /* remove tools tab */
		}

		return $nav;
	}
}
