<?php

namespace GFPDF;

use GFPDF\Controller;
use GFPDF\Model;
use GFPDF\View;
use GFPDF\Helper;
use GFPDF\Stat;
use GFPDF_Core;

use GFFormsModel;

/**
 * Bootstrap / Router Class
 * The bootstrap is loaded on WordPress 'plugins_loaded' functionality
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

/*
 * Load dependancies
 */
require_once(PDF_PLUGIN_DIR . 'src/autoload.php');

/**
 * @since 4.0
 */
class Router implements Helper\Helper_Interface_Actions, Helper\Helper_Interface_Filters {

	/**
	 * Holds our log class
	 * @var Object
	 * @since 4.0
	 */
	public $log;

	/**
	 * Holds abstracted functions related to the forms plugin
	 * @var Object
	 * @since 4.0
	 */
	public $form;

	/**
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 * @var Object
	 * @since 4.0
	 */
	public $notices;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 * @var Object
	 * @since 4.0
	 */
	public $data;

	/**
	 * Holds our Helper_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 * @var Object
	 * @since 4.0
	 */
	public $options;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 * @var Object
	 * @since 4.0
	 */
	public $misc;

	/**
	 * Add user depreciation notice for any methods not included in current object
	 * @since  4.0
	 */
	public function __call( $name, $arguments ) {
		trigger_error( sprintf( __( '"%s" has been depreciated as of Gravity PDF 4.0', 'gravitypdf' ), $name ), E_USER_DEPRECATED );
	}

	/**
	 * Add user depreciation notice for any methods not included in current object
	 * @since  4.0
	 */
	public static function __callStatic( $name, $arguments ) {
		trigger_error( sprintf( __( '"%s" has been depreciated as of Gravity PDF 4.0', 'gravitypdf' ), $name ), E_USER_DEPRECATED );
	}

	/**
	 * Setup our plugin functionality
	 * Note: Fires on WordPress' init hook
	 * @since 4.0
	 */
	public function init() {

		/* Set up our logger is not running via CLI (unit testing) */
		$this->setup_logger();

		/* Set up our form object */
		$this->form = new Helper\Helper_Form();

		/* Set up our data access layer */
		$this->data = new Helper\Helper_Data();
		$this->data->init();

		/* Set up our misc object */
		$this->misc = new Helper\Helper_Misc( $this->log, $this->form, $this->data );

		/* Set up our notices */
		$this->notices = new Helper\Helper_Notices();
		$this->notices->init();

		/* Set up our options object - this is initialised on admin_init but other classes need to access its methods before this */
		$this->options = new Helper\Helper_Options_Fields( $this->log, $this->form, $this->data, $this->misc, $this->notices );

		/* Load modules */
		$this->installer();
		$this->welcome_screen();
		$this->gf_settings();
		$this->gf_form_settings();
		$this->pdf();
		$this->shortcodes();
		$this->actions();

		/* Add localisation support */
		load_plugin_textdomain( 'gravitypdf', false,  dirname( plugin_basename( __FILE__ ) ) . '/assets/languages/' );

		/**
		 * Run generic actions and filters needed to get the plugin functional
		 * The controllers will set more specific actions / filters as needed
		 */
		$this->add_actions();
		$this->add_filters();

	}

	/**
	 * Add required plugin actions
	 * @since 4.0
	 * @return void
	 */
	public function add_actions() {
		add_action( 'init', array( $this, 'register_assets' ) );
		add_action( 'init', array( $this, 'load_assets' ), 15 );

		/**
		 * Cache our Gravity PDF Settings and register our settings fields with the Options API
		 */
		add_action( 'init', array( $this, 'init_settings_api' ), 1 );
		add_action( 'admin_init', array( $this, 'setup_settings_fields' ), 1 );
	}

