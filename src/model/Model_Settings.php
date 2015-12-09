<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;

use Psr\Log\LoggerInterface;

/**
 * Settings Model
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
 * Model_Welcome_Screen
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class Model_Settings extends Helper_Abstract_Model {

	/**
	 * Errors with the global form submission process are stored here
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	public $form_settings_errors;

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
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var \GFPDF\Helper\Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	protected $options;

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
	 * Set up our dependancies
	 *
	 * @param \GFPDF\Helper\Helper_Abstract_Form    $form    Our abstracted Gravity Forms helper functions
	 * @param \Monolog\Logger|LoggerInterface       $log     Our logger class
	 * @param \GFPDF\Helper\Helper_Notices          $notices Our notice class used to queue admin messages and errors
	 * @param \GFPDF\Helper\Helper_Abstract_Options $options Our options class which allows us to access any settings
	 * @param \GFPDF\Helper\Helper_Data             $data    Our plugin data store
	 * @param \GFPDF\Helper\Helper_Misc             $misc    Our miscellaneous class
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Form $form, LoggerInterface $log, Helper_Notices $notices, Helper_Abstract_Options $options, Helper_Data $data, Helper_Misc $misc ) {

		/* Assign our internal variables */
		$this->form    = $form;
		$this->log     = $log;
		$this->options = $options;
		$this->notices = $notices;
		$this->data    = $data;
		$this->misc    = $misc;
	}

	/**
	 * Get the form setting error and remove any duplicates
	 *
	 * @since 4.0
	 *
	 * @return  void
	 */
	public function setup_form_settings_errors() {

		/* set up a place to access form setting validation errors */
		$this->form_settings_errors = get_transient( 'settings_errors' );

		/* remove multiple errors for a single form */
		if ( $this->form_settings_errors ) {
			$set                    = false;
			$updated_settings_error = array();

			/* loop through current errors */
			foreach ( $this->form_settings_errors as $error ) {
				if ( $error['setting'] != 'gfpdf-notices' || ! $set ) {
					$updated_settings_error[] = $error;
				}

				if ( $error['setting'] == 'gfpdf-notices' ) {
					$set = true;
				}
			}

			/* update transient */
			set_transient( 'settings_errors', $updated_settings_error, 30 );

			$this->log->addNotice( 'PDF Settings Errors', array(
				'original' => $this->form_settings_errors,
				'cleaned'  => $updated_settings_error,
			) );
		}
	}

	/**
	 * If any errors have been passed back from the options.php page we will highlight the actual fields that caused them
	 *
	 * @param  array $settings The get_registered_fields() array
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function highlight_errors( $settings ) {

		/* We fire too late to tap into get_settings_error() so our data storage holds the details */
		$errors = $this->form_settings_errors;

		/* Loop through errors if any and highlight the appropriate settings */
		if ( is_array( $errors ) && sizeof( $errors ) > 0 ) {
			foreach ( $errors as $error ) {

				/* Skip over if not an error */
				if ( $error['type'] !== 'error' ) {
					continue;
				}

				/* Loop through our data until we find a match */
				$found = false;
				foreach ( $settings as $key => &$group ) {
					foreach ( $group as $id => &$item ) {
						if ( $item['id'] === $error['code'] ) {
							$item['class'] = ( isset( $item['class'] ) ) ? $item['class'] . ' gfield_error' : 'gfield_error';
							$found         = true;
							break;
						}
					}

					/* exit outer loop */
					if ( $found ) {
						break;
					}
				}
			}
		}

		return $settings;
	}

	/**
	 * Install the files stored in /src/templates/ to the user's template directory
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function install_templates() {

		$destination = ( is_multisite() ) ? $this->data->multisite_template_location : $this->data->template_location;
		$copy        = $this->misc->copyr( PDF_PLUGIN_DIR . 'src/templates/', $destination );
		if ( is_wp_error( $copy ) ) {
			$this->log->addError( 'Template Installation Error.' );
			$this->notices->add_error( sprintf( __( 'There was a problem copying all PDF templates to %s. Please try again.', 'gravity-forms-pdf-extended' ), '<code>' . $this->misc->relative_path( $destination ) . '</code>' ) );

			return false;
		}

		$this->notices->add_notice( sprintf( __( 'Gravity PDF Custom Templates successfully installed to %s.', 'gravity-forms-pdf-extended' ), '<code>' . $this->misc->relative_path( $destination ) . '</code>' ) );
		$this->options->update_option( 'custom_pdf_template_files_installed', true );

		return true;
	}


	/**
	 * Removes the current font's TTF or OTF files from our font directory
	 *
	 * @param  array $fonts The font config
	 *
	 * @return boolean        True on success, false on failure
	 *
	 * @since  4.0
	 */
	public function remove_font_file( $fonts ) {

		$fonts = array_filter( $fonts );
		$types = array( 'regular', 'bold', 'italics', 'bolditalics' );

		foreach ( $types as $type ) {
			if ( isset( $fonts[ $type ] ) ) {
				$filename = basename( $fonts[ $type ] );

				if ( is_file( $this->data->template_font_location . $filename ) && ! unlink( $this->data->template_font_location . $filename ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Check that the font name passed conforms to our expected nameing convesion
	 *
	 * @param  string $name The font name to check
	 *
	 * @return boolean       True on valid, false on failure
	 *
	 * @since 4.0
	 */
	public function is_font_name_valid( $name ) {

		$regex = '^[A-Za-z0-9 ]+$';

		if ( preg_match( "/$regex/", $name ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Query our custom fonts options table and check if the font name already exists
	 *
	 * @param  string    $name The font name to check
	 * @param int|string $id   The configuration ID (if any)
	 *
	 * @return bool True if valid, false on failure
	 *
	 * @since 4.0
	 */
	public function is_font_name_unique( $name, $id = '' ) {

		/* Get the shortname of the current font */
		$name = $this->options->get_font_short_name( $name );

		/* Loop through default fonts and check for duplicate */
		$default_fonts = $this->options->get_installed_fonts();

		unset( $default_fonts[ __( 'User-Defined Fonts', 'gravity-forms-pdf-extended' ) ] );

		/* check for exact match */
		foreach ( $default_fonts as $group ) {
			if ( isset( $group[ $name ] ) ) {
				return false;
			}
		}

		$custom_fonts = $this->options->get_option( 'custom_fonts' );

		if ( is_array( $custom_fonts ) ) {
			foreach ( $custom_fonts as $font ) {

				/* Skip over itself */
				if ( ! empty( $id ) && $font['id'] == $id ) {
					continue;
				}

				if ( $name == $this->options->get_font_short_name( $font['font_name'] ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Handles the database updates required to save a new font
	 *
	 * @param  array $fonts
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function install_fonts( $fonts ) {

		$types  = array( 'regular', 'bold', 'italics', 'bolditalics' );
		$errors = array();

		foreach ( $types as $type ) {

			/* Check if a key exists for this type and process */
			if ( isset( $fonts[ $type ] ) ) {
				$path = $this->misc->convert_url_to_path( $fonts[ $type ] );

				/* Couldn't find file so throw error */
				if ( is_wp_error( $path ) ) {
					$errors[] = sprintf( __( 'Could not locate font on web server: %s', 'gravity-forms-pdf-extended' ), $fonts[ $type ] );
				}

				/* Copy font to our fonts folder */
				$filename = basename( $path );
				if ( ! is_file( $this->data->template_font_location . $filename ) && ! copy( $path, $this->data->template_font_location . $filename ) ) {
					$errors[] = sprintf( __( 'There was a problem installing the font %s. Please try again.', 'gravity-forms-pdf-extended' ), $filename );
				}
			}
		}

		/* If errors were found then return */
		if ( sizeof( $errors ) > 0 ) {
			$this->log->addError( 'Install Error.', array( 'errors' => $errors ) );

			return array( 'errors' => $errors );
		} else {
			/* Insert our font into the database */
			$custom_fonts = $this->options->get_option( 'custom_fonts' );

			/* Prepare our font data and give it a unique id */
			if ( empty( $fonts['id'] ) ) {
				$id          = uniqid();
				$fonts['id'] = $id;
			}

			$custom_fonts[ $fonts['id'] ] = $fonts;

			/* Update our font database */
			$this->options->update_option( 'custom_fonts', $custom_fonts );
		}

		/* Fonts sucessfully installed so return font data */

		return $fonts;
	}

	/**
	 * Load Recent forum articles meta box
	 *
	 * @param object $object The metabox object
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function process_meta_pdf_recent_forum_articles( $object ) {
		$controller = $this->getController();

		/* get our list of recent forum topics */
		$latest = $this->get_latest_forum_topics();

		/* call view to render topics */
		$controller->view->add_meta_pdf_recent_forum_articles( $object, $latest );
	}

	/**
	 * Call forum endpoint and get the latest topic information
	 *
	 * @return string|boolean
	 *
	 * @since 4.0
	 */
	public function get_latest_forum_topics() {

		/* check if we have a transient set up with cached response */
		if ( false !== ( $topics = get_transient( 'gfpdf_latest_forum_topics' ) ) ) {
			return $topics;
		}

		/* set up the api endpoint details */
		$url = 'https://support.gravitypdf.com/latest.json';

		$args = array(
			'timeout' => 10,
		);

		/* do query */
		$response = wp_remote_get( $url, $args );

		/* check for errors */
		if ( is_wp_error( $response ) ) {
			return false;
		}

		/* decode json response */
		$json = json_decode( $response['body'], true );

		/* check we have the correct keys */
		if ( ! isset( $json['topic_list']['topics'] ) ) {
			return false;
		}

		/* cannot filter number of topics requested from endpoint so slice the data */
		$topics = array_slice( $json['topic_list']['topics'], 2, 5 );

		/* set a transient cache */
		set_transient( 'gfpdf_latest_forum_topics', $topics, 86400 ); /* cache for a day */

		return $topics;
	}

	/**
	 * Turn capabilities into more friendly strings
	 *
	 * @param  string $cap The wordpress-style capability
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function style_capabilities( $cap ) {
		$cap = str_replace( 'gravityforms', 'gravity_forms', $cap );
		$cap = str_replace( '_', ' ', $cap );
		$cap = ucwords( $cap );

		return $cap;
	}

	/**
	 * Add meta boxes used in the settings "help" tab
	 *
	 * @since  4.0
	 *
	 * @return  void
	 */
	public function add_meta_boxes() {

		$controller = $this->getController();

		/* set the meta box id */
		$id = 'pdf_knowledgebase';
		add_meta_box(
			$id,
			__( 'Documentation', 'gravity-forms-pdf-extended' ),
			array( $controller->view, 'add_meta_' . $id ),
			'pdf-help-and-support',
			'row-1'
		);

		/* set the meta box id */
		$id = 'pdf_support_forum';
		add_meta_box(
			$id,
			__( 'Support Forum', 'gravity-forms-pdf-extended' ),
			array( $controller->view, 'add_meta_' . $id ),
			'pdf-help-and-support',
			'row-1'
		);

		/* set the meta box id */
		$id = 'pdf_direct';
		add_meta_box(
			$id,
			__( 'Contact Us', 'gravity-forms-pdf-extended' ),
			array( $controller->view, 'add_meta_' . $id ),
			'pdf-help-and-support',
			'row-1'
		);

		/* set the meta box id */
		$id = 'pdf_popular_articles';
		add_meta_box(
			$id,
			__( 'Popular Documentation', 'gravity-forms-pdf-extended' ),
			array( $controller->view, 'add_meta_' . $id ),
			'pdf-help-and-support',
			'row-2'
		);

		/* set the meta box id */
		$id = 'pdf_recent_forum_articles';
		add_meta_box(
			$id,
			__( 'Recent Forum Activity', 'gravity-forms-pdf-extended' ),
			array( $this, 'process_meta_' . $id ),
			'pdf-help-and-support',
			'row-2'
		);

		/* set the meta box id */
		$id = 'pdf_support_hours';
		add_meta_box(
			$id,
			__( 'Support Hours', 'gravity-forms-pdf-extended' ),
			array( $controller->view, 'add_meta_' . $id ),
			'pdf-help-and-support',
			'row-2'
		);
	}


	/**
	 * Check a user is authorized to make modifications via this endpoint and
	 * that there is a valid nonce
	 *
	 * @return void
	 *
	 * @since  4.0
	 */
	private function ajax_font_validation() {

		/* prevent unauthorized access */
		if ( ! $this->form->has_capability( 'gravityforms_edit_settings' ) ) {
			/* fail */
			$this->log->addCritical( 'Lack of User Capabilities.', array(
				'user'      => wp_get_current_user(),
				'user_meta' => get_user_meta( get_current_user_id() ),
			) );

			header( 'HTTP/1.1 401 Unauthorized' );
			wp_die( '401' );
		}

		/*
         * Validate Endpoint
         */
		$nonce    = $_POST['nonce'];
		$nonce_id = 'gfpdf_font_nonce';

		if ( ! wp_verify_nonce( $nonce, $nonce_id ) ) {
			/* fail */
			$this->log->addWarning( 'Nonce Verification Failed.' );

			header( 'HTTP/1.1 401 Unauthorized' );
			wp_die( '401' );
		}
	}

	/**
	 * AJAX Endpoint for saving the custom font
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function save_font() {

		$this->log->addNotice( 'Running AJAX Endpoint', array( 'type' => 'Save Font' ) );

		/* prevent unauthorized access */
		$this->ajax_font_validation();

		/* Handle the validation and saving of the font */
		$results = $this->process_font( $_POST['payload'] );

		/* If we reached this point the results were successful so return the new object */
		$this->log->addNotice( 'AJAX Endpoint Successful', array( 'results' => $results ) );

		echo json_encode( $results );
		wp_die();
	}

	/**
	 * AJAX Endpoint for deleting a custom font
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function delete_font() {

		$this->log->addNotice( 'Running AJAX Endpoint', array( 'type' => 'Delete Font' ) );

		/* prevent unauthorized access */
		$this->ajax_font_validation();

		/* Get the required details for deleting fonts */
		$id    = $_POST['id'];
		$fonts = $this->options->get_option( 'custom_fonts' );

		/* Check font actually exists and remove */
		if ( isset( $fonts[ $id ] ) ) {

			if ( $this->remove_font_file( $fonts[ $id ] ) ) {
				unset( $fonts[ $id ] );

				if ( $this->options->update_option( 'custom_fonts', $fonts ) ) {
					/* Success */
					$this->log->addNotice( 'AJAX Endpoint Successful' );
					echo json_encode( array( 'success' => true ) );
					wp_die();
				}
			}
		}

		header( 'HTTP/1.1 400 Bad Request' );

		$return = array(
			'error' => __( 'Could not delete Gravity PDF font correctly. Please try again.', 'gravity-forms-pdf-extended' ),
		);

		$this->log->addError( 'AJAX Endpoint Error', array( 'error' => $return ) );

		echo json_encode( $return );
		wp_die();
	}

	/**
	 * Validate user input and save as new font
	 *
	 * @param  array $font The four font fields to be processed
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function process_font( $font ) {

		/* remove any empty fields */
		$font = array_filter( $font );

		/* Check we have the required data */
		if ( ! isset( $font['font_name'] ) || ! isset( $font['regular'] ) ||
		     strlen( $font['font_name'] ) === 0 || strlen( $font['regular'] ) === 0
		) {

			header( 'HTTP/1.1 400 Bad Request' );

			$return = array(
				'error' => __( 'Required fields have not been included.', 'gravity-forms-pdf-extended' ),
			);

			$this->log->addWarning( 'Validation Failed.', $return );

			echo json_encode( $return );
			wp_die();
		}

		/* Check we have a valid font name */
		$name = $font['font_name'];

		if ( ! $this->is_font_name_valid( $name ) ) {

			header( 'HTTP/1.1 400 Bad Request' );

			$return = array(
				'error' => __( 'Font name is not valid. Only alphanumeric characters and spaces are accepted.', 'gravity-forms-pdf-extended' ),
			);

			$this->log->addWarning( 'Validation Failed.', $return );

			echo json_encode( $return );
			wp_die();
		}

		/* Check the font name is unique */
		$shortname = $this->options->get_font_short_name( $name );
		$id        = ( isset( $font['id'] ) ) ? $font['id'] : '';

		if ( ! $this->is_font_name_unique( $shortname, $id ) ) {

			header( 'HTTP/1.1 400 Bad Request' );

			$return = array(
				'error' => __( 'A font with the same name already exists. Try a different name.', 'gravity-forms-pdf-extended' ),
			);

			$this->log->addWarning( 'Validation Failed.', $return );

			echo json_encode( $return );
			wp_die();
		}

		/* Move fonts to our Gravity PDF font folder */
		$installation = $this->install_fonts( $font );

		/* Check if any errors occured installing the fonts */
		if ( isset( $installation['errors'] ) ) {

			header( 'HTTP/1.1 400 Bad Request' );

			$return = array(
				'error' => $installation,
			);

			$this->log->addWarning( 'Validation Failed.', $return );

			echo json_encode( $return );
			wp_die();
		}

		/* If we got here the installation was successful so return the data */

		return $installation;
	}

	/**
	 * Create a file in our tmp directory and check if it is publically accessible (i.e no .htaccess protection)
	 *
	 * @param $_POST ['nonce']
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function check_tmp_pdf_security() {

		/* prevent unauthorized access */
		if ( ! $this->form->has_capability( 'gravityforms_view_settings' ) ) {
			/* fail */
			$this->log->addCritical( 'Lack of User Capabilities.', array(
				'user'      => wp_get_current_user(),
				'user_meta' => get_user_meta( get_current_user_id() ),
			) );

			header( 'HTTP/1.1 401 Unauthorized' );
			wp_die( '401' );
		}

		/**
		 * Verify our nonce is valid before doing anything
		 */
		$nonce    = $_POST['nonce'];
		$nonce_id = 'gfpdf-direct-pdf-protection';

		if ( ! wp_verify_nonce( $nonce, $nonce_id ) ) {
			/* fail */
			$this->log->addWarning( 'Nonce Verification Failed.' );

			header( 'HTTP/1.1 401 Unauthorized' );
			wp_die( '401' );
		}

		/* Create our tmp file and do our actual check */
		echo json_encode( $this->test_public_tmp_directory_access() );
		wp_die();
	}

	/**
	 * Create a file in our tmp directory and verify if it's protected from the public
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function test_public_tmp_directory_access() {
		$tmp_dir       = $this->data->template_tmp_location;
		$tmp_test_file = 'public_tmp_directory_test.txt';
		$return        = true;

		/* create our file */
		@touch( $tmp_dir . $tmp_test_file );

		/* verify it exists */
		if( is_file( $tmp_dir . $tmp_test_file ) ) {
			/* Run our test */
			$site_url = $this->misc->convert_path_to_url( $tmp_dir );

			if( $site_url !== false ) {

				$response = wp_remote_get( $site_url );

				if( ! is_wp_error( $response ) ) {

					/* Check if the web server responded with a OK status code and fail our test */
					if( isset( $response['response']['code'] ) && $response['response']['code'] === 200 ) {
						$return = false;
					}
				}
			}
		}

		/* Cleanup our test file */
		@unlink( $tmp_dir . $tmp_test_file );

		return $return;
	}

}
