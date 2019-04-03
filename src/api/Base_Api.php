<?php

namespace GFPDF\Api\V1;

use GFPDF\Helper\Helper_Trait_Logger;

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
 * Class Base_Api
 *
 * @package GFPDF\Api\V1
 *
 * @since 5.2
 */
abstract class Base_Api {

	/**
	 * @since 5.2
	 */
	use Helper_Trait_Logger;

	/**
	 * @since 5.2
	 */
	const ENTRYPOINT = 'gravitypdf';

	/**
	 * @since 5.2
	 */
	const VERSION = 'v1';

	/**
	 * Initialise our endpoint
	 *
	 * @since 5.2
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register' ] );
	}

	/**
	 * Simple wrapper to check the current user's capabilities in the context of Gravity Forms
	 *
	 * @param string $capability
	 *
	 * @return bool
	 *
	 * @since 5.2
	 */
	protected function has_capabilities( $capability ) {
		$gform = \GPDFAPI::get_form_class();
		return $gform->has_capability( $capability );
	}

	/**
	 * Register WordPress REST API endpoint(s)
	 *
	 * @return void
	 *
	 * @internal Use `register_rest_route()` to register WordPress REST API endpoint(s)
	 *
	 * @since 5.2
	 */
	abstract public function register();
}
