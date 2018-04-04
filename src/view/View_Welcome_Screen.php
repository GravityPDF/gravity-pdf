<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Abstract_Form;

/**
 * Welcome Screen View
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
 * View_Welcome_Screen
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class View_Welcome_Screen extends Helper_Abstract_View {

	/**
	 * Set the view's name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_type = 'Welcome';

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var \GFPDF\Helper\Helper_Form
	 *
	 * @since 4.0
	 */
	protected $gform;

	/**
	 * Setup our class by injecting all our dependancies
	 *
	 * @param array                                          $data_cache An array of data to pass to the view
	 * @param \GFPDF\Helper\Helper_Form|Helper_Abstract_Form $gform      Our abstracted Gravity Forms helper functions
	 *
	 * @since 4.0
	 */
	public function __construct( $data_cache = [], Helper_Abstract_Form $gform ) {

		/* Call our parent constructor */
		parent::__construct( $data_cache );

		/* Assign our internal variables */
		$this->gform = $gform;
	}

	/**
	 * Output the welcome screen
	 *
	 * @since 4.0
	 */
	public function welcome() {

		/* Load any variables we want to pass to our view */
		$args = [
			'forms' => $this->gform->get_forms(),
		];

		/* Render our view */
		$this->load( 'welcome', $args );

	}
}
