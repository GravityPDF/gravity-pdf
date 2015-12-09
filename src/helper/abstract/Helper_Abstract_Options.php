<?php

namespace GFPDF\Helper;

use Psr\Log\LoggerInterface;

use WP_Error;

/**
 * Our Gravity PDF Options API
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

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
	 * Holds abstracted functions related to the forms plugin
	 *
	 * @var \GFPDF\Helper\Helper_Form
	 *
	 * @since 4.0
	 */
	protected $form;

	/**
	 * Holds our log class
	 *
	 * @var \Monolog\Logger|LoggerInterface
	 *
	 * @since 4.0
	 */
	protected $log;

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
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 *
	 * @var \GFPDF\Helper\Helper_Notices
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
	private $settings = array();

	/**
	 * Holds the Gravity Form PDF Settings
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	private $form_settings = array();


	/**
	 * Helper_Abstract_Options constructor.
	 *
	 * @param \Monolog\Logger|LoggerInterface    $log
	 * @param \GFPDF\Helper\Helper_Abstract_Form $form
	 * @param \GFPDF\Helper\Helper_Data          $data
	 * @param \GFPDF\Helper\Helper_Misc          $misc
	 * @param \GFPDF\Helper\Helper_Notices       $notices
	 *
	 * @since 4.0
	 */
	public function __construct( LoggerInterface $log, Helper_Abstract_Form $form, Helper_Data $data, Helper_Misc $misc, Helper_Notices $notices ) {

		/* Assign our internal variables */
		$this->log     = $log;
		$this->form    = $form;
		$this->data    = $data;
		$this->misc    = $misc;
		$this->notices = $notices;
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

		/* Register our core santize functions */
		add_filter( 'gfpdf_settings_sanitize', array( $this, 'sanitize_required_field' ), 10, 4 );
		add_filter( 'gfpdf_settings_sanitize', array( $this, 'sanitize_all_fields' ), 10, 4 );

		add_filter( 'gfpdf_settings_sanitize_text', array( $this, 'sanitize_trim_field' ) );
		add_filter( 'gfpdf_settings_sanitize_textarea', array( $this, 'sanitize_trim_field' ) );
		add_filter( 'gfpdf_settings_sanitize_number', array( $this, 'sanitize_number_field' ) );
		add_filter( 'gfpdf_settings_sanitize_paper_size', array( $this, 'sanitize_paper_size' ) );
	}

	/**
	 * Get the plugin's settings from the database
	 *
	 * @since 4.0
	 *
	 * @return  void
	 */
	public function set_plugin_settings() {
		if ( false == get_option( 'gfpdf_settings' ) ) {
			add_option( 'gfpdf_settings' );
		}

		/* assign our settings */
		$this->settings = $this->get_settings();
	}

	/**
	 * Add all settings sections and fields
	 *
	 * @param array $fields Fields that should be registered
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function register_settings( $fields = array() ) {
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
					method_exists( $this, $option['type'] . '_callback' ) ? array(
						$this,
						$option['type'] . '_callback',
					) : array( $this, 'missing_callback' ),
					'gfpdf_settings_' . $tab,
					'gfpdf_settings_' . $tab,
					array(
						'id'                 => isset( $option['id'] )         	    			? $option['id']      				: null,
						'desc'               => ! empty( $option['desc'] )      				? $option['desc']    				: '',
						'desc2'              => ! empty( $option['desc2'] )     				? $option['desc2']   				: '',
						'type'               => isset( $option['type'] )        				? $option['type']    				: null,
						'name'               => isset( $option['name'] )        				? $option['name']    				: null,
						'size'               => isset( $option['size'] )        				? $option['size']    				: null,
						'options'            => isset( $option['options'] )     				? $option['options'] 				: '',
						'std'                => isset( $option['std'] )         				? $option['std']     				: '',
						'min'                => isset( $option['min'] )         				? $option['min']     				: null,
						'max'                => isset( $option['max'] )         				? $option['max']     				: null,
						'step'               => isset( $option['step'] )        				? $option['step']    				: null,
						'chosen'             => isset( $option['chosen'] )      				? $option['chosen']  				: null,
						'class'              => isset( $option['class'] )       				? $option['class']  				: null,
						'inputClass'         => isset( $option['inputClass'] )  				? $option['inputClass']  			: null,
						'placeholder'        => isset( $option['placeholder'] ) 				? $option['placeholder'] 			: null,
						'tooltip'            => isset( $option['tooltip'] )     				? $option['tooltip'] 				: null,
						'multiple'           => isset( $option['multiple'] )    				? $option['multiple'] 				: null,
						'required'           => isset( $option['required'] )    				? $option['required'] 		 		: null,
						'uploaderTitle'      => isset( $option['uploaderTitle'] )    			? $option['uploaderTitle'] 			: null,
						'uploaderButtonText' => isset( $option['uploaderButtonText'] )    		? $option['uploaderButtonText'] 	: null,
						'toggle'			 => isset( $option['toggle'] )						? $option['toggle'] 				: null,
					)
				);
			}
		}

		/* Creates our settings in the options table */
		register_setting( 'gfpdf_settings', 'gfpdf_settings', array( $this, 'settings_sanitize' ) );
	}

	/**
	 * Update a current registered settings
	 *
	 * @param  string $group_id     The top-level group we're updating
	 * @param  string $setting_id   The section group we're updating
	 * @param  string $option_id    The option we are updating
	 * @param  mixed  $option_value The new option value
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
	 * @since 4.0
	 *
	 * @return array GFPDF settings
	 */
	public function get_settings() {
		$tempSettings = get_transient( 'gfpdf_settings_user_data' );
		delete_transient( 'gfpdf_settings_user_data' );

		if ( $tempSettings !== false ) {
			$settings = $tempSettings;
		} else {
			$settings = ( is_array( get_option( 'gfpdf_settings' ) ) ) ? get_option( 'gfpdf_settings' ) : array();
		}

		return apply_filters( 'gfpdf_get_settings', $settings );
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

		/* get GF settings */
		$form_id = ( ! empty( $_GET['id'] ) ) ? (int) rgget( 'id' ) : (int) rgpost( 'id' );
		$pid     = ( ! empty( $_GET['pid'] ) ) ? rgget( 'pid' ) : rgpost( 'gform_pdf_id' );

		/* return early if no ID set */
		if ( ! $form_id ) {
			return array();
		}

		$settings = $this->get_pdf( $form_id, $pid );

		if ( ! is_wp_error( $settings ) ) {
			/* get the selected form settings */
			return $settings;
		}

		$this->log->addError( 'Settings Retreival Error', array(
			'form_id'  => $form_id,
			'pid'      => $pid,
			'WP_Error' => $settings,
		) );

		/* there was an error */

		return array();
	}

	/**
	 * Get Form Settings
	 *
	 * Retrieves all form PDF settings
	 *
	 * @since 4.0
	 *
	 * @param integer $form_id The Gravity Form ID
	 *
	 * @return array|WP_Error An array of GFPDF settings, or WP_Error
	 */
	public function get_form_pdfs( $form_id ) {

		if ( ! isset( $this->data->form_settings ) ) {
			$this->data->form_settings = array();
		}

		$form_id = (int) $form_id;

		if ( 0 === $form_id ) {

			$error = new WP_Error( 'invalid_id', __( 'You must pass in a valid form ID', 'gravity-forms-pdf-extended' ) );
			$this->log->addError( 'Error Getting Settings.', array( 'WP_Error' => $error ) );

			return $error;
		}

		/* If we haven't pulled the form meta data from the database do so now */
		if ( ! isset( $this->data->form_settings[ $form_id ] ) ) {

			$form = $this->form->get_form( $form_id );

			if ( empty( $form ) ) {

				$error = new WP_Error( 'invalid_id', __( 'You must pass in a valid form ID', 'gravity-forms-pdf-extended' ) );
				$this->log->addError( 'Error Getting Settings.', array( 'WP_Error' => $error ) );

				return $error;
			}

			/* Pull the settings from the $form object, if they exist */
			$settings = ( isset( $form['gfpdf_form_settings'] ) ) ? $form['gfpdf_form_settings'] : array();

			/* Store the settings in our data object. Run filter to allow devs to modify the object as needed */
			$this->data->form_settings[ $form_id ] = apply_filters( 'gfpdf_get_form_settings', $settings );

		}

		/* return the form meta data */

		return $this->data->form_settings[ $form_id ];
	}

	/**
	 * Get pdf config
	 *
	 * Looks to see if the specified setting exists, returns default if not
	 *
	 * @since 4.0
	 *
	 * @param integer $form_id The Gravity Form ID
	 *
	 * @param string  $pdf_id  The Gravity Form PDF ID
	 *
	 * @return array|WP_Error
	 */
	public function get_pdf( $form_id, $pdf_id ) {

		$this->log->addNotice( 'Getting Settings.', array(
			'form_id' => $form_id,
			'pdf_id'  => $pdf_id,
		) );

		$gfpdf_options = $this->get_form_pdfs( $form_id );

		if ( ! is_wp_error( $gfpdf_options ) ) {

			/* Get our PDF array if it exists */
			$value = ! empty( $gfpdf_options[ $pdf_id ] ) ? $gfpdf_options[ $pdf_id ] : new WP_Error( 'invalid_pdf_id', __( 'You must pass in a valid PDF ID', 'gravity-forms-pdf-extended' ) );

			if ( ! is_wp_error( $value ) ) {
				$value = apply_filters( 'gfpdf_pdf_config', $value );
				$value = apply_filters( 'gfpdf_pdf_config_' . $form_id, $value );

				return $value;
			}

			/* return WP_Error */

			return $value;
		}

		/* return WP_Error */

		return $gfpdf_options;
	}


	/**
	 * Create a new PDF configuration option for that form
	 *
	 * @param integer $form_id The form ID
	 * @param array   $value   The settings array
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	public function add_pdf( $form_id, $value = array() ) {

		$this->log->addNotice( 'Adding Settings.', array(
			'form_id'      => $form_id,
			'new_settings' => $value,
		) );

		/* First let's grab the current settings */
		$options = $this->get_form_pdfs( $form_id );

		if ( ! is_wp_error( $options ) ) {

			/* check the ID, if any */
			$value['id']     = ( isset( $value['id'] ) ) ? $value['id'] : uniqid();
			$value['active'] = ( isset( $value['active'] ) ) ? $value['active'] : true;

			/* Let's let devs alter that value coming in */
			$value = apply_filters( 'gfpdf_form_add_pdf', $value, $form_id );
			$value = apply_filters( 'gfpdf_form_add_pdf_' . $form_id, $value, $form_id );

			$results = $this->update_pdf( $form_id, $value['id'], $value, true, false );

			if ( $results ) {

				/* return the ID if successful */
				$this->log->addNotice( 'Successfuly Added.', array( 'pdf' => $value ) );

				return $value['id'];
			}

			$this->log->addError( 'Error Saving.', array(
				'error' => $results,
				'pdf'   => $value,
			) );
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
	 * @since    4.0
	 *
	 * @param integer         $form_id   The Gravity Form ID
	 * @param string          $pdf_id    The PDF Setting ID
	 * @param bool|int|string $value     The PDF settings array
	 * @param bool            $update_db Whether we should just update the local PDF settings array, or update the DB as well
	 * @param bool            $filters   Whether we should apply the update filters
	 *
	 * @return bool True if updated, false if not.
	 */
	public function update_pdf( $form_id, $pdf_id, $value = '', $update_db = true, $filters = true ) {

		$this->log->addNotice( 'Updating Settings.', array(
			'form_id'      => $form_id,
			'pdf_id'       => $pdf_id,
			'new_settings' => $value,
		) );

		if ( empty( $value ) || ! is_array( $value ) || sizeof( $value ) == 0 ) {
			/* No value was passed in so we will delete the PDF */
			$remove_option = $this->delete_pdf( $form_id, $pdf_id );

			return $remove_option;
		}

		/* First let's grab the current settings */
		$options = $this->get_form_pdfs( $form_id );

		if ( ! is_wp_error( $options ) ) {

			/* Don't run when adding a new PDF */
			if ( $filters ) {

				$this->log->addNotice( 'Trigger Filters.' );

				/* Let's let devs alter that value coming in */
				$value = apply_filters( 'gfpdf_form_update_pdf', $value, $form_id, $pdf_id );
				$value = apply_filters( 'gfpdf_form_update_pdf_' . $form_id, $value, $form_id, $pdf_id );
			}

			/* Next let's try to update the value */
			$options[ $pdf_id ] = $value;

			/* get the up-to-date form object and merge in the results */
			$form = $this->form->get_form( $form_id );

			/* Update our GFPDF settings */
			$form['gfpdf_form_settings'] = $options;

			$did_update = false;
			if ( $update_db ) {

				$this->log->addNotice( 'Update Form.', array( 'form' => $form ) );

				/* Update the database, if able */
				$did_update = $this->form->update_form( $form );
			}

			if ( ! $update_db || $did_update !== false ) {

				/* If it updated successfully let's update the global variable */
				$this->log->addNotice( 'Save Local Form Cache.' );

				$this->data->form_settings[ $form_id ] = $options;
			}

			/* true if successful, false if failed */

			return $did_update;
		}

		return false;
	}

	/**
	 * Remove an option
	 *
	 * Removes an Gravity PDF setting value in both the db and the global variable.
	 *
	 * @since    4.0
	 *
	 * @param integer $form_id The Gravity Form ID
	 * @param string  $pdf_id  The Gravity Form PDF ID
	 *
	 * @return bool True if updated, false if not.
	 */
	public function delete_pdf( $form_id, $pdf_id ) {

		$this->log->addNotice( 'Deleting Setting.', array(
			'form_id' => $form_id,
			'pdf_id'  => $pdf_id,
		) );

		/* First let's grab the current settings */
		$options = $this->get_form_pdfs( $form_id );

		if ( ! is_wp_error( $options ) ) {

			/* Next let's try to update the value */
			if ( isset( $options[ $pdf_id ] ) ) {

				$this->log->addNotice( 'Found Setting. Now deleting...', array( 'pdf' => $options[ $pdf_id ] ) );

				unset( $options[ $pdf_id ] );
			}

			/* get the form and merge in the results */
			$form = $this->form->get_form( $form_id );

			/* Update our GFPDF settings */
			$form['gfpdf_form_settings'] = $options;

			/* update the database, if able */
			$did_update = $this->form->update_form( $form );

			/* If it updated, let's update the global variable */
			if ( $did_update !== false ) {

				$this->log->addNotice( 'Setting Deleted.', array(
					'form_id' => $form_id,
					'pdf_id'  => $pdf_id,
				) );

				$this->data->form_settings[ $form_id ] = $options;
			}

			/* true if successful, false if failed */

			return $did_update;
		}

		$this->log->addError( 'PDF Delete Failed.', array(
			'form_id' => $form_id,
			'pdf_id'  => $pdf_id,
			'form'    => ( isset( $form ) ) ? $form : '',
		) );

		return false;
	}

	/**
	 * Get an option
	 *
	 * Looks to see if the specified setting exists, returns default if not
	 *
	 * @since 4.0
	 *
	 * @param string $key     The options key to get
	 * @param bool   $default The default option value if the key isn't found
	 *
	 * @return mixed
	 */
	public function get_option( $key = '', $default = false ) {

		$gfpdf_options = $this->settings;

		$value = ! empty( $gfpdf_options[ $key ] ) ? $gfpdf_options[ $key ] : $default;
		$value = apply_filters( 'gfpdf_get_option', $value, $key, $default );

		return apply_filters( 'gfpdf_get_option_' . $key, $value, $key, $default );
	}

	/**
	 * Update an option
	 *
	 * Updates an Gravity PDF setting value in both the db and the global variable.
	 * Warning: Passing in an empty, false or null string value will remove
	 *          the key from the gfpdf_options array.
	 *
	 * @since 4.0
	 *
	 * @param string          $key   The Key to update
	 * @param string|bool|int $value The value to set the key to
	 *
	 * @return boolean True if updated, false if not.
	 */
	public function update_option( $key = '', $value = false ) {

		// If no key, exit
		if ( empty( $key ) ) {
			$this->log->addError( 'Option Update Error', array(
				'key'   => $key,
				'value' => $value,
			) );

			return false;
		}

		if ( empty( $value ) ) {
			$remove_option = $this->delete_option( $key );

			return $remove_option;
		}

		/* First let's grab the current settings */
		$options = get_option( 'gfpdf_settings' );

		/* Let's let devs alter that value coming in */
		$value = apply_filters( 'gfpdf_update_option', $value, $key );
		$value = apply_filters( 'gfpdf_update_option_' . $key, $value, $key );

		/* Next let's try to update the value */
		$options[ $key ] = $value;
		$did_update      = update_option( 'gfpdf_settings', $options );

		/* If it updated, let's update the global variable */
		if ( $did_update ) {
			$this->settings[ $key ] = $value;
		}

		return $did_update;
	}

	/**
	 * Remove an option
	 *
	 * Removes an Gravity PDF setting value in both the db and the global variable.
	 *
	 * @since 4.0
	 *
	 * @param string $key The Key to delete
	 *
	 * @return boolean True if updated, false if not.
	 */
	public function delete_option( $key = '' ) {

		// If no key, exit
		if ( empty( $key ) ) {
			$this->log->addError( 'Option Delete Error' );

			return false;
		}

		// First let's grab the current settings
		$options = get_option( 'gfpdf_settings' );

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
		$capabilities = array();

		/* Add Gravity Forms Capabilities */
		$gf_caps = $this->form->get_capabilities();

		foreach ( $gf_caps as $gf_cap ) {
			$capabilities[ __( 'Gravity Forms Capabilities', 'gravity-forms-pdf-extended' ) ][ $gf_cap ] = apply_filters( 'gfpdf_capability_name', $gf_cap );
		}

		foreach ( $roles as $role ) {
			foreach ( $role['capabilities'] as $cap => $val ) {
				if ( ! isset( $capabilities[ $cap ] ) && ! in_array( $cap, $gf_caps ) ) {
					$capabilities[ __( 'Active WordPress Capabilities', 'gravity-forms-pdf-extended' ) ][ $cap ] = apply_filters( 'gfpdf_capability_name', $cap );
				}
			}
		}

		/* sort alphabetically */
		foreach ( $capabilities as &$val ) {
			ksort( $val );
		}

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
		return apply_filters( 'gfpdf_get_paper_size', array(
			__( 'Common Sizes', 'gravity-forms-pdf-extended' ) => array(
				'A4'        => __( 'A4 (210 x 297mm)', 'gravity-forms-pdf-extended' ),
				'LETTER'    => __( 'Letter (8.5 x 11in)', 'gravity-forms-pdf-extended' ),
				'LEGAL'     => __( 'Legal (8.5 x 14in)', 'gravity-forms-pdf-extended' ),
				'LEDGER'    => __( 'Ledger / Tabloid (11 x 17in)', 'gravity-forms-pdf-extended' ),
				'EXECUTIVE' => __( 'Executive (7 x 10in)', 'gravity-forms-pdf-extended' ),
				'CUSTOM'    => __( 'Custom Paper Size', 'gravity-forms-pdf-extended' ),
			),

			__( '"A" Sizes', 'gravity-forms-pdf-extended' ) => array(
				'A0'  => __( 'A0 (841 x 1189mm)', 'gravity-forms-pdf-extended' ),
				'A1'  => __( 'A1 (594 x 841mm)', 'gravity-forms-pdf-extended' ),
				'A2'  => __( 'A2 (420 x 594mm)', 'gravity-forms-pdf-extended' ),
				'A3'  => __( 'A3 (297 x 420mm)', 'gravity-forms-pdf-extended' ),
				'A5'  => __( 'A5 (210 x 297mm)', 'gravity-forms-pdf-extended' ),
				'A6'  => __( 'A6 (105 x 148mm)', 'gravity-forms-pdf-extended' ),
				'A7'  => __( 'A7 (74 x 105mm)', 'gravity-forms-pdf-extended' ),
				'A8'  => __( 'A8 (52 x 74mm)', 'gravity-forms-pdf-extended' ),
				'A9'  => __( 'A9 (37 x 52mm)', 'gravity-forms-pdf-extended' ),
				'A10' => __( 'A10 (26 x 37mm)', 'gravity-forms-pdf-extended' ),
			),

			__( '"B" Sizes', 'gravity-forms-pdf-extended' ) => array(
				'B0'  => __( 'B0 (1414 x 1000mm)', 'gravity-forms-pdf-extended' ),
				'B1'  => __( 'B1 (1000 x 707mm)', 'gravity-forms-pdf-extended' ),
				'B2'  => __( 'B2 (707 x 500mm)', 'gravity-forms-pdf-extended' ),
				'B3'  => __( 'B3 (500 x 353mm)', 'gravity-forms-pdf-extended' ),
				'B4'  => __( 'B4 (353 x 250mm)', 'gravity-forms-pdf-extended' ),
				'B5'  => __( 'B5 (250 x 176mm)', 'gravity-forms-pdf-extended' ),
				'B6'  => __( 'B6 (176 x 125mm)', 'gravity-forms-pdf-extended' ),
				'B7'  => __( 'B7 (125 x 88mm)', 'gravity-forms-pdf-extended' ),
				'B8'  => __( 'B8 (88 x 62mm)', 'gravity-forms-pdf-extended' ),
				'B9'  => __( 'B9 (62 x 44mm)', 'gravity-forms-pdf-extended' ),
				'B10' => __( 'B10 (44 x 31mm)', 'gravity-forms-pdf-extended' ),
			),

			__( '"C" Sizes', 'gravity-forms-pdf-extended' ) => array(
				'C0'  => __( 'C0 (1297 x 917mm)', 'gravity-forms-pdf-extended' ),
				'C1'  => __( 'C1 (917 x 648mm)', 'gravity-forms-pdf-extended' ),
				'C2'  => __( 'C2 (648 x 458mm)', 'gravity-forms-pdf-extended' ),
				'C3'  => __( 'C3 (458 x 324mm)', 'gravity-forms-pdf-extended' ),
				'C4'  => __( 'C4 (324 x 229mm)', 'gravity-forms-pdf-extended' ),
				'C5'  => __( 'C5 (229 x 162mm)', 'gravity-forms-pdf-extended' ),
				'C6'  => __( 'C6 (162 x 114mm)', 'gravity-forms-pdf-extended' ),
				'C7'  => __( 'C7 (114 x 81mm)', 'gravity-forms-pdf-extended' ),
				'C8'  => __( 'C8 (81 x 57mm)', 'gravity-forms-pdf-extended' ),
				'C9'  => __( 'C9 (57 x 40mm)', 'gravity-forms-pdf-extended' ),
				'C10' => __( 'C10 (40 x 28mm)', 'gravity-forms-pdf-extended' ),
			),

			__( '"RA" and "SRA" Sizes', 'gravity-forms-pdf-extended' ) => array(
				'RA0'  => __( 'RA0 (860 x 1220mm)', 'gravity-forms-pdf-extended' ),
				'RA1'  => __( 'RA1 (610 x 860mm)', 'gravity-forms-pdf-extended' ),
				'RA2'  => __( 'RA2 (430 x 610mm)', 'gravity-forms-pdf-extended' ),
				'RA3'  => __( 'RA3 (305 x 430mm)', 'gravity-forms-pdf-extended' ),
				'RA4'  => __( 'RA4 (215 x 305mm)', 'gravity-forms-pdf-extended' ),
				'SRA0' => __( 'SRA0 (900 x 1280mm)', 'gravity-forms-pdf-extended' ),
				'SRA1' => __( 'SRA1 (640 x 900mm)', 'gravity-forms-pdf-extended' ),
				'SRA2' => __( 'SRA2 (450 x 640mm)', 'gravity-forms-pdf-extended' ),
				'SRA3' => __( 'SRA3 (320 x 450mm)', 'gravity-forms-pdf-extended' ),
				'SRA4' => __( 'SRA4 (225 x 320mm)', 'gravity-forms-pdf-extended' ),
			),
		) );
	}

	/**
	 * Parse our installed PDF template files
	 *
	 * @return array The array of templates
	 *
	 * @since 4.0
	 */
	public function get_templates() {

		$templates = array();
		$legacy    = array();

		$prefix_text = __( 'User Templates: ', 'gravity-forms-pdf-extended' );
		$legacy_text = __( 'Legacy', 'gravity-forms-pdf-extended' );

		/**
		 * Load the user's templates
		 */
		$discovered_user_templates = glob( $this->data->template_location . '*.php' );

		if ( is_multisite() ) {
			$discovered_user_templates = array_merge( $discovered_user_templates, glob( $this->data->multisite_template_location . '*.php' ) );
		}

		foreach ( $discovered_user_templates as $filename ) {

			/* Get the header information to find out what group it's in and if it is compatible with our verison of Gravity PDF */
			$info = $this->get_template_headers( $filename );
			$file = basename( $filename, '.php' );

			if ( ! empty( $info['template'] ) ) {

				/* Check if template compatible */
				if ( ! empty( $info['required_pdf_version'] ) && version_compare( $info['required_pdf_version'], PDF_EXTENDED_VERSION, '>' ) ) {
					$info['template'] .= ' (+ ' . _x( 'needs', 'Required', 'gravity-forms-pdf-extended' ) . ' v' . $info['required_pdf_version'] . ')';
				}

				$templates[ $prefix_text . $info['group'] ][ $file ] = $info['template'];
			} else if ( $file !== 'configuration' && $file !== 'configuration.archive' ) { /* exclude legacy configuration file */
				$legacy[ $file ] = $this->misc->human_readable( $file );
			}
		}

		/**
		 * Load templates included with Gravity PDF
		 * We'll exclude any files overridden by the user
		 */
		foreach ( $this->get_plugin_pdf_templates() as $filename ) {
			$info = $this->get_template_headers( $filename );
			$file = basename( $filename, '.php' );

			/* only add core template if not being overridden by user template */
			if ( ! isset( $templates[ $prefix_text . $info['group'] ][ $file ] ) ) {
				$templates[ $info['group'] ][ $file ] = $info['template'];
			}
		}

		/*
         * Add our legacy array to the end of our templates array
		 */
		if ( sizeof( $legacy ) > 0 ) {
			$templates[ $legacy_text ] = $legacy;
		}

		return apply_filters( 'gfpdf_template_list', $templates );
	}

	/**
	 * An array used to parse the template headers
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_template_header_details() {
		/**
		 * We load in data from the PDF template headers
		 *
		 * @var array
		 */
		return apply_filters( 'gfpdf_template_header_details', array(
			'template'             => __( 'Template Name', 'gravity-forms-pdf-extended' ),
			'version'              => __( 'Version', 'gravity-forms-pdf-extended' ),
			'description'          => __( 'Description', 'gravity-forms-pdf-extended' ),
			'author'               => __( 'Author', 'gravity-forms-pdf-extended' ),
			'group'                => __( 'Group', 'gravity-forms-pdf-extended' ),
			'required_pdf_version' => __( 'Required PDF Version', 'gravity-forms-pdf-extended' ),
		) );
	}

	/**
	 * Gets the template information based on the raw template name
	 *
	 * @param  string $name The template to get information for
	 *
	 * @return array       The template information
	 *
	 * @since 4.0
	 */
	public function get_template_information( $name ) {

		if ( is_file( $this->data->template_location . $name . '.php' ) ) {
			$template          = $this->get_template_headers( $this->data->template_location . $name . '.php' );
			$template['group'] = __( 'User Templates: ', 'gravity-forms-pdf-extended' ) . $template['group'];

			return $template;
		}

		if ( is_file( PDF_PLUGIN_DIR . 'src/templates/' . $name . '.php' ) ) {
			return $this->get_template_headers( PDF_PLUGIN_DIR . 'src/templates/' . $name . '.php' );
		}

		return false;
	}

	/**
	 * Get the current template headers
	 *
	 * @param  string $path The path to the file
	 *
	 * @return array        Details about the file
	 *
	 * @since 4.0
	 */
	public function get_template_headers( $path ) {
		$info = get_file_data( $path, $this->get_template_header_details() );

		/* this is a legacy template */
		if ( empty( $info['template'] ) ) {
			return array( 'group' => 'Legacy' );
		} else {
			return $info;
		}
	}

	/**
	 * Returns an array of the current PDF templates shipped with Gravity PDF
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_plugin_pdf_templates() {
		return glob( PDF_PLUGIN_DIR . 'src/templates/*.php' );
	}


	/**
	 * Parse our installed font files
	 *
	 * @return array The array of fonts
	 *
	 * @since 4.0
	 */
	public function get_installed_fonts() {
		$fonts = array(
			__( 'Unicode', 'gravity-forms-pdf-extended' ) => array(
				'dejavusanscondensed'  => 'Dejavu Sans Condensed',
				'dejavusans'           => 'Dejavu Sans',
				'dejavuserifcondensed' => 'Dejavu Serif Condensed',
				'dejavuserif'          => 'Dejavu Serif',
				'dejavusansmono'       => 'Dejavu Sans Mono',

				'mph2bdamase' => 'MPH 2B Damase',
			),

			__( 'Indic', 'gravity-forms-pdf-extended' ) => array(
				'lohitkannada' => 'Lohit Kannada',
				'pothana2000'  => 'Pothana2000',
			),

			__( 'Arabic', 'gravity-forms-pdf-extended' ) => array(
				'xbriyaz'               => 'XB Riyaz',
				'lateef'                => 'Lateef',
				'kfgqpcuthmantahanaskh' => 'Bahif Uthman Taha',
			),

			__( 'Other', 'gravity-forms-pdf-extended' ) => array(
				'estrangeloedessa' => 'Estrangelo Edessa (Syriac)',
				'kaputaunicode'    => 'Kaputa (Sinhala)',
				'abyssinicasil'    => 'Abyssinica SIL (Ethiopic)',
				'aboriginalsans'   => 'Aboriginal Sans (Cherokee / Canadian)',
				'jomolhari'        => 'Jomolhari (Tibetan)',
				'sundaneseunicode' => 'Sundanese (Sundanese)',
				'taiheritagepro'   => 'Tai Heritage Pro (Tai Viet)',
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
			),
		);

		$fonts = $this->add_custom_fonts( $fonts );

		return apply_filters( 'gfpdf_font_list', $fonts );
	}

	/**
	 * If any custom fonts add them to our font list
	 *
	 * @param array $fonts Current font list
	 *
	 * @since 4.0
	 *
	 * @return array The list of custom fonts installed in a preformatted array
	 */
	public function add_custom_fonts( $fonts = array() ) {

		$custom_fonts = $this->get_custom_fonts();

		if ( sizeof( $custom_fonts ) > 0 ) {

			$user_defined_fonts = array();

			/* Loop through our fonts and assign them to a new array in the appropriate format */
			foreach ( $custom_fonts as $font ) {
				$user_defined_fonts[ $font['shortname'] ] = $font['font_name'];
			}

			/* Merge the new fonts at the beginning of the $fonts array */
			$fonts = $this->misc->array_unshift_assoc( $fonts, __( 'User-Defined Fonts', 'gravity-forms-pdf-extended' ), $user_defined_fonts );
		}

		return $fonts;
	}

	/**
	 * Get a list of the custom fonts installed
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_custom_fonts() {
		$fonts = $this->get_option( 'custom_fonts' );

		if ( is_array( $fonts ) && sizeof( $fonts ) > 0 ) {
			foreach ( $fonts as &$font ) {
				$font['shortname'] = $this->get_font_short_name( $font['font_name'] );
			}

			return $fonts;
		}

		return array();
	}

	/**
	 * Get font shortname we can use an in array
	 *
	 * @param  string $name The font name to convert
	 *
	 * @return string       Shortname of font
	 *
	 * @since  4.0
	 */
	public function get_font_short_name( $name ) {
		return mb_strtolower( str_replace( ' ', '', $name ), 'UTF-8' );
	}

	/**
	 * Get the font's display name from the font key
	 *
	 * @param  string $font_key The font key to search for
	 *
	 * @return mixed (String / Object)           The font display name or WP_Error
	 *
	 * @since 4.0
	 */
	public function get_font_display_name( $font_key ) {

		foreach ( $this->get_installed_fonts() as $groups ) {
			if ( isset( $groups[ $font_key ] ) ) {
				return $groups[ $font_key ];
			}
		}

		return new WP_Error( 'font_not_found', __( 'Could not find Gravity PDF Font' ) );
	}

	/**
	 * Parse our PDF privilages
	 *
	 * @return array The array of privilages
	 *
	 * @since 4.0
	 */
	public function get_privilages() {
		$privilages = array(
			'copy'          => __( 'Copy', 'gravity-forms-pdf-extended' ),
			'print'         => __( 'Print - Low Resolution', 'gravity-forms-pdf-extended' ),
			'print-highres' => __( 'Print - High Resolution', 'gravity-forms-pdf-extended' ),
			'modify'        => __( 'Modify', 'gravity-forms-pdf-extended' ),
			'annot-forms'   => __( 'Annotate', 'gravity-forms-pdf-extended' ),
			'fill-forms'    => __( 'Fill Forms', 'gravity-forms-pdf-extended' ),
			'extract'       => __( 'Extract', 'gravity-forms-pdf-extended' ),
			'assemble'      => __( 'Assemble', 'gravity-forms-pdf-extended' ),
		);

		return apply_filters( 'gfpdf_privilages_list', $privilages );
	}

	/**
	 * Increment the PDF Generation Counter
	 * To decrease load on the database we'll increment by 10 after a rand() function matches
	 * This is less accurate but we only need a rough guesstimation to prompt the user
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function increment_pdf_count() {

		$rand = rand( 1, 10 );

		if ( 10 === $rand ) {
			$total_pdf_count = (int) $this->get_option( 'pdf_count', 0 );
			$total_pdf_count += 10;
			$this->update_option( 'pdf_count', $total_pdf_count );
		}
	}

	/**
	 * Settings Sanitization
	 *
	 * Adds a settings error (for the updated message)
	 * Run on admin options.php page
	 *
	 * @since 4.0
	 *
	 * @param array $input The value inputted in the field
	 *
	 * @return string $input Sanitizied value
	 *
	 */
	public function settings_sanitize( $input = array() ) {

		$gfpdf_options = $this->settings;

		if ( empty( $_POST['_wp_http_referer'] ) || empty( $_POST['option_page'] ) || $_POST['option_page'] != 'gfpdf_settings' ) {
			return $input;
		}

		parse_str( $_POST['_wp_http_referer'], $referrer );

		$all_settings = $this->get_registered_fields();
		$tab          = isset( $referrer['tab'] ) ? $referrer['tab'] : 'general';
		$settings     = ( ! empty( $all_settings[ $tab ] ) ) ? $all_settings[ $tab ] : array();

		/*
         * Get all setting types
		 */
		$tab_len = strlen( $tab );
		foreach ( $all_settings as $id => $s ) {
			/*
             * Check if extra item(s) belongs on page but isn't the existing page
             * Note that this requires the section ID share a similar ID to what is referenced in $tab
			 */
			if ( $tab != $id && $tab == substr( $id, 0, $tab_len ) ) {
				$settings = array_merge( $settings, $s );
			}
		}

		$input = $input ? $input : array();
		$input = apply_filters( 'gfpdf_settings_' . $tab . '_sanitize', $input );

		/**
		 * Loop through the settings whitelist and add any missing required fields to the $input
		 * Prevalant with Select boxes
		 */
		foreach ( $settings as $key => $value ) {
			if ( isset( $value['required'] ) && $value['required'] ) {
				switch ( $value['type'] ) {
					case 'select':
						if ( ! isset( $input[ $key ] ) ) {
							$input[ $key ] = array();
						}
						break;

					default:
						if ( ! isset( $input[ $key ] ) ) {
							$input[ $key ] = '';
						}
						break;
				}
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

			/* General filter */
			$input[ $key ] = apply_filters( 'gfpdf_settings_sanitize', $input[ $key ], $key, $input, $settings[ $key ] );

			if ( $type ) {
				/* Field type specific filter */
				$input[ $key ] = apply_filters( 'gfpdf_settings_sanitize_' . $type, $value, $key, $input, $settings[ $key ] );
			}
		}

		/* check for errors */
		if ( count( get_settings_errors() ) === 0 ) {
			/* Merge our new settings with the existing */
			$output = array_merge( $gfpdf_options, $input );
			add_settings_error( 'gfpdf-notices', '', __( 'Settings updated.', 'gravity-forms-pdf-extended' ), 'updated' );
		} else {
			/* error is thrown. store the user data in a transient so fields are remembered */
			set_transient( 'gfpdf_settings_user_data', array_merge( $gfpdf_options, $input ), 30 );

			/* return nothing */

			return array();
		}

		return $output;
	}


	/**
	 * Sanitize text / textarea fields
	 *
	 * @since 4.0
	 *
	 * @param array $input The field value
	 *
	 * @return string $input Sanitizied value
	 */
	public function sanitize_trim_field( $input ) {
		return trim( $input );
	}

	/**
	 * Sanitize number fields
	 *
	 * @since 4.0
	 *
	 * @param array $input The field value
	 *
	 * @return string $input Sanitizied value
	 */
	public function sanitize_number_field( $input ) {
		return (integer) $input;
	}

	/**
	 * Converts negative numbers to positive numbers
	 *
	 * @param  array $input The unsanitized paper size
	 *
	 * @return array        The sanitized paper size
	 *
	 * @since 4.0
	 */
	public function sanitize_paper_size( $input ) {
		if ( is_array( $input ) && sizeof( $input ) == 3 ) {
			$input[0] = abs( $input[0] );
			$input[1] = abs( $input[1] );
		}

		return $input;
	}

	/**
	 * Sanitize all fields depending on type
	 *
	 * @since 4.0
	 *
	 * @param mixed  $value    The field's user input value
	 * @param string $key      The settings key
	 * @param array  $input    All user fields
	 * @param array  $settings The field settings
	 *
	 * @return string $input Sanitizied value
	 */
	public function sanitize_all_fields( $value, $key, $input, $settings ) {

		if ( ! isset( $settings['type'] ) ) {
			$settings['type'] = '';
		}

		switch ( $settings['type'] ) {
			case 'rich_editor':
			case 'textarea':
				return wp_kses( $value, $this->misc->get_allowed_html_tags() );
				break;

			/* treat as plain text */
			default:
				if ( is_array( $value ) ) {
					array_walk_recursive( $value, function ( &$item ) {
						$item = wp_strip_all_tags( $item );
					} );

					return $value;
				} else {
					return wp_strip_all_tags( $value );
				}

				break;
		}
	}

	/**
	 * Sanitize all required fields
	 *
	 * @since 4.0
	 *
	 * @param mixed  $value    The field's user input value
	 * @param string $key      The settings key
	 * @param array  $input    All user fields
	 * @param array  $settings The field settings
	 *
	 * @return string $input Sanitizied value
	 */
	public function sanitize_required_field( $value, $key, $input, $settings ) {

		if ( isset( $settings['required'] ) && $settings['required'] === true ) {

			switch ( $settings['type'] ) {
				case 'select':
				case 'multicheck':
					$size = count( $value );
					if ( empty( $value ) || sizeof( array_filter( $value ) ) !== $size ) {
						/* throw error */
						add_settings_error( 'gfpdf-notices', $key, __( 'PDF Settings could not be saved. Please enter all required information below.', 'gravity-forms-pdf-extended' ) );
					}
					break;

				case 'paper_size':
					if ( isset( $input['default_pdf_size'] ) && $input['default_pdf_size'] === 'CUSTOM' ) {
						if ( sizeof( array_filter( $value ) ) !== 3 ) {
							/* throw error */
							add_settings_error( 'gfpdf-notices', $key, __( 'PDF Settings could not be saved. Please enter all required information below.', 'gravity-forms-pdf-extended' ) );
						}
					}
					break;

				default:
					if ( strlen( trim( $value ) ) === 0 ) {
						/* throw error */
						add_settings_error( 'gfpdf-notices', $key, __( 'PDF Settings could not be saved. Please enter all required information below.', 'gravity-forms-pdf-extended' ) );
					}
					break;
			}
		}

		return $value;
	}

	/**
	 * Gets the correct option value based on the field type
	 *
	 * @param  array $args The field articles
	 *
	 * @return String       The current value for that particular field
	 *
	 * @since  4.0
	 */
	public function get_form_value( $args = array() ) {

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
		if ( $args['id'] == 'conditionalLogic' && isset( $pdf_form_settings['conditionalLogic'] ) ) {
			$pdf_form_settings['conditionalLogic'] = json_encode( $pdf_form_settings['conditionalLogic'] );
		}

		switch ( $args['type'] ) {
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

				break;

			case 'radio':

				if ( isset( $options[ $args['id'] ] ) && isset( $args['options'][ $options[ $args['id'] ] ] ) ) {
					return $options[ $args['id'] ];

				} elseif ( isset( $pdf_form_settings[ $args['id'] ] ) && isset( $args['options'][ $pdf_form_settings[ $args['id'] ] ] ) ) {
					return $pdf_form_settings[ $args['id'] ];

				} elseif ( isset( $args['std'] ) && isset( $args['std'] ) ) {
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
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function checkbox_callback( $args ) {

		/* get our selected value */
		$checked  = $this->get_form_value( $args );
		$class    = ( isset( $args['inputClass'] ) ) ? esc_attr( $args['inputClass'] ) : '';
		$required = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$id       = ( isset( $args['idOverride'] ) ) ? esc_attr( $args['idOverride'] ) : 'gfpdf_settings[' . esc_attr( $args['id'] ) . ']';

		$html = '<input type="checkbox" id="' . $id . '" class="gfpdf_settings_' . $args['id'] . ' ' . $class . '" name="gfpdf_settings[' . $args['id'] . ']" value="1" ' . $checked . ' ' . $required . ' />';
		$html .= '<label for="' . $id . '"> ' . wp_kses( $args['desc'], $this->misc->get_allowed_html_tags() ) . '</label>';

		if ( isset( $args['tooltip'] ) ) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses( $args['tooltip'], $this->misc->get_allowed_html_tags() ) . '</span>';
		}

		echo $html;
	}

	/**
	 * Multicheck Callback
	 *
	 * Renders multiple checkboxes.
	 *
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function multicheck_callback( $args ) {

		$class      = ( isset( $args['inputClass'] ) ) ? esc_attr( $args['inputClass'] ) : '';
		$required   = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$args['id'] = esc_attr( $args['id'] );

		if ( ! empty( $args['options'] ) ) {
			foreach ( $args['options'] as $key => $option ) {

				/* Set up multi-select option to pass to our form value getter */
				$args['multi-key']    = esc_attr( $key );
				$args['multi-option'] = $option;

				$enabled = $this->get_form_value( $args );

				echo '<input name="gfpdf_settings[' . $args['id'] . '][' . esc_attr( $key ) . ']" id="gfpdf_settings[' . $args['id'] . '][' . esc_attr( $key ) . ']" class="gfpdf_settings_' . $args['id'] . ' ' . $class . '" type="checkbox" value="' . $option . '" ' . checked( $option, $enabled, false ) . ' ' . $required . ' />&nbsp;';
				echo '<label for="gfpdf_settings[' . $args['id'] . '][' . esc_attr( $key ) . ']">' . $option . '</label><br />';
			}

			echo '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> ' . wp_kses( $args['desc'], $this->misc->get_allowed_html_tags() ) . '</label></span>';

			if ( isset( $args['tooltip'] ) ) {
				echo '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses( $args['tooltip'], $this->misc->get_allowed_html_tags() ) . '</span>';
			}
		}
	}

	/**
	 * Radio Callback
	 *
	 * Renders radio boxes.
	 *
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function radio_callback( $args ) {

		/* get selected value (if any) */
		$selected   = $this->get_form_value( $args );
		$required   = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$args['id'] = esc_attr( $args['id'] );
		$html       = '';

		foreach ( $args['options'] as $key => $option ) {

			$checked = false;
			if ( $selected == $key ) {
				$checked = true;
			}

			$html .= '<label for="gfpdf_settings[' . $args['id'] . '][' . esc_attr( $key ) . ']"><input name="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . '" id="gfpdf_settings[' . $args['id'] . '][' . esc_attr( $key ) . ']" type="radio" value="' . esc_attr( $key ) . '" ' . checked( true, $checked, false ) . ' ' . $required . ' />';
			$html .= $option . '</label> &nbsp;&nbsp;';
		}

		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> ' . wp_kses( $args['desc'], $this->misc->get_allowed_html_tags() ) . '</label></span>';

		if ( isset( $args['tooltip'] ) ) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses( $args['tooltip'], $this->misc->get_allowed_html_tags() ) . '</span>';
		}

		echo $html;
	}

	/**
	 * Text Callback
	 *
	 * Renders text fields.
	 *
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function text_callback( $args ) {

		/* get selected value (if any) */
		$value      = $this->get_form_value( $args );
		$class      = ( isset( $args['inputClass'] ) ) ? esc_attr( $args['inputClass'] ) : '';
		$required   = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$args['id'] = esc_attr( $args['id'] );

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? esc_attr( $args['size'] ) : 'regular';
		$html = '<input type="text" class="' . $size . '-text ' . $class . '" id="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . '" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '" ' . $required . ' />';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> ' . wp_kses( $args['desc'], $this->misc->get_allowed_html_tags() ) . '</label></span>';

		if ( isset( $args['tooltip'] ) ) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses( $args['tooltip'], $this->misc->get_allowed_html_tags() ) . '</span>';
		}

		echo $html;
	}

	/**
	 * Number Callback
	 *
	 * Renders number fields.
	 *
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function number_callback( $args ) {

		/* get selected value (if any) */
		$value = $this->get_form_value( $args );

		/* ensure value is not an array */
		if ( is_array( $value ) ) {
			$value = implode( ' ', $value );
		}

		/* check if required */
		$class      = ( isset( $args['inputClass'] ) ) ? esc_attr( $args['inputClass'] ) : '';
		$required   = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$args['id'] = esc_attr( $args['id'] );

		$max  = isset( $args['max'] ) ? (int) $args['max'] : 999999;
		$min  = isset( $args['min'] ) ? (int) $args['min'] : 0;
		$step = isset( $args['step'] ) ? (int) $args['step'] : 1;

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? esc_attr( $args['size'] ) : 'regular';
		$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text gfpdf_settings_' . $args['id'] . ' ' . $class . '" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '" ' . $required . ' /> ' . $args['desc2'];
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> ' . wp_kses( $args['desc'], $this->misc->get_allowed_html_tags() ) . '</label></span>';

		if ( isset( $args['tooltip'] ) ) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses( $args['tooltip'], $this->misc->get_allowed_html_tags() ) . '</span>';
		}

		echo $html;
	}

	/**
	 * Textarea Callback
	 *
	 * Renders textarea fields.
	 *
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function textarea_callback( $args ) {

		/* get selected value (if any) */
		$value      = $this->get_form_value( $args );
		$class      = ( isset( $args['inputClass'] ) ) ? esc_attr( $args['inputClass'] ) : '';
		$required   = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$args['id'] = esc_attr( $args['id'] );

		$html = '<textarea cols="50" rows="5" id="gfpdf_settings[' . $args['id'] . ']" class="large-text gfpdf_settings_' . $args['id'] . ' ' . $class . '" name="gfpdf_settings[' . $args['id'] . ']" ' . $required . '>' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> ' . wp_kses( $args['desc'], $this->misc->get_allowed_html_tags() ) . '</label></span>';

		if ( isset( $args['tooltip'] ) ) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses( $args['tooltip'], $this->misc->get_allowed_html_tags() ) . '</span>';
		}

		/* Check if the field should include a toggle option */
		$toggle = ( ! empty( $args['toggle'] ) ) ? $args['toggle'] : false;

		if ( $toggle !== false ) {
			$html = $this->create_toggle_input( $toggle, $html, $value );
		}

		echo $html;
	}

	/**
	 * Password Callback
	 *
	 * Renders password fields.
	 *
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function password_callback( $args ) {

		/* get selected value (if any) */
		$value      = $this->get_form_value( $args );
		$class      = ( isset( $args['inputClass'] ) ) ? esc_attr( $args['inputClass'] ) : '';
		$required   = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$args['id'] = esc_attr( $args['id'] );

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? esc_attr( $args['size'] ) : 'regular';
		$html = '<input type="password" class="' . $size . '-text ' . $class . '" id="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . '" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '" ' . $required . ' />';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> ' . wp_kses( $args['desc'], $this->misc->get_allowed_html_tags() ) . '</label></span>';

		if ( isset( $args['tooltip'] ) ) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses( $args['tooltip'], $this->misc->get_allowed_html_tags() ) . '</span>';
		}

		echo $html;
	}

	/**
	 * Select Callback
	 *
	 * Renders select fields.
	 *
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function select_callback( $args ) {

		/* get selected value (if any) */
		$value       = $this->get_form_value( $args );
		$placeholder = ( isset( $args['placeholder'] ) ) ? esc_attr( $args['placeholder'] ) : '';
		$chosen      = ( isset( $args['chosen'] ) ) ? 'gfpdf-chosen' : '';
		$class       = ( isset( $args['inputClass'] ) ) ? esc_attr( $args['inputClass'] ) : '';
		$required    = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$args['id']  = esc_attr( $args['id'] );

		$multiple = $multipleExt = '';
		if ( isset( $args['multiple'] ) ) {
			$multiple    = 'multiple';
			$multipleExt = '[]';
		}

		$html = '<select id="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . ' ' . $class . ' ' . $chosen . '" name="gfpdf_settings[' . $args['id'] . ']' . $multipleExt . '" data-placeholder="' . $placeholder . '" ' . $multiple . ' ' . $required . '>';

		foreach ( $args['options'] as $option => $name ) {
			if ( ! is_array( $name ) ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $v ) {
						$selected = selected( $option, $v, false );
						if ( $selected != '' ) {
							break;
						}
					}
				} else {
					$selected = selected( $option, $value, false );
				}

				$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
			} else {
				$html .= '<optgroup label="' . esc_attr( $option ) . '">';
				foreach ( $name as $op_value => $op_label ) {
					$selected = '';
					if ( is_array( $value ) ) {
						foreach ( $value as $v ) {
							$selected = selected( $op_value, $v, false );
							if ( $selected != '' ) {
								break;
							}
						}
					} else {
						$selected = selected( $op_value, $value, false );
					}

					$html .= '<option value="' . esc_attr( $op_value ) . '" ' . $selected . '>' . esc_html( $op_label ) . '</option>';
				}
				$html .= '</optgroup>';
			}
		}

		$html .= '</select>';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> ' . wp_kses( $args['desc'], $this->misc->get_allowed_html_tags() ) . '</label></span>';

		if ( isset( $args['tooltip'] ) ) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses( $args['tooltip'], $this->misc->get_allowed_html_tags() ) . '</span>';
		}

		echo $html;
	}

	/**
	 * Rich Editor Callback
	 *
	 * Renders rich editor fields.
	 *
	 * @since 4.0
	 *
	 * @param array  $args       Arguments passed by the setting
	 *
	 * @global float $wp_version The WordPress Version
	 */
	public function rich_editor_callback( $args ) {
		/* get selected value (if any) */
		$value = $this->get_form_value( $args );

		$rows       = isset( $args['size'] ) ? esc_attr( $args['size'] ) : 20;
		$args['id'] = esc_attr( $args['id'] );
		$class      = ( isset( $args['inputClass'] ) ) ? esc_attr( $args['inputClass'] ) : '';

		if ( function_exists( 'wp_editor' ) ) {
			ob_start();
			echo '<span class="mt-gfpdf_settings_' . $args['id'] . '" style="float:right; position:relative; right: 10px; top: 90px;"></span>';
			wp_editor( stripslashes( $value ), 'gfpdf_settings_' . $args['id'], apply_filters( 'gfpdf_rich_editor_settings', array(
				'textarea_name' => 'gfpdf_settings[' . $args['id'] . ']',
				'textarea_rows' => $rows,
				'editor_class'  => 'gfpdf_settings_' . $args['id'] . ' ' . $class,
				'autop'         => false,
			) ) );
			$html = ob_get_clean();
		} else {
			$html = '<textarea class="large-text" rows="10" class="gfpdf_settings_' . $args['id'] . ' ' . $class . '" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		}

		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> ' . wp_kses( $args['desc'], $this->misc->get_allowed_html_tags() ) . '</label></span>';

		if ( isset( $args['tooltip'] ) ) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses( $args['tooltip'], $this->misc->get_allowed_html_tags() ) . '</span>';
		}

		/* Check if the field should include a toggle option */
		$toggle = ( ! empty( $args['toggle'] ) ) ? $args['toggle'] : false;

		if ( $toggle !== false ) {
			$html = $this->create_toggle_input( $toggle, $html, $value );
		}

		echo $html;
	}

	/**
	 * Upload Callback
	 *
	 * Renders upload fields.
	 *
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function upload_callback( $args ) {

		/* get selected value (if any) */
		$value                = $this->get_form_value( $args );
		$uploader_title       = ( isset( $args['uploaderTitle'] ) ) ? esc_attr( $args['uploaderTitle'] ) : __( 'Select Media', 'gravity-forms-pdf-extended' );
		$uploader_button_text = ( isset( $args['uploaderButtonText'] ) ) ? esc_attr( $args['uploaderButtonText'] ) : __( 'Select Media', 'gravity-forms-pdf-extended' );
		$button_text          = ( isset( $args['buttonText'] ) ) ? esc_attr( $args['buttonText'] ) : __( 'Upload File', 'gravity-forms-pdf-extended' );
		$class                = ( isset( $args['inputClass'] ) ) ? esc_attr( $args['inputClass'] ) : '';
		$required             = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$args['id']           = esc_attr( $args['id'] );
		$size                 = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? esc_attr( $args['size'] ) : 'regular';

		$html = '<input type="text" class="' . $size . '-text gfpdf_settings_' . $args['id'] . ' ' . $class . '" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '" ' . $required . ' />';
		$html .= '<span>&nbsp;<input type="button" class="gfpdf_settings_upload_button button-secondary" value="' . $button_text . '" data-uploader-title="' . $uploader_title . '" data-uploader-button-text="' . $uploader_button_text . '" /></span>';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> ' . wp_kses( $args['desc'], $this->misc->get_allowed_html_tags() ) . '</label></span>';

		if ( isset( $args['tooltip'] ) ) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses( $args['tooltip'], $this->misc->get_allowed_html_tags() ) . '</span>';
		}

		echo $html;
	}


	/**
	 * Color picker Callback
	 *
	 * Renders color picker fields.
	 *
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function color_callback( $args ) {

		/* get selected value (if any) */
		$value      = $this->get_form_value( $args );
		$default    = isset( $args['std'] ) ? esc_attr( $args['std'] ) : '';
		$class      = ( isset( $args['inputClass'] ) ) ? esc_attr( $args['inputClass'] ) : '';
		$required   = ( isset( $args['required'] ) && $args['required'] === true ) ? 'required' : '';
		$args['id'] = esc_attr( $args['id'] );

		$html = '<input type="text" class="gfpdf-color-picker gfpdf_settings_' . $args['id'] . ' ' . $class . '" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" ' . $required . ' />';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> ' . wp_kses( $args['desc'], $this->misc->get_allowed_html_tags() ) . '</label></span>';

		if ( isset( $args['tooltip'] ) ) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses( $args['tooltip'], $this->misc->get_allowed_html_tags() ) . '</span>';
		}

		echo $html;
	}

	/**
	 * Add a button callback.
	 *
	 * Renders a button onto the settings field.
	 *
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function button_callback( $args ) {

		$nonce = wp_create_nonce( 'gfpdf_settings[' . $args['id'] . ']' );
		$class = ( isset( $args['inputClass'] ) ) ? esc_attr( $args['inputClass'] ) : '';

		$html = '<button id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . '][name]" value="' . $args['id'] . '" class="button gfpdf-button ' . $class . '" type="submit">' . esc_html( $args['std'] ) . '</button>';
		$html .= '<span class="gf_settings_description">' . wp_kses( $args['desc'], $this->misc->get_allowed_html_tags() ) . '</span>';
		$html .= '<input type="hidden" name="gfpdf_settings[' . $args['id'] . '][nonce]" value="' . $nonce . '" />';

		if ( isset( $args['tooltip'] ) ) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses( $args['tooltip'], $this->misc->get_allowed_html_tags() ) . '</span>';
		}

		echo $html;
	}

	/**
	 * Gravity Forms Conditional Logic Callback
	 *
	 * Renders the GF Conditional logic container
	 *
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function conditional_logic_callback( $args ) {
		$args['idOverride'] = 'gfpdf_conditional_logic';
		$args['type']       = 'checkbox';

		$this->checkbox_callback( $args );

		$html = '<div id="gfpdf_conditional_logic_container" class="gfpdf_conditional_logic">
			<!-- content dynamically created from form_admin.js -->
		</div>';

		echo $html;
	}

	/**
	 * Render a hidden field
	 *
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function hidden_callback( $args ) {

		/* get selected value (if any) */
		$value      = $this->get_form_value( $args );
		$class      = ( isset( $args['inputClass'] ) ) ? esc_attr( $args['inputClass'] ) : '';
		$args['id'] = esc_attr( $args['id'] );

		$html = '<input type="hidden" class="' . $class . '" id="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . '" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '" />';

		echo $html;
	}

	/**
	 * Render the custom paper size functionality
	 *
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function paper_size_callback( $args ) {

		/* get selected value (if any) */
		$value = $this->get_form_value( $args );

		if ( empty( $value ) ) {
			$value = array( '', '', 'mm' );
		}

		$placeholder = ( isset( $args['placeholder'] ) ) ? esc_attr( $args['placeholder'] ) : '';
		$chosen      = ( isset( $args['chosen'] ) ) ? 'gfpdf-chosen' : '';
		$class       = ( isset( $args['inputClass'] ) ) ? esc_attr( $args['inputClass'] ) : '';
		$size        = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? esc_attr( $args['size'] ) : 'regular';

		$html = '<input type="number" class="' . $size . '-text gfpdf_settings_' . $args['id'] . '" id="gfpdf_settings[' . $args['id'] . ']_width" min="1" name="gfpdf_settings[' . $args['id'] . '][]" value="' . esc_attr( stripslashes( $value[0] ) ) . '" required /> ' . __( 'Width', 'gravity-forms-pdf-extended' );
		$html .= ' <input type="number" class="' . $size . '-text gfpdf_settings_' . $args['id'] . '" id="gfpdf_settings[' . $args['id'] . ']_height" min="1" name="gfpdf_settings[' . $args['id'] . '][]" value="' . esc_attr( stripslashes( $value[1] ) ) . '" required /> ' . __( 'Height', 'gravity-forms-pdf-extended' );

		$measurement = apply_filters( 'gfpdf_paper_size_dimensions', array(
			'millimeters' => __( 'mm', 'gravity-forms-pdf-extended' ),
			'inches'      => __( 'inches', 'gravity-forms-pdf-extended' ),
		) );

		$html .= '&nbsp; — &nbsp; <select id="gfpdf_settings[' . $args['id'] . ']_measurement" style="width: 75px" class="gfpdf_settings_' . $args['id'] . ' ' . $class . ' ' . $chosen . '" name="gfpdf_settings[' . $args['id'] . '][]" data-placeholder="' . $placeholder . '">';

		$measure_value = esc_attr( stripslashes( $value[2] ) );
		foreach ( $measurement as $key => $val ) {
			$selected = ( $measure_value === $key ) ? 'selected="selected"' : '';
			$html .= '<option value="' . $key . '" ' . $selected . '>' . $val . '</option>';
		}

		$html .= '</select> ';

		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . esc_attr( $args['id'] ) . ']"> ' . wp_kses( $args['desc'], $this->misc->get_allowed_html_tags() ) . '</label></span>';

		echo $html;
	}

	/**
	 * Descriptive text callback.
	 *
	 * Renders descriptive text onto the settings field.
	 *
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function descriptive_text_callback( $args ) {
		echo wp_kses( $args['desc'], $this->misc->get_allowed_html_tags() );
	}

	/**
	 * Hook Callback
	 *
	 * Adds a do_action() hook in place of the field
	 *
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function hook_callback( $args ) {
		do_action( 'gfpdf_' . $args['id'], $args );
	}

	/**
	 * Missing Callback
	 *
	 * If a public function is missing for settings callbacks alert the user.
	 *
	 * @since 4.0
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function missing_callback( $args ) {
		printf( __( 'The callback used for the <strong>%s</strong> setting is missing.', 'gravity-forms-pdf-extended' ), $args['id'] );
	}

	/**
	 * Creates jQuery toggle functionality for the current fiel
	 *
	 * @param  String $toggle The text to be used in the toggle
	 * @param  String $html   The field HTML
	 * @param  String $value  Whether the field currently has a value
	 *
	 * @return String         The modified HTML
	 */
	public function create_toggle_input( $toggle, $html, $value ) {

		$has_value       = ( strlen( $value ) > 0 ) ? 1 : 0;
		$current_display = ( ! $has_value ) ? 'style="display: none;"' : '';

		$toggle_elm = '<label><input class="gfpdf-input-toggle" type="checkbox" value="1" ' . checked( $has_value, 1, false ) . ' /> ' . esc_attr( $toggle ) . '</label>';

		$html = '<div class="gfpdf-toggle-wrapper" ' . $current_display . '>' .
		        $html .
		        '</div>';

		$html = $toggle_elm . $html;

		return $html;
	}
}
