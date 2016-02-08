<?php

/**
 * A public API developers can use to work with Gravity PDF (similar to Gravity Forms GFAPI class)
 *
 * This class is in the public namespace
 *
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

/**
 * An easy-to-use API developers can use to work with Gravity PDF
 *
 * @since 4.0
 */
class GPDFAPI {

	/**
	 * Returns our public logger class which uses Monolog (a PSR-3 compatible logging interface - https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md)
	 *
	 *
	 * Log messages can be added with any of the following:
	 *
	 * $gfpdf->log->addDebug( $message )
	 * $gfpdf->log->addInfo( $message )
	 * $gfpdf->log->addNotice( $message )
	 * $gfpdf->log->addWarning( $message )
	 * $gfpdf->log->addError( $message )
	 * $gfpdf->log->addCritical( $message )
	 * $gfpdf->log->addAlert( $message )
	 * $gfpdf->log->addEmergency( $message )
	 *
	 * When in production Gravity PDF will only log to a file when the Gravity Forms Logging plugin is enabled and Gravity PDF is set to "Log errors only" ($log->addError() or higher) or "Log all messages" ($log->addNotice() or higher)
	 *
	 * @return \Monolog\Logger
	 *
	 * @since 4.0
	 */
	public static function get_log_class() {
		global $gfpdf;

		return $gfpdf->log;
	}

	/**
	 * Returns our public notice queue system to make it easy to display errors and messages to the user.
	 *
	 * Usage:
	 * $notices->add_notice( String $message );
	 * $notices->add_error( String $error );
	 *
	 * This taps into the 'admin_notices' or 'network_admin_notices' WordPress hooks so you need to add your notices before then.
	 *
	 * @return \GFPDF\Helper\Helper_Notices
	 *
	 * @since 4.0
	 */
	public static function get_notice_class() {
		global $gfpdf;

		return $gfpdf->notices;
	}

	/**
	 * Returns our public data class which we use to store important global information related to Gravity PDF
	 *
	 * This uses PHP magic methods __set() and __get() to access and store information.
	 *
	 * Usage:
	 *
	 * $data->title; //returns "Gravity PDF"
	 * $data->title = 'Gravity PDF 4.0'; //sets $data->title to "Gravity PDF 4.0"
	 *
	 * Note: Our __get() magic method returns variables by reference
	 *
	 * @return \GFPDF\Helper\Helper_Data
	 *
	 * @since 4.0
	 */
	public static function get_data_class() {
		global $gfpdf;

		return $gfpdf->data;
	}

	/**
	 * Returns our access layer class for all Gravity PDF Settings (both global and form specific)
	 *
	 * Note: Most relevant methods have been broken our and are avaiable through the GPDFAPI directly (GPDFAPI::get_pdf, GPDFAPI::get_plugin_settings ect)
	 *
	 * @return \GFPDF\Helper\Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	public static function get_options_class() {
		global $gfpdf;

		return $gfpdf->options;
	}

	/**
	 * Returns our miscellaneous methods (or common methods) used throughout the plugin.
	 *
	 * Usage:
	 *
	 * $misc->is_gfpdf_page();
	 *
	 * @return \GFPDF\Helper\Helper_Misc
	 *
	 * @since 4.0
	 */
	public static function get_misc_class() {
		global $gfpdf;

		return $gfpdf->misc;
	}

	/**
	 * Returns our abstracted Gravity Forms API class we use throughout the plugin
	 *
	 * While you could just use the GFAPI directly, some methods in this class have been cache-optimised and are specifically tuned for Gravity PDF.
	 * Note: not all the methods in the GFAPI are implimented.
	 *
	 * Usage:
	 *
	 * $form->get_form( $form_id );
	 *
	 * @return \GFPDF\Helper\Helper_Form
	 *
	 * @since 4.0
	 */
	public static function get_form_class() {
		global $gfpdf;

		return $gfpdf->form;
	}

