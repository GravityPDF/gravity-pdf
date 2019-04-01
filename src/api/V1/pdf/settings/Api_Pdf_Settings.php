<?php

namespace GFPDF\Api\V1\Pdf\Settings;

use GFPDF\Api\CallableApiResponse;
use Psr\Log\LoggerInterface;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;

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
 * Class ApiPdfSettingEndpoint
 *
 * @package GFPDF\Plugins\GravityPDF\API
 */
class Api_Pdf_Settings implements CallableApiResponse {

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

	public function __construct( LoggerInterface $log,  Helper_Data $data,  Helper_Misc $misc) {
		/* Assign our internal variables */
		$this->log   = $log;
		$this->data  = $data;
		$this->misc  = $misc;
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
	 * @since 5.2
	 */
	public function register_endpoint() {
		register_rest_route(
			'gravity-pdf/v1', /* @TODO - pass `gravity-pdf` portion via __construct() */
			'/pdf/settings/',
			[
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'check_tmp_pdf_security' ],

				'permission_callback' => function() {
					return current_user_can( 'gravityforms_view_settings', 'gfpdf-direct-pdf-protection' );
				},
			]
		);
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

		/* Create our tmp file and do our actual check */
		$result =  json_encode( $this->test_public_tmp_directory_access() );

		if (!$result) {
			return new \WP_Error( '400', '', [ 'status' => 400 ] );
		}

		$response = new \WP_REST_Response(array('message' => 'Tmp file successfully created '));
		$response->set_status(200);

		return $response;
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
		file_put_contents( $tmp_dir . $tmp_test_file, 'failed-if-read' );

		/* verify it exists */
		if ( is_file( $tmp_dir . $tmp_test_file ) ) {

			/* Run our test */
			$site_url = $this->misc->convert_path_to_url( $tmp_dir );

			if ( $site_url !== false ) {

				$response = wp_remote_get( $site_url . $tmp_test_file );

				if ( ! is_wp_error( $response ) ) {

					/* Check if the web server responded with a OK status code and we can read the contents of our file, then fail our test */
					if ( isset( $response['response']['code'] ) && $response['response']['code'] === 200 &&
					     isset( $response['body'] ) && $response['body'] === 'failed-if-read'
					) {
						$response_object = $response['http_response'];
						$raw_response    = $response_object->get_response_object();
						$this->log->warning(
							'PDF temporary directory not protected',
							[
								'url'         => $raw_response->url,
								'status_code' => $raw_response->status_code,
								'response'    => $raw_response->raw,
							]
						);

						$return = false;
					}
				}
			}
		}

		/* Cleanup our test file */
		@unlink( $tmp_dir . $tmp_test_file );

		return $return;
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
	public function response( \WP_REST_Request $request ) {
		return new \WP_Error( 'some_error_code', 'Some error message', [ 'status' => 400 ] );
	}

}
