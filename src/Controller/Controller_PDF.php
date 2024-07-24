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
use GFPDF\Helper\Helper_PDF;
use GFPDF\Model\Model_PDF;
use GFPDF\Statics\Debug;
use GFPDF\View\View_PDF;
use Psr\Log\LoggerInterface;
use SiteGround_Optimizer\Minifier\Minifier;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
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
 * @property View_PDF $view
 * @property Model_PDF $model
 *
 * @since 4.0
 */
class Controller_PDF extends Helper_Abstract_Controller {

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
	 * @return void
	 * @since 4.0
	 */
	public function init() {
		$this->add_actions();
		$this->add_filters();

		/* Add scheduled tasks */
		if ( ! wp_next_scheduled( 'gfpdf_cleanup_tmp_dir' ) ) {
			wp_schedule_event( time(), 'hourly', 'gfpdf_cleanup_tmp_dir' );
		}
	}

	/**
	 * @return void
	 * @since 4.0
	 */
	public function add_actions() {
		/* Process PDF if needed */
		add_action( 'parse_request', [ $this, 'process_legacy_pdf_endpoint' ] ); /* legacy PDF endpoint */
		add_action( 'parse_request', [ $this, 'process_pdf_endpoint' ] ); /* new PDF endpoint */

		/* Set up pre- and post-generation PDF hooks */
		add_action( 'gfpdf_pre_pdf_generation', [ $this, 'add_pre_pdf_hooks' ] );
		add_action( 'gfpdf_post_pdf_generation', [ $this, 'remove_pre_pdf_hooks' ] );

		/* Set up pre generation hooks when streaming PDF to the browser */
		$add_pre_view_or_download_pdf_hooks = function( $form, $entry, $settings ) {
			$this->add_pre_view_or_download_pdf_hooks( $form, $entry, $settings );
		};

		add_action( 'gfpdf_view_or_download_pdf', $add_pre_view_or_download_pdf_hooks, 10, 3 );

		/* Display PDF links in Gravity Forms Admin Area */
		add_action( 'gform_entries_first_column_actions', [ $this->model, 'view_pdf_entry_list' ], 10, 4 );
		add_action( 'gravityflow_workflow_detail_sidebar', [ $this->model, 'view_pdf_gravityflow_inbox' ], 10, 4 );

		/* Add hooks to save PDF to disk, or run right after a PDF is saved to disk */
		add_action( 'gform_after_submission', [ $this->model, 'maybe_save_pdf' ], 10, 2 );
		add_action( 'gfpdf_post_pdf_generation', [ $this->model, 'trigger_post_save_pdf' ], 10, 4 );

		/* Scheduled clean-up actions */
		add_action( 'gfpdf_cleanup_tmp_dir', [ $this->model, 'cleanup_tmp_dir' ] );

		/* Remove legacy Gravity Perk Population Anything Support */
		if ( class_exists( '\GPPA_Compatibility_GravityPDF' ) ) {
			$gp_pdf_compat = \GPPA_Compatibility_GravityPDF::get_instance();
			remove_action( 'gfpdf_pre_view_or_download_pdf', [ $gp_pdf_compat, 'hydrate_form_hook_for_pdf_view_or_download' ] );
			remove_action( 'gfpdf_pre_generate_and_save_pdf_notification', [ $gp_pdf_compat, 'hydrate_form_hook' ] );
			remove_action( 'gfpdf_pre_generate_and_save_pdf', [ $gp_pdf_compat, 'hydrate_form_hook' ] );
		}

		/* Add Legal Signature support */
		if ( defined( 'FG_LEGALSIGNING_VERSION' ) ) {
			add_filter( 'gfpdf_mpdf_class_config', [ $this->model, 'register_legal_signing_font_path_with_mpdf' ] );
			add_filter( 'mpdf_font_data', [ $this->model, 'register_legal_signing_fonts_with_mpdf' ] );
			add_action( 'gfpdf_core_template', [ $this->view, 'add_legalsigning_styles' ], 10, 3 );
		}
	}

