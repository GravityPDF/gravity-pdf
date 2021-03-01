<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Model\Model_Shortcodes;

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
 * Controller_Mergetags
 * Handles the PDF display and authentication
 *
 * @since 4.1
 */
class Controller_Mergetags extends Helper_Abstract_Controller implements Helper_Interface_Filters {

	/**
	 * Setup our class by injecting all our dependencies
	 *
	 * @param Helper_Abstract_Model|Model_Shortcodes $model Our Shortcodes Model the controller will manage
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
	 * @return void
	 * @since 4.1
	 *
	 */
	public function init() {
		$this->add_filters();
	}

	/**
	 * Apply any filters needed for the settings page
	 *
	 * @return void
	 * @since 4.1
	 *
	 */
	public function add_filters() {
		add_filter( 'gform_replace_merge_tags', [ $this->model, 'process_pdf_mergetags' ], 10, 4 );
		add_filter( 'gform_custom_merge_tags', [ $this->model, 'add_pdf_mergetags' ], 10, 2 );
	}
}
