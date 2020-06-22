<?php

/*
 * A public API developers can use to work with Gravity PDF (similar to Gravity Forms GFAPI class)
 *
 * This class is in the public namespace
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

/**
 * An easy-to-use API developers can use to work with Gravity PDF
 *
 * See https://gravitypdf.com/documentation/v5/developer-api-whats-it-for/ for more information about this API
 *
 * @since 4.0
 */
final class GPDFAPI {

	/**
	 * Returns our public logger class which uses Monolog (a PSR-3 compatible logging interface - https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md)
	 *
	 * Log messages can be added with any of the following:
	 *
	 * $gfpdf->log->debug( $message, [$parameters = array()] )
	 * $gfpdf->log->info( $message, [$parameters = array()] )
	 * $gfpdf->log->notice( $message, [$parameters = array()] )
	 * $gfpdf->log->warning( $message, [$parameters = array()] )
	 * $gfpdf->log->error( $message, [$parameters = array()] )
	 * $gfpdf->log->critical( $message, [$parameters = array()] )
	 * $gfpdf->log->alert( $message, [$parameters = array()] )
	 * $gfpdf->log->emergency( $message, [$parameters = array()] )
	 *
	 * When in production Gravity PDF will only log to a file when the Gravity Forms Logging plugin is enabled and Gravity PDF is set to "Log errors only" ($log->addError() or higher) or "Log all messages" ($log->addNotice() or higher)
	 *
	 * See https://gravitypdf.com/documentation/v5/api_get_log_class/ for more information about this method
	 *
	 * @return \Psr\Log\LoggerInterface
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
	 * See https://gravitypdf.com/documentation/v5/api_get_notice_class/ for more information about this method
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
	 * See https://gravitypdf.com/documentation/v5/get_data_class/ for more information about this method
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
	 * See https://gravitypdf.com/documentation/v5/api_get_options_class/ for more information about this method
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
	 * See https://gravitypdf.com/documentation/v5/api_get_misc_class/ for more information about this method
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
	 * Returns our templates methods used throughout the plugin.
	 *
	 * Usage:
	 *
	 * $templates->get_all_templates();
	 *
	 * See @TODO https://gravitypdf.com/documentation/v5/api_get_templates_class/ for more information about this method
	 *
	 * @return \GFPDF\Helper\Helper_Templates
	 *
	 * @since 4.1
	 */
	public static function get_templates_class() {
		global $gfpdf;

		return $gfpdf->templates;
	}

	/**
	 * Returns our abstracted Gravity Forms API class we use throughout the plugin
	 *
	 * While you could just use the GFAPI directly, some methods in this class have been cache-optimised and are specifically tuned for Gravity PDF.
	 * Note: not all the methods in the GFAPI are implimented.
	 *
	 * Usage:
	 *
	 * $gform->get_form( $form_id );
	 *
	 * See https://gravitypdf.com/documentation/v5/api_get_form_class/ for more information about this method
	 *
	 * @return \GFPDF\Helper\Helper_Form
	 *
	 * @since 4.0
	 */
	public static function get_form_class() {
		global $gfpdf;

		return $gfpdf->gform;
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
	 * See https://gravitypdf.com/documentation/v5/api_get_mvc_class/ for more information about this method
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
			return static::get_mvc_class( 'View_PDF' );
		}

		if ( $type === 'model' ) {
			return static::get_mvc_class( 'Model_PDF' );
		}

