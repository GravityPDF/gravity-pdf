<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Form;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Model\Model_PDF;
use GFPDF\View\View_PDF;
use Psr\Log\LoggerInterface;
use SiteGround_Optimizer\Minifier\Minifier;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
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
class Controller_PDF extends Helper_Abstract_Controller implements Helper_Interface_Actions, Helper_Interface_Filters {

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var Helper_Form
	 *
	 * @since 4.0
	 */
	protected $gform;

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Setup our class by injecting all our dependencies
	 *
	 * @param Helper_Abstract_Model|Model_PDF $model Our PDF Model the controller will manage
	 * @param Helper_Abstract_View|View_PDF   $view  Our PDF View the controller will manage
	 * @param Helper_Abstract_Form            $gform Our abstracted Gravity Forms helper functions
	 * @param LoggerInterface                 $log   Our logger class
	 * @param Helper_Misc                     $misc  Our miscellaneous class
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
	 * @return void
	 * @since 4.0
	 *
	 */
	public function init() {
		/*
		 * Tell Gravity Forms to add our form PDF settings pages
		 */
		$this->add_actions();
		$this->add_filters();

		/* Add scheduled tasks */
		if ( ! wp_next_scheduled( 'gfpdf_cleanup_tmp_dir' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'gfpdf_cleanup_tmp_dir' );
		}
	}

	/**
	 * Apply any actions needed for the settings page
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function add_actions() {
		/* Process PDF if needed */
		add_action( 'parse_request', [ $this, 'process_legacy_pdf_endpoint' ], 1 ); /* legacy PDF endpoint */
		add_action( 'parse_request', [ $this, 'process_pdf_endpoint' ], 1 ); /* new PDF endpoint */

		/* Allow custom PDF tags / CSS */
		add_action( 'gfpdf_pre_pdf_generation', [ $this, 'add_pre_pdf_hooks' ] );
		add_action( 'gfpdf_post_pdf_generation', [ $this, 'remove_pre_pdf_hooks' ] );

		/* Display PDF links in Gravity Forms Admin Area */
		add_action( 'gform_entries_first_column_actions', [ $this->model, 'view_pdf_entry_list' ], 10, 4 );
		add_action( 'gravityflow_workflow_detail_sidebar', [ $this->model, 'view_pdf_gravityflow_inbox' ], 10, 4 );

		/* Add save PDF actions */
		add_action( 'gform_after_submission', [ $this->model, 'maybe_save_pdf' ], 10, 2 );
		add_action( 'gfpdf_post_pdf_generation', [ $this->model, 'trigger_post_save_pdf' ], 10, 4 );

		/* Clean-up actions */
		$maybe_cleanup_after_form_submission = function ( $entry, $form ) {
			$options = \GPDFAPI::get_options_class();
			/* Exit if background processing is enabled */
			if ( $options->get_option( 'background_processing', 'No' ) === 'Yes' ) {
				return;
			}

			$this->model->cleanup_pdf( $entry, $form );
		};

		add_action( 'gform_after_submission', $maybe_cleanup_after_form_submission, 9999, 2 );
		add_action( 'gform_after_update_entry', [ $this->model, 'cleanup_pdf_after_submission' ], 9999, 2 );
		add_action( 'gfpdf_cleanup_tmp_dir', [ $this->model, 'cleanup_tmp_dir' ] );

		/* Add Gravity Perk Population Anything Support */
		if ( function_exists( 'gp_populate_anything' ) ) {
			add_action( 'gfpdf_pre_pdf_generation', [ $this->model, 'enable_gp_populate_anything' ] );
			add_action( 'gfpdf_pre_pdf_generation_output', [ $this->model, 'disable_gp_populate_anything' ] );

			/* register preferred hydration method */
			add_filter( 'gfpdf_current_form_object', [ $this->model, 'gp_populate_anything_hydrate_form' ], 5, 2 );

			/* remove legacy filters */
			if ( class_exists( '\GPPA_Compatibility_GravityPDF' ) ) {
				$gp_pdf_compat = \GPPA_Compatibility_GravityPDF::get_instance();
				remove_action( 'gfpdf_pre_view_or_download_pdf', [ $gp_pdf_compat, 'hydrate_form_hook_for_pdf_view_or_download' ] );
				remove_action( 'gfpdf_pre_generate_and_save_pdf_notification', [ $gp_pdf_compat, 'hydrate_form_hook' ] );
				remove_action( 'gfpdf_pre_generate_and_save_pdf', [ $gp_pdf_compat, 'hydrate_form_hook' ] );
			}
		}

