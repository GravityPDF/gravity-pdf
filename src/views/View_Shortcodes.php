<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;

/**
 * Shortcode View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

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
 * Controls the Gravity PDF Shortcode View / Display
 * @since 4.0
 */
class View_Shortcodes extends Helper_Abstract_View
{
	/**
	 * Set the view's name
	 * @var string
	 * @since 4.0
	 */
	protected $ViewType = 'Shortcodes';


	public function __construct( $data = array() ) {
		$this->data = $data;
	}

	/**
	 * Shortcode Error: Entry ID not passed through the shortcode - directly or through the URL.
	 * @return String The error message
	 * @since 4.0
	 */
	public function no_entry_id() {
		return $this->load( 'no_entry_id', array(), false );
	}

	/**
	 * Shortcode Error: Entry ID, Form ID or PDF ID mismatch. Cannot get PDF configuration.
	 * @return String The error message
	 * @since 4.0
	 */
	public function invalid_pdf_config() {
		return $this->load( 'invalid_pdf_config', array(), false );
	}

	/**
	 * Generate the Gravity PDF link
	 * @param  Array $attr The parameters
	 * @return String       The Shortcode Markup
	 */
	public function display_gravitypdf_shortcode( $attr ) {
		return $this->load( 'gravitypdf', $attr, false );
	}
}
