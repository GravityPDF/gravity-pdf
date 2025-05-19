<?php

namespace GFPDF\Helper;

use GFPDF\Controller\Controller_Custom_Fonts;
use GFPDF\Model\Model_Custom_Fonts;
use GFPDF\Statics\Kses;
use Psr\Log\LoggerInterface;
use WP_Error;

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
 * Class to set up the settings api callbacks
 *
 * Pulled straight from the Easy Digital Download register-settings.php file (props to Pippin and team)
 * and modified to suit our requirements
 *
 * @since 4.0
 */
abstract class Helper_Abstract_Options implements Helper_Interface_Filters {

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
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Templates object
	 * used to ease access to our PDF templates
	 *
	 * @var Helper_Templates
	 *
	 * @since 4.0
	 */
	protected $templates;

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
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 *
	 * @var Helper_Notices
	 *
	 * @since 4.0
	 */
	protected $notices;

	/**
	 * Holds the current global user settings
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	private $settings = [];

	/**
	 * Holds the Gravity Form PDF Settings
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	private $form_settings = [];


	/**
	 * Helper_Abstract_Options constructor.
	 *
	 * @param LoggerInterface      $log
	 * @param Helper_Abstract_Form $gform
	 * @param Helper_Data          $data
	 * @param Helper_Misc          $misc
	 * @param Helper_Notices       $notices
	 * @param Helper_Templates     $templates
	 *
	 * @since 4.0
	 */
	public function __construct( LoggerInterface $log, Helper_Abstract_Form $gform, Helper_Data $data, Helper_Misc $misc, Helper_Notices $notices, Helper_Templates $templates ) {

		/* Assign our internal variables */
		$this->log       = $log;
		$this->gform     = $gform;
		$this->data      = $data;
		$this->misc      = $misc;
		$this->notices   = $notices;
		$this->templates = $templates;
	}

	/**
	 * Returns an array of registered fields
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	abstract public function get_registered_fields();

	/**
	 * Initialise the options API
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function init() {
		$this->set_plugin_settings();
		$this->add_filters();
	}


	/**
	 * Add our filters
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function add_filters() {

		/* Register our core sanitize functions */
		add_filter( 'gfpdf_settings_sanitize', [ $this, 'sanitize_required_field' ], 10, 4 );
		add_filter( 'gfpdf_settings_sanitize', [ $this, 'sanitize_all_fields' ], 10, 4 );

