<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Model\Model_Shortcodes;
use GFPDF\View\View_Shortcodes;
use Psr\Log\LoggerInterface;

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
 * Controller_PDF
 * Handles the PDF display and authentication
 *
 * @since 4.0
 */
class Controller_Shortcodes extends Helper_Abstract_Controller implements Helper_Interface_Filters {

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Setup our class by injecting all our dependencies
	 *
	 * @param Helper_Abstract_Model|Model_Shortcodes $model Our Shortcodes Model the controller will manage
	 * @param Helper_Abstract_View|View_Shortcodes   $view  Our Shortcodes View the controller will manage
	 * @param LoggerInterface                        $log   Our logger class
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Model $model, Helper_Abstract_View $view, LoggerInterface $log ) {

		/* Assign our internal variables */
		$this->log = $log;

		/* Load our model and view */
		$this->model = $model;
		$this->model->setController( $this );

		$this->view = $view;
		$this->view->setController( $this );
	}

	/**
	 * Initialise our class defaults
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function init() {
		$this->add_filters();
		$this->add_shortcodes();
	}

	/**
	 * Apply any filters needed for the settings page
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function add_filters() {

		add_filter( 'gform_confirmation', [ $this->model, 'gravitypdf_confirmation' ], 100, 3 );
		add_filter( 'gform_notification', [ $this->model, 'gravitypdf_notification' ], 100, 3 );
		add_filter( 'gform_admin_pre_render', [ $this->model, 'gravitypdf_redirect_confirmation' ] );
		add_filter( 'gform_confirmation', [ $this->model, 'gravitypdf_redirect_confirmation_shortcode_processing' ], 10, 3 );

		/* Basic GravityView Support */
		add_filter( 'gravityview/fields/custom/content_before', [ $this->model, 'gravitypdf_gravityview_custom' ], 10 );
	}


	/**
	 * Register our shortcodes
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function add_shortcodes() {
		add_shortcode( 'gravitypdf', [ $this->model, 'process' ] );
	}
}
