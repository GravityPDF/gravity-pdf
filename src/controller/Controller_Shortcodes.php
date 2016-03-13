<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Interface_Filters;

use Psr\Log\LoggerInterface;

/**
 * PDF Shortcode Controller
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
 * Controller_PDF
 * Handles the PDF display and authentication
 *
 * @since 4.0
 */
class Controller_Shortcodes extends Helper_Abstract_Controller implements Helper_Interface_Filters {

	/**
	 * Holds our log class
	 *
	 * @var \Monolog\Logger|LoggerInterface
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Setup our class by injecting all our dependancies
	 *
	 * @param Helper_Abstract_Model|\GFPDF\Model\Model_Shortcodes $model Our Shortcodes Model the controller will manage
	 * @param Helper_Abstract_View|\GFPDF\View\View_Shortcodes    $view  Our Shortcodes View the controller will manage
	 * @param \Monolog\Logger|LoggerInterface                     $log   Our logger class
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
	 * @since 4.0
	 *
	 * @return void
	 */
	public function init() {

		$this->add_filters();
		$this->add_shortcodes();

		/* Add support for the new shortcake UI currently being considered for core integration */
		if ( is_admin() ) {
			$this->add_shortcake_support();
		}
	}

	/**
	 * Apply any filters needed for the settings page
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function add_filters() {

		add_filter( 'gform_confirmation', array( $this->model, 'gravitypdf_confirmation' ), 10, 3 );
		add_filter( 'gform_notification', array( $this->model, 'gravitypdf_notification' ), 10, 3 );
		add_filter( 'gform_admin_pre_render', array( $this->model, 'gravitypdf_redirect_confirmation' ) );
	}


	/**
	 * Register our shortcodes
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function add_shortcodes() {
		add_shortcode( 'gravitypdf', array( $this->model, 'gravitypdf' ) );
	}


	/**
	 * Register our shortcake attributes
	 * See https://github.com/fusioneng/Shortcake for more details
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function add_shortcake_support() {

		/* Exist if the shortcake function doesn't exist */
		if ( ! function_exists( 'shortcode_ui_register_for_shortcode' ) ) {
			return null;
		}

		$this->log->addNotice( 'Enable Shortcake support.' );

		/* Enhance further */
		shortcode_ui_register_for_shortcode( 'gravitypdf', array(
			'label' => __( 'Gravity PDF', 'gravity-forms-pdf-extended' ),

			'listItemImage' => 'dashicons-admin-site',

			'attrs' => array(
				array(
					'label' => 'ID',
					'attr'  => 'id',
					'type'  => 'text',
				),

				array(
					'label' => 'Anchor Text',
					'attr'  => 'text',
					'type'  => 'text',
				),

				array(
					'label'   => 'View',
					'attr'    => 'type',
					'type'    => 'select',
					'default' => 'download',
					'options' => array(
						'download' => 'Download',
						'view'     => 'View',
					),
				),

				array(
					'label'       => 'Anchor Class',
					'attr'        => 'class',
					'type'        => 'text',
					'description' => 'Optional. Add any class name – separated by a space – you want to apply to the PDF link.',
					'meta'        => array(
						'placeholder' => '',
					),
				),

				array(
					'label'       => 'Entry ID',
					'attr'        => 'entry',
					'type'        => 'text',
					'description' => 'Optional. You can pass in a specific ID or let us auto select one.',
					'meta'        => array(
						'placeholder' => '',
					),
				),
			),
		) );
	}
}