		/* Add Legal Signature support */
		if ( defined( 'FG_LEGALSIGNING_VERSION' ) ) {
			add_filter( 'gfpdf_mpdf_class_config', [ $this->model, 'register_legal_signing_font_path_with_mpdf' ] );
			add_filter( 'mpdf_font_data', [ $this->model, 'register_legal_signing_fonts_with_mpdf' ] );
			add_action( 'gfpdf_core_template', [ $this->view, 'add_legalsigning_styles' ], 10, 3 );
		}
	}

	/**
	 * Apply any filters needed for the settings page
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function add_filters() {
		/* PDF authentication middleware */
		add_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_public_access' ], 10, 3 );
		add_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_signed_url_access' ], 15, 3 );
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
		add_filter( 'gfpdf_field_middleware', [ $this->model, 'field_middle_page' ], 10, 5 );
		add_filter( 'gfpdf_field_middleware', [ $this->model, 'field_middle_blacklist' ], 10, 7 );

		/* Tap into GF notifications */
		add_filter(
			'gform_notification',
			[
				$this->model,
				'notifications',
			],
			9999,
			3
		); /* ensure Gravity PDF is one of the last filters to be applied */

		/* Change mPDF settings */
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

		/* Third Party Conflict Fixes */
		add_filter( 'gfpdf_pre_view_or_download_pdf', [ $this, 'sgoptimizer_html_minification_fix' ] );
		add_filter( 'gfpdf_legacy_pre_view_or_download_pdf', [ $this, 'sgoptimizer_html_minification_fix' ] );
		add_filter(
			'gfpdf_pre_pdf_generation_output',
			function () {
				add_filter( 'weglot_active_translation', '__return_false' );
			}
		);

		/* Meta boxes */
		add_filter( 'gform_entry_detail_meta_boxes', [ $this->model, 'register_pdf_meta_box' ], 10, 3 );

		/* Page field support */
		add_filter( 'gfpdf_current_form_object', [ $this->model, 'register_page_fields' ] );
	}

	/**
	 * Determines if we should process the PDF at this stage
	 * Fires just before the main WP_Query is executed (we don't need it)
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function process_pdf_endpoint() {

		/* exit early if all the required URL parameters aren't met */
		if ( empty( $GLOBALS['wp']->query_vars['gpdf'] ) || empty( $GLOBALS['wp']->query_vars['pid'] ) || empty( $GLOBALS['wp']->query_vars['lid'] ) ) {
			return null;
		}

		$this->prevent_index();

		$pid    = $GLOBALS['wp']->query_vars['pid'];
		$lid    = (int) $GLOBALS['wp']->query_vars['lid'];
		$action = ( ( isset( $GLOBALS['wp']->query_vars['action'] ) ) && $GLOBALS['wp']->query_vars['action'] === 'download' ) ? 'download' : 'view';

		$this->log->notice(
			'Processing PDF endpoint.',
			[
				'pid'    => $pid,
				'lid'    => $lid,
				'action' => $action,
			]
		);

		/*  Send to our model to handle validation / authentication */
		do_action( 'gfpdf_pre_view_or_download_pdf', $lid, $pid, $action );
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
	 * @return void
	 * @since 4.0
	 *
	 */
	public function process_legacy_pdf_endpoint() {

		/* phpcs:disable WordPress.Security.NonceVerification.Recommended */
		if ( empty( $_GET['gf_pdf'] ) || empty( $_GET['fid'] ) || empty( $_GET['lid'] ) || empty( $_GET['template'] ) ) {
			return null;
		}

		$this->prevent_index();

		$config = [
			'lid'      => (int) explode( ',', $_GET['lid'] )[0],
			'fid'      => (int) $_GET['fid'],
			'aid'      => isset( $_GET['aid'] ) ? (int) $_GET['aid'] : false,
			'template' => sanitize_html_class( substr( $_GET['template'], 0, -4 ) ), /* strip .php from the template name */
			'action'   => isset( $_GET['download'] ) ? 'download' : 'view',
		];
		/* phpcs:enable */

		$this->log->notice(
			'Processing Legacy PDF endpoint.',
			[
				'config' => $config,
			]
		);

		/* Attempt to find a valid config */
		$pid = $this->model->get_legacy_config( $config );

		if ( is_wp_error( $pid ) ) {
			$this->pdf_error( $pid );
		}

		/* Store our ids in the WP query_vars object */
		$GLOBALS['wp']->query_vars['gpdf'] = 1;
		$GLOBALS['wp']->query_vars['pid']  = $pid;
		$GLOBALS['wp']->query_vars['lid']  = $config['lid'];

		/* Send to our model to handle validation / authentication */
		do_action( 'gfpdf_legacy_pre_view_or_download_pdf', $config['lid'], $pid, $config['action'] );
		$results = $this->model->process_pdf( $pid, $config['lid'], $config['action'] );

		/* if error, display to user */
		if ( is_wp_error( $results ) ) {
			$this->pdf_error( $results );
		}
	}

	/**
	 * @since 5.1.1
	 */
	public function add_pre_pdf_hooks() {
		add_filter( 'wp_kses_allowed_html', [ $this->view, 'allow_pdf_html' ] );
		add_filter( 'safe_style_css', [ $this->view, 'allow_pdf_css' ] );
	}

	/**
	 * @since 5.1.1
	 */
	public function remove_pre_pdf_hooks() {
		remove_filter( 'wp_kses_allowed_html', [ $this->view, 'allow_pdf_html' ] );
		remove_filter( 'safe_style_css', [ $this->view, 'allow_pdf_css' ] );
	}

	/**
	 * Prevent the PDF Endpoints being indexed
	 *
	 * @since 5.2
	 */
	public function prevent_index() {
		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow', true );
		}
	}

	/**
	 * Disables the Siteground HTML Minifier when generating PDFs for the browser
	 *
	 * @since 5.1.5
	 *
	 * @see   https://github.com/GravityPDF/gravity-pdf/issues/863
	 */
	public function sgoptimizer_html_minification_fix() {
		if ( class_exists( '\SiteGround_Optimizer\Minifier\Minifier' ) ) {

			/* Remove the shutdown buffer and manually close an open buffers */
			$minifier = Minifier::get_instance();
			remove_action( 'shutdown', [ $minifier, 'end_html_minifier_buffer' ] );

			while ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
		}
	}

	/**
	 * Output PDF error to user
	 *
	 * @param Object $error The WP_Error object
	 *
	 * @since 4.0
	 */
	private function pdf_error( $error ) {

		$this->log->error(
			'PDF Generation Error.',
			[
				'WP_Error_Message' => $error->get_error_message(),
				'WP_Error_Code'    => $error->get_error_code(),
			]
		);

		switch ( $error->get_error_code() ) {
			case 'timeout_expired':
			case 'access_denied':
				$status_code = 401;
				break;

			case 'not_found':
			case 'inactive':
			case 'conditional_logic':
				$status_code = 404;
				break;

			case 'invalid_pdf_id':
				$status_code = 400;
				break;

			default:
				$status_code = 500;
		}

		/* only display detailed error to admins */
		$whitelist_errors = [ 'timeout_expired', 'access_denied' ];
		if ( $this->gform->has_capability( 'gravityforms_view_settings' ) || in_array( $error->get_error_code(), $whitelist_errors, true ) ) {
			wp_die( esc_html( $error->get_error_message() ), $status_code ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			wp_die( esc_html__( 'There was a problem generating your PDF', 'gravity-pdf' ), $status_code ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
