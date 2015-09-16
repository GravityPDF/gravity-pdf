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
	 * @return Object Monolog\Logger
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
	 * @return Object Helper_Notices
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
	 * @return Object Helper_Data
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
	 * @return Object Helper_Options_Fields (extends Helper_Options)
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
	 * @return Object Helper_Misc
	 */
	public static function get_misc_class() {
		global $gfpdf;
		return $gfpdf->misc;
	}

	/**
	 * Gets a list of current PDFs setup for a particular Gravity Form
	 * @param  Integer $form_id The Gravity Form ID
	 * @return Array / WP_Error Array of PDF settings or WP_Error
	 * @since 4.0
	 */
	public static function get_form_pdfs( $form_id ) {
		$options = self::get_options_class();
		return $options->get_form_pdfs( $form_id );
	}

	/**
	 * Gets a specific Gravity Form PDF configuration
	 * @param  Integer $form_id The Gravity Form ID
	 * @param  String $pdf_id   The PDF ID
	 * @return Array / WP_Error Array of PDF settings or WP_Error
	 * @since 4.0
	 */
	public static function get_pdf( $form_id, $pdf_id ) {
		$options = self::get_options_class();
		return $options->get_pdf( $form_id, $pdf_id );
	}

	/**
	 * Add a new PDF to a Gravity Form
	 * @param Integer $form_id  The Gravity Form ID
	 * @param Array  $settings  The settings for the PDF
	 * @return Boolean / String The PDF ID on success, false on failure
	 */
	public static function add_pdf( $form_id, $settings = array() ) {
		$options = self::get_options_class();
		return $options->add_pdf( $form_id, $settings );
	}

	/**
	 * Updates an existing Gravity Form PDF. Passing an empty $settings array will delete the PDF
	 * @param  Integer $form_id  The Gravity Form ID
	 * @param  String $pdf_id    The PDF ID
	 * @param  Array  $settings  The settings for the PDF
	 * @return Boolean           True on success, false on failure
	 * @since  4.0
	 */
	public static function update_pdf( $form_id, $pdf_id, $settings = array() ) {
		$options = self::get_options_class();
		return $options->update_pdf( $form_id, $pdf_id, $settings );
	}

	/**
	 * Gets a specific Gravity Form PDF configuration
	 * @param  Integer $form_id The Gravity Form ID
	 * @param  String $pdf_id   The PDF ID
	 * @return Boolean           True on success, false on failure
	 * @since  4.0
	 */
	public static function delete_pdf( $form_id, $pdf_id ) {
		$options = self::get_options_class();
		return $options->delete_pdf( $form_id, $pdf_id );
	}

	/**
	 * Retrieve an array of the global Gravity PDF settings (this doesn't include individual form configuration details - see GPDFAPI::get_form_pdfs)
	 * @return Array
	 * @since 4.0
	 */
	public static function get_plugin_settings() {
		$options = self::get_options_class();
		return $options->get_settings();
	}

	/**
	 * Get an option from the global Gravity PDF settings. If it doesn't exist the $default value will be returned
	 * @param  String $key     The Gravity PDF option key
	 * @param  Mixed $default  What's returned if the option doesn't exist
	 * @return Mixed
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
	 * @param String $key   The option key to add
	 * @param Mixed $value
	 * @return Boolean / WP_Error
	 * @since 4.0
	 */
	public static function add_plugin_option( $key, $value ) {
		$options = self::get_options_class();

		/* Check the option doesn't already exist */
		if( null !== $options->get_option( $key, null ) ) {
			return new WP_Error( 'option_exists', __( 'The option key %s already exists. Use GPDFAPI::update_plugin_option instead', 'gravitypdf') );
		}

		return self::update_plugin_option( $key, $value );
	}

	/**
	 * Updates a Gravity PDF global option. Will create option if it doesn't exist.
	 *
	 * If $value is falsy (determined by empty() ) the option is deleted.
	 *
	 * @param String $key   The option key to update
	 * @param Mixed $value
	 * @return Boolean / WP_Error
	 * @since 4.0
	 */
	public static function update_plugin_option( $key, $value ) {
		$options = self::get_options_class();
		return $options->update_option( $key, $value );
	}

	/**
	 * Delete's a Gravity PDF global option.
	 *
	 * @param String $key   The option key to delete
	 * @return Boolean
	 * @since 4.0
	 */
	public static function delete_plugin_option( $key ) {
		$options = self::get_options_class();
		return $options->delete_option( $key );
	}
}
