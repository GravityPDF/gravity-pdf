<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;

use Psr\Log\LoggerInterface;


/**
 * PDF Display Controller
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
 * Controller_PDF
 * Handles the PDF display and authentication
 *
 * @since 4.0
 */
class Controller_PDF extends Helper_Abstract_Controller implements Helper_Interface_Actions, Helper_Interface_Filters {

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var \GFPDF\Helper\Helper_Form
	 *
	 * @since 4.0
	 */
	protected $gform;

	/**
	 * Holds our log class
	 *
	 * @var \Monolog\Logger|LoggerInterface
	 *
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var \GFPDF\Helper\Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Setup our class by injecting all our dependancies
	 *
	 * @param Helper_Abstract_Model|\GFPDF\Model\Model_PDF $model Our PDF Model the controller will manage
	 * @param Helper_Abstract_View|\GFPDF\View\View_PDF    $view  Our PDF View the controller will manage
	 * @param \GFPDF\Helper\Helper_Abstract_Form           $gform Our abstracted Gravity Forms helper functions
	 * @param \Monolog\Logger|LoggerInterface              $log   Our logger class
	 * @param \GFPDF\Helper\Helper_Misc                    $misc  Our miscellaneous class
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Model $model, Helper_Abstract_View $view, Helper_Abstract_Form $gform, LoggerInterface $log, Helper_Misc $misc ) {

		/* Assign our internal variables */
		$this->gform = $gform;
		$this->log   = $log;
		$this->misc  = $misc;

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
		/*
         * Tell Gravity Forms to add our form PDF settings pages
         */
		$this->add_actions();
		$this->add_filters();

