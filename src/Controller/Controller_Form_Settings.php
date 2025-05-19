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
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
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
		add_filter( 'gfpdf_form_settings_sanitize_textarea', [ $this->options, 'sanitize_trim_field' ] );
		add_filter( 'gfpdf_form_settings_sanitize_number', [ $this->options, 'sanitize_number_field' ], 10, 4 );
		add_filter( 'gfpdf_form_settings_sanitize_paper_size', [ $this->options, 'sanitize_paper_size' ] );
		add_filter( 'gfpdf_form_settings_sanitize_hidden', [ $this->model, 'decode_json' ], 10, 2 );

		add_filter( 'gfpdf_skip_highlight_errors', [ $this->model, 'check_custom_size_error_highlighting' ], 10, 3 );

		/* Store our TinyMCE Options */
		add_filter( 'tiny_mce_before_init', [ $this, 'store_tinymce_settings' ] );

		/* Update our PDF settings before the form gets updated */
		add_filter( 'gform_form_update_meta', [ $this, 'clear_cached_pdf_settings' ], 10, 3 );

		/* Conditional logic */
		add_filter( 'gform_rule_source_value', [ $this, 'conditional_logic_set_rule_source_value' ], 10, 5 );
		add_filter( 'gform_is_value_match', [ $this, 'conditional_logic_is_value_match' ], 10, 6 );
	}

	/**
	 * Determine if we should be saving the PDF settings
	 *
	 * @since 4.0
	 */
	public function maybe_save_pdf_settings() {
		/* phpcs:disable WordPress.Security.NonceVerification.Recommended */
		$form_id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$pdf_id  = isset( $_GET['pid'] ) ? sanitize_html_class( $_GET['pid'] ) : false;
		/* phpcs:enable */

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

		/* phpcs:disable WordPress.Security.NonceVerification.Recommended */
		$form_id = isset( $_GET['id'] ) ? (int) $_GET['id'] : false;
		$pdf_id  = isset( $_GET['pid'] ) ? sanitize_html_class( $_GET['pid'] ) : false;
		/* phpcs:enable */

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
	 * This usually occurs when multiple browser windows are open, and we'll fetch the current PDF settings before saving the form data.
	 *
	 * @internal https://github.com/GravityPDF/gravity-pdf/issues/640
	 * @internal https://github.com/GravityPDF/gravity-pdf/issues/1464
	 *
	 * @param array  $form
	 * @param int    $form_id
	 * @param string $meta_name
	 *
	 * @return array
	 *
	 * @since 4.2
	 */
	public function clear_cached_pdf_settings( $form, $form_id, $meta_name ) {
		if ( $meta_name !== 'display_meta' || ! is_admin() ) {
			return $form;
		}

		/* Only flush the form cache if on the form editor, or making changes to specific form settings pages */
		if (
			rgpost( 'action' ) === 'form_editor_save_form' /* ajax form editor save */
			|| ! empty( rgpost( 'gforms_update_form' ) ) /* non-ajax form editor save */
			|| ! empty( rgpost( 'gform-settings-save' ) ) /* ajax form settings save */
			|| rgpost( 'action' ) === 'rg_update_notification_active' /* ajax notification state */
			|| ! empty( rgpost( 'gform_notification_list_action' ) ) /* ajax notification duplicate/delete */
			|| rgpost( 'action' ) === 'gwcp_save_condtional_logic' /* GWiz Conditional Pricing feed */
		) {
			/* In the unlikely event the PDFs have been updated since the execution cycle begun, clear the form cache */
			\GFFormsModel::flush_current_forms();

			$updated_form = $this->gform->get_form( $form_id );

			/* If for whatever reason the form cannot be found, return the original */
			if ( $updated_form !== null ) {
				$form['gfpdf_form_settings'] = $updated_form['gfpdf_form_settings'] ?? [];
			}
		}

		return $form;
	}

	/**
	 * Process entry meta conditional logic rules
	 *
	 * @param int|string $source_value The value of the rule’s configured field ID, entry meta, or custom property.
	 * @param array      $rule         The GF current rule object https://docs.gravityforms.com/conditional-logic-object/#rule
	 * @param array      $form
	 * @param array      $logic        The GF conditional logic object https://docs.gravityforms.com/conditional-logic-object/
	 * @param array|null $entry        The entry currently being processed, if available.
	 *
	 * @return int|string
	 *
	 * @since 6.9.0
	 */
	public function conditional_logic_set_rule_source_value( $source_value, $rule, $form, $logic, $entry ) {

		$keys   = array_keys( $this->data->get_conditional_logic_options( $form ) );
		$target = $rule['fieldId'] ?? null;

		if ( ! $entry || ! in_array( $target, $keys, true ) ) {
			return $source_value;
		}

		/* Use Gravity Wiz filter to enable automatic compatibility with any other plugins that use this snippet */
		$runtime_entry_meta_keys = apply_filters( 'gwclem_runtime_entry_meta_keys', [ 'payment_status' ] );

		/* Refresh the entry object, if required */
		if ( in_array( $target, $runtime_entry_meta_keys, true ) ) {
			$entry = \GFAPI::get_entry( $entry['id'] );
		}

		switch ( $target ) {
			case 'date_created':
			case 'payment_date':
				/* Convert to local date without time */
				$value = $entry[ $target ];
				if ( ! $value ) {
					return $value;
				}

				$lead_gmt_time   = mysql2date( 'G', $value );
				$lead_local_time = \GFCommon::get_local_timestamp( $lead_gmt_time );

				return date_i18n( 'Y-m-d', $lead_local_time, true );

			default:
				return rgar( $entry, $target );
		}
	}

	/**
	 * Add extra date comparison checks to Gravity Forms conditional logic
	 *
	 * @param bool $is_match
	 * @param string $field_value
	 * @param string $target_value
	 * @param string $operation
	 * @param string $source_field
	 * @param array $rule
	 *
	 * @return bool
	 *
	 * @since 6.9.0
	 */
	public function conditional_logic_is_value_match( $is_match, $field_value, $target_value, $operation, $source_field, $rule ) {

		/* Only deal with less/more than date rules */
		if (
			empty( $field_value ) ||
			empty( $target_value ) ||
			! is_array( $rule ) ||
			! in_array( $rule['fieldId'] ?? 0, [ 'date_created', 'payment_date' ], true ) ||
			! in_array( $operation, [ '>', '<' ], true )
		) {
			return $is_match;
		}

		$date1 = strtotime( $field_value );
		$date2 = strtotime( $target_value );

		if ( $operation === '>' ) {
			return $date1 > $date2;
		}

		return $date1 < $date2;
	}
}
