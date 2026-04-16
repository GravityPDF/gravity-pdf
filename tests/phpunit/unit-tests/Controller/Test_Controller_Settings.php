<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Addon;
use GFPDF\Helper\Helper_Logger;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Singleton;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   settings
 */
class Test_Controller_Settings extends WP_UnitTestCase {
	/**
	 * @var Controller_Settings
	 */
	public $controller;

	/**
	 * The WP Unit Test Set up function
	 */
	public function set_up() {
		global $gfpdf;

		parent::set_up();

		remove_all_actions( 'init' );

		$model = $gfpdf->singleton->get_class( 'Model_Settings' );
		$view  = $gfpdf->singleton->get_class( 'View_Settings' );

		$this->controller = new Controller_Settings( $model, $view, $gfpdf->gform, $gfpdf->log, $gfpdf->notices, $gfpdf->data, $gfpdf->misc );
	}

	public function test_bulk_license_check_schedule_with_no_addons() {
		$this->controller->add_filters();
		do_action( 'init' );
		$this->assertFalse( wp_next_scheduled( 'gfpdf_bulk_license_check' ) );
	}

	public function test_bulk_license_check_schedule_with_addons() {
		$addon = new ControllerSettingsAddon(
			'my-custom-plugin',
			'My Custom Plugin',
			'Gravity PDF',
			'1.0',
			'/path/to/plugin/file.php',
			\GPDFAPI::get_data_class(),
			\GPDFAPI::get_options_class(),
			new Helper_Singleton(),
			new Helper_Logger( 'my-custom-plugin', 'My Custom Plugin' ),
			new Helper_Notices()
		);

		$addon->init();
		$this->controller->init();
		do_action( 'init' );

		$this->assertNotFalse( wp_next_scheduled( 'gfpdf_bulk_license_check' ) );
	}
}

class ControllerSettingsAddon extends Helper_Abstract_Addon {
}
