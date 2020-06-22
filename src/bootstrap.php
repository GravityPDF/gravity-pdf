<?php

namespace GFPDF;

use GFPDF\Controller;
use GFPDF\Model;
use GFPDF\View;
use GFPDF\Helper;

use GFPDF_Core;
use Psr\Log\LoggerInterface;

/*
 * Bootstrap / Router Class
 * The bootstrap is loaded on WordPress 'plugins_loaded' functionality
 */

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Load dependancies
 */
require_once( PDF_PLUGIN_DIR . 'src/autoload.php' );

/**
 * @since 4.0
 */
class Router implements Helper\Helper_Interface_Actions, Helper\Helper_Interface_Filters {

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 4.0
	 */
	public $log;

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var \GFPDF\Helper\Helper_Form
	 *
	 * @since 4.0
	 */
	public $gform;

	/**
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 *
	 * @var \GFPDF\Helper\Helper_Notices
	 *
	 * @since 4.0
	 */
	public $notices;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var \GFPDF\Helper\Helper_Data
	 *
	 * @since 4.0
	 */
	public $data;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var \GFPDF\Helper\Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	public $options;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var \GFPDF\Helper\Helper_Misc
	 *
	 * @since 4.0
	 */
	public $misc;

	/**
	 * Holds our Helper_Templates object
	 * used to ease access to our PDF templates
	 *
	 * @var \GFPDF\Helper\Helper_Templates
	 *
	 * @since 4.0
	 */
	public $templates;

	/**
	 * Makes our MVC classes sudo-singletons by allowing easy access to the original objects
	 * through `$singleton->get_class();`
	 *
	 * @var \GFPDF\Helper\Helper_Singleton
	 *
	 * @since 4.0
	 */
	public $singleton;

	/**
	 * Add user depreciation notice for any methods not included in current object
	 *
	 * @param string $name      The function name to be called
	 * @param array  $arguments An enumerated array containing the parameters passed to the $name'ed method
	 *
	 * @since 4.0
	 */
	public function __call( $name, $arguments ) {
		trigger_error( sprintf( esc_html__( '"%s" has been deprecated as of Gravity PDF 4.0', 'gravity-forms-pdf-extended' ), $name ), E_USER_DEPRECATED );
	}

	/**
	 * Add user depreciation notice for any methods not included in current object
	 *
	 * @param string $name      The function name to be called
	 * @param array  $arguments An enumerated array containing the parameters passed to the $name'ed method
	 *
	 * @since  4.0
	 */
	public static function __callStatic( $name, $arguments ) {
		trigger_error( sprintf( esc_html__( '"%s" has been deprecated as of Gravity PDF 4.0', 'gravity-forms-pdf-extended' ), $name ), E_USER_DEPRECATED );
	}

	/**
	 * Fired on the `after_setup_theme` action to initialise our plugin
	 *
	 * We do this on this hook instead of plugins_loaded so that users can tap into all our actions and filters
	 * directly from their theme (usually the functions.php file).
	 *
	 * @since 4.0
	 */
	public static function initialise_plugin() {

		global $gfpdf;

		/* Initialise our Router class */
		$gfpdf = new Router();
		$gfpdf->init();

		/* Add backwards compatibility support */
		$deprecated = new GFPDF_Core();
		$deprecated->setup_constants();
		$deprecated->setup_deprecated_paths();
	}

