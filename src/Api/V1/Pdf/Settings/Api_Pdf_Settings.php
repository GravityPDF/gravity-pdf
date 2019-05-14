<?php

namespace GFPDF\Api\V1\Pdf\Settings;

use GFPDF\Api\V1\Base_Api;
use GFPDF\Exceptions\GravityPdfRuntimeException;
use WP_Error;

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
 * Class ApiPdfSettingEndpoint
 *
 * @package GFPDF\Plugins\GravityPDF\API
 */
class Api_Pdf_Settings extends Base_Api {

	/**
	 * @var string
	 * @since 5.2
	 */
	protected $test_file_path;

	/**
	 * @var string
	 * @since 5.2
	 */
	protected $test_file_url;

	/**
	 * Api_Pdf_Settings constructor.
	 *
	 * @param string $test_file_path
	 * @param string $test_file_url
	 */
	public function __construct( $test_file_path, $test_file_url ) {
		$this->test_file_path = $test_file_path;
		$this->test_file_url  = $test_file_url;
	}

	/**
	 * @since 5.2
	 */
	public function register() {
		register_rest_route(
			self::ENTRYPOINT . '/' . self::VERSION,
			'/pdf/settings/',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'check_temporary_pdf_directory_security' ],
				'permission_callback' => function() {
					return $this->has_capabilities( 'gravityforms_view_settings' );
				},
			]
		);
	}

	/**
	 * Create a test file in the temporary folder and try read its content via a remote GET request
	 *
	 * @return bool|WP_Error
	 *
	 * @since 5.2
	 */
	public function check_temporary_pdf_directory_security() {
		try {
			$this->create_test_file();

			if ( $this->test_file_url === false ) {
				throw new GravityPdfRuntimeException( 'Could not determine the temporary file URL' );
			}

			$response = wp_remote_get( $this->test_file_url );

			if ( is_wp_error( $response ) ) {
				throw new GravityPdfRuntimeException( 'Remote request for temporary file URL failed' );
			}

			/* File was successfully accessed over public URL. Test failed */
			if ( wp_remote_retrieve_response_code( $response ) === 200 && wp_remote_retrieve_response_message( $response ) === 'failed-if-read' ) {
				return false;
			}

			return true;
		} catch ( GravityPdfRuntimeException $e ) {
			return new WP_Error( 'runtime_error', $e->getMessage(), [ 'status' => 500 ] );
		}
	}

	/**
	 * Create the temporary test file
	 *
	 * @return bool
	 * @throws GravityPdfRuntimeException
	 *
	 * @since 5.2
	 */
	protected function create_test_file() {
		if ( ! is_file( $this->test_file_path ) ) {
			file_put_contents( $this->test_file_path, 'failed-if-read' );

			if ( ! is_file( $this->test_file_path ) ) {
				throw new GravityPdfRuntimeException( 'Could not create temporary test file' );
			}
		}

		return true;
	}
}