		/* Add scheduled tasks */
		if ( ! wp_next_scheduled( 'gfpdf_cleanup_tmp_dir' ) ) {
			wp_schedule_event( time(), 'daily', 'gfpdf_cleanup_tmp_dir' );
		}
	}

	/**
	 * Apply any actions needed for the settings page
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function add_actions() {
		/* Process PDF if needed */
		add_action( 'parse_request', [ $this, 'process_legacy_pdf_endpoint' ] ); /* legacy PDF endpoint */
		add_action( 'parse_request', [ $this, 'process_pdf_endpoint' ] ); /* new PDF endpoint */

		/* Display PDF links in Gravity Forms Admin Area */
		add_action( 'gform_entries_first_column_actions', [ $this->model, 'view_pdf_entry_list' ], 10, 4 );
		add_action( 'gform_entry_info', [ $this->model, 'view_pdf_entry_detail' ], 10, 2 );

		/* Add save PDF filter */
		add_action( 'gform_after_submission', [ $this->model, 'maybe_save_pdf' ], 10, 2 );

		/* Clean-up actions */
		add_action( 'gform_after_submission', [ $this->model, 'cleanup_pdf' ], 9999, 2 );
		add_action( 'gform_after_update_entry', [ $this->model, 'cleanup_pdf_after_submission' ], 9999, 2 );
		add_action( 'gfpdf_cleanup_tmp_dir', [ $this->model, 'cleanup_tmp_dir' ] );
	}

	/**
	 * Apply any filters needed for the settings page
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function add_filters() {
		/* PDF authentication middleware */
		add_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_public_access' ], 10, 3 );
		add_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_active' ], 20, 3 );
		add_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_conditional' ], 30, 3 );
		add_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_owner_restriction' ], 40, 3 );
		add_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_logged_out_timeout' ], 50, 3 );
		add_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_auth_logged_out_user' ], 60, 3 );
		add_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_user_capability' ], 70, 3 );

		/* Field display middleware */
		add_filter( 'gfpdf_field_middleware', [ $this->model, 'field_middle_exclude' ], 10, 5 );
		add_filter( 'gfpdf_field_middleware', [ $this->model, 'field_middle_conditional_fields' ], 10, 5 );
		add_filter( 'gfpdf_field_middleware', [ $this->model, 'field_middle_product_fields' ], 10, 5 );
		add_filter( 'gfpdf_field_middleware', [ $this->model, 'field_middle_html_fields' ], 10, 5 );
		add_filter( 'gfpdf_field_middleware', [ $this->model, 'field_middle_blacklist' ], 10, 7 );

		/* Tap into GF notifications */
		add_filter( 'gform_notification', [
			$this->model,
			'notifications',
		], 9999, 3 ); /* ensure Gravity PDF is one of the last filters to be applied */

		/* Modify mPDF's path locations */
		add_filter( 'mpdf_tmp_path', [ $this->model, 'mpdf_tmp_path' ] );
		add_filter( 'mpdf_fontdata_path', [ $this->model, 'mpdf_tmp_font_path' ] );

		/* Change mPDF settings */
		add_filter( 'mpdf_current_font_path', [ $this->model, 'set_current_pdf_font' ], 10, 2 );
		add_filter( 'mpdf_font_data', [ $this->model, 'register_custom_font_data_with_mPDF' ] );
		add_filter( 'mpdf_font_data', [ $this->model, 'add_unregistered_fonts_to_mPDF' ], 20 );
		add_filter( 'gfpdf_mpdf_init_class', [ $this->model, 'set_watermark_font' ], 10, 4 );

		/* Process mergetags and shortcodes in PDF */
		add_filter( 'gfpdf_pdf_html_output', [ $this->gform, 'process_tags' ], 10, 3 );
		add_filter( 'gfpdf_pdf_html_output', 'do_shortcode' );

		add_filter( 'gfpdf_pdf_core_template_html_output', [ $this->gform, 'process_tags' ], 10, 3 );

		/* Backwards compatibility for our Tier 2 plugin */
		add_filter( 'gfpdfe_pre_load_template', [ 'PDFRender', 'prepare_ids' ], 1, 8 );

		/* Pre-process our template arguments and automatically render them in PDF */
		add_filter( 'gfpdf_template_args', [ $this->model, 'preprocess_template_arguments' ] );
		add_filter( 'gfpdf_pdf_html_output', [ $this->view, 'autoprocess_core_template_options' ], 5, 4 );

		/* Cleanup filters */
		add_filter( 'gform_before_resend_notifications', [ $this->model, 'resend_notification_pdf_cleanup' ], 10, 2 );

		/* GravityView */
		add_filter( 'gravityview/internal/ignored_endpoints', [ $this->model, 'fix_gravityview_frontpage_conflict' ] );
	}

	/**
	 * Determines if we should process the PDF at this stage
	 * Fires just before the main WP_Query is executed (we don't need it)
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function process_pdf_endpoint() {

		/* exit early if all the required URL parameters aren't met */
		if ( empty( $GLOBALS['wp']->query_vars['gpdf'] ) || empty( $GLOBALS['wp']->query_vars['pid'] ) || empty( $GLOBALS['wp']->query_vars['lid'] ) ) {
			return null;
		}

		$pid    = $GLOBALS['wp']->query_vars['pid'];
		$lid    = (int) $GLOBALS['wp']->query_vars['lid'];
		$action = ( ( isset( $GLOBALS['wp']->query_vars['action'] ) ) && $GLOBALS['wp']->query_vars['action'] == 'download' ) ? 'download' : 'view';

		$this->log->addNotice( 'Processing PDF endpoint.', [
			'pid'    => $pid,
			'lid'    => $lid,
			'action' => $action,
		] );

		/*  Send to our model to handle validation / authentication */
		$results = $this->model->process_pdf( $pid, $lid, $action );

		/* if error, display to user */
		if ( is_wp_error( $results ) ) {
			$this->pdf_error( $results );
		}
	}

	/**
	 * Determines if we should process the legacy PDF endpoint at this stage (the one with $_GET variables)
	 * Fires just before the main WP_Query is executed (we don't need it)
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function process_legacy_pdf_endpoint() {

		/* exit early if all our required parameters aren't met */
		if ( empty( $_GET['gf_pdf'] ) || empty( $_GET['fid'] ) || empty( $_GET['lid'] ) || empty( $_GET['template'] ) ) {
			return null;
		}

		$config = [
			'lid'      => $_GET['lid'],
			'fid'      => (int) $_GET['fid'],
			'aid'      => ( isset( $_GET['aid'] ) ) ? (int) $_GET['aid'] : false,
			'template' => substr( $_GET['template'], 0, -4 ), /* strip .php from the template name */
			'action'   => ( isset( $_GET['download'] ) ) ? 'download' : 'view',
		];

		$this->log->addNotice( 'Processing Legacy PDF endpoint.', [
			'config' => $config,
		] );

		/* Attempt to find a valid config */
		$pid = $this->model->get_legacy_config( $config );

		if ( is_wp_error( $pid ) ) {
			return $this->pdf_error( $pid );
		}

		/* Store our ids in the WP query_vars object */
		$GLOBALS['wp']->query_vars['gpdf'] = 1;
		$GLOBALS['wp']->query_vars['pid']  = $pid;
		$GLOBALS['wp']->query_vars['lid']  = $config['lid'];

		/* Send to our model to handle validation / authentication */
		$results = $this->model->process_pdf( $pid, $config['lid'], $config['action'] );

		/* if error, display to user */
		if ( is_wp_error( $results ) ) {
			$this->pdf_error( $results );
		}
	}

	/**
	 * Output PDF error to user
	 *
	 * @param  Object $error The WP_Error object
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	private function pdf_error( $error ) {

		$this->log->addError( 'PDF Generation Error.', [
			'WP_Error_Message' => $error->get_error_message(),
			'WP_Error_Code'    => $error->get_error_code(),
		] );

		/* only display detailed error to admins */
		$whitelist_errors = [ 'timeout_expired', 'access_denied' ];
		if ( $this->gform->has_capability( 'gravityforms_view_settings' ) || in_array( $error->get_error_code(), $whitelist_errors ) ) {
			wp_die( $error->get_error_message() );
		} else {
			wp_die( esc_html__( 'There was a problem generating your PDF', 'gravity-forms-pdf-extended' ) );
		}
	}
}