	/**
	 * Returns the original Model/View/Controller class we initialised in our /src/bootstrap.php file
	 *
	 * This method acts like a faux singleton provider (but none of our classes are static or singletons themselves - hence the 'faux') as you get the originally initialised class back
	 *
	 * This is very useful when you want to remove any filters / actions we set in a controller's add_filters() or add_actions() methods
	 * You can also use to to easily access any public methods in our classes
	 *
	 * Note: This method only returns Controller_ / Model_ / View_  classes. Use the other methods above to access our Helper_ classes
	 *
	 * Usage:
	 *
	 * $class = GPDFAPI::get_mcv_class( 'Controller_PDF' );
	 *
	 * //if we have a class returned
	 * if( $class !== false ) {
	 *     //remove a middleware filter
	 *     remove_filter( 'gfpdf_pdf_middleware', array( $class, 'middle_active' ), 10 );
	 * }
	 *
	 * @param string $class_name The name of one of our MVC classes (no namespace)
	 *
	 * @return object|bool Will return your object if found, otherwise false
	 *
	 * @since 4.0
	 */
	public static function get_mvc_class( $class_name ) {
		global $gfpdf;

		return $gfpdf->singleton->get_class( $class_name );
	}

	/**
	 * Returns a new instance of one of our PDF generating code (model or view)
	 *
	 * @param  string $type Type of class to return. Valid options include 'view' or 'model'
	 *
	 * @return object|WP_Error
	 *
	 * @since  4.0
	 */
	public static function get_pdf_class( $type = 'view' ) {

		if ( $type === 'view' ) {
			return static::get_mvc_class( 'View_PDF');
		}

		if ( $type === 'model' ) {
			return static::get_mvc_class( 'Model_PDF');
		}

		return new WP_Error( 'invalid_type', __( 'The $type parameter is invalid. Only "view" and "model" are accepted', 'gravity-forms-pdf-extended' ) );
	}

	/**
	 * Gets a list of current PDFs setup for a particular Gravity Form
	 *
	 * @param  integer $form_id The Gravity Form ID
	 *
	 * @return array|WP_Error Array of PDF settings or WP_Error
	 *
	 * @since 4.0
	 */
	public static function get_form_pdfs( $form_id ) {
		$options = self::get_options_class();

		return $options->get_form_pdfs( $form_id );
	}

	/**
	 * Gets a specific Gravity Form PDF configuration
	 *
	 * @param  integer $form_id The Gravity Form ID
	 * @param  string  $pdf_id  The PDF ID
	 *
	 * @return array|WP_Error Array of PDF settings or WP_Error
	 * @since 4.0
	 */
	public static function get_pdf( $form_id, $pdf_id ) {
		$options = self::get_options_class();

		return $options->get_pdf( $form_id, $pdf_id );
	}

	/**
	 * Add a new PDF to a Gravity Form
	 *
	 * @param integer $form_id  The Gravity Form ID
	 * @param array   $settings The settings for the PDF
	 *
	 * @return boolean / String The PDF ID on success, false on failure
	 *
	 * @since 4.0
	 */
	public static function add_pdf( $form_id, $settings = array() ) {
		$options = self::get_options_class();

		return $options->add_pdf( $form_id, $settings );
	}

	/**
	 * Updates an existing Gravity Form PDF. Passing an empty $settings array will delete the PDF
	 *
	 * @param  integer $form_id  The Gravity Form ID
	 * @param  string  $pdf_id   The PDF ID
	 * @param  array   $settings The settings for the PDF
	 *
	 * @return boolean           True on success, false on failure
	 *
	 * @since  4.0
	 */
	public static function update_pdf( $form_id, $pdf_id, $settings = array() ) {
		$options = self::get_options_class();

		return $options->update_pdf( $form_id, $pdf_id, $settings );
	}

	/**
	 * Gets a specific Gravity Form PDF configuration
	 *
	 * @param  integer $form_id The Gravity Form ID
	 * @param  string  $pdf_id  The PDF ID
	 *
	 * @return boolean           True on success, false on failure
	 *
	 * @since  4.0
	 */
	public static function delete_pdf( $form_id, $pdf_id ) {
		$options = self::get_options_class();

		return $options->delete_pdf( $form_id, $pdf_id );
	}

