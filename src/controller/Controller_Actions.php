<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_View;

/**
 * Actions Controller
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
 * Controller_Actions
 * Controller to trigger anything that requires a one-time user interaction
 * Examples include a configuration importer, or a promo
 *
 * @since 4.0
 */
class Controller_Actions extends Helper_Abstract_Controller implements Helper_Interface_Actions, Helper_Interface_Filters
{
	/**
	 * Load our model and view and required actions
	 */
	public function __construct( Helper_Abstract_Model $model, Helper_Abstract_View $view ) {
		/* load our model and view */
		$this->model = $model;
		$this->model->setController( $this );

		$this->view  = $view;
		$this->view->setController( $this );
	}

	/**
	 * Initialise our class defaults
	 * @since 4.0
	 * @return void
	 */
	public function init() {
		 $this->add_actions();
		 $this->add_filters();
	}

	/**
	 * Apply any actions
	 * @since 4.0
	 * @return void
	 */
	public function add_actions() {
		add_action( 'admin_init', array( $this, 'route' ) );
		add_action( 'admin_init', array( $this, 'route_notices' ), 20 ); /* Run later than our route check */
	}

	/**
	 * Apply any filters
	 * @since 4.0
	 * @return void
	 */
	public function add_filters() {

	}

	/**
	 * Holds our one-time action routes
	 *
	 * Routes should contain the following keys: action, action_text, condition, process, view, capability
	 * action: The action ID to be processed
	 * action_text: The text used in our main button
	 * condition: The function or method to call to determine if a notice should be displayed (Boolean)
	 * process: The function to handle a successful action. On success the disable_route() method should be called
	 * view: The function used to display the notice content
	 * @return Array
	 * @since 4.0
	 */
	public function get_routes() {

		$routes = array(
			array(
				'action'     => 'review_plugin',
				'action_text' => __( 'Review Submitted', 'gravitypdf' ),
				'condition'  => array( $this->model, 'review_condition' ),
				'process'    => array( $this->model, 'dismiss_notice' ),
				'view'       => array( $this->view, 'review_plugin' ),
				'capability' => 'gravityforms_view_settings',
			),
		);

		return apply_filters( 'gfpdf_one_time_action_routes', $routes );
	}

	/**
	 * Setup our route notices, if they should be enabled
	 * @return void
	 * @since 4.0
	 */
	public function route_notices() {
		global $gfpdf;

		foreach($this->get_routes() as $route) {

			/* Before displaying check the user has the correct capabilities, the notice isn't already been dismissed and the route condition has been met */
			if( $gfpdf->form->has_capability( $route['capability'] ) &&
				! $this->model->is_notice_already_dismissed( $route['action'] ) &&
				call_user_func( $route['condition'] ) ) {

				$gfpdf->log->addNotice( __CLASS__ . '::' . __METHOD__ . '(): ' . 'Trigger Action Notification.', array( 'route' => $route ) );
				$gfpdf->notices->add_notice( call_user_func($route['view'], $route['action'], $route['action_text'] ) );
			}
		}
	}

	/**
	 * Run approprate events
	 * @return void
	 * @since 4.0
	 */
	public function route() {
		global $gfpdf;

		foreach($this->get_routes() as $route) {

			/* Check we have a valid action and the display condition is true */
			if( rgpost('action') == 'gfpdf_' . $route['action'] && call_user_func( $route['condition'] ) ) {

				/* Check user capability */
				if( ! $gfpdf->form->has_capability( $route['capability'] ) ) {
			
					$gfpdf->log->addCritical( __CLASS__ . '::' . __METHOD__ . '(): ' . 'Lack of User Capabilities.', array(
						'user'      => wp_get_current_user(),
						'user_meta' => get_user_meta( get_current_user_id() )
					) );

					wp_die( __( 'You do not have permission to access this page', 'gravitypdf' ) );
				}

				/* Check nonce is valid */
				if ( ! wp_verify_nonce( rgpost( 'gfpdf_action_' . $route['action'] ), 'gfpdf_action_' . $route['action'] ) ) {
					
					$gfpdf->log->addWarning( __CLASS__ . '::' . __METHOD__ . '(): ' . 'Nonce Verification Failed.' );
					$gfpdf->notices->add_error( __( 'There was a problem processing the action. Please try again.', 'gravitypdf' ) );

					continue;
				}

				/* Check if the user wants to dismiss the notice, otherwise process the route */
				if( isset( $_POST['gfpdf-dismiss-notice'] ) ) {
					$gfpdf->log->addNotice( __CLASS__ . '::' . __METHOD__ . '(): ' . 'Dismiss Action.', array( 'route' => $route ) );
					$this->model->dismiss_notice( $type );
				} else {
					$gfpdf->log->addNotice( __CLASS__ . '::' . __METHOD__ . '(): ' . 'Trigger Action Process.', array( 'route' => $route ) );
					call_user_func( $route['process'], $route['action'], $route );
				}
			}
		}
	}

}