	/**
	 * Setup our plugin functionality
	 * Note: This method runs during the `after_setup_theme` action
	 *
	 * @since 4.0
	 */
	public function init() {

		/* Set up our logger is not running via CLI (unit testing) */
		$logger    = new Helper\Helper_Logger( 'gravity-pdf', 'Gravity PDF' );
		$this->log = $logger->get_logger();

		/* Set up our form object */
		$this->gform = new Helper\Helper_Form();

		/* Set up our data access layer */
		$this->data = new Helper\Helper_Data();
		$this->data->init();

		/* Set up our misc object */
		$this->misc = new Helper\Helper_Misc( $this->log, $this->gform, $this->data );

		/* Set up our notices */
		$this->notices = new Helper\Helper_Notices();
		$this->notices->init();

		/* Setup our template helper */
		$this->templates = new Helper\Helper_Templates( $this->log, $this->data, $this->gform );

		/* Set up our options object - this is initialised on admin_init but other classes need to access its methods before this */
		$this->options = new Helper\Helper_Options_Fields(
			$this->log,
			$this->gform,
			$this->data,
			$this->misc,
			$this->notices,
			$this->templates
		);

		/* Setup our Singleton object */
		$this->singleton = new Helper\Helper_Singleton();

		/* Load modules */
		$this->installer();
		$this->welcome_screen();
		$this->gf_settings();
		$this->gf_form_settings();
		$this->pdf();
		$this->shortcodes();
		$this->mergetags();
		$this->actions();
		$this->template_manager();
		$this->load_core_font_handler();
		$this->load_debug();
		$this->check_system_status();

		/* Add localisation support */
		$this->add_localization_support();

		/**
		 * Run generic actions and filters needed to get the plugin functional
		 * The controllers will set more specific actions / filters as needed
		 */
		$this->add_actions();
		$this->add_filters();

		/*
		 * Trigger action to signify Gravity PDF is now loaded
		 *
		 * See https://gravitypdf.com/documentation/v5/gfpdf_fully_loaded/ for more details about this action
		 */
		do_action( 'gfpdf_fully_loaded', $this );
	}

