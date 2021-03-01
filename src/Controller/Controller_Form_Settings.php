<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Form;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Options_Fields;
use GFPDF\Model\Model_Form_Settings;
use GFPDF\View\View_Form_Settings;

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
 * Controller_Form_Settings
 * Controls the individual form PDF settings pages
 *
 * @since 4.0
 */
class Controller_Form_Settings extends Helper_Abstract_Controller implements Helper_Interface_Actions, Helper_Interface_Filters {
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
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	protected $options;

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
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var Helper_Form
	 *
	 * @since 4.2
	 */
	protected $gform;

	/**
	 * Setup our class by injecting all our dependencies
	 *
	 * @param Helper_Abstract_Model|Model_Form_Settings $model   Our Form Model the controller will manage
	 * @param Helper_Abstract_View|View_Form_Settings   $view    Our Form View the controller will manage
	 * @param Helper_Data                               $data    Our plugin data store
	 * @param Helper_Abstract_Options                   $options Our options class which allows us to access any settings
	 * @param Helper_Misc                               $misc    Our miscellaneous methods
	 * @param Helper_Form                               $form    Out Gravity Forms object
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Model $model, Helper_Abstract_View $view, Helper_Data $data, Helper_Abstract_Options $options, Helper_Misc $misc, Helper_Form $form ) {

		/* Assign our internal variables */
		$this->data    = $data;
		$this->options = $options;
		$this->misc    = $misc;
		$this->gform   = $form;

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
		 * Tell Gravity Forms to add our form PDF settings pages
		 */
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

		/* Trigger our save method */
		add_action( 'admin_init', [ $this, 'maybe_save_pdf_settings' ], 5 );

		/* Tell Gravity Forms to add our form PDF settings pages */
		add_action( 'gform_form_settings_menu', [ $this->model, 'add_form_settings_menu' ] );
		add_action( 'gform_form_settings_page_' . $this->data->slug, [ $this, 'display_page' ] );

		/* Add AJAX endpoints */
		add_action( 'wp_ajax_gfpdf_list_delete', [ $this->model, 'delete_gf_pdf_setting' ] );
		add_action( 'wp_ajax_gfpdf_list_duplicate', [ $this->model, 'duplicate_gf_pdf_setting' ] );
		add_action( 'wp_ajax_gfpdf_change_state', [ $this->model, 'change_state_pdf_setting' ] );
		add_action( 'wp_ajax_gfpdf_get_template_fields', [ $this->model, 'render_template_fields' ] );
	}

	/**
	 * Apply any filters needed for the settings page
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function add_filters() {

		/* Add custom field information if we have a template selected */
		add_filter( 'gfpdf_form_settings_custom_appearance', [ $this->model, 'register_custom_appearance_settings' ] );
		add_filter( 'gfpdf_form_settings', [ $this->model, 'register_template_group' ] );

		/* Add Validation Errors */
		add_filter( 'gfpdf_form_settings', [ $this->model, 'validation_error' ] );
		add_filter( 'gfpdf_form_settings_appearance', [ $this->model, 'validation_error' ] );

		/* Sanitize Results */
		add_filter( 'gfpdf_form_settings_sanitize', [ $this->options, 'sanitize_all_fields' ], 10, 4 );
		add_filter( 'gfpdf_form_settings_sanitize_text', [ $this->model, 'parse_filename_extension' ], 15, 2 );
		add_filter( 'gfpdf_form_settings_sanitize_text', [ $this->options, 'sanitize_trim_field' ], 15, 2 );
		add_filter( 'gfpdf_form_settings_sanitize_hidden', [ $this->model, 'decode_json' ], 10, 2 );

		add_filter( 'gfpdf_skip_highlight_errors', [ $this->model, 'check_custom_size_error_highlighting' ], 10, 3 );

		/* Store our TinyMCE Options */
		add_filter( 'tiny_mce_before_init', [ $this, 'store_tinymce_settings' ] );

		/* Update our PDF settings before the form gets updated */
		add_filter( 'gform_form_update_meta', [ $this, 'clear_cached_pdf_settings' ], 10, 2 );
	}

	/**
	 * Determine if we should be saving the PDF settings
	 *
	 * @since 4.0
	 */
	public function maybe_save_pdf_settings() {
		$form_id = ( isset( $_GET['id'] ) ) ? (int) $_GET['id'] : false;
		$pdf_id  = ( isset( $_GET['pid'] ) ) ? $_GET['pid'] : false;

		/* Load the add/edit page */
		if ( $pdf_id !== false && rgpost( 'gfpdf_save_pdf' ) ) {
			$this->model->process_submission( $form_id, $pdf_id );
		}
	}

	/**
	 * Processes / Setup the form settings page.
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function display_page() {

		/* Determine whether to load the add/edit page, or the list view */
		$form_id = ( isset( $_GET['id'] ) ) ? (int) $_GET['id'] : false;
		$pdf_id  = ( isset( $_GET['pid'] ) ) ? $_GET['pid'] : false;

		/* Load the add/edit page */
		if ( $pdf_id !== false ) {
			$this->model->show_edit_view( $form_id, $pdf_id );

			return null;
		}

		/* process list view */
		$this->model->process_list_view( $form_id );
	}

	/**
	 * Store our TinyMCE init settings for use in our AJAX wp_editor calls
	 *
	 * @param array $settings The current TinyMCE Settings
	 *
	 * @return array Original Settings
	 *
	 * @since 4.0
	 */
	public function store_tinymce_settings( $settings ) {

		if ( empty( $this->data->tiny_mce_editor_settings ) ) {
			$this->data->tiny_mce_editor_settings = $settings;
		}

		return $settings;
	}

	/**
	 * Updating the form can sometimes remove the PDF configuration if the original editor begins work after the PDF is saved.
	 * This usually occurs in a multi-user environment with multiple devs. To fix this, before the form is updated via the
	 * form editor we will pull the latest PDF settings.
	 *
	 * @Internal https://github.com/GravityPDF/gravity-pdf/issues/640
	 *
	 * @param array $form
	 * @param int   $form_id
	 *
	 * @return array
	 *
	 * @since 4.2
	 */
	public function clear_cached_pdf_settings( $form, $form_id ) {
		if ( is_admin() && ! rgempty( 'gform_meta' ) && isset( $form['gfpdf_form_settings'] ) ) {
			$updated_form                = $this->gform->get_form( $form_id );
			$form['gfpdf_form_settings'] = ( isset( $updated_form['gfpdf_form_settings'] ) ) ? $updated_form['gfpdf_form_settings'] : [];
		}

		return $form;
	}
}
