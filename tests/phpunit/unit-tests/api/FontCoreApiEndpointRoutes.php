<?php

namespace GFPDF\Api\V1\Fonts\Core;
// GFPDF\Tests\GravityPDF;

use GFPDF\Api\V1\Base_Api;
use GFPDF\Api\V1\Fonts\Core;
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
 * Class TestFontCoreApiEndpoint
 *
 * @package GFPDF\Tests\GravityPDF
 *
 * @group   REST-API
 */
class TestFontCoreApiEndpointRoutes extends WP_UnitTestCase {

	/**
	 * @var $class
	 *
	 * @since 5.2
	 */
	protected $class;
	/**
	 * Setup the REST API CORE FONT Endpoints
	 *
	 * @since 5.2
	 */
    public function setUp() {
    	
		$this->log = GPDFAPI::get_log_class();

        $this->class = new Api_Fonts_Core( $this->log, '' );
        $this->class->init();

        parent::setUp();
    }

    /**
     * Test our endpoints are registered correctly
     *
     * @since 5.2
     */
    public function test_rest_api_font_core_endpoints() {
        $wp_rest_server = rest_get_server();
        do_action( 'rest_api_init' );

        $this->assertContains( 'gravity-pdf/v1', $wp_rest_server->get_namespaces() );
        $this->assertArrayHasKey( '/gravity-pdf/v1/fonts/core', $wp_rest_server->get_routes() );
    }

	/**
	 * @since 5.2
	 */
    public function test_save_core_font() {

        $request = new WP_REST_Request( \WP_REST_Server::CREATABLE, '/gravity-pdf/v1/fonts/core' );

        $request->set_body_params( [
            'font_name' => '',
        ] );

        /* Test empty font name */
        $response = $this->class->save_core_font( $request );

        if ( is_wp_error( $response ) ) {
	        $res = $response->get_error_data( 'download_and_save_font' );
            $this->assertSame( 400, $res['status'] );
        }

        /* Mock remote request and simulate success */
        $request->set_body_params( [
            'font_name' => 'Test',
        ] );

        $api_response = function() {
            return new WP_Error();
        };

        add_filter( 'pre_http_request', $api_response );

        $response = $this->class->save_core_font( $request );
        $this->assertTrue( is_wp_error( $response ) );

        remove_filter( 'pre_http_request', $api_response );

    }

    protected function stub_remote_request( $response ) {

    }

    protected function unstub_remote_request() {

    }

}
