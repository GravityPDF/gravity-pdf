<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Model\Model_System_Report;
use GFPDF\View\View_System_Report;

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

	public function __construct( Helper_Abstract_Model $model, Helper_Abstract_View $view ) {
		$this->model = $model;
		$this->model->setController( $this );

		$this->view = $view;
		$this->view->setController( $this );
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
