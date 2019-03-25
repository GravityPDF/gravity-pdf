<?php

namespace GFPDF\Api\V1\Fonts\Core;

use GFPDF\Api\CallableApiResponse;

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
					return current_user_can( '' );
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
		return new \WP_Error( 'some_error_code', 'Some error message', [ 'status' => 400 ] );
	}
}
