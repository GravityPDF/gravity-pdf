<?php

namespace GFPDF\Tests;

use GFPDF\Controller\Controller_Welcome_Screen;
use GFPDF\Model\Model_Welcome_Screen;
use GFPDF\View\View_Welcome_Screen;

use WP_UnitTestCase;
use WP_UnitTest_Factory;

/**
 * Test Gravity PDF Welcome Screen Functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the model / view / controller for the Welcome Screen
 *
 * @since 4.0
 * @group welcome-screen
 */
class Test_Welcome_Screen extends WP_UnitTestCase {

	/**
	 * Our Welcome Screen Controller
	 *
	 * @var \GFPDF\Controller\Controller_Welcome_Screen
	 *
	 * @since 4.0
	 */
	public $controller;

	/**
	 * Our Welcome Screen Model
	 *
	 * @var \GFPDF\Model\Model_Welcome_Screen
	 *
	 * @since 4.0
	 */
	public $model;

	/**
	 * Our Welcome Screen View
	 *
	 * @var \GFPDF\View\View_Welcome_Screen
	 *
	 * @since 4.0
	 */
	public $view;

	/**
	 * An administrator user ID we included
	 *
	 * @var int
	 *
	 * @since 4.1
	 */
	public $user_id;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function setUp() {
		global $gfpdf;

		/* run parent method */
		parent::setUp();

		/* Setup our test classes */
		$this->model = new Model_Welcome_Screen( $gfpdf->log );
		$this->view  = new View_Welcome_Screen(
			[
				'display_version' => PDF_EXTENDED_VERSION,
			],
			$gfpdf->gform
		);

		$this->controller = new Controller_Welcome_Screen( $this->model, $this->view, $gfpdf->log, $gfpdf->data, $gfpdf->options );
		$this->controller->init();
	}

	/**
	 * Test the appropriate actions are set up
	 *
	 * @since 4.0
	 */
	public function test_actions() {
		$this->assertEquals( 10, has_action( 'admin_menu', [ $this->model, 'admin_menus' ] ) );
		$this->assertEquals( 10, has_action( 'admin_head', [ $this->model, 'hide_admin_menus' ] ) );
	}

	/**
	 * Test the appropriate filters are set up
	 *
	 * @since 4.0
	 */
	public function test_filters() {
		$this->assertEquals( 10, has_filter( 'admin_title', [ $this->model, 'add_page_title' ] ) );
	}

	/**
	 * Test the getting started page loads correctly
	 *
	 * @since 4.0
	 */
	public function test_getting_started_screen() {

		ob_start();
		$this->model->getting_started_screen();
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, 'gfpdf-welcome-screen' ) );
	}

	/**
	 * Check our welcome and update admin menus are correctly added
	 *
	 * @since 4.0
	 */
	public function test_admin_menus() {
		global $_wp_submenu_nopriv;

		/* Run our registration */
		$this->model->admin_menus();

		/* Test the results */
		$this->assertTrue( isset( $_wp_submenu_nopriv['index.php']['gfpdf-getting-started'] ) );
	}

	/**
	 * Check the page titles load correctly
	 *
	 * @since 4.0
	 */
	public function test_add_page_title() {

		/* Test a pass */
		$this->assertEquals( 'Title', $this->model->add_page_title( 'Title' ) );

		/* Test welcome screen */
		$_GET['page'] = 'gfpdf-getting-started';
		$this->assertEquals( 'Welcome to Gravity PDF', $this->model->add_page_title( 'Title' ) );
	}
}
