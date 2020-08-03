<?php

namespace GFPDF\Controller;

use GF_System_Report;
use GFCommon;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class TestControllerSystemReport
 *
 * @package GFPDF\Controller
 *
 * @group   system-report
 */
class TestControllerSystemReport extends WP_UnitTestCase {

	/**
	 * @var Controller_System_Report
	 */
	protected $controller;

	public function setUp() {
		$this->controller = new Controller_System_Report( false );
		$this->controller->init();
	}

	public function test_filters() {
		$this->assertSame( 10, has_filter( 'gform_system_report', [ $this->controller, 'system_report' ] ) );
	}

	public function test_system_report() {
		require_once( GFCommon::get_base_path() . '/includes/system-status/class-gf-system-status.php' );
		require_once( GFCommon::get_base_path() . '/includes/system-status/class-gf-system-report.php' );
		require_once( GFCommon::get_base_path() . '/includes/system-status/class-gf-update.php' );

		$system_report = GF_System_Report::get_system_report();

		$this->assertEquals( 'allow_url_fopen', $system_report[2]['tables'][1]['items'][11]['label'] );
	}
}
