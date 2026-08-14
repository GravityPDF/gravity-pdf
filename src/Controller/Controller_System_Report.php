<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Model\Model_System_Report;
use GFPDF\View\View_System_Report;
use GFPDF\Statics\Deprecation;

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
	}

	/**
	 * Register a Site Health test for any deprecated functionality in use on this site
	 *
	 * The admin notice can be dismissed for good, and the Gravity Forms system report has to be sought out. This
	 * puts the same detections where WordPress reports the rest of a site's problems, and keeps them there until
	 * they're fixed.
	 *
	 * @param array $tests
	 *
	 * @return array
	 * @since 6.17.0
	 */
	public function site_status_tests( $tests ) {
		if ( ! is_array( $tests ) || ! $this->gform->has_capability( 'gravityforms_view_settings' ) ) {
			return $tests;
		}

		$tests['direct']['gravity_pdf_deprecated_features'] = [
			'label' => esc_html__( 'Deprecated Gravity PDF functionality', 'gravity-pdf' ),
			'test'  => [ $this, 'deprecated_features_test' ],
		];

		return $tests;
	}

	/**
	 * Run the deprecated functionality Site Health test
	 *
	 * @return array
	 * @since 6.17.0
	 */
	public function deprecated_features_test() {
		return $this->view->get_deprecated_features_test( Deprecation::refresh_signals() );
	}

	/**
	 * Add the deprecated functionality to the Site Health Info tab
	 *
	 * The Info tab is what users copy into a support ticket, so the detections travel with it.
	 *
	 * @param array $info
	 *
	 * @return array
	 * @since 6.17.0
	 */
	public function debug_information( $info ) {
		if ( ! is_array( $info ) || ! $this->gform->has_capability( 'gravityforms_view_settings' ) ) {
			return $info;
		}

		return array_merge( $info, $this->view->get_deprecated_debug_information( Deprecation::refresh_signals() ) );
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
