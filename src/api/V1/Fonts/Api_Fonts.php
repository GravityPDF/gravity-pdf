<?php

namespace GFPDF\Api\V1\Fonts;

use GFPDF\Helper\Helper_Misc;
use Psr\Log\LoggerInterface;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Abstract_Options;

/**
 * @package     Gravity PDF Previewer
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF Previewer.

    Copyright (C) 2018, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 3 as published
    by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Class ApiFontsEndpoint
 *
 * @package GFPDF\Plugins\GravityPDF\API
 */
class Api_Fonts {

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
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var \GFPDF\Helper\Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	protected $options;

	public function __construct( LoggerInterface $log, Helper_Misc $misc, Helper_Data $data, Helper_Abstract_Options $options ) {
		/* Assign our internal variables */
		$this->log   = $log;
		$this->misc  = $misc;
		$this->data  = $data;
		$this->options   = $options;
	}

	/**
	 * Initialise our module
	 *
	 * @since 0.1
	 */
	public function init() {		
		$this->add_actions();
	}

	/**
	 * @since 0.1
	 */
	public function add_actions() {		
		add_action( 'rest_api_init', [ $this, 'register_endpoint' ] );			
	}

	/**
	 * Register our PDF save font endpoint
	 *
	 * @Internal Use this endpoint to save fonts
	 *
	 * @since 5.2
	 */
	public function register_endpoint() {
		register_rest_route(
			'gravity-pdf/v1', /* @TODO - pass `gravity-pdf` portion via __construct() */
			'/fonts/',
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'save_font' ],
				'permission_callback' => function() {
					return current_user_can( 'gravityforms_edit_settings' );
				},
			]
		);

		register_rest_route(
			'gravity-pdf/v1', /* @TODO - pass `gravity-pdf` portion via __construct() */
			'/fonts/',
			[
				'methods'  => \WP_REST_Server::DELETABLE,
				'callback' => [ $this, 'delete_font' ],

				'permission_callback' => function() {
					return current_user_can(  'gravityforms_edit_settings' );
				},
			]
		);

	}

	/**
	 * Register our PDF save font endpoint
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 *
	 * @since 5.2
	 */
	public function save_font( \WP_REST_Request $request ) {

		// get the json parameter
		$params = $request->get_json_params();

		/* Handle the validation and saving of the font */
		$payload = isset( $params['payload'] ) ? $params['payload'] : '';
		$results = $this->process_font( $payload );

		// There was an issue downloading and saving fonts
		if (!$results) {
			return new \WP_Error( 'process_font', 'Save Font Failed', [ 'status' => 400 ] );
		}

		/* If we reached this point the results were successful so return the new object */
		$this->log->addNotice(
			'AJAX – Successfully Saved Font',
			[
				'results' => $results,
			]
		);

		return  new \WP_REST_Response(array('message' => 'Font saved successfully'));

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
			$return = [
				'error' => esc_html__( 'Required fields have not been included.', 'gravity-forms-pdf-extended' ),
			];

			$this->log->addWarning( 'Font Validation Failed', $return );

			return new \WP_Error( 'required_fields_missing', 'Required fields have not been included.', [ 'status' => 402 ] );
		}

		/* Check we have a valid font name */
		$name = $font['font_name'];

		if ( ! $this->is_font_name_valid( $name ) ) {

			$return = [
				'error' => esc_html__( 'Font name is not valid. Only alphanumeric characters and spaces are accepted.', 'gravity-forms-pdf-extended' ),
			];

			$this->log->addWarning( 'Font Validation Failed', $return );

			return new \WP_Error( 'invalid_font_name', 'Font name is not valid. Only alphanumeric characters and spaces are accepted.', [ 'status' => 400 ] );

		}

		/* Check the font name is unique */
		$shortname = $this->options->get_font_short_name( $name );
		$id        = ( isset( $font['id'] ) ) ? $font['id'] : '';

		if ( ! $this->is_font_name_unique( $shortname, $id ) ) {

			$return = [
				'error' => esc_html__( 'A font with the same name already exists. Try a different name.', 'gravity-forms-pdf-extended' ),
			];

			$this->log->addWarning( 'Font Validation Failed', $return );

			return new \WP_Error( 'font_name_exist', 'A font with the same name already exists. Try a different name.', [ 'status' => 422 ] );
		}

		/* Move fonts to our Gravity PDF font folder */
		$installation = $this->install_fonts( $font );

		/* Check if any errors occured installing the fonts */
		if ( isset( $installation['errors'] ) ) {

			$return = [
				'error' => $installation,
			];

			$this->log->addWarning( 'Font Validation Failed', $return );

			return new \WP_Error( 'installation_error', 'Font Installation Failed.', [ 'status' => 500 ] );

		}

		/* If we got here the installation was successful so return the data */
		return $installation;
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
	 * Handles the database updates required to save a new font
	 *
	 * @param  array $fonts
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function install_fonts( $fonts ) {

		$types  = [ 'regular', 'bold', 'italics', 'bolditalics' ];
		$errors = [];

		foreach ( $types as $type ) {

			/* Check if a key exists for this type and process */
			if ( isset( $fonts[ $type ] ) ) {
				$path = $this->misc->convert_url_to_path( $fonts[ $type ] );

				/* Couldn't find file so throw error */
				if ( is_wp_error( $path ) ) {
					$errors[] = sprintf( esc_html__( 'Could not locate font on web server: %s', 'gravity-forms-pdf-extended' ), $fonts[ $type ] );
				}

				/* Copy font to our fonts folder */
				$filename = basename( $path );
				if ( ! is_file( $this->data->template_font_location . $filename ) && ! copy( $path, $this->data->template_font_location . $filename ) ) {
					$errors[] = sprintf( esc_html__( 'There was a problem installing the font %s. Please try again.', 'gravity-forms-pdf-extended' ), $filename );
				}
			}
		}

		/* If errors were found then return */
		if ( sizeof( $errors ) > 0 ) {
			$this->log->addError(
				'Install Error.',
				[
					'errors' => $errors,
				]
			);

			return new \WP_Error( 'font_installation_error', 'Font Installation Failed.', [ 'errors' => $errors ] );

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

			/* Cleanup the mPDF tmp directory to prevent font caching issues  */
			$this->misc->cleanup_dir( $this->data->mpdf_tmp_location );

		}

		/* Fonts sucessfully installed so return font data */

		return $fonts;
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

		unset( $default_fonts[ esc_html__( 'User-Defined Fonts', 'gravity-forms-pdf-extended' ) ] );

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
	 * Description @todo
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 *
	 * @since 5.2
	 */
	public function delete_font(\WP_REST_Request $request) {

		// get the json parameter
		$params = $request->get_json_params();

		/* Get the required details for deleting fonts */
		$id    = ( isset( $params['id'] ) ) ? $params['id'] : '';
		$fonts = $this->options->get_option( 'custom_fonts' );

		/* Check font actually exists and remove */
		if ( isset( $fonts[ $id ] ) ) {

			if ( $this->remove_font_file( $fonts[ $id ] ) ) {
				unset( $fonts[ $id ] );

				/* Cleanup the mPDF tmp directory to prevent font caching issues  */
				$this->misc->cleanup_dir( $this->data->mpdf_tmp_location );

				if ( $this->options->update_option( 'custom_fonts', $fonts ) ) {
					/* Success */
					$this->log->addNotice( 'AJAX – Successfully Deleted Font' );

					return new \WP_REST_Response(array('message' => 'AJAX – Successfully Deleted Font'));
				}
			}
		}

		$return = [
			'error' => esc_html__( 'Could not delete Gravity PDF font correctly. Please try again.', 'gravity-forms-pdf-extended' ),
		];

		$this->log->addError( 'AJAX Endpoint Error', $return );

		return new \WP_Error( '500', 'Could not delete Gravity PDF font correctly. Please try again.', [ 'status' => 500 ] );


	}

	/**
	 * Removes the current font's TTF files from our font directory
	 *
	 * @param  array $fonts The font config
	 *
	 * @return boolean        True on success, false on failure
	 *
	 * @since  4.0
	 */
	public function remove_font_file( $fonts ) {

		$fonts = array_filter( $fonts );
		$types = [ 'regular', 'bold', 'italics', 'bolditalics' ];

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

}
