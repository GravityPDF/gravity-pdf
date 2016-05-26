<?php

namespace GFPDF\Tests;

use GFPDF\Controller\Controller_Actions;
use GFPDF\Model\Model_Actions;
use GFPDF\View\View_Actions;

use WP_UnitTestCase;

use Exception;

/**
 * Test Gravity PDF Actions functionality
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
 * Test the model / view / controller for the Actions MVC
 *
 * @since 4.0
 *
 * @group actions
 */
class Test_Actions extends WP_UnitTestCase {
	/**
	 * Our Controller
	 *
	 * @var \GFPDF\Controller\Controller_Actions
	 *
	 * @since 4.0
	 */
	public $controller;

	/**
	 * Our Model
	 *
	 * @var \GFPDF\Model\Model_Actions
	 *
	 * @since 4.0
	 */
	public $model;

	/**
	 * Our View
	 *
	 * @var \GFPDF\View\View_Actions
	 *
	 * @since 4.0
	 */
	public $view;

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
		$this->model = new Model_Actions( $gfpdf->data, $gfpdf->options, $gfpdf->notices );
		$this->view  = new View_Actions( array() );

		$this->controller = new Controller_Actions( $this->model, $this->view, $gfpdf->gform, $gfpdf->log, $gfpdf->notices );
		$this->controller->init();
	}

	/**
	 * Test the appropriate actions are set up
	 *
	 * @since 4.0
	 */
	public function test_actions() {
		$this->assertSame( 10, has_action( 'admin_init', array( $this->controller, 'route' ) ) );
		$this->assertSame( 20, has_action( 'admin_init', array( $this->controller, 'route_notices' ) ) );
	}

	/**
	 * Test we are registering the required default routes
	 *
	 * @since 4.0
	 */
	public function test_get_routes() {

		$routes = $this->controller->get_routes();

		$counter  = 0;
		$expected = array( 'review_plugin', 'migrate_v3_to_v4' );

		foreach ( $routes as $route ) {
			if ( in_array( $route['action'], $expected ) ) {
				$counter++;
			}
		}

		$this->assertSame( 2, $counter );
	}

	/**
	 * Test route notices are displayed correctly (verfiy capability, check for dismissal, check condition met)
	 *
	 * @since 4.0
	 */
	public function test_route_notices() {
		global $gfpdf;

		set_current_screen( 'edit.php' );

		/* Set up a custom route */
		add_filter( 'gfpdf_one_time_action_routes', function( $routes ) {

			return array(
				array(
					'action'      => 'test_action',
					'action_text' => 'My Test Action',
					'condition'   => function() {
						return true;
					},
					'process'     => function() {
						echo 'processing';
					},
					'view'        => function() {
						return 'my test view';
					},
					'capability'  => 'gravityforms_view_settings',
				),
			);
		} );

		/* Verify no notices present */
		$this->assertFalse( $gfpdf->notices->has_notice() );

		/* Test failure due to no capabilities */
		$this->controller->route_notices();

		/* Verify no notices present */
		$this->assertFalse( $gfpdf->notices->has_notice() );

		/* Set up authorized user */
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );

		/* Verify notice now present */
		$this->controller->route_notices();

		/* Verify notice now exists */
		$this->assertTrue( $gfpdf->notices->has_notice() );

		/* Cleanup notices */
		$gfpdf->notices->clear();

		/* Check routes aren't handled when not in admin area */
		set_current_screen( 'front' );

		$this->controller->route_notices();
		$this->assertFalse( $gfpdf->notices->has_notice() );

		wp_set_current_user( 0 );
	}

	/**
	 * Test route notices are displayed correctly (verfiy capability, check for dismissal, check condition met)
	 *
	 * @since 4.0
	 */
	public function test_route_notices_fail_condition() {
		global $gfpdf;

		/* Set up a custom route */
		add_filter( 'gfpdf_one_time_action_routes', function( $routes ) {

			return array(
				array(
					'action'      => 'test_action',
					'action_text' => 'My Test Action',
					'condition'   => function() {
						return false;
					},
					'process'     => function() {
						echo 'processing';
					},
					'view'        => function() {
						return 'my test view';
					},
					'capability'  => 'gravityforms_view_settings',
				),
			);
		} );

		/* Set up authorized user */
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );

		/* Verify no notices present */
		$this->assertFalse( $gfpdf->notices->has_notice() );

		/* Verify notice now present */
		$this->controller->route_notices();

		/* Verify notice now exists */
		$this->assertFalse( $gfpdf->notices->has_notice() );

		/* Cleanup notices */
		$gfpdf->notices->clear();

		wp_set_current_user( 0 );
	}

	/**
	 * Test the route actions trigger correctly
	 *
	 * @since 4.0
	 */
	public function test_route() {

		/* Set up a custom route */
		add_filter( 'gfpdf_one_time_action_routes', function( $routes ) {

			return array(
				array(
					'action'      => 'test_action',
					'action_text' => 'My Test Action',
					'condition'   => function() {
						return true;
					},
					'process'     => function() {
						echo 'processing';
					},
					'view'        => function() {
						return 'my test view';
					},
					'capability'  => 'gravityforms_view_settings',
				),
			);
		} );

		$_POST['gfpdf_action'] = 'gfpdf_test_action';

		/* Fail capability check */
		try {
			$this->controller->route();
		} catch ( Exception $e ) {
			/* Expected */
		}

		$this->assertEquals( 'You do not have permission to access this page', $e->getMessage() );

		/* Set up authorized user */
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );

		/* Force nonce fail */
		ob_start();
		$this->controller->route();
		$html = ob_get_clean();

		$this->assertSame( '', $html );

		/* Check action runs correctly */
		$_POST['gfpdf_action_test_action'] = wp_create_nonce( 'gfpdf_action_test_action' );
		ob_start();
		$this->controller->route();
		$html = ob_get_clean();

		$this->assertSame( 'processing', $html );

		/* Dismiss the notice */
		$_POST['gfpdf-dismiss-notice'] = 'yes';
		ob_start();
		$this->controller->route();
		$html = ob_get_clean();

		$this->assertSame( '', $html );
		$this->assertTrue( $this->model->is_notice_already_dismissed( 'test_action' ) );

		wp_set_current_user( 0 );

	}

	/**
	 * Check if the notice dismisal checker is accurate
	 *
	 * @since 4.0
	 */
	public function test_is_notice_already_dismissed() {
		$type = 'review_plugin';

		$this->assertFalse( $this->model->is_notice_already_dismissed( $type ) );
		$this->model->dismiss_notice( $type );
		$this->assertTrue( $this->model->is_notice_already_dismissed( $type ) );
	}


	/**
	 * Check the core review action conditional works as expected
	 *
	 * @since 4.0
	 */
	public function test_review_condition() {
		global $gfpdf;

		$this->assertFalse( $this->model->review_condition() );
		$gfpdf->options->update_option( 'pdf_count', 101 );
		$this->assertTrue( $this->model->review_condition() );
	}

	/**
	 * Check the core migration action conditional works as expected
	 *
	 * @since 4.0
	 */
	public function test_migration_condition() {
		global $gfpdf;

		$path = ( is_multisite() ) ? $gfpdf->data->multisite_template_location : $gfpdf->data->template_location;

		/* Multisite can only be run by super admins */
		if ( is_multisite() ) {
			$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
			grant_super_admin( $user_id );
			wp_set_current_user( $user_id );
		}

		@unlink( $path . 'configuration.php' );
		$this->assertFalse( $this->model->migration_condition() );
		touch( $path . 'configuration.php' );

		$this->assertTrue( $this->model->migration_condition() );

		unlink( $path . 'configuration.php' );

		wp_set_current_user( 0 );

	}

	/**
	 * Check our primary action button view generates correctly
	 *
	 * @since 4.0
	 */
	public function test_get_action_buttons() {

		$html = $this->view->get_action_buttons( 'review_plugin', 'Review' );

		$this->assertNotFalse( strpos( $html, 'Review</button>' ) );
		$this->assertNotFalse( strpos( $html, 'name="gfpdf-dismiss-notice"' ) );

		/* Check action button without dismissal button */
		$html = $this->view->get_action_buttons( 'review_plugin', 'Review', 'disabled' );

		$this->assertNotFalse( strpos( $html, 'Review</button>' ) );
		$this->assertFalse( strpos( $html, 'name="gfpdf-dismiss-notice"' ) );
	}
}
