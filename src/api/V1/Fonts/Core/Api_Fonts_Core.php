<?php

namespace GFPDF\Api\V1\Fonts\Core;

use GFPDF\Api\V1\Base_Api;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
	This file is part of Gravity PDF.

	Gravity PDF – Copyright (c) 2019, Blue Liquid Designs

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
 * Class Api_Fonts_Core
 *
 * @package GFPDF\Api\V1\Core\Fonts
 */
class Api_Fonts_Core extends Base_Api {

	/**
	 * @var string
	 *
	 * @since 5.2
	 */
	protected $template_font_location;

	/**
	 * @var string
	 *
	 * @since 5.2
	 */
	protected $github_repo = 'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/master/';

	/**
	 * Api_Fonts_Core constructor.
	 *
	 * @param string $template_font_location The absolute path to the current PDF font directory
	 *
	 * @since 5.2
	 */
	public function __construct( $template_font_location ) {
		$this->template_font_location = $template_font_location;
	}

	/**
	 * Register WordPress REST API endpoint(s)
	 *
	 * @return void
	 *
	 * @internal Use `register_rest_route()` to register WordPress REST API endpoint(s)
	 *
	 * @since    5.2
	 */
	public function register() {
		register_rest_route(
			self::ENTRYPOINT . '/' . self::VERSION,
			'/fonts/core/',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_core_font' ],
				'permission_callback' => function() {
					return $this->has_capabilities( 'gravityforms_edit_settings' );
				},
			]
		);

	}

	/**
	 * Processes the rest API endpoint
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array|\WP_Error
	 *
	 * @since 5.2
	 */
	public function save_core_font( \WP_REST_Request $request ) {
		$params = $request->get_json_params();

		/* Download and save our font */
		$fontname = isset( $params['font_name'] ) ? $params['font_name'] : '';
		$results  = $this->download_and_save_font( $fontname );

		if ( ! $results ) {
			return new \WP_Error( 'download_and_save_font', 'Core Font Download Failed', [ 'status' => 400 ] );
		}

		return [ 'message' => 'Font saved successfully' ];
	}

	/**
	 * Stream files from remote server and save them locally
	 *
	 * @param $fontname
	 *
	 * @return bool
	 *
	 * @since 5.2
	 */
	protected function download_and_save_font( $fontname ) {

		if ( empty( $fontname ) ) {
			return false;
		}

		/* Only the font name is passed via AJAX. The Repo we download from is fixed (prevent security issues) */
		$response = wp_remote_get(
			$this->github_repo . $fontname,
			[
				'timeout'  => 60,
				'stream'   => true,
				'filename' => $this->template_font_location . $fontname,
			]
		);

		/* Check for errors and log them to file */
		if ( is_wp_error( $response ) ) {
			$this->logger->addError(
				'Core Font Download Failed',
				[
					'name'             => $fontname,
					'WP_Error_Message' => $response->get_error_message(),
					'WP_Error_Code'    => $response->get_error_code(),
				]
			);

			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			$this->logger->addError(
				'Core Font API Response Failed',
				[
					'response_code' => $response_code,
				]
			);

			return false;
		}

		/* If we got here, the call was successfull */
		return true;
	}
}
