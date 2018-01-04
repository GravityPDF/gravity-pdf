<?php

namespace GFPDF\Helper;

use GF_Field;

/**
 * Extends the Helper_Field_Container class and disables
 * Gravity Forms CSS Ready Class support
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
 * @since 4.0
 */
class Helper_Field_Container_Void extends Helper_Field_Container {

	/*
	 * Empty method easily disables Helper_Field_Container functionality
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function generate( GF_Field $field ) {
		/* Do nothing */
	}

	/**
	 * Empty method easily disables Helper_Field_Container functionality
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function close() {
		/* Do nothing */
	}

	/**
	 * Empty method easily disables Helper_Field_Container functionality
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function does_fit_in_row( GF_Field $field ) {
		/* Do nothing */
	}

	/**
	 * Empty method easily disables Helper_Field_Container functionality
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function maybe_display_faux_column( GF_Field $field ) {
		/* Do nothing */
	}
}
