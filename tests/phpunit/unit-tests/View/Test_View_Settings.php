<?php

namespace GFPDF\View;

use GFCommon;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_View_Settings
 *
 * @package GFPDF\Viewr
 *
 * @use     view
 */
class Test_View_Settings extends WP_UnitTestCase {

	/**
	 * @var View_Settings
	 */
	protected $view;

	public function setUp() {
		global $gfpdf;

		$this->view = new View_Settings( [], $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );
	}

	/**
	 * @since system-report
	 */
	public function test_allow_url_fopen_status() {
		global $gfpdf;

		require_once( GFCommon::get_base_path() . '/tooltips.php' );

		ob_start();
		$gfpdf->data->allow_url_fopen = false;
		$this->view->system_status();

		$html = ob_get_clean();
		$this->assertRegExp( '/allow_url_fopen/', $html );
		$this->assertRegExp( '/You may notice image display issues/', $html );

		ob_start();
		$gfpdf->data->allow_url_fopen = true;
		$this->view->system_status();

		$html = ob_get_clean();
		$this->assertRegExp( '/allow_url_fopen/', $html );
		$this->assertNotRegExp( '/You may notice image display issues/', $html );
	}
}
