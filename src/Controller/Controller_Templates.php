<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Interface_Actions;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	 * Setup our class by injecting all our dependencies
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
