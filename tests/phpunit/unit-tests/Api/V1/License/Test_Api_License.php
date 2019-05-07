<?php

namespace GFPDF\Api\V1\License;

use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Abstract_Addon;
use GFPDF\Helper\Helper_Logger;
use GFPDF\Helper\Helper_Singleton;
use WP_UnitTestCase;
use WP_REST_Request;
use GPDFAPI;

/**
 * @package     Gravity PDF GravityPDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF GravityPDF.

    Copyright (C) 2018, Blue Liquid Designs

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
 * Class TestApiLicense
 *
 * @package GFPDF\Tests\GravityPDF
 *
 * @group   REST-API
 */
class TestApiLicense extends WP_UnitTestCase {

	/**
	 * @var Api_License
	 * @since 5.2
	 */
	protected $class;

	/**
	 * @var Helper_Data
	 */
	protected $data;

	/**
	 * @since 5.2
	 */
	public function setUp() {
		$this->data  = GPDFAPI::get_data_class();
		$this->class = new Api_License( GPDFAPI::get_log_class(), $this->data );
		$this->class->init();

		parent::setUp();
	}

	/**
	 * @since 5.2
	 */
	public function test_rest_api_license_endpoints() {
		$wp_rest_server = rest_get_server();
		do_action( 'rest_api_init' );

		$this->assertContains( 'gravity-pdf/v1', $wp_rest_server->get_namespaces() );
		$this->assertArrayHasKey( '/gravity-pdf/v1/license/(?P<id>\d+)/deactivate', $wp_rest_server->get_routes() );
	}

	/**
	 * @param array $data
	 *
	 * @return WP_REST_Request
	 *
	 * @since 5.2
	 */
	protected function get_request( $data ) {
		$request = new WP_REST_Request();
		$request->set_body( json_encode( $data ) );
		$request->set_header( 'content-type', 'application/json' );
		$request->get_json_params();

		return $request;
	}

	/**
	 * @since 5.2
	 */
	public function test_deactivate_license_key_validation() {
		$request  = $this->get_request( [ 'addon_name' => '', 'license' => '' ] );
		$response = $this->class->process_license_deactivation( $request );
		$this->assertSame( 400, $response->get_error_data( 'license_deactivation_fields_missing' )['status'] );

		/* Test unregistered addon */
		$request  = $this->get_request( [ 'addon_name' => 'test', 'license' => '12345' ] );
		$response = $this->class->process_license_deactivation( $request );
		$this->assertSame( 404, $response->get_error_data( 'license_deactivation_addon_not_found' )['status'] );
	}

	/**
	 * @since 5.2
	 */
	public function test_deactivate_license_key_api() {
		$request = $this->get_request( [ 'addon_name' => 'test', 'license' => '12345' ] );

		/* Mock remote request and simulate success */
		$this->data->addon['test'] = new TestAddon( 'test', 'Test', 'Gravity PDF', '1.0', '', $this->data, GPDFAPI::get_options_class(), new Helper_Singleton(), new Helper_Logger( 'test', 'Test' ), GPDFAPI::get_notice_class() );

		$api_response = function() {
			return new \WP_Error();
		};

		add_filter( 'pre_http_request', $api_response );

		$response = $this->class->process_license_deactivation( $request );
		$this->assertSame( 400, $response->get_error_data( 'license_deactivation_schedule_license_check' )['status'] );

		remove_filter( 'pre_http_request', $api_response );

		/* Get a success test */
		$api_response = function() {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [ 'license' => 'deactivated' ] ),
			];
		};

		add_filter( 'pre_http_request', $api_response );
		$response = $this->class->process_license_deactivation( $request );
		$this->assertArrayHasKey( 'success', $response );
		remove_filter( 'pre_http_request', $api_response );
	}
}

class TestAddon extends Helper_Abstract_Addon {
	public function plugin_updater() {

	}
}