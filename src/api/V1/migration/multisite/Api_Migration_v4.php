<?php

namespace GFPDF\Api\V1\Migration\Multisite;

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
 * Class ApiMigrationv4Endpoint
 *
 * @package GFPDF\Plugins\GravityPDF\API
 */
class Api_Migration_v4 {

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
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'ajax_multisite_v3_migration' ],

				'permission_callback' => function() {
					return current_user_can( 'manage_sites' );
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
			return new \WP_Error( '401', 'You are not authorized to perform this action. Please try again.', [ 'status' => 401 ] );

		}

		/* Check there's a configuration file to migrate */
		$blog_id = ( isset( $params['blog_id'] ) ) ? (int) $params['blog_id'] : 0;

		/* Check if we have a config file that should be migrated */
		$path = $this->data->template_location . $blog_id . '/';

		if ( ! is_file( $path . 'configuration.php' ) ) {

			$return = [
				'error' => sprintf( esc_html__( 'No configuration.php file found for site #%s', 'gravity-forms-pdf-extended' ), $blog_id ),
			];

			$this->log->addError( 'AJAX Endpoint Failed', $return );

			return new \WP_Error( '404', 'No configuration.php file found for site #%s', [ 'status' => 404 ] );
		}

		/* Setup correct migration settings */
		switch_to_blog( $blog_id );
		$this->data->multisite_template_location = $path;

		/* Do migration */
		if ( $this->migrate_v3( $path ) ) {

			return new \WP_REST_Response(array('message' => 'Migration completed successfully '));

		} else {

			$return = [
				'error' => sprintf( esc_html__( 'Database import problem for site #%s', 'gravity-forms-pdf-extended' ), $blog_id ),
			];

			$this->log->addError( 'AJAX Endpoint Failed', $return );

			return new \WP_Error( '422', 'Database import problem for site #%s', [ 'status' => 422 ] );

		}

		$this->log->addError( 'AJAX Endpoint Failed' );

		return new \WP_Error( '500', 'Internal Server Error', [ 'status' => 500 ] );
	}

}
