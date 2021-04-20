<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Form;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Controller_Uninstaller;
use GFPDF\Model\Model_Install;
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
 * Controller_Installer
 * Controls the installation and uninstallation of Gravity PDF
 *
 * @since 4.0
 */
class Controller_Install extends Helper_Abstract_Controller implements Helper_Interface_Actions, Helper_Interface_Filters {

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
	 * @param Helper_Abstract_Model|Model_Install $model   Our Install Model the controller will manage
	 * @param Helper_Abstract_Form                $gform   Our Install View the controller will manage
	 * @param LoggerInterface                     $log     Our logger class
	 * @param Helper_Notices                      $notices Our notice class used to queue admin messages and errors
	 * @param Helper_Data                         $data    Our plugin data store
	 * @param Helper_Misc                         $misc    Our miscellaneous methods
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
	 * @return void
	 * @since 4.0
	 *
	 */
	public function init() {
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
		add_action( 'wp_loaded', [ $this, 'check_install_status' ], 9999 );

		/* rewrite endpoints */
		add_action( 'init', [ $this->model, 'register_rewrite_rules' ] );
	}

	/**
	 * Apply any filters needed for the settings page
	 *
	 * @return void
	 * @since 4.0
	 *
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
			/* See https://docs.gravitypdf.com/v6/developers/actions/gfpdf_version_changed for more details about this action */
			do_action( 'gfpdf_version_changed', get_option( 'gfpdf_current_version' ), PDF_EXTENDED_VERSION );
			update_option( 'gfpdf_current_version', PDF_EXTENDED_VERSION );
		}
	}

	/**
	 * Determine if we should be saving the PDF settings
	 *
	 * @since 4.0
	 */
	public function maybe_uninstall() {
		_doing_it_wrong( __METHOD__, 'This method has been moved to Controller_Uninstall::uninstall_addon()', '6.0' );
	}
}