	/**
	 * Add required plugin filters
	 * @since 4.0
	 * @return void
	 */
	public function add_filters() {

		/* Automatically handle GF noconflict mode */
		add_filter( 'gform_noconflict_scripts', array( $this, 'auto_noconflict_scripts' ) );
		add_filter( 'gform_noconflict_styles', array( $this, 'auto_noconflict_styles' ) );

		/* Enable Gravity Forms Logging */
		add_filter( 'gform_logging_supported', array( $this, 'add_gf_logger' ) );
	}

	/**
	 * Initialise our logging class (we're using Monolog instead of Gravity Form's KLogger)
	 * and set up appropriate handlers based on the logger settings
	 * @return void
	 * @since 4.0
	 */
	public function setup_logger() {

		/* Initialise our logger */
		$this->log = new \Monolog\Logger( 'gravitypdf' );

		/* Ensure log contains details about the method / class / function calling it and the processor peak memory */
		$this->log->pushProcessor( new \Monolog\Processor\IntrospectionProcessor );
		$this->log->pushProcessor( new \Monolog\Processor\MemoryPeakUsageProcessor );

		/* Prevent logging in CLI mode */
		if ( substr( php_sapi_name(), 0, 3 ) === 'cli' ) {
			$this->log->pushHandler( new \Monolog\Handler\NullHandler( \Monolog\Logger::INFO ) ); /* throw logs away */
			return;
		}

		/* If running an alpha, beta or rc version of the plugin send all logs to Loggly */
		$this->maybe_run_remote_logging();

		/* Setup our Gravity Forms local file logger, if enabled */
		$this->setup_gravityforms_logging();
	}

	/**
	 * Setup Gravity Forms logging, if currently enabled by the user
	 * @return void
	 * @since 4.0
	 * @todo Change 'gravity-pdf' back to 'gravity-forms-pdf-extended' to match original plugin folder name
	 */
	private function setup_gravityforms_logging() {

		/* Check if Gravity Forms logging is enabled and push stream logging */
		if ( class_exists( 'GFLogging' ) ) {

			/* Get the current plugin logger settings and check if it's enabled */
			$settings  = get_option( 'gf_logging_settings', array() );
			$log_level = rgar( $settings, 'gravity-pdf' );

			if ( ! empty($log_level) && $log_level !== 6 ) {

				/* Set our log file */
				$log_file_name = GFFormsModel::get_upload_root() . 'logs/gravity-pdf.txt';

				/* Convert Gravity Forms log levels to the appropriate Monolog level */
				$monolog_level = ($log_level == 4) ? \Monolog\Logger::ERROR : \Monolog\Logger::INFO;

				/* Setup our stream and change the format to more-suit Gravity Forms */
				$formatter = new \Monolog\Formatter\LineFormatter( "%datetime% - %level_name% --> %message% %context% %extra%\n" );
				$stream    = new \Monolog\Handler\StreamHandler( $log_file_name, $monolog_level );
				$stream->setFormatter( $formatter );

				/* Add our log file stream */
				$this->log->pushHandler( $stream );
			}
		}
	}

	/**
	 * Send all logs to Loggly (https://www.loggly.com/) when running a dev version
	 * of Gravity PDF. This allows us to better track any problems a user might have.
	 * @return void
	 * @since 4.0
	 */
	private function maybe_run_remote_logging() {

		/* Enable remote logging */
		if ( $this->is_development_version( PDF_EXTENDED_VERSION ) ) {
			/* Setup Loggly logging with correct format for buffer logging */
			$formatter = new \Monolog\Formatter\LogglyFormatter();
			$loggly    = new \Monolog\Handler\LogglyHandler( '8ad317ed-213d-44c9-a2e8-f2eebd542c66/tag/gravitypdf', \Monolog\Logger::INFO );
			$loggly->setFormatter( $formatter );

			/* Set up our buffer logging to save multiple API calls */
			$buffer = new \Monolog\Handler\BufferHandler( $loggly, 20, \Monolog\Logger::INFO );

			/* Push additional log details about function called from, peak memory and user IP / referrer */
			$this->log->pushProcessor( new \Monolog\Processor\WebProcessor );

			/* Impliment our buffer */
			$this->log->pushHandler( $buffer );
		}
	}

