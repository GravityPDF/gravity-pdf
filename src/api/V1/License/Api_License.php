<?php

namespace GFPDF\Api\V1\License;

use GFPDF\Api\V1\Base_Api;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Abstract_Addon;

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
 * Class Api_License
 *
 * @package GFPDF\Api\V1\License
 */
class Api_License extends Base_Api {

	/**
	 * Holds our log class
	 *
	 * @var \Monolog\Logger
	 *
	 * @since 4.0
	 */
	public $log;

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
	 * Api_License constructor.
	 *
	 * @param Helper_Data $data
	 *
	 * @since 5.2
	 */
	public function __construct( LoggerInterface $log, Helper_Data $data ) {
		$this->log 	= $log;
		$this->data = $data;
	}

	/**
	 * Register our PDF save font endpoint
	 *
	 * @Internal Use this endpoint to save fonts
	 *
	 * @since    5.2
	 */
	public function register() {
		register_rest_route(
			self::ENTRYPOINT . '/' . self::VERSION,
			'/license/(?P<id>\d+)/deactivate',
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'process_license_deactivation' ],

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
	public function process_license_deactivation( \WP_REST_Request $request ) {

		$params = $request->get_json_params();

		/* Get the required details */
		/* do not proceed if these are empty/null */
		if ( empty( $params['addon_name'] ) || empty( $params['license'] ) ) {
			return new \WP_Error( 'process_license_deactivation', 'Required Field is missing, please try again', [ 'status' => 400 ] );
		}

		$addon = $this->data->addon( $params['addon_name'] );

		/* Check add-on currently installed */
		if ( empty( $addon ) ) {
			return new \WP_Error( 'process_license_deactivation', 'An error occurred during deactivation, please try again', [ 'status' => 404 ] );
		}

		$was_deactivated = $this->deactivate_license_key( $addon, $params['license'] );
		
		if ( ! $was_deactivated ) {
			$license_info = $addon->get_license_info();
			return new \WP_Error( 'schedule_license_check', $license_info['message'], [ 'status' => 400 ] );
		}

		$this->log->addNotice( 'Successfully Deactivated License' );
		return [ 'success' => esc_html__( 'License deactivated.', 'gravity-forms-pdf-extended' ) ];
	}

	/**
	 * Do API call to GravityPDF.com to deactivate add-on license
	 *
	 * @param Helper_Abstract_Addon $addon
	 * @param string                $license_key
	 *
	 * @return bool
	 *
	 * @since 5.2
	 */
	public function deactivate_license_key( Helper_Abstract_Addon $addon, $license_key ) {
		$response = wp_remote_post(
			$this->data->store_url,
			[
				'timeout' => 15,
				'body'    => [
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
}