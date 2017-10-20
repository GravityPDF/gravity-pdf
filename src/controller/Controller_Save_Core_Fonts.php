<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;

use Psr\Log\LoggerInterface;

/**
 * Save Core Fonts Controller
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2017, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2017, Blue Liquid Designs

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
 * Class Controller_Save_Core_Fonts
 *
 * @package GFPDF\Controller
 *
 * @since   5.0
 */
class Controller_Save_Core_Fonts extends Helper_Abstract_Controller implements Helper_Interface_Actions {

	/**
	 * @var string
	 *
	 * @since 5.0
	 */
	protected $github_repo = 'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/master/';

	/**
	 * Set up our dependancies
	 *
	 * @param Helper_Abstract_View|\GFPDF\View\View_Save_Core_Fonts $view Our Actions View the controller will manage
	 * @param \Monolog\Logger|LoggerInterface                       $log  Our logger class
	 * @param \GFPDF\Helper\Helper_Data                             $data Our plugin data store
	 * @param \GFPDF\Helper\Helper_Misc                             $misc Our miscellaneous class
	 *
	 * @since 5.0
	 */
	public function __construct( Helper_Abstract_View $view, LoggerInterface $log, Helper_Data $data, Helper_Misc $misc ) {
		/* Assign our internal variables */
		$this->log  = $log;
		$this->data = $data;
		$this->misc = $misc;

		$this->view = $view;
		$this->view->setController( $this );
	}

	/**
	 * Initialise our class defaults
	 *
	 * @since 5.0
	 *
	 * @return void
	 */
	public function init() {
		$this->add_actions();
	}

	/**
	 * Apply any actions needed for the welcome page
	 *
	 * @since 5.0
	 *
	 * @return void
	 */
	public function add_actions() {
		/* Register our AJAX event */
		add_action( 'wp_ajax_gfpdf_save_core_font', [ $this, 'save_core_font' ] );

		/* Add custom setting callbacks */
		add_action( 'gfpdf_install_core_fonts', [ $this->view, 'core_fonts_setting' ] );
	}

	/**
	 * An AJAX endpoint that handles authentication and downloading the core font
	 *
	 * @since 5.0
	 *
	 * @return void
	 */
	public function save_core_font() {
		/* User / CORS validation */
		$this->misc->handle_ajax_authentication( 'Save Core Font', 'gravityforms_edit_settings' );

		/* Download and save our font */
		$fontname = isset( $_POST['font_name'] ) ? $_POST['font_name'] : '';
		$results  = $this->download_and_save_font( $fontname );

		/* Return results */
		header( 'Content-Type: application/json' );
		echo json_encode( $results );
		wp_die();
	}

	/**
	 * Stream files from remote server and save them locally
	 *
	 * @param $fontname
	 *
	 * @since 5.0
	 *
	 * @return bool
	 */
	protected function download_and_save_font( $fontname ) {

		/* Only the font name is passed via AJAX. The Repo we download from is fixed (prevent security issues) */
		$res = wp_remote_get( $this->github_repo . $fontname, [
			'timeout'  => 60,
			'stream'   => true,
			'filename' => $this->data->template_font_location . $fontname,
		] );

		$res_code = wp_remote_retrieve_response_code( $res );

		/* Check for errors and log them to file */
		if ( is_wp_error( $res ) ) {
			$this->log->addError( 'Core Font Download Failed', [
				'name'             => $fontname,
				'WP_Error_Message' => $res->get_error_message(),
				'WP_Error_Code'    => $res->get_error_code(),
			] );

			return false;
		}

		if ( $res_code != '200' ) {
			$this->log->addError( 'Core Font API Response Failed', [
				'response_code' => wp_remote_retrieve_response_code( $res ),
			] );

			return false;
		}

		/* If we got here, the call was successfull */

		return true;
	}
}