	/**
	 * Add required plugin actions
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function add_actions() {

		add_action( 'init', [ $this, 'register_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_admin_assets' ], 20 );

		/* Cache our Gravity PDF Settings and register our settings fields with the Options API */
		add_action( 'init', [ $this, 'init_settings_api' ], 1 );
		add_action( 'admin_init', [ $this, 'setup_settings_fields' ], 1 );
	}

	/**
	 * Add required plugin filters
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function add_filters() {

		/* Automatically handle GF noconflict mode */
		add_filter( 'gform_noconflict_scripts', [ $this, 'auto_noconflict_scripts' ] );
		add_filter( 'gform_noconflict_styles', [ $this, 'auto_noconflict_styles' ] );

		/* Add quick links on the plugins page */
		add_filter( 'plugin_action_links_' . PDF_PLUGIN_BASENAME, [ $this, 'plugin_action_links' ] );
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );

		/* Add class when on Gravity PDF pages */
		add_filter( 'admin_body_class', [ $this, 'add_body_class' ] );
	}


	/**
	 * Setup WordPress localization support
	 *
	 * @since 4.0
	 */
	private function add_localization_support() {
		load_plugin_textdomain( 'gravity-forms-pdf-extended', false, dirname( plugin_basename( __FILE__ ) ) . '/assets/languages/' );
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param    mixed $links Plugin Action links
	 *
	 * @return    array
	 *
	 * @since 4.0
	 */
	public function plugin_action_links( $links ) {

		$action_links = [
			'getting_started' => '<a href="' . esc_url( admin_url( 'index.php?page=gfpdf-getting-started' ) ) . '" title="' . esc_attr__( 'Get started with Gravity PDF', 'gravity-forms-pdf-extended' ) . '">' . esc_html__( 'Getting Started', 'gravity-forms-pdf-extended' ) . '</a>',
			'settings'        => '<a href="' . esc_url( $this->data->settings_url ) . '" title="' . esc_attr__( 'View Gravity PDF Settings', 'gravity-forms-pdf-extended' ) . '">' . esc_html__( 'Settings', 'gravity-forms-pdf-extended' ) . '</a>',
		];

		return array_merge( $action_links, $links );
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param    mixed $links Plugin Row Meta
	 * @param    mixed $file  Plugin Base file
	 *
	 * @return    array
	 *
	 * @since  4.0
	 */
	public function plugin_row_meta( $links, $file ) {

		if ( $file === PDF_PLUGIN_BASENAME ) {
			$row_meta = [
				'docs'           => '<a href="' . esc_url( 'https://gravitypdf.com/documentation/v5/five-minute-install/' ) . '" title="' . esc_attr__( 'View Gravity PDF Documentation', 'gravity-forms-pdf-extended' ) . '">' . esc_html__( 'Docs', 'gravity-forms-pdf-extended' ) . '</a>',
				'support'        => '<a href="' . esc_url( $this->data->settings_url . '&tab=help' ) . '" title="' . esc_attr__( 'Get Help and Support', 'gravity-forms-pdf-extended' ) . '">' . esc_html__( 'Support', 'gravity-forms-pdf-extended' ) . '</a>',
				'extension-shop' => '<a href="' . esc_url( 'https://gravitypdf.com/extension-shop/' ) . '" title="' . esc_attr__( 'View Gravity PDF Extensions Shop', 'gravity-forms-pdf-extended' ) . '">' . esc_html__( 'Extensions', 'gravity-forms-pdf-extended' ) . '</a>',
				'template-shop'  => '<a href="' . esc_url( 'https://gravitypdf.com/template-shop/' ) . '" title="' . esc_attr__( 'View Gravity PDF Template Shop', 'gravity-forms-pdf-extended' ) . '">' . esc_html__( 'Templates', 'gravity-forms-pdf-extended' ) . '</a>',
			];

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}

	/**
	 * If on a Gravity Form page add a new class
	 *
	 * @param array $classes
	 *
	 * @since 4.0
	 *
	 * @return string
	 */
	public function add_body_class( $classes ) {

		if ( $this->misc->is_gfpdf_page() ) {
			$classes .= ' gfpdf-page';
		}

		return $classes;
	}

	/**
	 * Register all css and js which can be enqueued when needed
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function register_assets() {
		$this->register_styles();
		$this->register_scripts();
	}

	/**
	 * Register requrired CSS
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	private function register_styles() {
		$version = PDF_EXTENDED_VERSION;

		wp_register_style( 'gfpdf_css_styles', PDF_PLUGIN_URL . 'dist/assets/css/gfpdf-styles.min.css', [ 'wp-color-picker', 'wp-jquery-ui-dialog' ], $version );
	}

	/**
	 * Register requrired JS
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	private function register_scripts() {
		$version = PDF_EXTENDED_VERSION;

		$pdf_settings_dependancies = [
			'wpdialogs',
			'jquery-ui-tooltip',
			'gform_forms',
			'gform_form_admin',
			'gform_chosen',
			'jquery-color',
			'wp-color-picker',
		];

		wp_register_script( 'gfpdf_js_settings', PDF_PLUGIN_URL . 'dist/assets/js/admin.min.js', $pdf_settings_dependancies, $version );

		$pdf_backbone_dependancies = [
			'gfpdf_js_settings',
			'backbone',
			'underscore',
			'gfpdf_js_backbone_model_binder',
			'wpdialogs',
		];

		wp_register_script( 'gfpdf_js_backbone', PDF_PLUGIN_URL . 'dist/assets/js/gfpdf-backbone.min.js', $pdf_backbone_dependancies, $version ); /* @TODO - remove backbone and use React */
		wp_register_script( 'gfpdf_js_backbone_model_binder', PDF_PLUGIN_URL . 'bower_components/backbone.modelbinder/Backbone.ModelBinder.min.js', [ 'backbone', 'underscore' ], $version );

		wp_register_script( 'gfpdf_js_entrypoint', PDF_PLUGIN_URL . 'dist/assets/js/app.bundle.min.js', [ 'jquery' ], $version );
		wp_register_script( 'gfpdf_js_entries', PDF_PLUGIN_URL . 'dist/assets/js/gfpdf-entries.min.js', [ 'jquery' ], $version );
		wp_register_script( 'gfpdf_js_v3_migration', PDF_PLUGIN_URL . 'dist/assets/js/gfpdf-migration.min.js', [ 'gfpdf_js_settings' ], $version );

		/* Localise admin script */
		wp_localize_script( 'gfpdf_js_entrypoint', 'GFPDF', $this->data->get_localised_script_data( $this->options, $this->gform ) );
		wp_localize_script( 'gfpdf_js_settings', 'GFPDF', $this->data->get_localised_script_data( $this->options, $this->gform ) );
	}


	/**
	 * Load any assets that are needed
	 *
	 * @since 4.0.4
	 *
	 * @return void
	 */
	public function load_admin_assets() {

		if ( $this->misc->is_gfpdf_page() ) {
			/*
			 * If present, remove elementor scripts which are causing JS errors
			 * @see https://github.com/GravityPDF/gravity-pdf/issues/844
			 */
			wp_dequeue_script( 'elementor-admin' );

			/* load styles */
			wp_enqueue_style( 'gfpdf_css_styles' );
			wp_enqueue_style( 'gform_chosen', \GFCommon::get_base_url() . '/css/chosen.min.css', [], \GFCommon::$version );

			/* load scripts */
			wp_enqueue_script( 'gfpdf_js_settings' );

			/* add media uploader */
			wp_enqueue_media();
			wp_enqueue_script( 'gfpdf_js_entrypoint' );

			/* Load TinyMCE styles */
			add_filter( 'tiny_mce_before_init', [ $this, 'tinymce_styles' ] );
		}

		if ( $this->misc->is_gfpdf_settings_tab( 'help' ) || $this->misc->is_gfpdf_settings_tab( 'tools' ) ) {
			wp_enqueue_script( 'gfpdf_js_backbone' );
		}

		if ( rgget( 'page' ) === 'gf_entries' ) {
			wp_enqueue_script( 'gfpdf_js_entries' );
			wp_enqueue_style( 'gfpdf_css_styles' );
		}

		wp_enqueue_style( 'gfpdf_css_admin_styles' );
	}

	/**
	 * Insert our own styles into the TinyMCE editor
	 *
	 * @param array $mce_init
	 *
	 * @return array
	 *
	 * @since 4.4
	 */
	public function tinymce_styles( $mce_init ) {
		$style                     = "body#tinymce { max-width: 100%; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;}";
		$mce_init['content_style'] = ( isset( $mce_init['content_style'] ) ) ? $mce_init['content_style'] . ' ' . $style : $style;
		return $mce_init;
	}

	/**
	 * Auto no-conflict any preloaded scripts that begin with 'gfpdf_'
	 *
	 * @since 4.0
	 *
	 * @param array $items The current list of no-conflict scripts
	 *
	 * @return array
	 */
	public function auto_noconflict_scripts( $items ) {

		$wp_scripts = wp_scripts();

		/**
		 * Set defaults we will allow to load on GF pages which are needed for Gravity PDF
		 * If any Gravity PDF modules requires WordPress-specific JS files you should add them to this list
		 */
		$default_scripts = [
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
		];

		foreach ( $wp_scripts->queue as $object ) {
			if ( substr( $object, 0, 8 ) === 'gfpdf_js' ) {
				$items[] = $object;
			}
		}

		if ( $this->misc->is_gfpdf_page() ) {
			$items = array_merge( $default_scripts, $items );
		}

		/* See https://gravitypdf.com/documentation/v5/gfpdf_gf_noconflict_scripts/ for more details about this filter */

		return apply_filters( 'gfpdf_gf_noconflict_scripts', $items );
	}

	/**
	 * Auto no-conflict any preloaded styles that begin with 'gfpdf_'
	 *
	 * @since 4.0
	 *
	 * @param array $items The current list of no-conflict styles
	 *
	 * @return array
	 */
	public function auto_noconflict_styles( $items ) {

		$wp_styles = wp_styles();

		/**
		 * Set defaults we will allow to load on GF pages which are needed for Gravity PDF
		 * If any Gravity PDF modules requires WordPress-specific CSS files you should add them to this list
		 */
		$default_styles = [
			'editor-buttons',
			'wp-jquery-ui-dialog',
			'media-views',
			'buttons',
			'thickbox',
			'gform_chosen',
		];

		foreach ( $wp_styles->queue as $object ) {
			if ( substr( $object, 0, 9 ) === 'gfpdf_css' ) {
				$items[] = $object;
			}
		}

		if ( $this->misc->is_gfpdf_page() ) {
			$items = array_merge( $default_styles, $items );
		}

		/* See https://gravitypdf.com/documentation/v5/gfpdf_gf_noconflict_styles/ for more details about this filter */

		return apply_filters( 'gfpdf_gf_noconflict_styles', $items );
	}

	/**
	 * Bootstrap our settings API for use
	 *
	 * @return void
	 *
	 * @return 4.0
	 */
	public function init_settings_api() {
		/* load our options API */
		$this->options->init();

		/*
		 * Async PDFs are conditionally loaded based of a global setting,
		 * so required to load after the settings have been loaded
		 */
		$this->async_pdfs();
	}

	/**
	 * Register our admin settings
	 *
	 * @return void
	 *
	 * @return 4.0
	 */
	public function setup_settings_fields() {
		global $pagenow;

		if ( $this->misc->is_gfpdf_page() || $pagenow === 'options.php' ) {
			/* register our options settings */
			$this->options->register_settings( $this->options->get_registered_fields() );
		}
	}

	/**
	 * Loads our Gravity PDF installer classes
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function installer() {
		$model = new Model\Model_Install( $this->gform, $this->log, $this->data, $this->misc, $this->notices, new Helper\Helper_Pdf_Queue( $this->log ) );
		$class = new Controller\Controller_Install( $model, $this->gform, $this->log, $this->notices, $this->data, $this->misc );
		$class->init();

		/* set up required data */
		$class->setup_defaults();

		/* Add to our singleton controller */
		$this->singleton->add_class( $class );
		$this->singleton->add_class( $model );
	}

	/**
	 * Include Welcome Screen functionality for installation / upgrades
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function welcome_screen() {

		$model = new Model\Model_Welcome_Screen( $this->log );
		$view  = new View\View_Welcome_Screen(
			[
				'display_version' => PDF_EXTENDED_VERSION,
			],
			$this->gform
		);

		$class = new Controller\Controller_Welcome_Screen( $model, $view, $this->log, $this->data, $this->options );
		$class->init();

		/* Add to our singleton controller */
		$this->singleton->add_class( $class );
		$this->singleton->add_class( $model );
		$this->singleton->add_class( $view );
	}

	/**
	 * Include Settings Page functionality
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function gf_settings() {

		$model = new Model\Model_Settings(
			$this->gform,
			$this->log,
			$this->notices,
			$this->options,
			$this->data,
			$this->misc,
			$this->templates
		);

		$view = new View\View_Settings(
			[],
			$this->gform,
			$this->log,
			$this->options,
			$this->data,
			$this->misc,
			$this->templates
		);

		$class = new Controller\Controller_Settings( $model, $view, $this->gform, $this->log, $this->notices, $this->data, $this->misc );
		$class->init();

		/* Add to our singleton controller */
		$this->singleton->add_class( $class );
		$this->singleton->add_class( $model );
		$this->singleton->add_class( $view );
	}

	/**
	 * Include Form Settings (PDF) functionality
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function gf_form_settings() {

		$model = new Model\Model_Form_Settings(
			$this->gform,
			$this->log,
			$this->data,
			$this->options,
			$this->misc,
			$this->notices,
			$this->templates
		);

		$view = new View\View_Form_Settings( [] );

		$class = new Controller\Controller_Form_Settings(
			$model,
			$view,
			$this->data,
			$this->options,
			$this->misc,
			$this->gform
		);

		$class->init();

		/* Add to our singleton controller */
		$this->singleton->add_class( $class );
		$this->singleton->add_class( $model );
		$this->singleton->add_class( $view );
	}

	/**
	 * Include PDF Display functionality
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function pdf() {

		$model = new Model\Model_PDF(
			$this->gform,
			$this->log,
			$this->options,
			$this->data,
			$this->misc,
			$this->notices,
			$this->templates,
			new Helper\Helper_Url_Signer()
		);

		$view = new View\View_PDF(
			[],
			$this->gform,
			$this->log,
			$this->options,
			$this->data,
			$this->misc,
			$this->templates
		);

		$class = new Controller\Controller_PDF( $model, $view, $this->gform, $this->log, $this->misc );
		$class->init();

		/* Add to our singleton controller */
		$this->singleton->add_class( $class );
		$this->singleton->add_class( $model );
		$this->singleton->add_class( $view );
	}

	/**
	 * Include PDF Shortcodes functionality
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function shortcodes() {

		$model = new Model\Model_Shortcodes( $this->gform, $this->log, $this->options, $this->misc, new Helper\Helper_Url_Signer() );
		$view  = new View\View_Shortcodes( [] );

		$class = new Controller\Controller_Shortcodes( $model, $view, $this->log );
		$class->init();

		/* Add to our singleton controller */
		$this->singleton->add_class( $class );
		$this->singleton->add_class( $model );
		$this->singleton->add_class( $view );
	}

	/**
	 * Include PDF Mergetag functionality
	 *
	 * @since 4.1
	 *
	 * @return void
	 */
	public function mergetags() {

		$model = new Model\Model_Mergetags( $this->options, $this->singleton->get_class( 'Model_PDF' ), $this->log, $this->misc );

		$class = new Controller\Controller_Mergetags( $model );
		$class->init();

		/* Add to our singleton controller */
		$this->singleton->add_class( $class );
		$this->singleton->add_class( $model );
	}

	/**
	 * Include one-time actions functionality
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function actions() {

		$model = new Model\Model_Actions( $this->data, $this->options, $this->notices );
		$view  = new View\View_Actions( [] );

		$class = new Controller\Controller_Actions( $model, $view, $this->gform, $this->log, $this->notices );
		$class->init();

		/* Add to our singleton controller */
		$this->singleton->add_class( $class );
		$this->singleton->add_class( $model );
		$this->singleton->add_class( $view );
	}

	/**
	 * Include template manager functionality
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function template_manager() {

		$model = new Model\Model_Templates( $this->templates, $this->log, $this->data, $this->misc );

		$class = new Controller\Controller_Templates( $model );
		$class->init();

		/* Add to our singleton controller */
		$this->singleton->add_class( $class );
		$this->singleton->add_class( $model );
	}

	/**
	 * Initialise our core font AJAX handler
	 *
	 * @since 5.0
	 *
	 * @return void
	 */
	public function load_core_font_handler() {
		$view  = new View\View_Save_Core_Fonts( [] );
		$class = new Controller\Controller_Save_Core_Fonts( $view, $this->log, $this->data, $this->misc );

		$class->init();

		$this->singleton->add_class( $class );
	}

	/**
	 * Initialise our debug code
	 *
	 * @since 5.1
	 *
	 * @return void
	 */
	public function load_debug() {
		$class = new Controller\Controller_Debug( $this->data, $this->options, $this->templates );

		$class->init();

		$this->singleton->add_class( $class );
	}

	/**
	 * Initialise our system status code
	 *
	 * @since 5.3
	 *
	 * @return void
	 */
	public function check_system_status() {
		$class = new Controller\Controller_System_Report( $this->data->allow_url_fopen );
		$class->init();

		$this->singleton->add_class( $class );
	}

	/**
	 * Initialise our background PDF processing handler
	 *
	 * @since 5.0
	 *
	 * @return void
	 */
	public function async_pdfs() {
		$queue     = new Helper\Helper_Pdf_Queue( $this->log );
		$model_pdf = $this->singleton->get_class( 'Model_PDF' );
		$class     = new Controller\Controller_Pdf_Queue( $queue, $model_pdf, $this->log );

		if ( $this->options->get_option( 'background_processing', 'Disable' ) === 'Enable' ) {
			$class->init();
		}

		$this->singleton->add_class( $queue );
		$this->singleton->add_class( $class );
	}

	/**
	 * Backwards compatibility with our early v3 templates
	 *
	 * @param $form_id
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_config_data( $form_id ) {
		return $this->get_default_config_data( $form_id );
	}

	/**
	 * Add backwards compatbility with v3.x.x default PDF template files
	 * This function will now pull the PDF configuration details from our query variables / or our backwards compatible URL params method
	 *
	 * @param integer $form_id The Gravity Form ID
	 *
	 * @return array The matched configuration being requested
	 *
	 * @since 4.0
	 */
	public function get_default_config_data( $form_id ) {

		$pid = isset( $GLOBALS['wp']->query_vars['pid'] ) ? $GLOBALS['wp']->query_vars['pid'] : '';

		$settings = $this->options->get_pdf( $form_id, $pid );

		if ( is_wp_error( $settings ) ) {

			$this->log->error(
				'Invalid PDF Settings.',
				[
					'form_id'          => $form_id,
					'pid'              => $pid,
					'WP_Error_Message' => $settings->get_error_message(),
					'WP_Error_Code'    => $settings->get_error_code(),
				]
			);

			/* Reset the settings so it forces everything to false */
			$settings = [];
		}

		return [
			'empty_field'     => ( isset( $settings['show_empty'] ) && $settings['show_empty'] === 'Yes' ) ? true : false,
			'html_field'      => ( isset( $settings['show_html'] ) && $settings['show_html'] === 'Yes' ) ? true : false,
			'page_names'      => ( isset( $settings['show_page_names'] ) && $settings['show_page_names'] === 'Yes' ) ? true : false,
			'section_content' => ( isset( $settings['show_section_content'] ) && $settings['show_section_content'] === 'Yes' ) ? true : false,
		];
	}
}


/**
 * Execute our bootstrap class
 *
 * We were forced to forgo initialising the plugin using an anonymous function call due to
 * our AJAX calls in our unit testing suite failing (boo)
 */
add_action( 'after_setup_theme', '\GFPDF\Router::initialise_plugin' );

