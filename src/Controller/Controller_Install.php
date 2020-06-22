<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Notices;
use Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller_Installer
 * Controls the installation and uninstallation of Gravity PDF
 *
 * @since 4.0
 */
class Controller_Install extends Helper_Abstract_Controller implements Helper_Interface_Actions, Helper_Interface_Filters {

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
	 * @var LoggerInterface
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
	 * Setup our class by injecting all our dependencies
	 *
	 * @param Helper_Abstract_Model|\GFPDF\Model\Model_Install $model   Our Install Model the controller will manage
	 * @param \GFPDF\Helper\Helper_Abstract_Form               $gform   Our Install View the controller will manage
	 * @param LoggerInterface                                  $log     Our logger class
	 * @param \GFPDF\Helper\Helper_Notices                     $notices Our notice class used to queue admin messages and errors
	 * @param \GFPDF\Helper\Helper_Data                        $data    Our plugin data store
	 * @param \GFPDF\Helper\Helper_Misc                        $misc    Our miscellaneous methods
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Model $model, Helper_Abstract_Form $gform, LoggerInterface $log, Helper_Notices $notices, Helper_Data $data, Helper_Misc $misc ) {

		/* Assign our internal variables */
		$this->gform   = $gform;
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
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function init() {
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
		add_action( 'admin_init', [ $this, 'maybe_uninstall' ] );
		add_action( 'wp_loaded', [ $this, 'check_install_status' ], 9999 );

		/* rewrite endpoints */
		add_action( 'init', [ $this->model, 'register_rewrite_rules' ] );
	}

	/**
	 * Apply any filters needed for the settings page
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function add_filters() {
		/* rewrite filters */
		add_filter( 'query_vars', [ $this->model, 'register_rewrite_tags' ] );
	}

	/**
	 * Set up data related to the plugin setup and installation
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function setup_defaults() {

		$this->data->is_installed   = $this->model->is_installed();
		$this->data->permalink      = $this->model->get_permalink_regex();
		$this->data->working_folder = $this->model->get_working_directory();
		$this->data->settings_url   = $this->model->get_settings_url();

		$this->data->memory_limit             = ini_get( 'memory_limit' );
		$this->data->allow_url_fopen          = (bool) ini_get( 'allow_url_fopen' );
		$this->data->template_transient_cache = 'gfpdf_template_info';

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
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function check_install_status() {

		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ! current_user_can( 'activate_plugins' ) ) {
			return null;
		}

		if ( ! $this->data->is_installed ) {
			$this->model->install_plugin();
		}

		if ( PDF_EXTENDED_VERSION !== get_option( 'gfpdf_current_version' ) ) {
			/* See https://gravitypdf.com/documentation/v5/gfpdf_version_changed/ for more details about this action */
			do_action( 'gfpdf_version_changed', get_option( 'gfpdf_current_version' ), PDF_EXTENDED_VERSION );
			update_option( 'gfpdf_current_version', PDF_EXTENDED_VERSION );
		}
	}

	/**
	 * Determine if we should be saving the PDF settings
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function maybe_uninstall() {

		/* check if we should be uninstalling */
		if ( rgpost( 'gfpdf_uninstall' ) ) {

			/* Check Nonce is valid */
			if ( ! wp_verify_nonce( rgpost( 'gfpdf-uninstall-plugin' ), 'gfpdf-uninstall-plugin' ) ) {
				$this->notices->add_error( esc_html__( 'There was a problem uninstalling Gravity PDF. Please try again.', 'gravity-forms-pdf-extended' ) );
				$this->log->warning( 'Nonce Verification Failed.' );

				return null;
			}

			/**
			 * Run the uninstaller if the user has the correct permissions
			 *
			 * If not a multisite any user with the GF uninstaller permission can remove it (usually just admins)
			 *
			 * If multisite only the super admin can uninstall the software. This is due to how the plugin shares similar directory structures across networked sites
			 */
			if ( ( ! is_multisite() && ! $this->gform->has_capability( 'gravityforms_uninstall' ) ) ||
				 ( is_multisite() && ! is_super_admin() )
			) {

				$this->log->critical(
					'Lack of User Capabilities.',
					[
						'user'      => wp_get_current_user(),
						'user_meta' => get_user_meta( get_current_user_id() ),
					]
				);

				wp_die( esc_html__( 'Access Denied', 'default' ), 403 );
			}

			$this->model->uninstall_plugin();
			$this->model->redirect_to_plugins_page();
		}

	}
}
