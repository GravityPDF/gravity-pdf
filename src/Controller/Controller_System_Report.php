<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
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
 * @since 5.3
 */
class Controller_System_Report extends Helper_Abstract_Controller {

	/**
	 * Holds our $allow_url_fopen value
	 *
	 * @since 5.3
	 *
	 * @var bool $allow_url_data
	 */
	protected $allow_url_data;

	/**
	 * Setup our class by injecting our dependencies
	 *
	 * @since 5.3
	 *
	 * @param boolean $allow_url_fopen
	 */
	public function __construct( $allow_url_fopen ) {
		$this->allow_url_data = $allow_url_fopen;
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
	 * Include the add-on table in the PHP Server Environment system status.
	 *
	 * @since 5.3
	 *
	 * @param array $system_report
	 *
	 * @return array
	 */
	public function system_report( $system_report ) {

		if ( isset( $system_report[2]['tables'][1]['items'] ) && is_array( $system_report[2]['tables'][1]['items'] ) ) {
			$is_enabled = $this->is_allow_url_fopen_enabled( $this->allow_url_data );

			$insert_val[] = [
				'label'        => 'allow_url_fopen',
				'label_export' => 'allow_url_fopen',
				'value'        => $is_enabled,
				'value_export' => $is_enabled,
			];

			array_splice( $system_report[2]['tables'][1]['items'], 11, 0, $insert_val );
		}

		return $system_report;
	}

	/**
	 * Yes or No status for allow_url_fopen
	 *
	 * @since 5.3
	 *
	 * @param $allow_url_fopen
	 *
	 * @return string
	 */
	protected function is_allow_url_fopen_enabled( $allow_url_fopen ) {
		return isset( $allow_url_fopen ) ? esc_html__( 'Yes', 'gravity-forms-pdf-extended' ) : esc_html__( 'No', 'gravity-forms-pdf-extended' );
	}
}
