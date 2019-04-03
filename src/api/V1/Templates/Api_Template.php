<?php

namespace GFPDF\Api\V1\Template;

use GFPDF\Helper\Helper_Misc;
use Psr\Log\LoggerInterface;
use GFPDF\Helper\Helper_Data;


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
 * Class ApiTemplateEndpoint
 *
 * @package GFPDF\Plugins\GravityPDF\API
 */
class Api_Template  {

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

	public function __construct( LoggerInterface $log, Helper_Misc $misc, Helper_Data $data ) {
		/* Assign our internal variables */
		$this->log   = $log;
		$this->misc  = $misc;
		$this->data  = $data;
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
			'gravity-pdf/v1', /* @TODO - pass `gravity-pdf` portion via __construct() */
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
			'gravity-pdf/v1', /* @TODO - pass `gravity-pdf` portion via __construct() */
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
	 * @since 4.1
	 */
	public function ajax_process_build_template_options_html() {

		$options_class = \GPDFAPI::get_options_class();

		$registered_settings = $options_class->get_registered_fields();
		$template_settings   = $registered_settings['form_settings']['template'];

		$templates = $template_settings['options'];
		$value     = $options_class->get_form_value( $template_settings );

		header( 'Content-Type: application/text' );
		echo $options_class->build_options_for_select( $templates, $value );

		/* Okay Response */
		wp_die( '', 200 );
	}

	/**
	 * AJAX Endpoint to handle the uploading of PDF templates
	 *
	 * @param string $_POST ['nonce'] a valid nonce
	 *
	 * @since 4.1
	 */
	public function ajax_process_uploaded_template() {

		$this->misc->handle_ajax_authentication( 'Process Uploaded Template Zip Package' );

		/* Validate uploaded file */
		try {
			$storage  = new FileSystem( $this->data->template_tmp_location );
			$file     = new File( 'template', $storage );
			$zip_path = $this->move_template_to_tmp_dir( $file );
		} catch ( Exception $e ) {
			$this->log->addWarning(
				'File validation and move failed',
				[
					'file'  => $_FILES,
					'error' => $e->getMessage(),
				]
			);

			/* Bad Request */
			wp_die( '400', 400 );
		}

		/* Unzip and check the PDF templates look valid */
		try {
			$this->unzip_and_verify_templates( $zip_path );
		} catch ( Exception $e ) {
			$this->cleanup_template_files( $zip_path );

			$this->log->addWarning(
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
	 * AJAX Endpoint for deleting user-uploaded PDF templates
	 *
	 * @param string $_POST ['nonce'] a valid nonce
	 * @param string $_POST ['id'] a valid PDF template ID
	 *
	 * @since 4.1
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
