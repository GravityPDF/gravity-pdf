<?php

namespace GFPDF\Helper;

/**
 * Actions Interface
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

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
 * A simple interface to standardise how actions and filters should be applied in classes
 *
 * @since 4.0
 */
interface Helper_Interface_Config {

	/**
	 * Classes should return a key => value array with the template settings
	 * The array should be multidimensional with the top-level keys being either "core" or "fields"
	 * The "core" array will allow boolean values to be passed to enable core features, such as "headers", "footers" or "backgrounds"
	 * The "fields" array allows a template to load in custom fields. It is based on our \GFPDF\Helper\Helper_Abstract_Options Settings API
	 * See the Helper_Options_Fields::register_settings() method for the exact fields that can be passed in
	 *
	 * @since 4.0
	 *
	 * @return array
	 */
	public function configuration();
}
