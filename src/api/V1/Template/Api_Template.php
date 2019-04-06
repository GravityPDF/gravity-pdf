<?php

namespace GFPDF\Api\V1\Template;

use GFPDF\Api\V1\Base_Api;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Data;

/**
 * @package     Gravity PDF 
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

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
 * Class ApiTemplateEndpoint
 *
 * @package GFPDF\Plugins\GravityPDF\API
 */
class Api_Template extends Base_Api {

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var \GFPDF\Helper\Helper_Misc
	 *
	 * @since 5.2
	 */
	protected $misc;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var \GFPDF\Helper\Helper_Data
	 *
	 * @since 5.2
	 */
	protected $data;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var \GFPDF\Helper\Helper_Options_Fields
	 *
	 * @since 5.2
	 */
	protected $options;

	/**
	 * Api_Pdf_Settings constructor.
	 *
	 * @param Helper_Misc $misc
	 * 
	 * @param Helper_Data $data
	 *
	 * @since 5.2
	 */
	public function __construct( Helper_Misc $misc, Helper_Data $data, Helper_Abstract_Options $options ) {				
		$this->misc  = $misc;
		$this->data  = $data;
		$this->options = $options;
	}

	/**
	 * Initialise our module
	 *
	 * @since 5.2
	 */
	public function init() {		
		$this->add_actions();
	}

	/**
	 * @since 5.2
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
			self::ENTRYPOINT . '/' . self::VERSION,
			'/template/',
			[
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'ajax_process_build_template_options_html' ],

				'permission_callback' => function() {
					return current_user_can( 'gravityforms_edit_settings' );
				},
			]
		);

		register_rest_route(
			self::ENTRYPOINT . '/' . self::VERSION,
			'/template/',
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'ajax_process_uploaded_template' ],

				'permission_callback' => function() {
					return current_user_can( 'gravityforms_edit_settings' );
				},
			]
		);

		register_rest_route(
			self::ENTRYPOINT . '/' . self::VERSION,
			'/template/',
			[
				'methods'  => \WP_REST_Server::DELETABLE,
				'callback' => [ $this, 'ajax_process_delete_template' ],

				'permission_callback' => function() {
					return current_user_can( 'gravityforms_edit_settings' );
				},
			]
		);

	}

	/**
	 * AJAX Endpoint for building the template select box options (so we don't have to recreate the logic in React)
	 *
	 * @param string $_POST ['nonce'] a valid nonce
	 *
	 * @since 5.2
	 */
	public function ajax_process_build_template_options_html() {

		$registered_settings = $this->options->get_registered_fields();

		$template_settings   = $registered_settings['form_settings']['template'];
		
		header( 'Content-Type: application/text' );
		echo $this->options->build_options_for_select( $template_settings['options'], $this->options->get_form_value( $template_settings ) );

		/* Okay Response */		
		return [ 'message' => 'Template options built successfully' ];
	}

	/**
	 * AJAX Endpoint to handle the uploading of PDF templates
	 *
	 * @param string $_POST ['nonce'] a valid nonce
	 *
	 * @since 5.2
	 */
	public function ajax_process_uploaded_template() {

		/* Validate uploaded file */
		try {
			$storage  = new FileSystem( $this->data->template_tmp_location );
			$file     = new File( 'template', $storage );
			$zip_path = $this->move_template_to_tmp_dir( $file );
		} catch ( Exception $e ) {
			$this->logger->addWarning(
				'File validation and move failed',
				[
					'file'  => $_FILES,
					'error' => $e->getMessage(),
				]
			);

			/* Bad Request */
			return new \WP_Error( 'validate_upload_file', 'File validation and move failed', [ 'status' => 400 ] );	
		}

		/* Unzip and check the PDF templates look valid */
		try {
			$this->unzip_and_verify_templates( $zip_path );
		} catch ( Exception $e ) {
			$this->cleanup_template_files( $zip_path );

			$this->logger->addWarning(
				'File validation and move failed',
				[
					'file'  => $_FILES,
					'error' => $e->getMessage(),
				]
			);

			header( 'Content-Type: application/json' );
			echo json_encode(
				[
					'error' => $e->getMessage(),
				]
			);

			/* Bad Response */
			wp_die( '', 400 );
		}

		/* Copy all the files to the active PDF working directory */
		$unzipped_dir_name = $this->get_unzipped_dir_name( $zip_path );
		$template_path     = $this->templates->get_template_path();

		$results = $this->misc->copyr( $unzipped_dir_name, $template_path );

		/* Get the template headers now all the files are in the right location */
		$this->templates->flush_template_transient_cache();
		$headers = $this->get_template_info( glob( $unzipped_dir_name . '*.php' ) );

		/* Fix template path */
		$headers = array_map(
			function( $header ) use ( $unzipped_dir_name, $template_path ) {
				$header['path'] = str_replace( $unzipped_dir_name, $template_path, $header['path'] );
				return $header;
			},
			$headers
		);

		/* Run PDF template SetUp method if required */
		$this->maybe_run_template_setup( $headers );

		/* Cleanup tmp uploaded files */
		$this->cleanup_template_files( $zip_path );

		if ( is_wp_error( $results ) ) {
			/* Internal Server Error */
			wp_die( '500', 500 );
		}

		/* Return newly-installed template headers */
		header( 'Content-Type: application/json' );
		echo json_encode(
			[
				'templates' => $headers,
			]
		);

		/* Okay Response */
		wp_die( '', 200 );
	}


