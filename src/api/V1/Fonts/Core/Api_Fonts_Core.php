<?php

namespace GFPDF\Api\V1\Fonts\Core;

use GFPDF\Api\CallableApiResponse;
use Psr\Log\LoggerInterface;


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
class Api_Fonts_Core implements CallableApiResponse {

	/**
	 * Holds our log class
	 *
	 * @var \Monolog\Logger|LoggerInterface
	 *
	 * @since 4.0
	 */
	protected $log;

	protected $github_repo = 'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/master/';

	public function __construct( LoggerInterface $log ) {

		/* Assign our internal variables */
		$this->log   = $log;
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
	 * @since 5.2
	 */
	public function register_endpoint() {
		register_rest_route(
			'gravity-pdf/v1', /* @TODO - pass `gravity-pdf` portion via __construct() */
			'/fonts/core/',
			[
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'response' ],
				 'permission_callback' => function() {
				 	return current_user_can( 'read' );
				 },
			]
		);

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
		// get the json parameter
		$params = $request->get_json_params();

		/* Download and save our font */
		$fontname = isset( $params['font_name'] ) ? $params['font_name'] : '';
		$results  = $this->download_and_save_font( $fontname );

		// There was an issue downloading and saving fonts
		if (!$results) {
			return new \WP_Error( '400', 'Core Font Download Failed', [ 'status' => 400 ] );
		}

		/* Return results */
//		header( 'Content-Type: application/json' );
//		echo json_encode( $results );
//		wp_die();

		$response = new \WP_REST_Response(array('message' => 'Font saved successfully', 'data' => array('status' => 200)));
		$response->set_status(200);
		return $response;

	}

	/**
	 * Stream files from remote server and save them locally
	 *
	 * @param $fontname
	 *
	 * @since 5.0
	 *
	 * @return bool
	 */
	protected function download_and_save_font( $fontname ) {
		/* Only the font name is passed via AJAX. The Repo we download from is fixed (prevent security issues) */
		$res = wp_remote_get(
			$this->github_repo . $fontname,
			[
				'timeout'  => 60,
				'stream'   => true,
				'filename' => $this->data->template_font_location . $fontname,
			]
		);

		$res_code = wp_remote_retrieve_response_code( $res );

		/* Check for errors and log them to file */
		if ( is_wp_error( $res ) ) {
			$this->log->addError(
				'Core Font Download Failed',
				[
					'name'             => $fontname,
					'WP_Error_Message' => $res->get_error_message(),
					'WP_Error_Code'    => $res->get_error_code(),
				]
			);

			return false;
		}

		if ( $res_code != '200' ) {
			$this->log->addError(
				'Core Font API Response Failed',
				[
					'response_code' => wp_remote_retrieve_response_code( $res ),
				]
			);

			return false;
		}

		/* If we got here, the call was successfull */

		return true;
	}
}