		return new WP_Error( 'invalid_type', esc_html__( 'The $type parameter is invalid. Only "view" and "model" are accepted', 'gravity-forms-pdf-extended' ) );
	}

	/**
	 * Gets a list of current PDFs setup for a particular Gravity Form
	 *
	 * See https://gravitypdf.com/documentation/v5/api_get_form_pdfs/ for more information about this method
	 *
	 * @param  integer $form_id The Gravity Form ID
	 *
	 * @return array|WP_Error Array of PDF settings or WP_Error
	 *
	 * @since 4.0
	 */
	public static function get_form_pdfs( $form_id ) {
		$options = static::get_options_class();

		return $options->get_form_pdfs( $form_id );
	}

	/**
	 * Gets a specific Gravity Form PDF configuration
	 *
	 * See https://gravitypdf.com/documentation/v5/api_get_pdf/ for more information about this method
	 *
	 * @param  integer $form_id The Gravity Form ID
	 * @param  string  $pdf_id  The PDF ID
	 *
	 * @return array|WP_Error Array of PDF settings or WP_Error
	 * @since 4.0
	 */
	public static function get_pdf( $form_id, $pdf_id ) {
		$options = static::get_options_class();

		return $options->get_pdf( $form_id, $pdf_id );
	}

	/**
	 * Add a new PDF to a Gravity Form
	 *
	 * See https://gravitypdf.com/documentation/v5/api_add_pdf/ for more information about this method
	 *
	 * @param integer $form_id  The Gravity Form ID
	 * @param array   $settings The settings for the PDF
	 *
	 * @return boolean / String The PDF ID on success, false on failure
	 *
	 * @since 4.0
	 */
	public static function add_pdf( $form_id, $settings = [] ) {
		$options = static::get_options_class();

		return $options->add_pdf( $form_id, $settings );
	}

	/**
	 * Updates an existing Gravity Form PDF. Passing an empty $settings array will delete the PDF
	 *
	 * See https://gravitypdf.com/documentation/v5/api_update_pdf/ for more information about this method
	 *
	 * @param  integer $form_id  The Gravity Form ID
	 * @param  string  $pdf_id   The PDF ID
	 * @param  array   $settings The settings for the PDF
	 *
	 * @return boolean           True on success, false on failure
	 *
	 * @since  4.0
	 */
	public static function update_pdf( $form_id, $pdf_id, $settings = [] ) {
		$options = static::get_options_class();

		return $options->update_pdf( $form_id, $pdf_id, $settings );
	}

	/**
	 * Deletes a specific Gravity Form PDF configuration
	 *
	 * See https://gravitypdf.com/documentation/v5/api_delete_pdf/ for more information about this method
	 *
	 * @param  integer $form_id The Gravity Form ID
	 * @param  string  $pdf_id  The PDF ID
	 *
	 * @return boolean          True on success, false on failure
	 *
	 * @since  4.0
	 */
	public static function delete_pdf( $form_id, $pdf_id ) {
		$options = static::get_options_class();

		return $options->delete_pdf( $form_id, $pdf_id );
	}

	/**
	 * Retrieve an array of the global Gravity PDF settings (this doesn't include individual form configuration details - see GPDFAPI::get_form_pdfs)
	 *
	 * See https://gravitypdf.com/documentation/v5/api_get_plugin_settings/ for more information about this method
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public static function get_plugin_settings() {
		$options = static::get_options_class();

		return $options->get_settings();
	}

	/**
	 * Get an option from the global Gravity PDF settings. If it doesn't exist the $default value will be returned
	 *
	 * See https://gravitypdf.com/documentation/v5/api_get_plugin_option/ for more information about this method
	 *
	 * @param string $key     The Gravity PDF option key
	 * @param mixed  $default What's returned if the option doesn't exist
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	public static function get_plugin_option( $key, $default = '' ) {
		$options = static::get_options_class();

		return $options->get_option( $key, $default );
	}

	/**
	 * Add a new Global option to Gravity PDF
	 *
	 * If option already exists a WP_Error is returned
	 * In most cases you'll want to use GPDFAPI::update_plugin_option() instead
	 *
	 * See https://gravitypdf.com/documentation/v5/api_add_plugin_option/ for more information about this method
	 *
	 * @param string $key The option key to add
	 * @param mixed  $value
	 *
	 * @return boolean|WP_Error
	 *
	 * @since 4.0
	 */
	public static function add_plugin_option( $key, $value ) {
		$options = static::get_options_class();

		/* Check the option doesn't already exist */
		if ( null !== $options->get_option( $key, null ) ) {
			return new WP_Error( 'option_exists', esc_html__( 'The option key %s already exists. Use GPDFAPI::update_plugin_option instead', 'gravity-forms-pdf-extended' ) );
		}

		return static::update_plugin_option( $key, $value );
	}

	/**
	 * Updates a Gravity PDF global option. Will create option if it doesn't exist.
	 *
	 * If $value is falsy (determined by empty() ) the option is deleted.
	 *
	 * See https://gravitypdf.com/documentation/v5/api_update_plugin_option/ for more information about this method
	 *
	 * @param string $key The option key to update
	 * @param mixed  $value
	 *
	 * @return boolean|WP_Error
	 *
	 * @since 4.0
	 */
	public static function update_plugin_option( $key, $value ) {
		$options = static::get_options_class();

		return $options->update_option( $key, $value );
	}

	/**
	 * Delete's a Gravity PDF global option.
	 *
	 * See https://gravitypdf.com/documentation/v5/api_delete_plugin_option/ for more information about this method
	 *
	 * @param string $key The option key to delete
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public static function delete_plugin_option( $key ) {
		$options = static::get_options_class();

		return $options->delete_option( $key );
	}

	/**
	 * When provided the Gravity Form entry ID and PDF ID, this method will correctly generate the PDF, save it to disk,
	 * trigger appropriate actions and return the absolute path to the PDF.
	 *
	 * See https://gravitypdf.com/documentation/v5/api_create_pdf/ for more information about this method
	 *
	 * @param  integer $entry_id The Gravity Form entry ID
	 * @param  string  $pdf_id   The Gravity PDF ID number (the pid number in the URL when viewing a setting in the admin area)
	 *
	 * @return mixed            Return the full path to the PDF, or a WP_Error on failure
	 *
	 * @since 4.0
	 */
	public static function create_pdf( $entry_id, $pdf_id ) {

		$form_class = static::get_form_class();

		/* Get our entry */
		$entry = $form_class->get_entry( $entry_id );

		if ( is_wp_error( $entry ) ) {
			return new WP_Error( 'invalid_entry', 'Make sure to pass in a valid Gravity Forms Entry ID' );
		}

		/* Get our settings */
		$setting = static::get_pdf( $entry['form_id'], $pdf_id );

		if ( is_wp_error( $setting ) ) {
			return new WP_Error( 'invalid_pdf_setting', 'Could not located the PDF Settings. Ensure you pass in a valid PDF ID.' );
		}

		$pdf  = static::get_mvc_class( 'Model_PDF' );
		$form = $form_class->get_form( $entry['form_id'] );

		do_action( 'gfpdf_pre_generate_and_save_pdf', $form, $entry, $setting );
		$filename = $pdf->generate_and_save_pdf( $entry, $setting );
		do_action( 'gfpdf_post_generate_and_save_pdf', $form, $entry, $setting );

		return $filename;
	}

	/**
	 * Generates the current entry's HTML product table
	 *
	 * See https://gravitypdf.com/documentation/v5/api_product_table/ for more information about this method
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

		$products = new GFPDF\Helper\Fields\Field_Products( new GF_Field(), $entry, $gfpdf->gform, $gfpdf->misc );

		if ( ! $products->is_empty() ) {

			if ( $return ) {
				return $products->html();
			}

			echo $products->html();
		}

		return null;
	}

	/**
	 * Generates a likert table
	 *
	 * See https://gravitypdf.com/documentation/v5/likert_table/ for more information about this method
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
		$form = $gfpdf->gform->get_form( $entry['form_id'] );

		/* Check for errors */
		if ( is_wp_error( $form ) ) {
			return null;
		}

		/* Find our field ID, if any */
		foreach ( $form['fields'] as $field ) {

			/* phpcs:disable */
			if ( $field->id == $field_id && $field->inputType == 'likert' ) {
				/* phpcs:enable */

				/* Output our likert */
				$likert = new GFPDF\Helper\Fields\Field_Likert( $field, $entry, $gfpdf->gform, $gfpdf->misc );

				if ( $return ) {
					return $likert->html();
				}

				echo $likert->html();
				break;
			}
		}

		return null;
	}

	/**
	 * Returns an array of all installed fonts
	 *
	 * @return array
	 *
	 * @since 4.3
	 */
	public static function get_pdf_fonts() {
		$options = static::get_options_class();

		return $options->get_installed_fonts();
	}

	/**
	 * Installs a PDF font on the file system
	 *
	 * See https://gravitypdf.com/documentation/v5/api_add_pdf_font/ for more information about this method
	 *
	 * @param array $font The font information to add.
	 *
	 * This array needs to be in the following format:
	 *
	 * Array (
	 *   'font_name'   => 'Lato',
	 *   'regular'     => '/full/path/to/font/Lato-Regular.ttf',
	 *   'italics'     => '/full/path/to/font/Lato-Italic.ttf',
	 *   'bold'        => '/full/path/to/font/Lato-Bold.ttf',
	 *   'bolditalics' => '/full/path/to/font/Lato-BoldItalic.ttf',
	 * )
	 *
	 * Only the 'font_name' and 'regular' keys are required.
	 * All fonts should be referenced with the full server path.
	 * Currently, only .ttf fonts are supported.
	 * The font name can only contain alphanumeric characters, or a space
	 *
	 * @return bool|WP_Error
	 *
	 * @since 4.1
	 */
	public static function add_pdf_font( $font ) {
		$settings = GPDFAPI::get_mvc_class( 'Model_Settings' );

		if ( ! isset( $font['font_name'] ) || ! $settings->is_font_name_valid( $font['font_name'] ) ) {
			return new WP_Error( 'invalid_font_name', 'Font name is not valid. Alphanumeric characters and spaces only.' );
		}

		if ( ! $settings->is_font_name_unique( $font['font_name'] ) ) {
			return new WP_Error( 'font_name_not_unique', 'A font with the same name already exists.' );
		}

		$results = $settings->install_fonts( $font );

		if ( isset( $results['errors'] ) ) {
			return new WP_Error( 'font_installation_error', implode( "\n\n", $results['errors'] ) );
		}

		return true;
	}

	/**
	 * Deletes one of the v4 fonts that is installed
	 *
	 * See https://gravitypdf.com/documentation/v5/delete_pdf_font/ for more information about this method
	 *
	 * @param string $font_name The font that should be deleted
	 *
	 * @return bool|WP_Error
	 *
	 * @since 4.1
	 */
	public static function delete_pdf_font( $font_name ) {
		$settings = GPDFAPI::get_mvc_class( 'Model_Settings' );
		$options  = GPDFAPI::get_options_class();
		$misc     = GPDFAPI::get_misc_class();
		$data     = GPDFAPI::get_data_class();

		$fonts   = $options->get_option( 'custom_fonts' );
		$font_id = $settings->get_font_id_by_name( $font_name );

		if ( ! isset( $fonts[ $font_id ] ) ) {
			return new WP_Error( 'font_not_installed', 'Font not installed.' );
		}

		/* Remove the font files */
		if ( ! $settings->remove_font_file( $fonts[ $font_id ] ) ) {
			return new WP_Error( 'font_delete_failure', 'There was a problem deleting the font files.' );
		}

		/* Cleanup our mPDF directory to prevent caching issues with mPDF */
		$misc->cleanup_dir( $data->mpdf_tmp_location );

		/* Update the database */
		unset( $fonts[ $font_id ] );
		if ( ! $options->update_option( 'custom_fonts', $fonts ) ) {
			return new WP_Error( 'font_delete_db_failure', 'There was a problem deleting the font from the database.' );
		}

		return true;
	}

	/**
	 * Return the $form_data array used in custom PDF templates
	 *
	 * @param int $entry_id
	 *
	 * @return array|WP_Error
	 *
	 * @since 4.4
	 */
	public static function get_form_data( $entry_id ) {
		$gform     = self::get_form_class();
		$pdf_model = self::get_mvc_class( 'Model_PDF' );
		$entry     = $gform->get_entry( $entry_id );

		if ( is_wp_error( $entry ) ) {
			return $entry;
		}

		return $pdf_model->get_form_data( $entry );
	}
}
