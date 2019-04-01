<?php

namespace GFPDF\Api\V1\Migration\Multisite;

use GFPDF\Api\CallableApiResponse;

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
 * Class ApiMigrationv4Endpoint
 *
 * @package GFPDF\Plugins\GravityPDF\API
 */
class Api_Migration_v4 implements CallableApiResponse {

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
			'/migration/multisite/',
			[
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'ajax_multisite_v3_migration' ],

				'permission_callback' => function() {
					return current_user_can( 'manage_sites','gfpdf_multisite_migration' );
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
	public function ajax_multisite_v3_migration( \WP_REST_Request $request ) {

		// get the json parameter
		$params = $request->get_json_params();

		/* Ensure multisite website */
		if ( ! is_multisite() ) {
			/* Unauthorized response */
			wp_die( '401', 401 );
		}

		/* User / CORS validation */
//		$misc->handle_ajax_authentication( 'Multisite v3 to v4 config', 'manage_sites', 'gfpdf_multisite_migration' );

		/* Check there's a configuration file to migrate */
		$blog_id = ( isset( $params['blog_id'] ) ) ? (int) $params['blog_id'] : 0;

		/* Check if we have a config file that should be migrated */
		$path = $this->data->template_location . $blog_id . '/';

		if ( ! is_file( $path . 'configuration.php' ) ) {

			$return = [
				'error' => sprintf( esc_html__( 'No configuration.php file found for site #%s', 'gravity-forms-pdf-extended' ), $blog_id ),
			];

			$this->log->addError( 'AJAX Endpoint Failed', $return );

			echo json_encode( [ 'results' => $return ] );
			wp_die();
		}

		/* Setup correct migration settings */
		switch_to_blog( $blog_id );
		$this->data->multisite_template_location = $path;

		/* Do migration */
		if ( $this->migrate_v3( $path ) ) {
			echo json_encode( [ 'results' => 'complete' ] );
			wp_die();
		} else {

			$return = [
				'error' => sprintf( esc_html__( 'Database import problem for site #%s', 'gravity-forms-pdf-extended' ), $blog_id ),
			];

			$this->log->addError( 'AJAX Endpoint Failed', $return );

			echo json_encode( [ 'results' => $return ] );
			wp_die();
		}

		$this->log->addError( 'AJAX Endpoint Failed' );

		/* Internal Server Error */
		wp_die( '500', 500 );
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
		return new \WP_Error( 'some_error_code', 'Some error message', [ 'status' => 400 ] );
	}

}
