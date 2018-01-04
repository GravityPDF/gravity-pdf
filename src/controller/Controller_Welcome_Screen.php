<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Abstract_Options;

use Psr\Log\LoggerInterface;

/**
 * Welcome Screen Controller
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
 * Controller_Welcome_Screen
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class Controller_Welcome_Screen extends Helper_Abstract_Controller implements Helper_Interface_Actions, Helper_Interface_Filters {

	/**
	 * Holds our log class
	 *
	 * @var \Monolog\Logger|LoggerInterface
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
	 * Setup our class by injecting all our dependancies
	 *
	 * @param Helper_Abstract_Model|\GFPDF\Model\Model_Welcome_Screen $model   Our Welcome Screen Model the controller will manage
	 * @param Helper_Abstract_View|\GFPDF\View\View_Welcome_Screen    $view    Our Welcome Screen View the controller will manage
	 * @param \Monolog\Logger|LoggerInterface                         $log     Our logger class
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