	/**
	 * @return void
	 * @since 4.0
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

		/* Gravity Forms PDF Attachments */
		add_filter( 'gform_notification', [ $this->model, 'notifications' ], 9999, 3 );

		/* Change mPDF settings */
		add_filter( 'mpdf_font_data', [ $this->model, 'register_custom_font_data_with_mPDF' ] );
		add_filter( 'mpdf_font_data', [ $this->model, 'add_unregistered_fonts_to_mPDF' ], 20 );
		add_filter( 'gfpdf_mpdf_init_class', [ $this->model, 'set_watermark_font' ], 10, 4 );

		/* Process mergetags and shortcodes in PDF */
		add_filter( 'gfpdf_pdf_core_template_html_output', [ $this->gform, 'process_tags' ], 10, 3 );
		add_filter( 'gfpdf_pdf_html_output', [ $this->gform, 'process_tags' ], 10, 3 );
		add_filter( 'gfpdf_pdf_html_output', 'do_shortcode' );

		/* Add support for ?html=1 helper parameter */
		$add_view_html_debugger = function( $html, $form, $entry, $pdf_settings, $helper_pdf ) {
			return $this->add_view_html_debugger( $html, $form, $entry, $pdf_settings, $helper_pdf );
		};

		add_filter( 'gfpdf_pdf_html_output', $add_view_html_debugger, 9999, 5 );

		/* Backwards compatibility for our Tier 2 plugin */
		add_filter( 'gfpdfe_pre_load_template', [ 'PDFRender', 'prepare_ids' ], 1, 8 );

		/* Pre-process our template arguments and automatically render them in PDF */
		add_filter( 'gfpdf_template_args', [ $this->model, 'preprocess_template_arguments' ] );
		add_filter( 'gfpdf_pdf_html_output', [ $this->view, 'autoprocess_core_template_options' ], 5, 4 );

		/* Meta boxes */
		add_filter( 'gform_entry_detail_meta_boxes', [ $this->model, 'register_pdf_meta_box' ], 10, 3 );

		/* Manipulate the form object (array) when generating PDFs */
		$add_current_form_object_hooks = function( $form, $entry, $source ) {
			return $this->add_current_form_object_hooks( $form, $entry, $source );
		};

		add_filter( 'gfpdf_current_form_object', $add_current_form_object_hooks, 10, 3 );

		/* Manipulate the PDF settings object (array) when generating PDFs */
		$add_current_pdf_settings_object_hooks = function( $pdf_settings, $form, $entry ) {
			return $this->add_current_pdf_settings_object_hooks( $pdf_settings, $form, $entry );
		};

