<?php

namespace GFPDF\Api\V1\Fonts\Core;
// GFPDF\Tests\GravityPDF;

use GFPDF\Api\V1\Base_Api;
use GFPDF\Api\V1\Fonts\Core;
use WP_UnitTestCase;
use WP_REST_Request;

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
	 * @var Helper_Logger
	 *
	 * @since 5.2
	 */
	private $log;

	/**
	 * @var string
	 *
	 * @since 5.2
	 */
	protected $template_font_location;

	/**
	 * @var PdfViewerApiResponse
	 *
	 * @since 0.1
	 */
	protected $class;
	/**
	 * Setup the REST API CORE FONT Endpoints
	 *
	 * @since 5.2
	 */
	public function setUp() {

		$wp_rest_server = rest_get_server();

		$this->log = new \Monolog\Logger( 'test' );

		$api1 = new Api_Fonts_Core( $this->log , $this->template_font_location );
		
		$api1->init();

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

		$this->assertContains( 'gravity-pdf/v1/', $wp_rest_server->get_namespaces() );

		$routes = $wp_rest_server->get_routes();

		$this->assertArrayHasKey( '/gravity-pdf/v1/fonts/core/', $routes );
		
	}

}
