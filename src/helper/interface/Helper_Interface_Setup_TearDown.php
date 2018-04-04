<?php

namespace GFPDF\Helper;

/**
 * The PDF template Setup / Tear Down interface
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
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
 * A simple interface to standardise how install and delete functionality is executed
 *
 * @since 4.0
 */
interface Helper_Interface_Setup_TearDown {

	/**
	 * This method will be triggered when a new PDF template is installed.
	 * It should contain any additional install code required.
	 *
	 * @return void
	 *
	 * @since 4.1
	 */
	public function setUp();

	/**
	 * This method will be triggered when a PDF template is deleted.
	 * It should contain any additional delete code required, like the removal
	 * of non-core files (i.e anything besides the template, template image and template config)
	 *
	 * @return void
	 *
	 * @since 4.1
	 */
	public function tearDown();
}
