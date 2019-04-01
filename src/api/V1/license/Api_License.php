<?php

namespace GFPDF\Api\V1\License;

use GFPDF\Api\CallableApiResponse;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Abstract_Addon;
use Psr\Log\LoggerInterface;

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
 * Class ApiLicenseEndpoint
 *
 * @package GFPDF\Plugins\GravityPDF\API
 */
class Api_License implements CallableApiResponse {

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

	public function __construct( LoggerInterface $log, Helper_Data $data ) {
		/* Assign our internal variables */
		$this->log   = $log;
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
			'/license/(?P<id>\d+)/deactivate',
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'process_license_deactivation' ],

				'permission_callback' => function() {
					return current_user_can( 'gravityforms_edit_settings', 'gfpdf_deactivate_license' );
				},
			]
		);
	}

	/**
	 * An AJAX endpoint for processing license deactivations
	 *
	 * @Internal Expected parameters include:
	 *           $_POST['addon_name']
	 *           $_POST['license']
	 *
	 * @since 4.2
	 */
	public function process_license_deactivation( \WP_REST_Request $request ) {

		/* User / CORS validation */
//		$this->misc->handle_ajax_authentication( 'Deactivate License', 'gravityforms_edit_settings', 'gfpdf_deactivate_license' );

		// get the json parameter
		$params = $request->get_json_params();

		/* Get the required details */
		$addon_slug = ( isset( $params['addon_name'] ) ) ? $params['addon_name'] : '';
		$license    = ( isset( $params['license'] ) ) ? $params['license'] : '';
		$addon      = ( isset( $this->data->addon[ $addon_slug ] ) ) ? $this->data->addon[ $addon_slug ] : false;

		/* Check add-on currently installed */
		if ( ! empty( $addon ) ) {
			if ( $this->deactivate_license_key( $addon, $license ) ) {
				$this->log->addNotice( 'AJAX – Successfully Deactivated License' );

				$response = new \WP_REST_Response(array('message' => 'Successfully Deactivated License'));
				$response->set_status(200);

			} elseif ( $addon->schedule_license_check() ) {

				$license_info = $addon->get_license_info();

				return new \WP_Error( '400', $license_info['message'], [ 'status' => 400 ] );

			}
		}

		$this->log->addError( 'AJAX Endpoint Error' );

		return new \WP_Error( '500', 'An error occurred during deactivation, please try again', [ 'status' => 500 ] );

	}

	/**
	 * Do API call to GravityPDF.com to deactivate add-on license
	 *
	 * @param Helper_Abstract_Addon $addon
	 * @param string                $license_key
	 *
	 * @return bool
	 *
	 * @since 4.2
	 */
	public function deactivate_license_key( Helper_Abstract_Addon $addon, $license_key ) {

		$response = wp_remote_post(
			$this->data->store_url,
			[
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => [
					'edd_action' => 'deactivate_license',
					'license'    => $license_key,
					'item_name'  => urlencode( $addon->get_short_name() ), // the name of our product in EDD
					'url'        => home_url(),
				],
			]
		);

		/* If API error exit early */
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		/* Get API response and check license is now deactivated */
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! isset( $license_data->license ) || $license_data->license !== 'deactivated' ) {
			return false;
		}

		/* Remove license data from database */
		$addon->delete_license_info();

		$this->log->addNotice(
			'License successfully deactivated',
			[
				'slug'    => $addon->get_slug(),
				'license' => $license_key,
			]
		);

		return true;
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
	public function response( \WP_REST_Request $request ) {

		return new \WP_Error( 'Interal Server Error', 'Internal error occurred', [ 'status' => 500 ] );

	}

}
