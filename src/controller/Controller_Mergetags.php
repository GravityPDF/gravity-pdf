<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Interface_Filters;

use Psr\Log\LoggerInterface;

/**
 * PDF Merge tag Controller
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
 * Controller_Mergetags
 * Handles the PDF display and authentication
 *
 * @since 4.1
 */
class Controller_Mergetags extends Helper_Abstract_Controller implements Helper_Interface_Filters {

	/**
	 * Setup our class by injecting all our dependancies
	 *
	 * @param Helper_Abstract_Model|\GFPDF\Model\Model_Shortcodes $model Our Shortcodes Model the controller will manage
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Model $model ) {

		/* Load our model and view */
		$this->model = $model;
		$this->model->setController( $this );
	}

	/**
	 * Initialise our class defaults
	 *
	 * @since 4.1
	 *
	 * @return void
	 */
	public function init() {
		$this->add_filters();
	}

	/**
	 * Apply any filters needed for the settings page
	 *
	 * @since 4.1
	 *
	 * @return void
	 */
	public function add_filters() {
		add_filter( 'gform_replace_merge_tags', [ $this->model, 'process_pdf_mergetags' ], 10, 4 );
		add_filter( 'gform_custom_merge_tags', [ $this->model, 'add_pdf_mergetags' ], 10, 2 );
	}
}
