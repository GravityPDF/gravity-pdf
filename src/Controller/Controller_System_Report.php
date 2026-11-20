<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Data;
use GFPDF\Model\Model_System_Report;
use GFPDF\View\View_System_Report;
use GFPDF\Statics\Deprecation;
use WP_REST_Server;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Controller_System_Report
 *
 * @package GFPDF\Controller
 *
 * @since   5.3
 */
class Controller_System_Report extends Helper_Abstract_Controller {

	/**
	 * @var Model_System_Report
	 */
	public $model;

	/**
	 * @var View_System_Report
	 */
	public $view;

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var Helper_Abstract_Form
	 *
	 * @since 6.17.0
	 */
	protected $gform;

	public function __construct( Helper_Abstract_Model $model, Helper_Abstract_View $view, Helper_Abstract_Form $gform ) {
		$this->model = $model;
		$this->model->setController( $this );

		$this->view = $view;
		$this->view->setController( $this );

		$this->gform = $gform;
	}

	/**
	 * Initialise our class defaults
	 *
	 * @since 5.3
	 */
	public function init() {
		$this->add_filters();
	}

	/**
	 * Apply filters needed for the system status page
	 *
	 * @since 5.3
	 */
	public function add_filters() {
		add_filter( 'gform_system_report', [ $this, 'system_report' ] );
		add_filter( 'site_status_tests', [ $this, 'site_status_tests' ] );
		add_filter( 'debug_information', [ $this, 'debug_information' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register a Site Health test for each check in the Gravity PDF part of the system report
	 *
	 * The admin notice can be dismissed for good, and the Gravity Forms system report has to be sought out. This
	 * puts the same checks where WordPress reports the rest of a site's problems, and keeps them there until
	 * they're fixed.
	 *
	 * @param array $tests
	 *
	 * @return array
	 * @since 6.17.0
	 * @since 6.19.0 A test for each System Report check, with removed and deprecated functionality tested apart
	 */
	public function site_status_tests( $tests ) {
		if ( ! is_array( $tests ) || ! $this->gform->has_capability( 'gravityforms_view_settings' ) ) {
			return $tests;
		}

		/* A Site Health run checks the site afresh */
		$this->model->flush_report_rows();

		/* Removed functionality is critical and scheduled removals recommended, so each group is a test of its own */
		$tests['direct']['gravity_pdf_removed_features'] = [
			'label' => esc_html__( 'Removed Gravity PDF functionality', 'gravity-pdf' ),
			'test'  => [ $this, 'removed_features_test' ],
		];

		$tests['direct']['gravity_pdf_deprecated_features'] = [
			'label' => esc_html__( 'Deprecated Gravity PDF functionality', 'gravity-pdf' ),
			'test'  => [ $this, 'deprecated_features_test' ],
		];

		$tests['direct']['gravity_pdf_cache_cleanup'] = [
			'label' => esc_html__( 'Gravity PDF cache cleanup', 'gravity-pdf' ),
			'test'  => [ $this, 'cache_cleanup_test' ],
		];

		$tests['direct']['gravity_pdf_core_fonts'] = [
			'label' => esc_html__( 'Gravity PDF core fonts', 'gravity-pdf' ),
			'test'  => [ $this, 'core_fonts_test' ],
		];

		foreach ( $this->get_report_tests() as $id => $test ) {
			if ( $id === 'gravity_pdf_temporary_folder' ) {
				continue;
			}

			$tests['direct'][ $id ] = [
				'label' => $test['label'],
				'test'  => function () use ( $id ) {
					return $this->report_row_test( $id );
				},
			];
		}

		/* It asks the site for a file in its temporary folder, which can be slow, so it runs once the page has loaded */
		$tests['async']['gravity_pdf_temporary_folder'] = [
			'label'             => $this->get_report_tests()['gravity_pdf_temporary_folder']['label'],
			'test'              => rest_url( Helper_Data::REST_API_BASENAME . 'v1/site-health/temporary-folder' ),
			'has_rest'          => true,
			'async_direct_test' => [ $this, 'temporary_folder_test' ],
		];

		return $tests;
	}

	/**
	 * The Site Health tests that read a System Report check: its row, how serious a failure is, and its titles
	 *
	 * @return array<string, array{section: string, key: string, status: string, label: string, good: string, bad: string}>
	 * @since 6.19.0
	 */
	protected function get_report_tests(): array {
		return [
			'gravity_pdf_folder_permissions' => [
				'section' => 'directories',
				'key'     => 'folder_permissions',
				'status'  => 'critical',
				'label'   => __( 'Gravity PDF folder permissions', 'gravity-pdf' ),
				'good'    => __( 'Gravity PDF can save PDFs', 'gravity-pdf' ),
				'bad'     => __( "Gravity PDF can't save PDFs", 'gravity-pdf' ),
			],

			'gravity_pdf_temporary_folder'   => [
				'section' => 'directories',
				'key'     => 'temp_folder_protected',
				'status'  => 'critical',
				'label'   => __( 'Gravity PDF temporary folder', 'gravity-pdf' ),
				'good'    => __( "Gravity PDF's temporary folder is private", 'gravity-pdf' ),
				'bad'     => __( 'PDFs could be opened by anyone who finds them', 'gravity-pdf' ),
			],

			'gravity_pdf_memory_limit'       => [
				'section' => 'php',
				'key'     => 'memory',
				'status'  => 'recommended',
				'label'   => __( 'Gravity PDF memory limit', 'gravity-pdf' ),
				'good'    => __( 'Gravity PDF has enough memory', 'gravity-pdf' ),
				'bad'     => __( 'Large PDFs may fail to generate', 'gravity-pdf' ),
			],

			'gravity_pdf_remote_images'      => [
				'section' => 'php',
				'key'     => 'allow_url_fopen',
				'status'  => 'recommended',
				'label'   => __( 'Gravity PDF remote images', 'gravity-pdf' ),
				'good'    => __( 'Gravity PDF can load images by URL', 'gravity-pdf' ),
				'bad'     => __( "Gravity PDF can't load images by URL", 'gravity-pdf' ),
			],

			'gravity_pdf_template_overrides' => [
				'section' => 'directories',
				'key'     => 'outdated_templates',
				'status'  => 'recommended',
				'label'   => __( 'Gravity PDF template overrides', 'gravity-pdf' ),
				'good'    => __( "Gravity PDF's template overrides are up to date", 'gravity-pdf' ),
				'bad'     => __( 'Some Gravity PDF template overrides are out of date', 'gravity-pdf' ),
			],
		];
	}

	/**
	 * Run a Site Health test from its System Report check's row
	 *
	 * @param string $id A test ID from self::get_report_tests()
	 *
	 * @since 6.19.0
	 */
	public function report_row_test( string $id ): array {
		$test = $this->get_report_tests()[ $id ];

		return $this->view->get_report_row_test( $id, $test, $this->model->get_report_rows()[ $test['section'] ][ $test['key'] ] ?? [] );
	}

	/**
	 * Run the temporary folder Site Health test, which asks the site for a file in the folder
	 *
	 * @since 6.19.0
	 */
	public function temporary_folder_test(): array {
		$id = 'gravity_pdf_temporary_folder';

		return $this->view->get_report_row_test( $id, $this->get_report_tests()[ $id ], $this->model->get_temp_folder_check() );
	}

	/**
	 * Register the route Site Health runs the temporary folder test through once its page has loaded
	 *
	 * @since 6.19.0
	 */
	public function register_routes(): void {
		register_rest_route(
			Helper_Data::REST_API_BASENAME . 'v1',
			'/site-health/temporary-folder',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'temporary_folder_test' ],
				'permission_callback' => function () {
					return current_user_can( 'view_site_health_checks' ) && $this->gform->has_capability( 'gravityforms_view_settings' );
				},
			]
		);
	}

	/**
	 * Run the PDF cache cleanup Site Health test
	 *
	 * @return array
	 * @since 7.0
	 */
	public function cache_cleanup_test() {
		return $this->view->get_cache_cleanup_test( $this->model->is_cache_cleanup_stalled() );
	}

	/**
	 * Run the core fonts Site Health test
	 *
	 * @return array
	 * @since 6.19.0
	 */
	public function core_fonts_test() {
		return $this->view->get_core_fonts_test( $this->model->get_core_font_health() );
	}

	/**
	 * Run the deprecated functionality Site Health test
	 *
	 * @return array
	 * @since 6.17.0
	 * @since 6.19.0 Scheduled removals only, as removed functionality has a test of its own
	 */
	public function deprecated_features_test() {
		return $this->view->get_deprecated_group_test( Deprecation::refresh_signals(), Deprecation::GROUP_DEPRECATED );
	}

	/**
	 * Run the removed functionality Site Health test
	 *
	 * @since 6.19.0
	 */
	public function removed_features_test(): array {
		return $this->view->get_deprecated_group_test( Deprecation::refresh_signals(), Deprecation::GROUP_UNSUPPORTED );
	}

	/**
	 * Add the Gravity PDF part of the system report to the Site Health Info tab
	 *
	 * The Info tab is what users copy into a support ticket, so it carries the same lines as the copied report.
	 *
	 * @param array $info
	 *
	 * @return array
	 * @since 6.17.0
	 * @since 6.19.0 One panel, with the lines of the copied System Report
	 */
	public function debug_information( $info ) {
		if ( ! is_array( $info ) || ! $this->gform->has_capability( 'gravityforms_view_settings' ) ) {
			return $info;
		}

		return array_merge( $info, $this->view->get_report_debug_information( $this->model->build_gravitypdf_report() ) );
	}

	/**
	 * Add the Gravity PDF system report to the Gravity Forms report
	 *
	 * @param array $system_report
	 *
	 * @return array
	 * @since 5.3
	 * @since 6.12.6 Moved data to the end of the report
	 */
	public function system_report( $system_report ) {

		if ( is_array( $system_report ) ) {
			$gravitypdf_report = $this->model->build_gravitypdf_report();
			$system_report     = $this->model->move_gravitypdf_active_plugins_to_gf_addons( $system_report );

			$system_report = array_merge(
				$system_report,
				$gravitypdf_report
			);
		}

		return $system_report;
	}
}
