<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller_Welcome_Screen
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class Controller_Welcome_Screen extends Helper_Abstract_Controller implements Helper_Interface_Actions, Helper_Interface_Filters {

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var \GFPDF\Helper\Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var \GFPDF\Helper\Helper_Abstract_Options
	 *
	 * @since 4.0
	 */
	protected $options;

	/**
	 * Setup our class by injecting all our dependencies
	 *
	 * @param Helper_Abstract_Model|\GFPDF\Model\Model_Welcome_Screen $model   Our Welcome Screen Model the controller will manage
	 * @param Helper_Abstract_View|\GFPDF\View\View_Welcome_Screen    $view    Our Welcome Screen View the controller will manage
	 * @param LoggerInterface                                         $log     Our logger class
	 * @param \GFPDF\Helper\Helper_Data                               $data    Our plugin data store
	 * @param \GFPDF\Helper\Helper_Abstract_Options                   $options Our options class which allows us to access any settings
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Model $model, Helper_Abstract_View $view, LoggerInterface $log, Helper_Data $data, Helper_Abstract_Options $options ) {

		/* Assign our internal variables */
		$this->log     = $log;
		$this->data    = $data;
		$this->options = $options;

		/* Load our model and view */
		$this->model = $model;
		$this->model->setController( $this );

		$this->view = $view;
		$this->view->setController( $this );
	}

	/**
	 * Initialise our class defaults
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function init() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Apply any actions needed for the welcome page
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function add_actions() {
		/* Load the welcome screen into the menu */
		add_action( 'admin_menu', [ $this->model, 'admin_menus' ] );
		add_action( 'admin_head', [ $this->model, 'hide_admin_menus' ] );
	}

	/**
	 * Apply any filters needed for the welcome page
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function add_filters() {
		add_filter( 'admin_title', [ $this->model, 'add_page_title' ], 10, 3 );
	}
}
