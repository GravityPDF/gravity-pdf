<?php

namespace GFPDF\Api;


use WP_REST_Request;

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
 * Interface CallableApiResponse
 *
 * For use in a class that handles the REST API callback which takes the WP_REST_Request class as a single argument
 *
 * @package GFPDF\Plugins\Previewer\API
 *
 * @since 0.1
 */
interface CallableApiResponse {

	/**
	 * The REST API callback
	 *
	 * @param $request
	 *
	 * @return mixed
	 *
	 * @since 0.1
	 */
	public function response( WP_REST_Request $request );
}