	/**
	 * Retrieve an array of the global Gravity PDF settings (this doesn't include individual form configuration details - see GPDFAPI::get_form_pdfs)
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public static function get_plugin_settings() {
		$options = self::get_options_class();

		return $options->get_settings();
	}

	/**
	 * Get an option from the global Gravity PDF settings. If it doesn't exist the $default value will be returned
	 *
	 * @param string $key     The Gravity PDF option key
	 * @param mixed  $default What's returned if the option doesn't exist
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	public static function get_plugin_option( $key, $default = '' ) {
		$options = self::get_options_class();

		return $options->get_option( $key, $default );
	}

	/**
	 * Add a new option to Gravity PDF
	 *
	 * If option already exists a WP_Error is returned
	 * In most cases you'll want to use GPDFAPI::update_plugin_option() instead
	 *
	 * @param string $key The option key to add
	 * @param mixed  $value
	 *
	 * @return boolean|WP_Error
	 *
	 * @since 4.0
	 */
	public static function add_plugin_option( $key, $value ) {
		$options = self::get_options_class();

		/* Check the option doesn't already exist */
		if ( null !== $options->get_option( $key, null ) ) {
			return new WP_Error( 'option_exists', __( 'The option key %s already exists. Use GPDFAPI::update_plugin_option instead', 'gravity-forms-pdf-extended' ) );
		}

		return self::update_plugin_option( $key, $value );
	}

	/**
	 * Updates a Gravity PDF global option. Will create option if it doesn't exist.
	 *
	 * If $value is falsy (determined by empty() ) the option is deleted.
	 *
	 * @param string $key The option key to update
	 * @param mixed  $value
	 *
	 * @return boolean|WP_Error
	 *
	 * @since 4.0
	 */
	public static function update_plugin_option( $key, $value ) {
		$options = self::get_options_class();

		return $options->update_option( $key, $value );
	}

	/**
	 * Delete's a Gravity PDF global option.
	 *
	 * @param string $key The option key to delete
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public static function delete_plugin_option( $key ) {
		$options = self::get_options_class();

		return $options->delete_option( $key );
	}

	/**
	 * When provided the Gravity Form entry ID and PDF ID, this method will correctly generate the PDF, save it to disk,
	 * trigger appropriate actions and return the absolute path to the PDF.
	 *
	 * @param  integer $entry_id The Gravity Form entry ID
	 * @param  string  $pdf_id   The Gravity PDF ID number (the pid number in the URL when viewing a setting in the admin area)
	 *
	 * @return mixed            Return the full path to the PDF, or a WP_Error on failure
	 *
	 * @since 4.0
	 */
	public static function create_pdf( $entry_id, $pdf_id ) {

		$form_class = self::get_form_class();

		/* Get our entry */
		$entry = $form_class->get_entry( $entry_id );

		if ( is_wp_error( $entry ) ) {
			return new WP_Error( 'invalid_entry', 'Make sure to pass in a valid Gravity Forms Entry ID' );
		}

		/* Get our settings */
		$setting = self::get_pdf( $entry['form_id'], $pdf_id );

		if ( is_wp_error( $setting ) ) {
			return new WP_Error( 'invalid_pdf_setting', 'Could not located the PDF Settings. Ensure you pass in a valid PDF ID.' );
		}

		$pdf = self::get_mvc_class( 'Model_PDF' );

		return $pdf->generate_and_save_pdf( $entry, $setting );
	}

	/**
	 * Generates the current entry's HTML product table
	 *
	 * @param  array   $entry  The Gravity Form entry
	 * @param  boolean $return Whether to output or return the HTML
	 *
	 * @return string|void     The product table or null
	 *
	 * @since  4.0
	 */
	public static function product_table( $entry, $return = false ) {
		global $gfpdf;

		$products = new GFPDF\Helper\Fields\Field_Products( new GF_Field(), $entry, $gfpdf->form, $gfpdf->misc );

		if ( $return ) {
			return $products->html();
		}

		echo $products->html();

		return null;
	}

	/**
	 * Generates a likert table
	 *
	 * @param  array   $entry    The Gravity Form entry
	 * @param  integer $field_id The likert field ID
	 * @param  boolean $return   Whether to output or return the HTML
	 *
	 * @return Mixed    The likert table or null
	 *
	 * @since  4.0
	 */
	public static function likert_table( $entry, $field_id, $return = false ) {
		global $gfpdf;

		/* Get our form */
		$form = $gfpdf->form->get_form( $entry['form_id'] );

		/* Check for errors */
		if ( is_wp_error( $form ) ) {
			return false;
		}

		/* Find our field ID, if any */
		foreach ( $form['fields'] as $field ) {

			if ( $field->id == $field_id && $field->inputType == 'likert' ) {

				/* Output our likert */
				$likert = new GFPDF\Helper\Fields\Field_Likert( $field, $entry, $gfpdf->form, $gfpdf->misc );

				if ( $return ) {
					return $likert->html();
				}

				echo $likert->html();
				break;
			}
		}
	}
}
