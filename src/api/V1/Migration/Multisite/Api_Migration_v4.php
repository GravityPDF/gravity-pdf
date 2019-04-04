<?php

namespace GFPDF\Api\V1\Migration\Multisite;

use GFPDF\Api\V1\Base_Api;
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
class Api_Migration_v4 extends Base_Api {

	/**
	 * Holds our log class
	 *
	 * @var \Monolog\Logger|LoggerInterface
	 *
	 * @since 5.2
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

	public function __construct( LoggerInterface $log,  Helper_Data $data) {
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
	public function register() {
		register_rest_route(
			self::ENTRYPOINT . '/' . self::VERSION,
			'/migration/multisite/',
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'ajax_multisite_v3_migration' ],

				'permission_callback' => function() {
					return $this->has_capabilities( 'manage_sites' );
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
			return new \WP_Error( 'ajax_multisite_v3_migration', 'You are not authorized to perform this action. Please try again.', [ 'status' => 401 ] );

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

			return new \WP_Error( 'no_configuration_file', 'No configuration.php file found for site #%s', [ 'status' => 404 ] );
		}

		/* Setup correct migration settings */
		switch_to_blog( $blog_id );
		$this->data->multisite_template_location = $path;

		/* Do migration */
		if ( $this->migrate_v3( $path ) ) {

			return [ 'message' => 'Migration completed successfully' ];

		} else {

			$return = [
				'error' => sprintf( esc_html__( 'Database import problem for site #%s', 'gravity-forms-pdf-extended' ), $blog_id ),
			];

			$this->log->addError( 'AJAX Endpoint Failed', $return );

			return new \WP_Error( 'unable_to_import', 'Database import problem for site #%s', [ 'status' => 422 ] );

		}

		$this->log->addError( 'AJAX Endpoint Failed' );

		return new \WP_Error( 'unable_to_connect_to_server', 'Internal Server Error', [ 'status' => 500 ] );
	}

	/**
	 * Does the migration and notice clearing (if unsuccessful)
	 *
	 * @param  string $path Path to the current site's template directory
	 *
	 * @return boolean
	 *
	 * @since    5.2
	 */
	private function migrate_v3( $path ) {

		$migration = new Helper_Migration(
			GPDFAPI::get_form_class(),
			GPDFAPI::get_log_class(),
			GPDFAPI::get_data_class(),
			GPDFAPI::get_options_class(),
			GPDFAPI::get_misc_class(),
			GPDFAPI::get_notice_class(),
			GPDFAPI::get_templates_class()
		);

		if ( $migration->begin_migration() ) {

			/**
			 * Migration Successful.
			 *
			 * If there was a problem removing the configuration file we'll automatically prevent the migration message displaying again
			 */
			if ( is_file( $path . 'configuration.php' ) ) {
				$this->dismiss_notice( 'migrate_v3_to_v4' );
			}

			return true;
		}

		return false;
	}

}