	/**
	 * Check if the current version of Gravity PDF is a development edition
	 * Development editions contain either 'alpha', 'beta', or 'rc' in the version number
	 * @param  String  $version The version to check
	 * @return boolean
	 * @since 4.0
	 */
	public function is_development_version( $version ) {
		
		$dev = false;
		$dev_version        = array('alpha', 'beta', 'rc');
		$plugin_version     = strtolower( $version );

		foreach( $dev_version as $v ) {
			if( strpos( $plugin_version, $v ) !== false ) {
				return true;
				break;
			}
		}

		return false;
	}

	/**
	 * Register all css and js which can be enqueued when needed
	 * @since 4.0
	 * @return void
	 */
	public function register_assets() {
		$this->register_styles();
		$this->register_scripts();
	}

	/**
	 * Register requrired CSS
	 * @since 4.0
	 * @return void
	 */
	private function register_styles() {
		$version = PDF_EXTENDED_VERSION;
		$suffix = '.min.';
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			$suffix  = '';
		}

		wp_register_style( 'gfpdf_css_styles', PDF_PLUGIN_URL . 'src/assets/css/gfpdf-styles'. $suffix .'.css', array( 'wp-color-picker' ), $version );
		wp_register_style( 'gfpdf_css_chosen_style', PDF_PLUGIN_URL . 'bower_components/chosen/chosen.min.css', array( 'wp-jquery-ui-dialog' ), $version );
	}

	/**
	 * Register requrired JS
	 * @since 4.0
	 * @return void
	 * @todo Limit js dependancies on particular pages (eg. form pdf settings vs global settings)
	 */
	private function register_scripts() {

		$version = PDF_EXTENDED_VERSION;
		$suffix  = '.min.';
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			$suffix = '';
		}

		wp_register_script( 'gfpdf_js_settings', PDF_PLUGIN_URL . 'src/assets/js/gfpdf-settings'. $suffix .'.js', array( 'wpdialogs', 'jquery-ui-tooltip', 'gform_forms', 'gform_form_admin', 'jquery-color', 'wp-color-picker' ), $version );
		wp_register_script( 'gfpdf_js_backbone', PDF_PLUGIN_URL . 'src/assets/js/gfpdf-backbone'. $suffix .'.js', array( 'gfpdf_js_settings', 'backbone', 'underscore', 'gfpdf_js_backbone_model_binder', 'wpdialogs' ), $version );
		wp_register_script( 'gfpdf_js_chosen', PDF_PLUGIN_URL . 'bower_components/chosen/chosen.jquery.min.js', array( 'jquery' ), $version );
		wp_register_script( 'gfpdf_js_backbone_model_binder', PDF_PLUGIN_URL . 'bower_components/backbone.modelbinder/Backbone.ModelBinder.js', array( 'backbone', 'underscore' ), $version );
		wp_register_script( 'gfpdf_js_entries', PDF_PLUGIN_URL . 'src/assets/js/gfpdf-entries' . $suffix . '.js', array( 'jquery' ), $version );

		/*
        * Localise admin script
        */
		wp_localize_script( 'gfpdf_js_settings', 'GFPDF', $this->data->get_localised_script_data( $this->options, $this->form ) );
	}


	/**
	 * Load any assets that are needed
	 * @since 4.0
	 * @return void
	 */
	public function load_assets() {

		if ( $this->misc->is_gfpdf_page() ) {
			/* load styles */
			wp_enqueue_style( 'gfpdf_css_styles' );
			wp_enqueue_style( 'gfpdf_css_chosen_style' );

			/* load scripts */
			wp_enqueue_script( 'gfpdf_js_settings' );
			wp_enqueue_script( 'gfpdf_js_chosen' );

			/* add media uploader */
			wp_enqueue_media();
		}

		if ( $this->misc->is_gfpdf_settings_tab( 'help' ) || $this->misc->is_gfpdf_settings_tab( 'tools' ) ) {
			 wp_enqueue_script( 'gfpdf_js_backbone' );
		}

		if ( is_admin() && rgget( 'page' ) == 'gf_entries' ) {
			wp_enqueue_script( 'gfpdf_js_entries' );
			wp_enqueue_style( 'gfpdf_css_styles' );
		}
	}

	 /**
	  * Auto no-conflict any preloaded scripts that begin with 'gfpdf_'
	  * @since 4.0
	  * @return void
	  */
	public function auto_noconflict_scripts( $items ) {

		$wp_scripts = wp_scripts();

		/* set defaults we will allow to load on GF pages */
		$default_scripts = array(
			'editor',
			'word-count',
			'quicktags',
			'wpdialogs-popup',
			'media-upload',
			'wplink',
			'backbone',
			'underscore',
			'media-editor',
			'media-models',
			'media-views',
			'plupload',
			'plupload-flash',
			'plupload-html4',
			'plupload-html5',
			'plupload-silverlight',
			'wp-plupload',
			'gform_placeholder',
			'jquery-ui-autocomplete',
			'thickbox',
		);

		foreach ( $wp_scripts->queue as $object ) {
			if ( substr( $object, 0, 8 ) === 'gfpdf_js' ) {
				$items[] = $object;
			}
		}

		if ( $this->misc->is_gfpdf_page() ) {
			$items = array_merge( $default_scripts, $items );
		}

		return apply_filters( 'gfpdf_autoload_gf_scripts', $items );
	}

	 /**
	  * Auto no-conflict any preloaded styles that begin with 'gfpdf_'
	  * @since 4.0
	  * @return void
	  */
	public function auto_noconflict_styles( $items ) {

		$wp_styles  = wp_styles();

		$default_styles = array(
			'editor-buttons',
			'wp-jquery-ui-dialog',
			'media-views',
			'buttons',
			'thickbox',
		);

		foreach ( $wp_styles->queue as $object ) {
			if ( substr( $object, 0, 9 ) === 'gfpdf_css' ) {
				$items[] = $object;
			}
		}

		if ( $this->misc->is_gfpdf_page() ) {
			$items = array_merge( $default_styles, $items );
		}

		return apply_filters( 'gfpdf_autoload_gf_styles', $items );
	}

	/**
	 * Register our plugin with Gravity Form's Logger
	 * @param Array $loggers
	 * @return Array
	 * @since 4.0
	 */
	public function add_gf_logger( $loggers ) {
		$loggers['gravity-pdf'] = __( 'Gravity PDF', 'gravitypdf' );

		return $loggers;
	}

	/**
	 * Bootstrap our settings API for use
	 * @return void
	 * @return 4.0
	 */
	public function init_settings_api() {
		/* load our options API */
		$this->options->init();
	}

	/**
	 * Register our admin settings
	 * @return void
	 * @return 4.0
	 */
	public function setup_settings_fields() {
		/* register our options settings */
		$this->options->register_settings( $this->options->get_registered_fields() );
	}

	public function installer() {
		$model = new Model\Model_Install( $this->form, $this->log, $this->data, $this->misc, $this->notices );
		$class = new Controller\Controller_Install( $model, $this->form, $this->log, $this->notices, $this->data, $this->misc );
		$class->init();

		/* set up required data */
		$class->setup_defaults();
	}

	/**
	 * Include Welcome Screen functionality for installation / upgrades
	 * @since 4.0
	 * @return void
	 */
	public function welcome_screen() {

		$model = new Model\Model_Welcome_Screen( $this->log );
		$view  = new View\View_Welcome_Screen(array(
			'display_version' => PDF_EXTENDED_VERSION,
		), $this->form );

		$class = new Controller\Controller_Welcome_Screen( $model, $view, $this->log, $this->data, $this->options );
		$class->init();
	}

	/**
	 * Include Settings Page functionality
	 * @since 4.0
	 * @return void
	 */
	public function gf_settings() {

		$model = new Model\Model_Settings( $this->form, $this->log, $this->notices, $this->options, $this->data, $this->misc );
		$view  = new View\View_Settings( array(), $this->form, $this->log, $this->options, $this->data, $this->misc );

		$class = new Controller\Controller_Settings( $model, $view, $this->form, $this->log, $this->notices, $this->data, $this->misc );
		$class->init();
	}

	/**
	 * Include Form Settings (PDF) functionality
	 * @since 4.0
	 * @return void
	 */
	public function gf_form_settings() {

		$model = new Model\Model_Form_Settings( $this->form, $this->log, $this->data, $this->options, $this->misc, $this->notices );
		$view  = new View\View_Form_Settings( array() );

		$class = new Controller\Controller_Form_Settings( $model, $view, $this->data, $this->options );
		$class->init();
	}

	/**
	 * Include PDF Display functionality
	 * @since 4.0
	 * @return void
	 */
	public function pdf() {

		$model = new Model\Model_PDF( $this->form, $this->log, $this->options, $this->data, $this->misc, $this->notices );
		$view  = new View\View_PDF( array(), $this->form, $this->log, $this->options, $this->data, $this->misc );

		$class = new Controller\Controller_PDF( $model, $view, $this->form, $this->log, $this->misc );
		$class->init();
	}

	/**
	 * Include PDF Shortcodes functionality
	 * @since 4.0
	 * @return void
	 */
	public function shortcodes() {

		$model = new Model\Model_Shortcodes( $this->form, $this->log, $this->options );
		$view  = new View\View_Shortcodes( array() );

		$class = new Controller\Controller_Shortcodes( $model, $view, $this->log );
		$class->init();
	}

	/**
	 * Include one-time actions functionality
	 * @since 4.0
	 * @return void
	 */
	public function actions() {

		$model = new Model\Model_Actions( $this->data, $this->options, $this->notices );
		$view  = new View\View_Actions( array() );

		$class = new Controller\Controller_Actions( $model, $view, $this->form, $this->log, $this->notices );
		$class->init();
	}

	/**
	 * Add backwards compatbility with v3.x.x default PDF template files
	 * This function will now pull the PDF configuration details from our query variables / or our backwards compatible URL params method
	 *
	 * @param  Integer $form_id  The Gravity Form ID
	 * @return  Array The matched configuration being requested
	 * @since 4.0
	 */
	public function get_default_config_data( $form_id ) {
		global $gfpdf;

		$pid = $GLOBALS['wp']->query_vars['pid'];
	
		$settings    = $gfpdf->options->get_pdf( $form_id, $pid );

		if ( is_wp_error( $settings ) ) {

			$this->log->addError( 'Invalid PDF Settings.', array(
				'form_id'  => $form_id,
				'pid'      => $pid,
				'WP_Error' => $settings,
			) );

			/* Set all optional config values to default */
			$settings = array(
				'empty'           => false,
				'html_field'      => false,
				'page_names'      => false,
				'section_content' => false,
			);
		}

		return array(
			'empty_field'     => ( isset( $settings['empty'] ) && $settings['empty'] == 'Yes' ) ? true : false,
			'html_field'      => ( isset( $settings['html_field'] ) && $settings['html_field'] == 'Yes' ) ? true : false,
			'page_names'      => ( isset( $settings['page_names'] ) && $settings['page_names'] == 'Yes' ) ? true : false,
			'section_content' => ( isset( $settings['section_content'] ) && $settings['section_content'] == 'Yes' ) ? true : false,
		);
	}
}


/**
 * Execute our bootstrap class
 */
new GFPDF_Core();
