<?php

namespace GFPDF\Api\V1\Security\Tmp;

use GFPDF\Api\V1\Base_Api;
use GFPDF\Helper\Helper_Misc;
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
 * Class ApiSecurityTmpDirectoryEndpoint
 *
 * @package GFPDF\Plugins\GravityPDF\API
 */
class Api_Security_Tmp_Directory extends Base_Api {

	/**
	 * @var boolean
	 *
	 * @since 5.2
	 */
	protected $has_access = true;

	/**
	 * @var string the temporary text file to write to directory
	 *
	 * @since 5.2
	 */
	protected $tmp_test_file = 'public_tmp_directory_test.txt';

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
	 * Api_Security_Tmp_Directory constructor.
	 *
	 * @param Helper_Data $data
	 *
	 * @since 5.2
	 */
	public function __construct( Helper_Misc $misc, Helper_Data $data ) {
		$this->misc  = $misc;
		$this->data  = $data;

	}

	/**
	 * @since 5.2
	 */
	public function register() {
		register_rest_route(
			self::ENTRYPOINT . '/' . self::VERSION,
			'/security/tmp/',
			[
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'check_tmp_pdf_security' ],
				'permission_callback' => function() {
					return $this->has_capabilities( 'gravityforms_view_settings' );					
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
	 * @since 5.2
	 */
	public function check_tmp_pdf_security( ) {

		/* check if we can access tmp directory */
		$result =  $this->test_public_tmp_directory_access();

		if (!$result) {

			return new \WP_Error( 'check_tmp_pdf_security', 'There was an error creating access to tmp directory', [ 'status' => 500 ] );
		}

		return [ 'message' => 'Tmp Directory Accessible' ];				
	}

	/**
	 * Create a file in our tmp directory and verify if it's protected from the public
	 *
	 * @return boolean
	 *
	 * @since 5.2
	 */
	public function test_public_tmp_directory_access() {

		/* create our file */
		file_put_contents( $this->data->template_tmp_location . $this->tmp_test_file, 'failed-if-read' );

		/* verify it exists */
		if ( is_file( $this->data->template_tmp_location . $this->tmp_test_file ) ) {

			/* Run our test */
			$site_url = $this->misc->convert_path_to_url( $this->data->template_tmp_location );

			if ( $site_url !== false ) {

				$response = wp_remote_get( $site_url . $this->tmp_test_file );

				if ( ! is_wp_error( $response ) ) {

					/* Check if the web server responded with a OK status code and we can read the contents of our file, then fail our test */
					if ( isset( $response['response']['code'] ) && $response['response']['code'] === 200 &&
					     isset( $response['body'] ) && $response['body'] === 'failed-if-read'
					) {
						$response_object = $response['http_response'];
						$raw_response    = $response_object->get_response_object();
						$this->logger->warning(
							'PDF temporary directory not protected',
							[
								'url'         => $raw_response->url,
								'status_code' => $raw_response->status_code,
								'response'    => $raw_response->raw,
							]
						);

						$this->has_access = false;
					}
				}
			}
		}

		/* Cleanup our test file */
		@unlink( $this->data->template_tmp_location . $this->tmp_test_file );

		return $this->has_access;
	}
}
