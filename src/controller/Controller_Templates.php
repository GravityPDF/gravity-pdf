<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Interface_Actions;


/**
 * The Template Nanagement controller
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
 * Controller_Templates
 * A general class for handling AJAX template actions
 *
 * @since 4.1
 */
class Controller_Templates extends Helper_Abstract_Controller implements Helper_Interface_Actions {

	/**
	 * Controller_Templates constructor.
	 *
	 * Setup our class by injecting all our dependancies
	 *
	 * @param Helper_Abstract_Model $model
	 *
	 * @since 4.1
	 */
	public function __construct( Helper_Abstract_Model $model ) {
		/* Load our model */
		$this->model = $model;
		$this->model->setController( $this );
	}

	/**
	 * Setup our class
	 *
	 * @since 4.1
	 */
	public function init() {
		$this->add_actions();
	}

	/**
	 * Add AJAX hooks for templates
	 *
	 * @since 4.1
	 */
	public function add_actions() {
		/* Add AJAX endpoints */
		add_action( 'wp_ajax_gfpdf_upload_template', [ $this->model, 'ajax_process_uploaded_template' ] );
		add_action( 'wp_ajax_gfpdf_delete_template', [ $this->model, 'ajax_process_delete_template' ] );
		add_action( 'wp_ajax_gfpdf_get_template_options', [ $this->model, 'ajax_process_build_template_options_html' ] );
	}
}