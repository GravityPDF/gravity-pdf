<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Abstract_Form;

/**
 * Welcome Screen View
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
 * View_Welcome_Screen
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class View_Welcome_Screen extends Helper_Abstract_View
{

	/**
	 * Set the view's name
	 * @var string
	 * @since 4.0
	 */
	protected $ViewType = 'Welcome';

	/**
	 * Holds abstracted functions related to the forms plugin
	 * @var Object
	 * @since 4.0
	 */
	protected $form;

	public function __construct( $data = array(), Helper_Abstract_Form $form ) {
		$this->data = $data;

		/* Assign our internal variables */
		$this->form        = $form;
	}

	/**
	 * Load the Welcome Tab tabs
	 * @since 4.0
	 */
	public function tabs() {

		/* Load any variables we want to pass to our view */
		$args = array(
			'selected' => isset( $_GET['page'] ) ? $_GET['page'] : 'gfpdf-getting-started',
		);

		$args = array_merge( $args, $this->data );

		/* load the tabs view */
		$this->load( 'tabs', $args );
	}

	/**
	 * Output the welcome screen
	 * @since 4.0
	 */
	public function welcome() {

		/* Load any variables we want to pass to our view */
		$args = array(
			'forms' => $this->form->get_forms(),
		);

		$args = array_merge( $args, $this->data );

		/* Render our view */
		$this->load( 'welcome', $args );

	}
}
