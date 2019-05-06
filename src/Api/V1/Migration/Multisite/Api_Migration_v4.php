<?php

namespace GFPDF\Api\V1\Migration\Multisite;

use GFPDF\Api\V1\Base_Api;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Migration;
use Psr\Log\LoggerInterface;

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
 * Class ApiMigrationv4Endpoint
 *
 * @package GFPDF\Plugins\GravityPDF\API
 */
class Api_Migration_v4 extends Base_Api {

	/**
	 * Holds our log class
	 *
	 * @var \Monolog\Logger
	 *
	 * @since 4.0
	 */
	public $log;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var \GFPDF\Helper\Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	protected $options;

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
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var \GFPDF\Helper\Helper_Migration
	 *
	 * @since 5.2
	 */
	protected $migration;

	/**
	 * Api_Migration_v4 constructor.
	 *
	 * @param Helper_Abstract_Options $options
	 * @param Helper_Data $data
	 * @param Helper_Migration $migration	 
	 *  
	 * @since 5.2
	 */
	public function __construct( LoggerInterface $log, Helper_Abstract_Options $options, Helper_Data $data, Helper_Migration $migration ) {		
		$this->log 	      = $log;
		$this->options    = $options;
		$this->data       = $data;
		$this->migration  = $migration;
	}

	/**
	 * Register our Multisite Migration endpoint
	 *
	 * @Internal Use this endpoint register multisite migration
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

			$this->log->addError( 'Configuration file not found', $path );

			return new \WP_Error( 'no_configuration_file', 'No configuration file found for site #%s', [ 'status' => 404 ] );
		}

		/* Setup correct migration settings */
		switch_to_blog( $blog_id );
		$this->data->multisite_template_location = $path;

		/* Do migration */
		if ( ! $this->migrate_v3( $path ) ) {

			$this->log->addError( 'AJAX Endpoint Failed' );

			return new \WP_Error( 'unable_to_connect_to_server', 'Database import problem for site #%s', [ 'status' => 500 ] );
		} 

		/* migration successful */
		return [ 'message' => 'Migration completed successfully' ];
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

		/* Start migration */
		if ( $this->migration->begin_migration() ) {
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

	/**
	 * Mark the current notice as being dismissed
	 *
	 * @param  string $type The current notice ID
	 *
	 * @return void
	 *
	 * @since 5.2
	 */
	public function dismiss_notice( $type ) {

		$dismissed_notices          = $this->options->get_option( 'action_dismissal', [] );
		$dismissed_notices[ $type ] = $type;
		$this->options->update_option( 'action_dismissal', $dismissed_notices );
	}	

}