		add_filter( 'gfpdf_settings_sanitize_text', [ $this, 'sanitize_trim_field' ] );
		add_filter( 'gfpdf_settings_sanitize_textarea', [ $this, 'sanitize_trim_field' ] );
		add_filter( 'gfpdf_settings_sanitize_number', [ $this, 'sanitize_number_field' ], 10, 4 );
		add_filter( 'gfpdf_settings_sanitize_paper_size', [ $this, 'sanitize_paper_size' ] );
	}

	/**
	 * Get the plugin's settings from the database
	 *
	 * @return  void
	 * @since 4.0
	 *
	 */
	public function set_plugin_settings() {
		/* assign our settings */
		$this->settings = $this->get_settings();
	}

	/**
	 * @param array $new_settings
	 *
	 * @Internal This option key is managed by WordPress Settings API. You cannot store info here that isn't already registered.
	 *           through $this->register_settings()
	 *
	 * @since    4.2
	 */
	public function update_settings( $new_settings ) {
		update_option( 'gfpdf_settings', $new_settings );
		$this->set_plugin_settings();
	}

	/**
	 * Add all settings sections and fields
	 *
	 * @param array $fields Fields that should be registered
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function register_settings( $fields = [] ) {
		global $wp_settings_fields;

		foreach ( $fields as $tab => $settings ) {

			/* Clear all previously set types */
			$group = 'gfpdf_settings_' . $tab;
			if ( isset( $wp_settings_fields[ $group ] ) ) {
				unset( $wp_settings_fields[ $group ] );
			}

			foreach ( $settings as $option ) {

				$name = isset( $option['name'] ) ? $option['name'] : '';

				add_settings_field(
					'gfpdf_settings[' . $option['id'] . ']',
					$name,
					method_exists( $this, $option['type'] . '_callback' ) ? [
						$this,
						$option['type'] . '_callback',
					] : [ $this, 'missing_callback' ],
					'gfpdf_settings_' . $tab,
					'gfpdf_settings_' . $tab,
					[
						'id'                 => isset( $option['id'] ) ? $option['id'] : null,
						'desc'               => ! empty( $option['desc'] ) ? $option['desc'] : '',
						'desc2'              => ! empty( $option['desc2'] ) ? $option['desc2'] : '',
						'type'               => isset( $option['type'] ) ? $option['type'] : null,
						'name'               => isset( $option['name'] ) ? $option['name'] : null,
						'size'               => isset( $option['size'] ) ? $option['size'] : null,
						'options'            => isset( $option['options'] ) ? $option['options'] : '',
						'std'                => isset( $option['std'] ) ? $option['std'] : '',
						'min'                => isset( $option['min'] ) ? $option['min'] : null,
						'max'                => isset( $option['max'] ) ? $option['max'] : null,
						'step'               => isset( $option['step'] ) ? $option['step'] : null,
						'chosen'             => isset( $option['chosen'] ) ? $option['chosen'] : null,
						'class'              => isset( $option['class'] ) ? $option['class'] : null,
						'inputClass'         => isset( $option['inputClass'] ) ? $option['inputClass'] : null,
						'placeholder'        => isset( $option['placeholder'] ) ? $option['placeholder'] : null,
						'tooltip'            => isset( $option['tooltip'] ) ? $option['tooltip'] : null,
						'multiple'           => isset( $option['multiple'] ) ? $option['multiple'] : null,
						'required'           => isset( $option['required'] ) ? $option['required'] : null,
						'uploaderTitle'      => isset( $option['uploaderTitle'] ) ? $option['uploaderTitle'] : null,
						'uploaderButtonText' => isset( $option['uploaderButtonText'] ) ? $option['uploaderButtonText'] : null,
						'toggle'             => isset( $option['toggle'] ) ? $option['toggle'] : null,
						'data'               => isset( $option['data'] ) ? $option['data'] : null,
					]
				);
			}
		}

		/* Creates our settings in the options table */
		register_setting( 'gfpdf_settings', 'gfpdf_settings', [ $this, 'settings_sanitize' ] );
	}

	/**
	 * Update a current registered settings
	 *
	 * @param string $group_id     The top-level group we're updating
	 * @param string $setting_id   The section group we're updating
	 * @param string $option_id    The option we are updating
	 * @param mixed  $option_value The new option value
	 *
	 * @return boolean              True on success, false on failure
	 *
	 * @since  4.0
	 */
	public function update_registered_field( $group_id, $setting_id, $option_id, $option_value ) {
		global $wp_settings_fields;

		$group   = 'gfpdf_settings_' . $group_id;
		$setting = "gfpdf_settings[$setting_id]";

		/* Check if our setting exists */
		if ( isset( $wp_settings_fields[ $group ][ $group ][ $setting ]['args'][ $option_id ] ) ) {
			/* phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited */
			$wp_settings_fields[ $group ][ $group ][ $setting ]['args'][ $option_id ] = $option_value;

			return true;
		}

		return false;
	}

	/**
	 * Get Settings
	 *
	 * Retrieves all plugin settings
	 *
	 * @return array GFPDF settings
	 * @since 4.0
	 *
	 */
	public function get_settings() {

		$is_temp = false;

		if ( $this->misc->is_gfpdf_page() ) {

			/*
			 * We are storing temporary settings in a transient when validation fails.
			 * This allows us to keep track of the updated fields without updating main settings in the DB
			 *
			 * We'll check if the transient exists and use it, otherwise get the main plugin settings from the options table
			 */
			$tmp_settings = get_transient( 'gfpdf_settings_user_data' );
			$is_temp      = $tmp_settings !== false;

			if ( $is_temp ) {
				delete_transient( 'gfpdf_settings_user_data' );
			}
		}

		$settings = $is_temp ? (array) $tmp_settings : get_option( 'gfpdf_settings', [] );

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_get_settings/ for more details about this filter */
		$settings = apply_filters( 'gfpdf_get_settings', $settings, $is_temp );

		/* Ensure $settings is an array and has not been corrupted somehow */
		$settings = is_array( $settings ) ? $settings : [];

		return $settings;
	}

	/**
	 * Get form settings if on that page in the admin area (by having ID and PID set in the $_GET or $_POST variables)
	 * Use get_pdf( $form_id, $pdf_id ) if you want to get a particular PDF setting
	 *
	 * @return array The stored form settings
	 *
	 * @since 4.0
	 */
	public function get_form_settings() {

		/* phpcs:disable WordPress.Security.NonceVerification.Recommended */
		$form_id = ! empty( $_GET['id'] ) ? (int) rgget( 'id' ) : (int) rgpost( 'id' );
		$pid     = ! empty( $_GET['pid'] ) ? sanitize_html_class( rgget( 'pid' ) ) : sanitize_html_class( rgpost( 'gform_pdf_id' ) );
		/* phpcs:enable */

		/* return early if no ID set */
		if ( ! $form_id || ! $pid ) {
			return [];
		}

		$settings = $this->get_pdf( $form_id, $pid );

		if ( ! is_wp_error( $settings ) ) {
			/* get the selected form settings */
			return $settings;
		}

		$this->log->error(
			'Settings Retrieval Error',
			[
				'form_id'          => $form_id,
				'pid'              => $pid,
				'WP_Error_Message' => $settings->get_error_message(),
				'WP_Error_Code'    => $settings->get_error_code(),
			]
		);

		/* there was an error */

		return [];
	}

	/**
	 * Get Form Settings
	 *
	 * Retrieves all form PDF settings
	 *
	 * @param integer $form_id The Gravity Form ID
	 *
	 * @return array|WP_Error An array of GFPDF settings, or WP_Error
	 * @since 4.0
	 *
	 */
	public function get_form_pdfs( $form_id ) {

		if ( ! isset( $this->data->form_settings ) ) {
			$this->data->form_settings = [];
		}

		$form_id = (int) $form_id;

		if ( 0 === $form_id ) {

			$error = new WP_Error( 'invalid_id', esc_html__( 'You must pass in a valid form ID', 'gravity-pdf' ) );
			$this->log->error(
				'Error Getting Settings.',
				[
					'WP_Error_Message' => $error->get_error_message(),
					'WP_Error_Code'    => $error->get_error_code(),
				]
			);

			return $error;
		}

		/* If we haven't pulled the form meta data from the database do so now */
		if ( ! isset( $this->data->form_settings[ $form_id ] ) ) {

			$form = $this->gform->get_form( $form_id );

			if ( empty( $form ) ) {

				$error = new WP_Error( 'invalid_id', esc_html__( 'You must pass in a valid form ID', 'gravity-pdf' ) );
				$this->log->error(
					'Error Getting Settings.',
					[
						'WP_Error_Message' => $error->get_error_message(),
						'WP_Error_Code'    => $error->get_error_code(),
					]
				);

				return $error;
			}

			/* Pull the settings from the $form object, if they exist */
			$settings = ( isset( $form['gfpdf_form_settings'] ) ) ? $form['gfpdf_form_settings'] : [];

			$this->data->form_settings[ $form_id ] = $settings;
		}

		/* return the form meta data */

		return $this->data->form_settings[ $form_id ];
	}

	/**
	 * Get pdf config
	 *
	 * Looks to see if the specified setting exists, returns default if not
	 *
	 * @param integer $form_id The Gravity Form ID
	 *
	 * @param string  $pdf_id  The Gravity Form PDF ID
	 *
	 * @return array|WP_Error
	 * @since 4.0
	 *
	 */
	public function get_pdf( $form_id, $pdf_id ) {
		$gfpdf_options = $this->get_form_pdfs( $form_id );

		if ( ! is_wp_error( $gfpdf_options ) ) {

			/* Get our PDF array if it exists */
			$pdf = ! empty( $gfpdf_options[ $pdf_id ] ) ? $gfpdf_options[ $pdf_id ] : new WP_Error( 'invalid_pdf_id', esc_html__( 'You must pass in a valid PDF ID', 'gravity-pdf' ) );

			if ( ! is_wp_error( $pdf ) ) {
				/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_pdf_config/ for more details about these filters */
				$pdf = apply_filters( 'gfpdf_pdf_config', $pdf, $form_id );
				$pdf = apply_filters( 'gfpdf_pdf_config_' . $form_id, $pdf, $form_id );

				return $pdf;
			}

			/* return WP_Error */

			return $pdf;
		}

		/* return WP_Error */

		return $gfpdf_options;
	}


	/**
	 * Create a new PDF configuration option for that form
	 *
	 * @param integer $form_id The form ID
	 * @param array   $pdf     The settings array
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	public function add_pdf( $form_id, $pdf = [] ) {
		$options = $this->get_form_pdfs( $form_id );

		if ( ! is_wp_error( $options ) ) {

			/* check the ID, if any */
			$pdf['id']     = ( isset( $pdf['id'] ) ) ? $pdf['id'] : uniqid();
			$pdf['active'] = ( isset( $pdf['active'] ) ) ? $pdf['active'] : true;

			/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_form_add_pdf/ for more details about these filters */
			$pdf = apply_filters( 'gfpdf_form_add_pdf', $pdf, $form_id );
			$pdf = apply_filters( 'gfpdf_form_add_pdf_' . $form_id, $pdf, $form_id );

			$results = $this->update_pdf( $form_id, $pdf['id'], $pdf, true, false );

			if ( $results ) {

				/* return the ID if successful */
				$this->log->notice(
					'Successfully Added New PDF',
					[
						'pdf' => $pdf,
					]
				);

				return $pdf['id'];
			}

			$this->log->error(
				'Error Saving New PDF',
				[
					'error' => $results,
					'pdf'   => $pdf,
				]
			);
		}

		return false;
	}

	/**
	 * Update an pdf config
	 *
	 * Updates a Gravity PDF setting value in both the db and the global variable.
	 * Warning: Passing in an empty, false or null string value will remove
	 *          the key from the gfpdf_options array.
	 *
	 * @param integer         $form_id   The Gravity Form ID
	 * @param string          $pdf_id    The PDF Setting ID
	 * @param bool|int|string $pdf       The PDF settings array
	 * @param bool            $update_db Whether we should just update the local PDF settings array, or update the DB as well
	 * @param bool            $filters   Whether we should apply the update filters
	 *
	 * @return bool True if updated, false if not.
	 * @since    4.0
	 *
	 */
	public function update_pdf( $form_id, $pdf_id, $pdf = '', $update_db = true, $filters = true ) {

		$this->log->notice(
			'Begin Updating PDF Settings',
			[
				'form_id'      => $form_id,
				'pdf_id'       => $pdf_id,
				'new_settings' => $pdf,
			]
		);

		if ( empty( $pdf ) || ! is_array( $pdf ) || count( $pdf ) === 0 ) {
			/* No value was passed in so we will delete the PDF */
			return $this->delete_pdf( $form_id, $pdf_id );
		}

		/* First let's grab the current settings */
		$options = $this->get_form_pdfs( $form_id );

		if ( ! is_wp_error( $options ) ) {

			/* Don't run when adding a new PDF */
			if ( $filters ) {
				$this->log->notice( 'Run PDF Update Filters' );

				/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_form_update_pdf/ for more details about these filters */
				$pdf = apply_filters( 'gfpdf_form_update_pdf', $pdf, $form_id, $pdf_id );
				$pdf = apply_filters( 'gfpdf_form_update_pdf_' . $form_id, $pdf, $form_id, $pdf_id );
			}

			/* Next let's try to update the value */
			$options[ $pdf_id ] = $pdf;

			/* get the up-to-date form object and merge in the results */
			$form = $this->gform->get_form( $form_id );

			/* Update our GFPDF settings */
			$form['gfpdf_form_settings'] = $options;

			$did_update = false;
			if ( $update_db ) {

				$this->log->notice(
					'Updating PDF Settings in Form Object',
					[
						'form_id' => $form['id'],
					]
				);

				/* Update the database, if able */
				$did_update = $this->gform->update_form( $form );
			}

			if ( ! $update_db || $did_update !== false ) {
				$this->data->form_settings[ $form_id ] = $options;
			}

			return $did_update;
		}

		$this->log->notice( 'Completed Updating PDF Settings' );

		return false;
	}

	/**
	 * Remove an option
	 *
	 * Removes an Gravity PDF setting value in both the db and the global variable.
	 *
	 * @param integer $form_id The Gravity Form ID
	 * @param string  $pdf_id  The Gravity Form PDF ID
	 *
	 * @return bool|WP_Error True if updated, false if not.
	 * @since    4.0
	 *
	 */
	public function delete_pdf( $form_id, $pdf_id ) {

		$this->log->notice(
			'Begin Deleting PDF Setting',
			[
				'form_id' => $form_id,
				'pdf_id'  => $pdf_id,
			]
		);

		/* First let's grab the current settings */
		$options = $this->get_form_pdfs( $form_id );

		if ( ! is_wp_error( $options ) ) {

			/* Next let's try to update the value */
			if ( isset( $options[ $pdf_id ] ) ) {
				unset( $options[ $pdf_id ] );
			}

			/* get the form and merge in the results */
			$form = $this->gform->get_form( $form_id );

			/* Update our GFPDF settings */
			$form['gfpdf_form_settings'] = $options;

			/* update the database, if able */
			$did_update = $this->gform->update_form( $form );

			/* If it updated, let's update the global variable */
			if ( $did_update !== false ) {

				$this->log->notice(
					'Completed Deleting PDF Setting',
					[
						'form_id' => $form_id,
						'pdf_id'  => $pdf_id,
					]
				);

				$this->data->form_settings[ $form_id ] = $options;
			}

			return $did_update;
		}

		$this->log->error(
			'Failed Deleting PDF Setting',
			[
				'form_id' => $form_id,
				'pdf_id'  => $pdf_id,
			]
		);

		return false;
	}

	/**
	 * Get a global setting option
	 *
	 * Looks to see if the specified setting exists, returns default if not
	 *
	 * @param string $key      The options key to get
	 * @param bool   $fallback The default option value if the key isn't found
	 *
	 * @return mixed
	 * @since 4.0
	 *
	 */
	public function get_option( $key = '', $fallback = false ) {

		$gfpdf_options = $this->settings;

		$value = ( ! empty( $gfpdf_options[ $key ] ) ) ? $gfpdf_options[ $key ] : $fallback;

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_get_option/ for more details about these filters */
		$value = apply_filters( 'gfpdf_get_option', $value, $key, $fallback );
		$value = apply_filters( 'gfpdf_get_option_' . $key, $value, $key, $fallback );

		return $value;
	}

	/**
	 * Update a global setting option
	 *
	 * Updates a Gravity PDF setting value in both the db and the global variable.
	 * Warning: Passing in an empty, false or null string value will remove
	 *          the key from the gfpdf_options array.
	 *
	 * @param string          $key   The Key to update
	 * @param string|bool|int $value The value to set the key to
	 *
	 * @return boolean True if updated, false if not.
	 * @since 4.0
	 *
	 */
	public function update_option( $key = '', $value = false ) {

		if ( empty( $key ) ) {
			$this->log->error(
				'Empty Option Key',
				[
					'value' => $value,
				]
			);

			return false;
		}

		if ( empty( $value ) ) {
			return $this->delete_option( $key );
		}

		/* First let's grab the current settings */
		$options = get_option( 'gfpdf_settings', [] );

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_update_option/ for more details about these filters */
		$value = apply_filters( 'gfpdf_update_option', $value, $key );
		$value = apply_filters( 'gfpdf_update_option_' . $key, $value, $key );

		/* Disable default sanitization (it shouldn't be triggered through this method) */
		remove_filter( 'sanitize_option_gfpdf_settings', [ $this, 'settings_sanitize' ] );

		/* Next let's try to update the value */
		$options[ $key ] = $value;
		$did_update      = update_option( 'gfpdf_settings', $options );

		/* Re-enable sanitization */
		add_filter( 'sanitize_option_gfpdf_settings', [ $this, 'settings_sanitize' ] );

		/* If it updated, let's update the global variable */
		if ( $did_update ) {
			$this->settings[ $key ] = $value;
		}

		return $did_update;
	}

	/**
	 * Remove a global setting option
	 *
	 * Removes an Gravity PDF setting value in both the db and the global variable.
	 *
	 * @param string $key The Key to delete
	 *
	 * @return boolean True if updated, false if not.
	 * @since 4.0
	 *
	 */
	public function delete_option( $key = '' ) {

		if ( empty( $key ) ) {
			$this->log->error( 'Option Delete Error' );

			return false;
		}

		// First let's grab the current settings
		$options = get_option( 'gfpdf_settings', [] );

		// Next let's try to update the value
		if ( isset( $options[ $key ] ) ) {
			unset( $options[ $key ] );
		}

		$did_update = update_option( 'gfpdf_settings', $options );

		if ( $did_update ) {
			$this->settings = $options;
		}

		return $did_update;
	}

	/**
	 * Get a list of user capabilities
	 *
	 * @return array The array of roles available
	 *
	 * @since 4.0
	 */
	public function get_capabilities() {

		/* sort through all roles and fetch unique capabilities */
		$roles        = get_editable_roles();
		$capabilities = [];

		/* Add Gravity Forms Capabilities */
		$gf_caps = $this->gform->get_capabilities();

		foreach ( $gf_caps as $gf_cap ) {
			$capabilities[ $gf_cap ] = $gf_cap;
		}

		foreach ( $roles as $role ) {
			if ( isset( $role['capabilities'] ) && is_array( $role['capabilities'] ) ) {
				foreach ( $role['capabilities'] as $cap => $val ) {
					if ( ! isset( $capabilities[ $cap ] ) && ! in_array( $cap, $gf_caps, true ) ) {
						$capabilities[ $cap ] = $cap;
					}
				}
			}
		}

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_capabilities/ for more details about this filter */

		return apply_filters( 'gfpdf_capabilities', $capabilities );
	}

	/**
	 * Return our paper size
	 *
	 * @return array The array of paper sizes available
	 *
	 * @since 4.0
	 */
	public function get_paper_size() {
		return apply_filters(
			'gfpdf_get_paper_size',
			[
				esc_html__( 'Common Sizes', 'gravity-pdf' ) => [
					'A4'        => esc_html__( 'A4 (210 x 297mm)', 'gravity-pdf' ),
					'LETTER'    => esc_html__( 'Letter (8.5 x 11in)', 'gravity-pdf' ),
					'LEGAL'     => esc_html__( 'Legal (8.5 x 14in)', 'gravity-pdf' ),
					'LEDGER'    => esc_html__( 'Ledger / Tabloid (11 x 17in)', 'gravity-pdf' ),
					'EXECUTIVE' => esc_html__( 'Executive (7 x 10in)', 'gravity-pdf' ),
					'CUSTOM'    => esc_html__( 'Custom Paper Size', 'gravity-pdf' ),
				],

				esc_html__( '"A" Sizes', 'gravity-pdf' ) => [
					'A0'  => esc_html__( 'A0 (841 x 1189mm)', 'gravity-pdf' ),
					'A1'  => esc_html__( 'A1 (594 x 841mm)', 'gravity-pdf' ),
					'A2'  => esc_html__( 'A2 (420 x 594mm)', 'gravity-pdf' ),
					'A3'  => esc_html__( 'A3 (297 x 420mm)', 'gravity-pdf' ),
					'A5'  => esc_html__( 'A5 (148 x 210mm)', 'gravity-pdf' ),
					'A6'  => esc_html__( 'A6 (105 x 148mm)', 'gravity-pdf' ),
					'A7'  => esc_html__( 'A7 (74 x 105mm)', 'gravity-pdf' ),
					'A8'  => esc_html__( 'A8 (52 x 74mm)', 'gravity-pdf' ),
					'A9'  => esc_html__( 'A9 (37 x 52mm)', 'gravity-pdf' ),
					'A10' => esc_html__( 'A10 (26 x 37mm)', 'gravity-pdf' ),
				],

				esc_html__( '"B" Sizes', 'gravity-pdf' ) => [
					'B0'  => esc_html__( 'B0 (1414 x 1000mm)', 'gravity-pdf' ),
					'B1'  => esc_html__( 'B1 (1000 x 707mm)', 'gravity-pdf' ),
					'B2'  => esc_html__( 'B2 (707 x 500mm)', 'gravity-pdf' ),
					'B3'  => esc_html__( 'B3 (500 x 353mm)', 'gravity-pdf' ),
					'B4'  => esc_html__( 'B4 (353 x 250mm)', 'gravity-pdf' ),
					'B5'  => esc_html__( 'B5 (250 x 176mm)', 'gravity-pdf' ),
					'B6'  => esc_html__( 'B6 (176 x 125mm)', 'gravity-pdf' ),
					'B7'  => esc_html__( 'B7 (125 x 88mm)', 'gravity-pdf' ),
					'B8'  => esc_html__( 'B8 (88 x 62mm)', 'gravity-pdf' ),
					'B9'  => esc_html__( 'B9 (62 x 44mm)', 'gravity-pdf' ),
					'B10' => esc_html__( 'B10 (44 x 31mm)', 'gravity-pdf' ),
				],

				esc_html__( '"C" Sizes', 'gravity-pdf' ) => [
					'C0'  => esc_html__( 'C0 (1297 x 917mm)', 'gravity-pdf' ),
					'C1'  => esc_html__( 'C1 (917 x 648mm)', 'gravity-pdf' ),
					'C2'  => esc_html__( 'C2 (648 x 458mm)', 'gravity-pdf' ),
					'C3'  => esc_html__( 'C3 (458 x 324mm)', 'gravity-pdf' ),
					'C4'  => esc_html__( 'C4 (324 x 229mm)', 'gravity-pdf' ),
					'C5'  => esc_html__( 'C5 (229 x 162mm)', 'gravity-pdf' ),
					'C6'  => esc_html__( 'C6 (162 x 114mm)', 'gravity-pdf' ),
					'C7'  => esc_html__( 'C7 (114 x 81mm)', 'gravity-pdf' ),
					'C8'  => esc_html__( 'C8 (81 x 57mm)', 'gravity-pdf' ),
					'C9'  => esc_html__( 'C9 (57 x 40mm)', 'gravity-pdf' ),
					'C10' => esc_html__( 'C10 (40 x 28mm)', 'gravity-pdf' ),
				],

				esc_html__( '"RA" and "SRA" Sizes', 'gravity-pdf' ) => [
					'RA0'  => esc_html__( 'RA0 (860 x 1220mm)', 'gravity-pdf' ),
					'RA1'  => esc_html__( 'RA1 (610 x 860mm)', 'gravity-pdf' ),
					'RA2'  => esc_html__( 'RA2 (430 x 610mm)', 'gravity-pdf' ),
					'RA3'  => esc_html__( 'RA3 (305 x 430mm)', 'gravity-pdf' ),
					'RA4'  => esc_html__( 'RA4 (215 x 305mm)', 'gravity-pdf' ),
					'SRA0' => esc_html__( 'SRA0 (900 x 1280mm)', 'gravity-pdf' ),
					'SRA1' => esc_html__( 'SRA1 (640 x 900mm)', 'gravity-pdf' ),
					'SRA2' => esc_html__( 'SRA2 (450 x 640mm)', 'gravity-pdf' ),
					'SRA3' => esc_html__( 'SRA3 (320 x 450mm)', 'gravity-pdf' ),
					'SRA4' => esc_html__( 'SRA4 (225 x 320mm)', 'gravity-pdf' ),
				],
			]
		);
	}


	/**
	 * Parse our installed font files
	 *
	 * @return array The array of fonts
	 *
	 * @since 4.0
	 */
	public function get_installed_fonts() {
		$fonts = [
			esc_html__( 'Unicode', 'gravity-pdf' ) => [
				'dejavusanscondensed'  => 'Dejavu Sans Condensed',
				'dejavusans'           => 'Dejavu Sans',
				'dejavuserifcondensed' => 'Dejavu Serif Condensed',
				'dejavuserif'          => 'Dejavu Serif',
				'dejavusansmono'       => 'Dejavu Sans Mono',

				'freesans'             => 'Free Sans',
				'freeserif'            => 'Free Serif',
				'freemono'             => 'Free Mono',

				'mph2bdamase'          => 'MPH 2B Damase',
			],

			esc_html__( 'Indic', 'gravity-pdf' )   => [
				'lohitkannada' => 'Lohit Kannada',
				'pothana2000'  => 'Pothana2000',
			],

			esc_html__( 'Arabic', 'gravity-pdf' )  => [
				'xbriyaz'               => 'XB Riyaz',
				'lateef'                => 'Lateef',
				'kfgqpcuthmantahanaskh' => 'Bahif Uthman Taha',
			],

			esc_html__( 'Chinese, Japanese, Korean', 'gravity-pdf' ) => [
				'sun-exta' => 'Sun Ext',
				'unbatang' => 'Un Batang (Korean)',
			],

			esc_html__( 'Other', 'gravity-pdf' )   => [
				'estrangeloedessa' => 'Estrangelo Edessa (Syriac)',
				'kaputaunicode'    => 'Kaputa (Sinhala)',
				'abyssinicasil'    => 'Abyssinica SIL (Ethiopic)',
				'aboriginalsans'   => 'Aboriginal Sans (Cherokee / Canadian)',
				'jomolhari'        => 'Jomolhari (Tibetan)',
				'sundaneseunicode' => 'Sundanese (Sundanese)',
				'taiheritagepro'   => 'Tai Heritage Pro (Tai Viet)',
				'aegyptus'         => 'Aegyptus (Egyptian Hieroglyphs)',
				'akkadian'         => 'Akkadian (Cuneiform)',
				'aegean'           => 'Aegean (Greek)',
				'quivira'          => 'Quivira (Greek)',
				'eeyekunicode'     => 'Eeyek (Meetei Mayek)',
				'lannaalif'        => 'Lanna Alif (Tai Tham)',
				'daibannasilbook'  => 'Dai Banna SIL (New Tai Lue)',
				'garuda'           => 'Garuda (Thai)',
				'khmeros'          => 'Khmer OS (Khmer)',
				'dhyana'           => 'Dhyana (Lao)',
				'tharlon'          => 'TharLon (Myanmar / Burmese)',
				'padaukbook'       => 'Padauk Book (Myanmar / Burmese)',
				'zawgyi-one'       => 'Zawgyi One (Myanmar / Burmese)',
				'ayar'             => 'Ayar Myanmar (Myanmar / Burmese)',
				'taameydavidclm'   => 'Taamey David CLM (Hebrew)',
			],
		];

		$fonts = $this->add_custom_fonts( $fonts );

		return apply_filters( 'gfpdf_font_list', $fonts );
	}

	/**
	 * If any custom fonts add them to our font list
	 *
	 * @param array $fonts Current font list
	 *
	 * @return array The list of custom fonts installed in a preformatted array
	 *
	 * @since 4.0
	 */
	public function add_custom_fonts( $fonts = [] ) {

		$custom_fonts = $this->get_custom_fonts();

		if ( count( $custom_fonts ) > 0 ) {

			$user_defined_fonts = [];

			/* Loop through our fonts and assign them to a new array in the appropriate format */
			foreach ( $custom_fonts as $font ) {
				$user_defined_fonts[ $font['id'] ] = $font['font_name'];
			}

			/* Merge the new fonts at the beginning of the $fonts array */
			$fonts = $this->misc->array_unshift_assoc( $fonts, esc_html__( 'User-Defined Fonts', 'gravity-pdf' ), $user_defined_fonts );
		}

		return $fonts;
	}

	/**
	 * Get a list of the custom fonts installed
	 *
	 * @return array
	 *
	 * @since 4.0
	 *
	 * @deprecated
	 */
	public function get_custom_fonts() {
		/** @var Controller_Custom_Fonts $custom_font_controller */
		$custom_font_controller = \GPDFAPI::get_mvc_class( 'Controller_Custom_Fonts' );

		return $custom_font_controller->get_all_items();
	}

	/**
	 * Get font shortname we can use an in array
	 *
	 * @param string $name The font name to convert
	 *
	 * @since  4.0
	 *
	 * @deprecated
	 */
	public function get_font_short_name( $name ): string {
		/** @var Model_Custom_Fonts $custom_font_model */
		$custom_font_model = \GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' );

		return $custom_font_model->get_font_short_name( $name );
	}

	/**
	 * Get the font's display name from the font key
	 *
	 * @param string $font_key The font key to search for
	 *
	 * @return mixed (String / Object)           The font display name or WP_Error
	 *
	 * @since 4.0
	 *
	 * @deprecated
	 */
	public function get_font_display_name( $font_key ) {

		foreach ( $this->get_installed_fonts() as $groups ) {
			if ( isset( $groups[ $font_key ] ) ) {
				return $groups[ $font_key ];
			}
		}

		return new WP_Error( 'font_not_found', esc_html__( 'Could not find Gravity PDF Font', 'gravity-pdf' ) );
	}

	/**
	 * Parse our PDF privileges
	 *
	 * @return array The array of privilages
	 *
	 * @since 4.0
	 */
	public function get_privilages() {
		$privileges = [
			'copy'          => esc_html__( 'Copy', 'gravity-pdf' ),
			'print'         => esc_html__( 'Print - Low Resolution', 'gravity-pdf' ),
			'print-highres' => esc_html__( 'Print - High Resolution', 'gravity-pdf' ),
			'modify'        => esc_html__( 'Modify', 'gravity-pdf' ),
			'annot-forms'   => esc_html__( 'Annotate', 'gravity-pdf' ),
			'fill-forms'    => esc_html__( 'Fill Forms', 'gravity-pdf' ),
			'extract'       => esc_html__( 'Extract', 'gravity-pdf' ),
			'assemble'      => esc_html__( 'Assemble', 'gravity-pdf' ),
		];

		return apply_filters( 'gfpdf_privilages_list', $privileges );
	}

	/**
	 * Settings Sanitization
	 *
	 * Adds a settings error (for the updated message)
	 * Run on admin options.php page
	 *
	 * @param array $input The value inputted in the field
	 *
	 * @return array $input Sanitized value
	 *
	 * @since 4.0
	 *
	 */
	public function settings_sanitize( $input = [] ) {

		$gfpdf_options = $this->settings;

		/* phpcs:ignore WordPress.Security.NonceVerification.Missing */
		if ( empty( $_POST['_wp_http_referer'] ) || empty( $_POST['option_page'] ) || $_POST['option_page'] !== 'gfpdf_settings' ) {
			return $input;
		}

		/* phpcs:ignore WordPress.Security.NonceVerification.Missing */
		parse_str( $_POST['_wp_http_referer'], $referrer );

		$all_settings = $this->get_registered_fields();
		$tab          = sanitize_key( $referrer['tab'] ?? 'general' );
		$settings     = ( ! empty( $all_settings[ $tab ] ) && $tab !== 'tools' ) ? $all_settings[ $tab ] : [];

		/*
		 * Get all setting types
		 */
		$tab_len = strlen( $tab );
		foreach ( $all_settings as $id => $s ) {
			/*
			 * Check if extra item(s) belongs on page but isn't the existing page
			 * Note that this requires the section ID share a similar ID to what is referenced in $tab
			 */
			if ( $tab !== $id && $tab === substr( $id, 0, $tab_len ) ) {
				$settings = array_merge( $settings, $s );
			}
		}

		$input = $input ? $input : [];
		$input = apply_filters( 'gfpdf_settings_' . $tab . '_sanitize', $input );

		/*
		 * Loop through the settings whitelist and add any missing fields to the $input
		 * (prevalent with Select boxes)
		 */
		foreach ( $settings as $key => $value ) {
			switch ( $value['type'] ) {
				case 'select':
				case 'multicheck':
					if ( ! isset( $input[ $key ] ) ) {
						$input[ $key ] = [];
					}
					break;

				default:
					if ( ! isset( $input[ $key ] ) ) {
						$input[ $key ] = '';
					}
					break;
			}
		}

		/* Loop through each setting being saved and pass it through a sanitization filter */
		foreach ( $input as $key => $value ) {

			/* Check if the input is apart of our whitelist, otherwise remove */
			if ( ! isset( $settings[ $key ] ) ) {
				unset( $input[ $key ] );
				continue;
			}

			/* Get the setting type (checkbox, select, etc) */
			$type = isset( $settings[ $key ]['type'] ) ? $settings[ $key ]['type'] : false;

			/*
			 * General filter
			 *
			 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_settings_sanitize/ for more details about this filter
			 */
			$input[ $key ] = apply_filters( 'gfpdf_settings_sanitize', $input[ $key ], $key, $input, $settings[ $key ] );

			if ( $type ) {
				/*
				 * Field type specific filter
				 *
				 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_settings_sanitize/ for more details about this filter
				 */
				$input[ $key ] = apply_filters( 'gfpdf_settings_sanitize_' . $type, $value, $key, $input, $settings[ $key ] );
			}
		}

		$settings_errors = get_settings_errors();
		if ( count( $settings_errors ) === 0 ) {
			/* Merge our new settings with the existing */
			$output = array_merge( $gfpdf_options, $input );
			add_settings_error( 'gfpdf-notices', '', esc_html__( 'Settings updated.', 'gravity-pdf' ), 'updated' );
		} elseif ( count( $settings_errors ) === 1 && $settings_errors[0]['setting'] === 'gfpdf-notices' && $settings_errors[0]['type'] === 'updated' ) {
			/* Merge our new settings with the existing, but without the update message (prevents saving issue) */
			$output = array_merge( $gfpdf_options, $input );
		} else {
			/* error is thrown. store the user data in a transient so fields are remembered */
			set_transient( 'gfpdf_settings_user_data', array_merge( $gfpdf_options, $input ), 30 );

			return [];
		}

		return $output;
	}

	/**
	 * Sanitize text / textarea fields
	 *
	 * @param string $input The field value
	 *
	 * @return string $input Sanitized value
	 * @since 4.0
	 *
	 */
	public function sanitize_trim_field( $input ) {
		return trim( $input );
	}

	/**
	 * Sanitize number fields
	 *
	 * @param mixed $value The field's user input value
	 * @param string $key The settings key
	 * @param array $input All user fields
	 * @param array $settings The field settings
	 *
	 * @return string $input Sanitized value
	 * @since 4.0
	 * @since 6.11 Force minimum and maximum values
	 */
	public function sanitize_number_field( $value, $key = '', $input = [], $settings = [] ) {
		if ( ! is_numeric( $value ) ) {
			return 0;
		}

		/* force PHP to convert string to int or float, depending on input */
		$value += 0;

		/* If number less than the minimum, set to the minimum */
		if ( ! empty( $settings['min'] ) && $value < $settings['min'] ) {
			$value = $settings['min'];
		}

		/* If number more than the maximum, set to the maximum */
		if ( ! empty( $settings['max'] ) && $value > $settings['max'] ) {
			$value = $settings['max'];
		}

		return $value;
	}

	/**
	 * Converts negative numbers to positive numbers
	 *
	 * @param array $input The unsanitized paper size
	 *
	 * @return array        The sanitized paper size
	 *
	 * @since 4.0
	 */
	public function sanitize_paper_size( $input ) {
		if ( is_array( $input ) && count( $input ) === 3 ) {
			$input[0] = abs( (float) $input[0] );
			$input[1] = abs( (float) $input[1] );
		}

		return $input;
	}

	/**
	 * Sanitize all fields depending on type
	 *
	 * @param mixed  $value    The field's user input value
	 * @param string $key      The settings key
	 * @param array  $input    All user fields
	 * @param array  $settings The field settings
	 *
	 * @return string|array $input Sanitized value
	 * @since 4.0
	 *
	 */
	public function sanitize_all_fields( $value, $key, $input, $settings ) {

		if ( ! isset( $settings['type'] ) ) {
			$settings['type'] = '';
		}

		if ( ( $settings['id'] ?? '' ) === 'conditionalLogic' ) {
			return ! empty( $value ) ? wp_json_encode( \GFFormsModel::sanitize_conditional_logic( json_decode( $value, true ) ) ) : '';
		}

		switch ( $settings['type'] ) {
			case 'rich_editor':
				if ( strpos( $value, 'telnet://{' ) === false ) {
					/*
					 * So merge tags can be used inside HTML attributes (like src="{tag:20}"), but the rich editor content
					 * can still be sanitized, we'll replace the opening { tag with a valid protocol that isn't very common.
					 * Doing this ensures the merge tag does not get malformed during sanitizing.
					 * When outputting rich text, it is important that the merge tags get processed first and then the output
					 * run through Kses::parse() or Kses::output() to ensure the HTML safe.
					 */
					$pattern = '([^{]*?})';
					$value   = preg_replace( "/=\"\{$pattern\"/mi", '="telnet://$1"', $value );
					$value   = Kses::parse( $value );
					$value   = preg_replace( "/=\"telnet:\/\/$pattern\"/mi", '="{$1"', $value );
				} else {
					/* Don't encode/decode merge tag before sanitizing */
					$value = Kses::parse( $value );
				}

				return $value;

			case 'textarea':
				return sanitize_textarea_field( $value );

			/* treat as plain text */
			default:
				if ( is_array( $value ) ) {
					return array_map( 'sanitize_text_field', $value );
				}

				return sanitize_text_field( $value );
		}
	}

	/**
	 * Sanitize all required fields
	 *
	 * @param mixed  $value    The field's user input value
	 * @param string $key      The settings key
	 * @param array  $input    All user fields
	 * @param array  $settings The field settings
	 *
	 * @return string $input Sanitized value
	 * @since 4.0
	 *
	 */
	public function sanitize_required_field( $value, $key, $input, $settings ) {

		if ( isset( $settings['required'] ) && $settings['required'] === true ) {

			switch ( $settings['type'] ) {
				case 'select':
				case 'multicheck':
					$size = count( $value );
					if ( empty( $value ) || count( array_filter( $value ) ) !== $size ) {
						/* throw error */
						add_settings_error( 'gfpdf-notices', $key, esc_html__( 'PDF Settings could not be saved. Please enter all required information below.', 'gravity-pdf' ) );
					}
					break;

				case 'paper_size':
					if ( isset( $input['default_pdf_size'] ) && $input['default_pdf_size'] === 'CUSTOM' ) {
						if ( count( array_filter( $value ) ) !== 3 ) {
							/* throw error */
							add_settings_error( 'gfpdf-notices', $key, esc_html__( 'PDF Settings could not be saved. Please enter all required information below.', 'gravity-pdf' ) );
						}
					}
					break;

				default:
					if ( strlen( trim( $value ) ) === 0 ) {
						/* throw error */
						add_settings_error( 'gfpdf-notices', $key, esc_html__( 'PDF Settings could not be saved. Please enter all required information below.', 'gravity-pdf' ) );
					}
					break;
			}
		}

		return $value;
	}

	/**
	 * Gets the correct option value based on the field type
	 *
	 * @param array $args The field articles
	 *
	 * @return string|array       The current value for that particular field
	 *
	 * @since  4.0
	 */
	public function get_form_value( $args = [] ) {

		/* If callback method called directly (and not through the Settings API) */
		if ( isset( $args['value'] ) ) {
			return $args['value'];
		}

		/* Get our global Gravity PDF Settings */
		$options = $this->settings;

		/* Get our PDF GF settings (if any) */
		$pdf_form_settings = $this->get_form_settings();

		if ( ! isset( $args['type'] ) ) {
			$args['type'] = '';
		}

		/* Fix up our conditional logic array so it returns a string value */
		if ( $args['id'] === 'conditionalLogic' && isset( $pdf_form_settings['conditionalLogic'] ) ) {
			$pdf_form_settings['conditionalLogic'] = wp_json_encode( $pdf_form_settings['conditionalLogic'] );
		}

		switch ( $args['type'] ) {
			case 'license':
				if ( isset( $options[ $args['id'] ] ) ) {
					return [
						'key'    => $options[ $args['id'] ],
						'msg'    => ( isset( $options[ $args['id'] . '_message' ] ) ) ? $options[ $args['id'] . '_message' ] : '',
						'status' => ( isset( $options[ $args['id'] . '_status' ] ) ) ? $options[ $args['id'] . '_status' ] : '',
					];
				} else {
					return [
						'key'    => '',
						'msg'    => '',
						'status' => '',
					];
				}

			case 'checkbox':
				if ( isset( $options[ $args['id'] ] ) ) {
					return checked( 1, $options[ $args['id'] ], false );

				} elseif ( isset( $pdf_form_settings[ $args['id'] ] ) ) {
					return checked( 1, $pdf_form_settings[ $args['id'] ], false );

				} elseif ( $args['std'] === true ) {
					return checked( 1, 1, false );
				}

				break;

			case 'multicheck':
				if ( isset( $options[ $args['id'] ][ $args['multi-key'] ] ) ) {
					return $args['multi-option'];

				} elseif ( isset( $pdf_form_settings[ $args['id'] ][ $args['multi-key'] ] ) ) {
					return $args['multi-option'];
				}

				/* Add support for switching setting from (multi)select to multicheck */
				$legacy_options = isset( $options[ $args['id'] ] ) ? array_flip( $options[ $args['id'] ] ) : [];
				if ( isset( $legacy_options[ $args['multi-option'] ] ) ) {
					return $args['multi-option'];
				}

				$legacy_options = isset( $pdf_form_settings[ $args['id'] ] ) ? array_flip( $pdf_form_settings[ $args['id'] ] ) : [];
				if ( isset( $legacy_options[ $args['multi-option'] ] ) ) {
					return $args['multi-option'];
				}

				/* Add default support */
				if ( ! isset( $options[ $args['id'] ] ) && ! isset( $pdf_form_settings[ $args['id'] ] ) && isset( $args['std'] ) ) {
					$args['std'] = is_array( $args['std'] ) ? $args['std'] : [ $args['std'] ];

					if ( in_array( $args['multi-key'], $args['std'], true ) ) {
						return $args['multi-option'];
					}
				}

				break;

			case 'radio':
				if ( isset( $options[ $args['id'] ] ) && isset( $args['options'][ $options[ $args['id'] ] ] ) ) {
					return $options[ $args['id'] ];

				} elseif ( isset( $pdf_form_settings[ $args['id'] ] ) && isset( $args['options'][ $pdf_form_settings[ $args['id'] ] ] ) ) {
					return $pdf_form_settings[ $args['id'] ];

				} elseif ( isset( $args['std'] ) ) {
					return $args['std'];
				}

				break;

			case 'password':
				if ( isset( $options[ $args['id'] ] ) ) {
					return trim( $options[ $args['id'] ] );

				} elseif ( isset( $pdf_form_settings[ $args['id'] ] ) ) {
					return trim( $pdf_form_settings[ $args['id'] ] );
				}

				break;

			case 'select':
			case 'paper_size':
				if ( isset( $options[ $args['id'] ] ) ) {
					return $options[ $args['id'] ];

				} elseif ( isset( $pdf_form_settings[ $args['id'] ] ) ) {
					return $pdf_form_settings[ $args['id'] ];

				} elseif ( isset( $args['std'] ) ) {
					return $args['std'];
				}
				break;

			/* treat as a text or hidden callback */
			default:
				if ( isset( $options[ $args['id'] ] ) ) {
					return trim( $options[ $args['id'] ] );

				} elseif ( isset( $pdf_form_settings[ $args['id'] ] ) ) {
					return trim( $pdf_form_settings[ $args['id'] ] );

				} elseif ( isset( $args['std'] ) ) {
					return $args['std'];
				}
				break;
		}

		/* if we made it here return empty string */

		return '';
	}

	/**
	 * Checkbox Callback
	 *
	 * Renders checkboxes.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function checkbox_callback( $args ) {

		/* get our selected value */
		$checked  = $this->get_form_value( $args );
		$required = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$id       = ( isset( $args['idOverride'] ) ) ? $args['idOverride'] : 'gfpdf_settings[' . $args['id'] . ']';

		?>
		<div class="gform-settings-description gform-kitchen-sink">
			<label for="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]">
				<?php echo wp_kses_post( $args['desc2'] ); ?>
			</label>
		</div>

		<input type="checkbox"
			   id="<?php echo esc_attr( $id ); ?>"
			   class="gfpdf_settings_<?php echo esc_attr( $args['id'] ); ?> <?php echo esc_attr( $args['inputClass'] ?? '' ); ?>"
			   name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
			   value="1"
			   <?php if ( strpos( $checked, 'checked' ) !== false ): ?>
					 checked="checked"
			   <?php endif ?>
			   <?php echo esc_attr( $required ); ?>
		/>

		<label for="<?php echo esc_attr( $id ); ?>">
			<?php echo wp_kses_post( $args['desc'] ); ?>
		</label>
		<?php
	}

	/**
	 * Multicheck Callback
	 *
	 * Renders multiple checkboxes.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function multicheck_callback( $args ) {

		$class    = $args['inputClass'] ?? '';
		$required = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';

		if ( empty( $args['options'] ) ) {
			return;
		}

		?>
		<div class="gform-settings-description gform-kitchen-sink">
			<label for="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]">
				<?php echo wp_kses_post( $args['desc'] ); ?>
			</label>
		</div>

		<?php
		foreach ( $args['options'] as $key => $option ):
			/* Set up multi-select option to pass to our form value getter */
			$args['multi-key']    = $key;
			$args['multi-option'] = $key;

			$enabled = $this->get_form_value( $args );

			?>

			<div class="gfpdf-settings-multicheck-wrapper">
				<input name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>][<?php echo esc_attr( $args['multi-key'] ); ?>]"
					   id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>][<?php echo esc_attr( $args['multi-key'] ); ?>]"
					   class="<?php echo esc_attr( 'gfpdf_settings_' . $args['id'] ); ?> <?php echo esc_attr( $class ); ?>"
					   type="checkbox"
					   value="<?php echo esc_attr( $args['multi-key'] ); ?>"
				<?php checked( $key, $enabled ); ?>
				<?php echo esc_attr( $required ); ?>
				/>
				&nbsp;

				<label for="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>][<?php echo esc_attr( $args['multi-key'] ); ?>]">
					<?php echo wp_kses_post( $option ); ?>
				</label>
			</div>
		<?php endforeach; ?>

		<?php echo wp_kses_post( $args['desc2'] ); ?>

		<?php
	}

	/**
	 * Radio Callback
	 *
	 * Renders radio boxes.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function radio_callback( $args ) {

		/* get selected value (if any) */
		$selected = $this->get_form_value( $args );
		$required = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';

		?>

		<div class="gform-settings-description gform-kitchen-sink">
			<label for="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]">
				<?php echo wp_kses_post( $args['desc'] ); ?>
			</label>
		</div>

		<?php
		foreach ( $args['options'] as $key => $option ):
			$checked = false;
			if ( $selected === $key ) {
				$checked = true;
			}
			?>

			<span class="entry-view">
				<input name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
					   class="<?php echo esc_attr( 'gfpdf_settings_' . $args['id'] ); ?>"
					   id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>][<?php echo esc_attr( $key ); ?>]"
					   type="radio"
					   value="<?php echo esc_attr( $key ); ?>"
					   <?php checked( true, $checked ); ?>
					   <?php echo esc_attr( $required ); ?>
				/>

				<label for="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>][<?php echo esc_attr( $key ); ?>]">
					<?php echo wp_kses_post( $option ); ?>
				</label>
			</span>
		<?php endforeach; ?>

		<?php echo wp_kses_post( $args['desc2'] ); ?>

		<?php
	}

	/**
	 * Text Callback
	 *
	 * Renders text fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function text_callback( $args ) {

		/* get selected value (if any) */
		$value      = $this->get_form_value( $args );
		$class      = $args['inputClass'] ?? '';
		$input_data = ( isset( $args['data'] ) && is_array( $args['data'] ) ) ? $args['data'] : [];
		$required   = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$size       = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';

		?>

		<div class="gform-settings-description gform-kitchen-sink">
			<label for="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]">
				<?php echo wp_kses_post( $args['desc'] ); ?>
			</label>
		</div>

		<input type="text"
			   class="<?php echo esc_attr( $size . '-text' ); ?> <?php echo esc_attr( $class ); ?>"
			   id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
			   class="<?php echo esc_attr( 'gfpdf_settings_' . $args['id'] ); ?>"
			   name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
			   value="<?php echo esc_attr( $value ); ?>"
			   <?php echo esc_attr( $required ); ?>
				<?php foreach ( $input_data as $data_id => $data_value ): ?>
					<?php echo esc_html( 'data-' . $data_id ); ?>="<?php echo esc_attr( $data_value ); ?>"
				<?php endforeach; ?>
		/>

		<?php echo wp_kses_post( $args['desc2'] ); ?>

		<?php
	}

	/**
	 * License Callback
	 *
	 * Renders a license field.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.2
	 *
	 */
	public function license_callback( $args ) {

		/* get selected value (if any) */
		$value = $this->get_form_value( $args );

		$error_statuses = [ '', 'active' ];
		$is_error       = ! in_array( $value['status'], $error_statuses, true );
		$is_active      = $value['status'] === 'active';
		?>

		<?php if ( ! empty( $value['msg'] ) ): ?>
			<?php if ( $is_error ): ?>
				<div class="alert gforms_note_error">
					<?php echo wp_kses_post( $value['msg'] ); ?>
				</div>
			<?php else : ?>
				<div id="message" class="alert gforms_note_success">
					<?php echo wp_kses_post( $value['msg'] ); ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<label for="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]" class="screen-reader-text">
			<?php echo esc_html( sprintf( __( '%s license key', 'gravity-pdf' ), $args['name'] ) ); ?>
		</label>

		<input autocomplete="off"
			   type="password"
			   id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
			   class="<?php echo esc_attr( 'gfpdf_settings_' . $args['id'] ); ?>"
			   name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
			   value="<?php echo esc_attr( ! empty( $value['key'] ) ? sha1( $value['key'] ) : '' ); ?>"
		/>

		<?php if ( $is_active ): ?>
			<button type="button"
					class="button primary white gfpdf-deactivate-license"
					data-addon-name="<?php echo esc_attr( substr( $args['id'], 8 ) ); ?>"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'gfpdf_deactivate_license' ) ); ?>"
			>
				<?php echo esc_attr__( 'Deactivate License', 'gravity-pdf' ); ?>
			</button>
		<?php endif; ?>

		<?php echo wp_kses_post( $args['desc2'] ); ?>

		<?php
	}

	/**
	 * Number Callback
	 *
	 * Renders number fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function number_callback( $args ) {

		/* get selected value (if any) */
		$value = $this->get_form_value( $args );

		/* ensure value is not an array */
		if ( is_array( $value ) ) {
			$value = implode( ' ', $value );
		}

		/* check if required */
		$class      = $args['inputClass'] ?? '';
		$required   = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$input_data = ( isset( $args['data'] ) && is_array( $args['data'] ) ) ? $args['data'] : [];

		$max  = isset( $args['max'] ) ? (int) $args['max'] : 999999;
		$min  = isset( $args['min'] ) ? (int) $args['min'] : 0;
		$step = isset( $args['step'] ) ? (int) $args['step'] : 1;

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';

		?>

		<div class="gform-settings-description gform-kitchen-sink">
			<label for="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]">
				<?php echo wp_kses_post( $args['desc'] ); ?>
			</label>
		</div>

		<input type="number"
			   step="<?php echo esc_attr( $step ); ?>"
			   max="<?php echo esc_attr( $max ); ?>"
			   min="<?php echo esc_attr( $min ); ?>"
			   class="<?php echo esc_attr( $size . '-text' ); ?> <?php echo esc_attr( 'gfpdf_settings_' . $args['id'] ); ?> <?php echo esc_attr( $class ); ?>"
			   id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
			   name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
			   value="<?php echo esc_attr( $value ); ?>"
		<?php echo esc_attr( $required ); ?>
		<?php foreach ( $input_data as $data_id => $data_value ): ?>
			<?php echo esc_html( 'data-' . $data_id ); ?>="<?php echo esc_attr( $data_value ); ?>"
		<?php endforeach; ?>
		/>

		<?php echo wp_kses_post( $args['desc2'] ); ?>

		<?php
	}

	/**
	 * Textarea Callback
	 *
	 * Renders textarea fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function textarea_callback( $args ) {

		/* get selected value (if any) */
		$value      = $this->get_form_value( $args );
		$class      = $args['inputClass'] ?? '';
		$required   = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$input_data = ( isset( $args['data'] ) && is_array( $args['data'] ) ) ? $args['data'] : [];
		$toggle     = ( ! empty( $args['toggle'] ) ) ? $args['toggle'] : false;

		?>

		<?php if ( $toggle !== false ): ?>
			<?php $this->start_toggle_input( $toggle, $value ); ?>
		<?php endif; ?>

		<div class="gform-settings-description gform-kitchen-sink">
			<label for="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]">
				<?php echo wp_kses_post( $args['desc'] ); ?>
			</label>
		</div>

		<textarea cols="50"
				  rows="5"
				  id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
				  class="large-text <?php echo esc_attr( 'gfpdf_settings_' . $args['id'] ); ?> <?php echo esc_attr( $class ); ?>"
				  name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
				<?php echo esc_attr( $required ); ?>
				<?php foreach ( $input_data as $data_id => $data_value ): ?>
					<?php echo esc_html( 'data-' . $data_id ); ?>="<?php echo esc_attr( $data_value ); ?>"
				<?php endforeach; ?>
		><?php echo esc_textarea( $value ); ?></textarea>

		<?php if ( $toggle !== false ): ?>
			<?php $this->end_toggle_input(); ?>
		<?php endif; ?>

		<?php echo wp_kses_post( $args['desc2'] ); ?>

		<?php
	}

	/**
	 * Password Callback
	 *
	 * Renders password fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function password_callback( $args ) {

		/* get selected value (if any) */
		$value    = $this->get_form_value( $args );
		$class    = $args['inputClass'] ?? '';
		$required = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$size     = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';

		?>

		<div class="gform-settings-description gform-kitchen-sink">
			<label for="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]">
				<?php echo wp_kses_post( $args['desc'] ); ?>
			</label>
		</div>

		<input type="password"
			   id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
			   class="<?php echo esc_attr( 'gfpdf_settings_' . $args['id'] ); ?> <?php echo esc_attr( $size . '-text' ); ?> <?php echo esc_attr( $class ); ?>"
			   name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
			   value="<?php echo esc_attr( $value ); ?>"
				<?php echo esc_attr( $required ); ?>
		/>

		<?php echo wp_kses_post( $args['desc2'] ); ?>

			<?php
	}

	/**
	 * Select Callback
	 *
	 * Renders select fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function select_callback( $args ) {

		/* get selected value (if any) */
		$value       = $this->get_form_value( $args );
		$placeholder = $args['placeholder'] ?? '';
		$class       = $args['inputClass'] ?? '';
		$required    = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$input_data  = ( isset( $args['data'] ) && is_array( $args['data'] ) ) ? $args['data'] : [];

		$multiple     = '';
		$multiple_ext = '';
		if ( isset( $args['multiple'] ) ) {
			$multiple     = 'multiple';
			$multiple_ext = '[]';
		}

		?>

		<div class="gform-settings-description gform-kitchen-sink">
			<label for="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]">
				<?php echo wp_kses_post( $args['desc'] ); ?>
			</label>
		</div>

		<?php if ( ! empty( $args['chosen'] ) ): ?>
			<span class="gform-settings-input__container">
				<span class="gform-settings-field__select--enhanced">
		<?php endif; ?>

		<select id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
				class="<?php echo esc_attr( 'gfpdf_settings_' . $args['id'] ); ?> <?php echo esc_attr( $class ); ?>"
				name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]<?php echo esc_attr( $multiple_ext ); ?>"
				data-placeholder="<?php echo esc_attr( $placeholder ); ?>"
				<?php echo esc_attr( $multiple ); ?>
				<?php echo esc_attr( $required ); ?>
				<?php foreach ( $input_data as $data_id => $data_value ): ?>
					<?php echo esc_html( 'data-' . $data_id ); ?>="<?php echo esc_attr( $data_value ); ?>"
				<?php endforeach; ?>
		>

		<?php $this->build_options_for_select( $args['options'], $value, true ); ?>

		</select>

		<?php if ( ! empty( $args['chosen'] ) ): ?>
				</span>
			</span>

			<script type="text/javascript">
			  jQuery( document ).ready( function () {
				jQuery( "#gfpdf_settings\\[<?php echo esc_attr( $args['id'] ); ?>\\]" ).select2( {
				  minimumResultsForSearch: Infinity,
				  dropdownCssClass: "gform-settings-field__select-enhanced-container",
				  dropdownParent: jQuery( "#gfpdf_settings\\[<?php echo esc_attr( $args['id'] ); ?>\\]" ).parent(),
				} );
			  } );
			</script>
		<?php endif; ?>

		<?php echo wp_kses_post( $args['desc2'] ); ?>

		<?php
	}

	/**
	 * Build our option groups for the select box
	 *
	 * @param array        $options       The list of options that should be displayed
	 * @param array|string $value         The selected option
	 * @param bool         $should_output To output or echo the content
	 *
	 * @return string|void
	 *
	 * @since 4.1
	 */
	public function build_options_for_select( $options, $value, $should_output = false ) {
		if ( ! $should_output ) {
			ob_start();
		}

		foreach ( $options as $option => $name ) {
			if ( ! is_array( $name ) ) {
				$selected = '';
				if ( is_array( $value ) ) {
					foreach ( $value as $v ) {
						$selected = selected( $option, $v, false );
						if ( $selected !== '' ) {
							break;
						}
					}
				} else {
					$selected = selected( $option, $value, false );
				}

				echo '<option value="' . esc_attr( $option ) . '" ' . ( strpos( $selected, 'selected' ) !== false ? 'selected="selected"' : '' ) . '>' . esc_html( $name ) . '</option>';
			} else {
				echo '<optgroup label="' . esc_attr( $option ) . '">';
				foreach ( $name as $op_value => $op_label ) {
					$selected = '';
					if ( is_array( $value ) ) {
						foreach ( $value as $v ) {
							$selected = selected( $op_value, $v, false );
							if ( $selected !== '' ) {
								break;
							}
						}
					} else {
						$selected = selected( $op_value, $value, false );
					}

					echo '<option value="' . esc_attr( $op_value ) . '" ' . ( strpos( $selected, 'selected' ) !== false ? 'selected="selected"' : '' ) . '>' . esc_html( $op_label ) . '</option>';
				}
				echo '</optgroup>';
			}
		}

		if ( ! $should_output ) {
			ob_get_clean();
		}
	}

	/**
	 * Rich Editor Callback
	 *
	 * Renders rich editor fields.
	 *
	 * @param array  $args       Arguments passed by the setting
	 *
	 * @since 4.0
	 *
	 * @global float $wp_version The WordPress Version
	 */
	public function rich_editor_callback( $args ) {
		/* get selected value (if any) */
		$value = $this->get_form_value( $args );

		$rows   = $args['size'] ?? 20;
		$class  = $args['inputClass'] ?? '';
		$toggle = ( ! empty( $args['toggle'] ) ) ? $args['toggle'] : false;

		?>

		<?php if ( $toggle !== false ): ?>
			<?php $this->start_toggle_input( $toggle, $value ); ?>
		<?php endif; ?>

		<div class="gform-settings-description gform-kitchen-sink">
			<label for="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]">
				<?php echo wp_kses_post( $args['desc'] ); ?>
			</label>
		</div>

		<?php if ( function_exists( 'wp_editor' ) ): ?>
			<span class="<?php echo esc_attr( 'mt-gfpdf_settings_' . $args['id'] ); ?> mt-gfpdf-merge-tag-selector-container"></span>
			<?php
			wp_editor(
				$value,
				'gfpdf_settings_' . $args['id'],
				apply_filters(
					'gfpdf_rich_editor_settings',
					[
						'textarea_name' => 'gfpdf_settings[' . $args['id'] . ']',
						'textarea_rows' => $rows,
						'editor_height' => $rows * 10, /* estimate row height at 10px */
						'editor_class'  => 'gfpdf_settings_' . $args['id'] . ' ' . $class,
						'autop'         => false,
					]
				)
			);
			?>
		<?php else : ?>
			<textarea rows="<?php echo esc_attr( $rows ); ?>"
					  class="<?php echo esc_attr( 'gfpdf_settings_' . $args['id'] ); ?> large-text <?php echo esc_attr( $class ); ?>"
					  id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
					  name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
			>
				<?php echo esc_textarea( $value ); ?>
			</textarea>
		<?php endif; ?>

		<?php if ( $toggle !== false ): ?>
			<?php $this->end_toggle_input(); ?>
		<?php endif; ?>

		<?php echo wp_kses_post( $args['desc2'] ); ?>

		<?php
	}

	/**
	 * Upload Callback
	 *
	 * Renders upload fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function upload_callback( $args ) {

		/* get selected value (if any) */
		$value                = $this->get_form_value( $args );
		$uploader_title       = $args['uploaderTitle'] ?? __( 'Select Media', 'gravity-pdf' );
		$uploader_button_text = $args['uploaderButtonText'] ?? __( 'Select Media', 'gravity-pdf' );
		$button_text          = $args['buttonText'] ?? __( 'Upload File', 'gravity-pdf' );
		$class                = $args['inputClass'] ?? '';
		$required             = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$size                 = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';

		?>

		<div class="gform-settings-description gform-kitchen-sink">
			<label for="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]">
				<?php echo wp_kses_post( $args['desc'] ); ?>
			</label>
		</div>

		<div class="gfpdf-upload-setting-container">
			<input type="text"
				   class="<?php echo esc_attr( $size . '-text' ); ?> <?php echo esc_attr( 'gfpdf_settings_' . $args['id'] ); ?> <?php echo esc_attr( $class ); ?>"
				   id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
				   name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
				   value="<?php echo esc_attr( $value ); ?>"
			<?php echo esc_attr( $required ); ?>
			/>

			<input type="button"
				   class="gfpdf_settings_upload_button button-secondary"
				   data-uploader-title="<?php echo esc_attr( $uploader_title ); ?>"
				   data-uploader-button-text="<?php echo esc_attr( $uploader_button_text ); ?>"
				   value="<?php echo esc_attr( $button_text ); ?>"
			/>
		</div>

		<?php echo wp_kses_post( $args['desc2'] ); ?>

		<?php
	}


	/**
	 * Color picker Callback
	 *
	 * Renders color picker fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function color_callback( $args ) {

		/* get selected value (if any) */
		$value    = $this->get_form_value( $args );
		$default  = $args['std'] ?? '';
		$class    = $args['inputClass'] ?? '';
		$required = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';

		?>

		<div class="gform-settings-description gform-kitchen-sink">
			<label for="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]">
				<?php echo wp_kses_post( $args['desc'] ); ?>
			</label>
		</div>

		<div>
			<input type="text"
				   class="gfpdf-color-picker <?php echo esc_attr( 'gfpdf_settings_' . $args['id'] ); ?> <?php echo esc_attr( $class ); ?>"
				   id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
				   name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
				   value="<?php echo esc_attr( $value ); ?>"
				   data-default-color="<?php echo esc_attr( $default ); ?>"
					<?php echo esc_attr( $required ); ?>
			/>
		</div>

		<?php
	}

	/**
	 * Add a button callback.
	 *
	 * Renders a button onto the settings field.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function button_callback( $args ) {

		$input_data = ( isset( $args['data'] ) && is_array( $args['data'] ) ) ? $args['data'] : [];
		$class      = $args['inputClass'] ?? '';

		?>

		<div class="gform-settings-description gform-kitchen-sink">
			<?php echo wp_kses_post( $args['desc'] ); ?>
		</div>

		<div id="gfpdf-button-wrapper-<?php echo esc_attr( $args['id'] ); ?>">
			<button id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
					name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>][name]"
					value="<?php echo esc_attr( $args['id'] ); ?>"
					class="button gfpdf-button <?php echo esc_attr( $class ); ?>"
					type="submit"
					<?php foreach ( $input_data as $data_id => $data_value ): ?>
						<?php echo esc_html( 'data-' . $data_id ); ?>="<?php echo esc_attr( $data_value ); ?>";
					<?php endforeach; ?>
			>
			<?php echo esc_html( $args['std'] ); ?>
			</button>
		</div>

		<input type="hidden"
			   name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>][nonce]"
			   value="<?php echo esc_attr( wp_create_nonce( 'gfpdf_settings[' . $args['id'] . ']' ) ); ?>"
		/>

		<?php
	}

	/**
	 * @param $args
	 *
	 * @since 6.0
	 */
	public function toggle_callback( $args ) {
		$value = $this->get_form_value( $args );

		/* Auto-upgrade routine */
		if ( in_array( $value, [ 'Enable' ], true ) ) {
			$value = 'Yes';
		}

		$class = $args['inputClass'] ?? '';
		$name  = $args['name'] ?? '';

		?>
		<div class="gform-settings-field gform-settings-field__toggle <?php echo esc_attr( $class ); ?>">
			<div class="gform-settings-description gform-kitchen-sink"><?php echo wp_kses_post( $args['desc'] ); ?></div>

			<span class="gform-settings-input__container">
				<input type="checkbox" id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]" name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]" value="Yes" <?php echo checked( $value, 'Yes', false ); ?> />
				<label class="gform-field__toggle-container" for="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]">
					<?php if ( ! empty( $name ) ): ?>
						<span class="screen-reader-text"><?php echo esc_html( $name ); ?></span>
					<?php endif; ?>

					<span class="gform-field__toggle-switch"></span>
				</label>
			</span>

			<?php echo wp_kses_post( $args['desc2'] ); ?>
		</div>
		<?php
	}

	/**
	 * Gravity Forms Conditional Logic Callback
	 *
	 * Renders the GF Conditional logic container
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function conditional_logic_callback( $args ) {
		$args['idOverride'] = 'gfpdf_conditional_logic';
		$args['type']       = 'checkbox';

		$this->checkbox_callback( $args );

		?>

		<div id="gfpdf_conditional_logic_container" class="gform-settings-field__conditional-logic" style="display: none">
			<!-- content dynamically created from form_admin.js -->
		</div>

		<?php
	}

	/**
	 * Render a hidden field
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function hidden_callback( $args ) {

		/* get selected value (if any) */
		$value      = $this->get_form_value( $args );
		$class      = $args['inputClass'] ?? '';
		$input_data = ( isset( $args['data'] ) && is_array( $args['data'] ) ) ? $args['data'] : [];

		?>

		<input type="hidden"
			   id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
			   class="<?php echo esc_attr( 'gfpdf_settings_' . $args['id'] ); ?> <?php echo esc_attr( $class ); ?>"
			   name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]"
			   value="<?php echo esc_attr( $value ); ?>"
			   <?php foreach ( $input_data as $data_id => $data_value ): ?>
					<?php echo esc_html( 'data-' . $data_id ); ?>="<?php echo esc_attr( $data_value ); ?>";
			   <?php endforeach; ?>
		/>

		<?php
	}

	/**
	 * Render the custom paper size functionality
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function paper_size_callback( $args ) {

		/* get selected value (if any) */
		$value = $this->get_form_value( $args );

		if ( empty( $value ) ) {
			$value = [ '', '', 'mm' ];
		}

		$placeholder = $args['placeholder'] ?? '';
		$class       = $args['inputClass'] ?? '';
		$size        = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';

		?>

		<div class="gform-settings-description gform-kitchen-sink">
			<label for="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]">
				<?php echo wp_kses_post( $args['desc'] ); ?>
			</label>
		</div>

		<?php esc_html_e( 'Width', 'gravity-pdf' ); ?>
		<input type="number"
			   class="<?php echo esc_attr( $size . '-text' ); ?> <?php echo esc_attr( 'gfpdf_settings_' . $args['id'] ); ?>"
			   id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]_width"
			   min="0"
			   style="width: 6rem"
			   name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>][]"
			   value="<?php echo esc_attr( $value[0] ); ?>"
		/>

		&nbsp;

		<?php esc_html_e( 'Height', 'gravity-pdf' ); ?>
		<input type="number"
			   class="<?php echo esc_attr( $size . '-text' ); ?> <?php echo esc_attr( 'gfpdf_settings_' . $args['id'] ); ?>"
			   id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]_height"
			   min="0"
			   style="width: 6rem"
			   name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>][]"
			   value="<?php echo esc_attr( $value[1] ); ?>"
		/>

		&nbsp;

		<?php
		$measure_value = $value[2] ?? '';
		$measurement   = apply_filters(
			'gfpdf_paper_size_dimensions',
			[
				'millimeters' => esc_html__( 'mm', 'gravity-pdf' ),
				'inches'      => esc_html__( 'inches', 'gravity-pdf' ),
			]
		);

		?>

		<select id="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>]_measurement"
				style="width: 6rem"
				class="<?php echo esc_attr( 'gfpdf_settings_' . $args['id'] ); ?> <?php echo esc_attr( $class ); ?>"
				name="gfpdf_settings[<?php echo esc_attr( $args['id'] ); ?>][]"
				data-placeholder="<?php echo esc_attr( $placeholder ); ?>"
		>

		<?php foreach ( $measurement as $key => $val ): ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $measure_value, $key ); ?>>
				<?php echo esc_html( $val ); ?>
			</option>
		<?php endforeach; ?>

		</select>

		<?php echo wp_kses_post( $args['desc2'] ); ?>

		<?php
	}

	/**
	 * Descriptive text callback.
	 *
	 * Renders descriptive text onto the settings field.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function descriptive_text_callback( $args ) {
		echo wp_kses_post( $args['desc'] );
	}

	/**
	 * Hook Callback
	 *
	 * Adds a do_action() hook in place of the field
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function hook_callback( $args ) {
		do_action( 'gfpdf_' . $args['id'], $args );
	}

	/**
	 * Missing Callback
	 *
	 * If a public function is missing for settings callbacks alert the user.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function missing_callback( $args ) {
		echo wp_kses_post( sprintf( __( 'The callback used for the %s setting is missing.', 'gravity-pdf' ), "<strong>{$args['id']}</strong>" ) );
	}

	/**
	 * Creates jQuery toggle functionality for the current field
	 *
	 * @param String $toggle The text to be used in the toggle
	 * @param String $html   The field HTML
	 * @param String $value  Whether the field currently has a value
	 *
	 * @return String         The modified HTML
	 */
	public function create_toggle_input( $toggle, $html, $value ) {

		$has_value = ( $value !== '' ) ? 1 : 0;

		$toggle_elm = '<label><input class="gfpdf-input-toggle" type="checkbox" value="1" ' . checked( $has_value, 1, false ) . ' /> ' . esc_attr( $toggle ) . '</label>';

		$html = '<div class="gfpdf-toggle-wrapper" ' . ( ( ! $has_value ) ? 'style="display: none;"' : '' ) . '>' .
				$html .
				'</div>';

		$html = $toggle_elm . $html;

		return $html;
	}

	/**
	 * Creates jQuery toggle functionality for the current field
	 *
	 * @param string $toggle The text to be used in the toggle
	 * @param string $value  Whether the field currently has a value
	 *
	 * @return void
	 *
	 * @since 6.4
	 */
	public function start_toggle_input( $toggle, $value ) {
		$has_value = ! empty( $value ) ? 1 : 0;

		?>

		<label>
			<input class="gfpdf-input-toggle"
				   type="checkbox"
				   value="1"
				   <?php checked( $has_value, 1 ); ?>
			/>

			<?php echo esc_html( $toggle ); ?>
		</label>

		<div class="gfpdf-toggle-wrapper" style="<?php echo esc_attr( $has_value === 0 ? 'display: none;' : '' ); ?>">

		<?php
	}

	/**
	 * Close the toggle input container
	 *
	 * @return void
	 *
	 * @since 6.4
	 */
	public function end_toggle_input() {
		?>
		</div>
		<?php
	}
}
