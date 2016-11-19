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
 * @copyright   Copyright (c) 2016, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2016, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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
		$this->view  = new View_Welcome_Screen( [
			'display_version' => PDF_EXTENDED_VERSION,
		], $gfpdf->gform );

		$this->controller = new Controller_Welcome_Screen( $this->model, $this->view, $gfpdf->log, $gfpdf->data, $gfpdf->options );
		$this->controller->init();

		/* Setup a new admin user */
		$factory = new WP_UnitTest_Factory();
		$this->user_id = $factory->user->create( array(
			'role'   => 'administrator',
		) );
	}

	/**
	 * Get a mocked version of our welcome screen controller so we can test the redirect functionality
	 *
	 * @param int $executed_num Number of times our mocked method will be run
	 *
	 * @return \PHPUnit_Framework_MockObject_Builder_InvocationMocker
	 *
	 * @since 4.1
	 */
	private function get_mocked_controller( $executed_num ) {
		global $gfpdf;

		$controller = $this->getMockBuilder( '\GFPDF\Controller\Controller_Welcome_Screen' )
		                   ->setConstructorArgs( [
			                   $this->model,
			                   $this->view,
			                   $gfpdf->log,
			                   $gfpdf->data,
			                   $gfpdf->options,
		                   ] )
		                   ->setMethods( [ 'redirect' ] )
		                   ->getMock();

		$controller->expects( $this->exactly( $executed_num ) )
		           ->method( 'redirect' );

		return $controller;
	}

	/**
	 * Test the appropriate actions are set up
	 *
	 * @since 4.0
	 */
	public function test_actions() {
		$this->assertEquals( 10, has_action( 'admin_menu', [ $this->model, 'admin_menus' ] ) );
		$this->assertEquals( 10, has_action( 'init', [ $this->controller, 'welcome' ] ) );
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
		$this->controller->getting_started_screen();
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, 'gfpdf-welcome-screen' ) );
	}

	/**
	 * Test the update page loads correctly
	 *
	 * @since 4.0
	 */
	public function test_update_screen() {

		ob_start();
		$this->controller->update_screen();
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, 'gfpdf-update-screen' ) );
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
		$this->assertTrue( isset( $_wp_submenu_nopriv['index.php']['gfpdf-update'] ) );
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

		/* Test update screen */
		$_GET['page'] = 'gfpdf-update';
		$this->assertEquals( "What&#039;s new in Gravity PDF?", $this->model->add_page_title( 'Title' ) );
	}

	/**
	 * @since 4.1
	 */
	public function test_welcome() {
		global $gfpdf;

		if( is_multisite() ) {
			return;
		}

		/* Setup our test */
		$controller = $this->get_mocked_controller( 2 );
		update_option( 'gfpdf_current_version', '1.0' );
		$gfpdf->data->is_installed = false;

		$this->assertNull( $controller->welcome() );

		/* ensure we are in the admin area */
		set_current_screen('dashboard');
		$this->assertNull( $controller->welcome() );

		/* ensure the current user has the correct privilages */
		wp_set_current_user( $this->user_id );
		$controller->welcome();

		/* Ensure if the versions are the same we get null */
		update_option( 'gfpdf_current_version', PDF_EXTENDED_VERSION );
		$this->assertNull( $controller->welcome() );

		/* Try a different version number */
		update_option( 'gfpdf_current_version', substr( PDF_EXTENDED_VERSION, 0, -4 ) . '.100.2' );
		$controller->welcome();

		/* If we are already on the getting started page we don't do the redirect */
		$_GET['page'] = 'gfpdf-getting-started';
		$this->assertNull( $controller->welcome() );
	}

	/**
	 * Test that our update screen is correctly shown
	 *
	 * @since 4.1
	 */
	public function test_maybe_display_update_screen() {
		global $gfpdf;

		$controller = $this->get_mocked_controller( 2 );

		update_option( 'gfpdf_current_version', PDF_EXTENDED_VERSION );
		$controller->maybe_display_update_screen( PDF_EXTENDED_VERSION );

		/* Check there's a failed attempt when the versions are exactly the same */
		$this->assertNull( $controller->maybe_display_update_screen( PDF_EXTENDED_VERSION ) );

		/* Check there's a failed attempt when we are just doing a patch update */
		update_option( 'gfpdf_current_version', '2.0' );
		$controller->maybe_display_update_screen( '2.0.1' );

		update_option( 'gfpdf_current_version', '2.0.5' );
		$controller->maybe_display_update_screen( '2.0.10' );

		/* Check we are successful on major version updates */
		update_option( 'gfpdf_current_version', '1.0' );
		$controller->maybe_display_update_screen( '2.0' );

		update_option( 'gfpdf_current_version', '1.0.5' );
		$controller->maybe_display_update_screen( '1.2.5' );

		/* Check it gets skipped when we disable the update screen option */
		$gfpdf->options->update_option( 'update_screen_action', 'Disable' );
		$controller->maybe_display_update_screen( '1.2.5' );
	}
}