	/**
	 * Extracts the zip file, checks there are valid PDF template files found and retreives information about them
	 *
	 * @param $zip_path The full path to the zip file
	 *
	 * @return array The PDF template headers from the valid files
	 *
	 * @throws Exception Thrown if a PDF template file isn't valid
	 *
	 * @since 4.1
	 */
	public function unzip_and_verify_templates( $zip_path ) {
		$this->enable_wp_filesystem();

		$dir     = $this->get_unzipped_dir_name( $zip_path );
		$results = unzip_file( $zip_path, $dir );

		/* If the unzip failed we'll throw an error */
		if ( is_wp_error( $results ) ) {
			throw new Exception( $results->get_error_message() );
		}

		/* Check unziped templates for a valid v4 header, or v3 string pattern */
		$files = glob( $dir . '*.php' );

		if ( ! is_array( $files ) || sizeof( $files ) === 0 ) {
			throw new Exception( esc_html__( 'No valid PDF template found in Zip archive.', 'gravity-forms-pdf-extended' ) );
		}

		$this->check_for_valid_pdf_templates( $files );
	}


	/**
	 * Remove the zip file and the unzipped directory
	 *
	 * @param $zip_path The full path to the zip file
	 *
	 * @since 4.1
	 */
	public function cleanup_template_files( $zip_path ) {
		$dir = $this->get_unzipped_dir_name( $zip_path );

		$this->misc->rmdir( $dir );
		unlink( $zip_path );
	}


	/**
	 * Gets the full path to a new directory which is based on the zip file's unique name
	 *
	 * @param string $zip_path The full path to the zip file
	 *
	 * @return string
	 *
	 * @since 4.1
	 */
	public function get_unzipped_dir_name( $zip_path ) {
		return dirname( $zip_path ) . '/' . basename( $zip_path, '.zip' ) . '/';
	}


	/**
	 * Get the PDF template info to pass to our application
	 *
	 * @param array $files
	 *
	 * @return array
	 *
	 * @since 4.1
	 */
	public function get_template_info( $files = [] ) {
		return array_map(
			function( $file ) {
					return $this->templates->get_template_info_by_path( $file );
			},
			$files
		);
	}


	/**
	 * Execute the setUp method on any templates that impliment it
	 *
	 * @param array $headers Contains the array returned from $this->get_template_info()
	 *
	 * @since 4.1
	 */
	public function maybe_run_template_setup( $headers = [] ) {
		foreach ( $headers as $template ) {
			$config = $this->templates->get_config_class( $template['id'] );

			/* Check if the PDF config impliments our Setup/TearDown interface and run the tear down */
			if ( in_array( 'GFPDF\Helper\Helper_Interface_Setup_TearDown', class_implements( $config ) ) ) {
				$config->setUp();
			}
		}
	}


	/**
	 * Sniffs the PHP file for signs that it's a valid Gravity PDF tempalte file
	 *
	 * @param array $files The full paths to the PDF templates
	 *
	 * @return array The PDF template header information
	 *
	 * @throws Exception Thrown if file found not to be valid
	 *
	 * @since 4.1
	 */
	public function check_for_valid_pdf_templates( $files = [] ) {
		foreach ( $files as $file ) {

			/* Check if we have a valid v4 template header in the file */
			$info = $this->templates->get_template_info_by_path( $file );

			if ( ! isset( $info['template'] ) || strlen( $info['template'] ) === 0 ) {
				/* Check if it's a v3 template */
				$fp        = fopen( $file, 'r' );
				$file_data = fread( $fp, 8192 );
				fclose( $fp );

				/* Check the first 8kiB contains the string RGForms or GFForms, which signifies our v3 templates */
				if ( strpos( $file_data, 'RGForms' ) === false && strpos( $file_data, 'GFForms' ) === false ) {
					throw new Exception( sprintf( esc_html__( 'The PHP file %s is not a valid PDF Template.', 'gravity-forms-pdf-extended' ), basename( $file ) ) );
				}
			}
		}
	}






	/**
	 * AJAX Endpoint for deleting user-uploaded PDF templates
	 *
	 * @param string $_POST ['nonce'] a valid nonce
	 * @param string $_POST ['id'] a valid PDF template ID
	 *
	 * @since 5.2
	 */
	public function ajax_process_delete_template() {

		$this->misc->handle_ajax_authentication( 'Delete PDF Template' );

		$template_id = ( isset( $_POST['id'] ) ) ? $_POST['id'] : '';

		/* Get all the necessary PDF template files to delete */
		try {
			$this->delete_template( $template_id );
		} catch ( Exception $e ) {
			/* Bad Request */
			wp_die( '400', 400 );
		}

		$this->templates->flush_template_transient_cache();

		header( 'Content-Type: application/json' );
		echo json_encode( true );

		/* Okay Response */
		wp_die( '', 200 );
	}

}