		add_filter( 'gfpdf_current_pdf_settings_object', $add_current_pdf_settings_object_hooks, 10, 3 );
	}

	/**
	 * Processes the View/Download PDF Endpoint
	 *
	 * Endpoint URLs:
	 *
	 * example format -> https://example.com/pdf/{pdfId}/{entryId}/
	 *
	 * view -> https://example.com/pdf/66307560bcdf4/2403/
	 * download -> https://example.com/pdf/66307560bcdf4/2403/download/
	 * add print dialog -> https://example.com/pdf/66307560bcdf4/2403/?print=1
	 *
	 * Recommend you generate the URL with a shortcode or merge tag
	 * See https://docs.gravitypdf.com/v6/users/shortcodes-and-mergetags
	 *
	 * This method runs just before the main WP_Query class is executed
	 *
	 * @return void
	 * @since 4.0
	 */
	public function process_pdf_endpoint() {

		/* exit early if all the required URL parameters aren't met */
		if ( empty( $GLOBALS['wp']->query_vars['gpdf'] ) || empty( $GLOBALS['wp']->query_vars['pid'] ) || empty( $GLOBALS['wp']->query_vars['lid'] ) ) {
			return null;
		}

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
	 * @deprecated 4.0 Added for backwards compatibility with v3 PDF links, but ideally should not be used
	 */
	public function process_legacy_pdf_endpoint() {

		/* phpcs:disable WordPress.Security.NonceVerification.Recommended */
		if ( empty( $_GET['gf_pdf'] ) || empty( $_GET['fid'] ) || empty( $_GET['lid'] ) || empty( $_GET['template'] ) ) {
			return null;
		}

		_doing_it_wrong( __METHOD__, 'Legacy PDF URLs are deprecated. Replace with the [gravitypdf] shortcode or PDF merge tags. See https://docs.gravitypdf.com/v6/users/shortcodes-and-mergetags for usage instructions.', '4.0' );

		$config = [
			'lid'      => (int) explode( ',', $_GET['lid'] )[0],
			'fid'      => (int) $_GET['fid'],
			'aid'      => isset( $_GET['aid'] ) ? (int) $_GET['aid'] : false,
			'template' => sanitize_html_class( substr( $_GET['template'], 0, -4 ) ), /* strip .php from the template name */
			'action'   => isset( $_GET['download'] ) ? 'download' : 'view',
		];
		/* phpcs:enable */

		/* Attempt to find a valid config */
		$pid = $this->model->get_legacy_config( $config );

		if ( is_wp_error( $pid ) ) {
			$this->pdf_error( $pid );
		}

		/* Store our ids in the WP query_vars object */
		$GLOBALS['wp']->query_vars['gpdf'] = 1;
		$GLOBALS['wp']->query_vars['pid']  = $pid;
		$GLOBALS['wp']->query_vars['lid']  = $config['lid'];

		$this->log->notice(
			'Processing Legacy PDF endpoint.',
			[
				'config' => $config,
				'pid'    => $pid,
			]
		);

		$this->log->warning( 'Legacy PDF URLs are deprecated. Replace with the [gravitypdf] shortcode or PDF merge tags. See https://docs.gravitypdf.com/v6/users/shortcodes-and-mergetags for usage instructions.' );

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

		$this->misc->maybe_load_gf_entry_detail_class(); /* Backwards compatible for legacy templates */

		/* Gravity Wiz Populate Anything support */
		if ( function_exists( 'gp_populate_anything' ) ) {
			$this->model->enable_gp_populate_anything();
		}
	}

	/**
	 * @since 5.1.1
	 */
	public function remove_pre_pdf_hooks() {
		remove_filter( 'wp_kses_allowed_html', [ $this->view, 'allow_pdf_html' ] );
		remove_filter( 'safe_style_css', [ $this->view, 'allow_pdf_css' ] );

		/* Gravity Wiz Populate Anything support */
		if ( function_exists( 'gp_populate_anything' ) ) {
			$this->model->disable_gp_populate_anything();
		}
	}

	/**
	 * Actions / hooks to run prior to streaming PDF to the browser
	 * These hooks will not be run when sending notifications, using GPDFAPI::create_pdf(),
	 *
	 * @return void
	 *
	 * @since 6.12
	 */
	protected function add_pre_view_or_download_pdf_hooks( $form, $entry, $settings ) {
		$this->prevent_index();

		/*
		 * Stop Weglot trying to transform the binary PDF
		 * See https://github.com/GravityPDF/gravity-pdf/pull/1505
		 */
		add_filter( 'weglot_active_translation', '__return_false' );

		/*
		 * Stop WP External Links plugin trying to transform the binary PDF
		 * See https://github.com/GravityPDF/gravity-pdf/issues/386
		 */
		add_filter( 'wpel_apply_settings', '__return_false' );

		/*
		 * Support ?data=1 helper parameter
		 * See https://docs.gravitypdf.com/v6/developers/helper-parameters#data1
		 */
		if ( $this->view->maybe_view_form_data() ) {
			$this->view->view_form_data( \GPDFAPI::get_form_data( $entry['id'] ) );
		}

		/*
		 * Support ?html=1 helper parameter
		 * See https://docs.gravitypdf.com/v6/developers/helper-parameters#html1
		 */
		if ( rgget( 'html' ) && Debug::is_enabled_and_can_view() ) {
			add_filter( 'gfpdf_override_pdf_bypass', '__return_true' );
		}
	}

	/**
	 * Disables the Siteground HTML Minifier when generating PDFs for the browser
	 *
	 * @since 5.1.5
	 * @see https://github.com/GravityPDF/gravity-pdf/issues/863
	 * @deprecated 6.12 All buffers are auto-closed before a PDF is sent to the browser
	 */
	public function sgoptimizer_html_minification_fix() {
		_doing_it_wrong( __METHOD__, 'This method has been removed and no alternative is available.', '6.12' );
	}

	/**
	 * Modify the form object specifically for the PDF request
	 *
	 * @param array $form
	 * @param array $entry
	 * @param string $source
	 *
	 * @return array
	 *
	 * @since 6.12
	 */
	protected function add_current_form_object_hooks( $form, $entry, $source ) {
		/* Make Page fields first class citizens in the form object */
		$form = $this->model->register_page_fields( $form );

		/* Gravity Perks Conditional Logic Date Fields support */
		if ( method_exists( 'GWConditionalLogicDateFields', 'convert_conditional_logic_date_field_values' ) ) {
			$form = \GWConditionalLogicDateFields::convert_conditional_logic_date_field_values( $form );
		}

		/* Gravity Perks Populate Anything support */
		if ( function_exists( 'gp_populate_anything' ) ) {
			$form = $this->model->gp_populate_anything_hydrate_form( $form, $entry );
		}

		return $form;
	}

	/**
	 * Modify the PDF settings specifically for the PDF request
	 *
	 * @param array $pdf_settings
	 * @param array $form
	 * @param array $entry
	 *
	 * @return array
	 *
	 * @since 6.12
	 */
	protected function add_current_pdf_settings_object_hooks( $pdf_settings, $form, $entry ) {
		$pdf_settings = $this->model->apply_backwards_compatibility_filters( $pdf_settings, $entry );

		return $pdf_settings;
	}

	/**
	 * A debugging tool that will output the HTML mark-up for the PDF to the browser
	 *
	 * Use ?html=1 to active when the website is in development/staging mode and the current logged-in
	 * user has appropriate capabilities. To easily see the raw source code on screen use ?html=1&raw=1
	 *
	 * @param string     $html
	 * @param array      $form
	 * @param array      $entry
	 * @param array      $pdf_settings
	 * @param Helper_PDF $helper_pdf
	 *
	 * @return string
	 *
	 * @since    6.12
	 *
	 * @internal was originally included in \GFPDF\Helper\Helper_PDF::maybe_display_raw_html()
	 * @link https://docs.gravitypdf.com/v6/developers/helper-parameters#html1
	 */
	protected function add_view_html_debugger( $html, $form, $entry, $pdf_settings, $helper_pdf ) {
		if ( ! is_string( $html ) ) {
			return $html;
		}

		if ( ! rgget( 'html' ) ) {
			return $html;
		}

		if ( ! Debug::is_enabled_and_can_view() ) {
			return $html;
		}

		$html = apply_filters( 'gfpdf_pre_html_browser_output', $html, $pdf_settings, $entry, $form, $helper_pdf );

		if ( rgget( 'raw' ) ) {
			echo '<pre><code>';
			echo htmlspecialchars( $html );
			echo '</code></pre>';
		} else {
			echo $html; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
		}

		exit;
	}

	/**
	 * Try to prevent the PDF being indexed or cached by the web server
	 *
	 * @since 5.2
	 * @since 6.12 Set DONOTCACHEPAGE constant (brought forward in the request cycle)
	 */
	public function prevent_index() {
		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow', true );
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}

	/**
	 * Display appropriate error to user when PDF cannot be display
	 *
	 * @param \WP_Error $error The WP_Error object
	 *
	 * @since 4.0
	 */
	protected function pdf_error( $error ) {

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
			wp_die( esc_html__( 'There was a problem creating the PDF', 'gravity-forms-pdf-extended' ), $status_code ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
