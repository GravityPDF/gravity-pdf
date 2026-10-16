<?php

declare( strict_types=1 );

namespace GFPDF\Model;
use Exception;
use GFPDF\Controller\Controller_Actions;
use GFPDF\Model\Model_Actions;
use GFPDF\View\View_Actions;
use GFPDF\Tests\Integration\TestCase;

/**
 * Test Gravity PDF Actions functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the model / view / controller for the Actions MVC
 *
 * @since 4.0
 *
 * @group actions
 */
class Test_Actions extends TestCase {
	/**
	 * Our Controller
	 *
	 * @var Controller_Actions
	 *
	 * @since 4.0
	 */
	public $controller;

	/**
	 * Our Model
	 *
	 * @var Model_Actions
	 *
	 * @since 4.0
	 */
	public $model;

	/**
	 * Our View
	 *
	 * @var View_Actions
	 *
	 * @since 4.0
	 */
	public $view;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function set_up(): void {
		global $gfpdf;

		/* run parent method */
		parent::set_up();

		/* Shared singleton — drop any notices leaked from prior tests */
		$gfpdf->notices->clear();

		/*
		 * Re-sync options cache with DB. test_route() inside this class dismisses the 'test_action'
		 * notice via the options cache; the DB write rolls back at end of test but the in-memory
		 * cache persists, leaving is_notice_already_dismissed() returning true for later tests.
		 */
		$gfpdf->options->set_plugin_settings();

		/* Setup our test classes */
		$this->model = new Model_Actions( $gfpdf->data, $gfpdf->options, $gfpdf->notices );
		$this->view  = new View_Actions( [] );

		$this->controller = new Controller_Actions( $this->model, $this->view, $gfpdf->gform, $gfpdf->log, $gfpdf->notices );
		$this->controller->init();
	}

	/**
	 * Test the appropriate actions are set up
	 *
	 * @since 4.0
	 */
	public function test_actions() {
		$this->assertSame( 10, has_action( 'admin_init', [ $this->controller, 'route' ] ) );
		$this->assertSame( 20, has_action( 'admin_init', [ $this->controller, 'route_notices' ] ) );
	}

	/**
	 * Test we are registering the required default routes
	 *
	 * @since 4.0
	 */
	public function test_get_routes() {
		$routes = $this->controller->get_routes();
		$this->assertGreaterThan( 0, $routes );
	}

	/**
	 * Test route notices are displayed correctly (verify capability, check for dismissal, check condition met)
	 *
	 * @since 4.0
	 */
	public function test_route_notices() {
		global $gfpdf;

		set_current_screen( 'edit.php' );

		/* Set up a custom route */
		add_filter(
			'gfpdf_one_time_action_routes',
			function( $routes ) {

				return [
					[
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
					],
				];
			}
		);

		/* Verify no notices present */
		$this->assertFalse( $gfpdf->notices->has_notice() );

		/* Test failure due to no capabilities */
		$this->controller->route_notices();

		/* Verify no notices present */
		$this->assertFalse( $gfpdf->notices->has_notice() );

		/* Set up authorized user */
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertIsInt( $user_id );
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
	 * Test route notices are displayed correctly (verify capability, check for dismissal, check condition met)
	 *
	 * @since 4.0
	 */
	public function test_route_notices_fail_condition() {
		global $gfpdf;

		/* Set up a custom route */
		add_filter(
			'gfpdf_one_time_action_routes',
			function( $routes ) {

				return [
					[
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
					],
				];
			}
		);

		/* Set up authorized user */
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertIsInt( $user_id );
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
		add_filter(
			'gfpdf_one_time_action_routes',
			function( $routes ) {

				return [
					[
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
					],
				];
			}
		);

		$_POST['gfpdf_action'] = 'gfpdf_test_action';

		/* Fail capability check */
		try {
			$this->controller->route();
			$this->fail( 'Expected Exception was not thrown on failed capability check.' );
		} catch ( Exception $e ) {
			$this->assertSame( 'You do not have permission to access this page', $e->getMessage() );
		}

		/* Set up authorized user */
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertIsInt( $user_id );
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
	 * Check if the notice dismissal checker is accurate
	 *
	 * @since 4.0
	 */
	public function test_is_notice_already_dismissed() {
		global $gfpdf;

		$type = 'review_plugin';

		$this->assertFalse( $this->model->is_notice_already_dismissed( $type ) );
		$this->model->dismiss_notice( $type );
		$this->assertTrue( $this->model->is_notice_already_dismissed( $type ) );

		/* A dismissal can be limited to one made since a given time, and to a set period */
		$this->assertFalse( $this->model->is_notice_already_dismissed( $type, time() + 10 ) );
		$this->assertTrue( $this->model->is_notice_already_dismissed( $type, 0, HOUR_IN_SECONDS ) );

		/* Dismissals before 6.17.3 hold the notice ID: still dismissed, but too old for a time limit */
		$gfpdf->options->update_option( 'action_dismissal', [ $type => $type ] );
		$this->assertTrue( $this->model->is_notice_already_dismissed( $type ) );
		$this->assertFalse( $this->model->is_notice_already_dismissed( $type, 0, HOUR_IN_SECONDS ) );
	}

	/**
	 * @since 6.17.3
	 */
	public function test_unwritable_folders_notice() {
		$tmp   = \GPDFAPI::get_data_class()->template_tmp_location;
		$since = time() - HOUR_IN_SECONDS;

		update_option( Model_Install::UNWRITABLE_FOLDERS, [ $tmp . 'unwritable-a' => $since, $tmp . 'unwritable-b' => $since ] );

		$route = array_column( $this->controller->get_routes(), null, 'action' )['unwritable_folders'];
		$this->assertTrue( call_user_func( $route['condition'] ) );

		$html = call_user_func( $route['view'], $route['action'], $route['action_text'] );
		$this->assertStringContainsString( 'unwritable-a', $html );
		$this->assertStringContainsString( 'name="gfpdf-dismiss-notice"', $html );

		call_user_func( $route['dismiss'] );
		$this->assertFalse( call_user_func( $route['condition'] ) );

		/* A folder that fails after the dismissal raises the notice again, listing only that folder */
		update_option( Model_Install::UNWRITABLE_FOLDERS, [ $tmp . 'unwritable-a' => $since, $tmp . 'unwritable-c' => $since ] );
		$this->assertSame( [ $tmp . 'unwritable-c' ], $this->model->get_undismissed_unwritable_folders() );

		/* So does a dismissed folder that recovered and has failed again since */
		update_option( Model_Install::UNWRITABLE_FOLDERS, [ $tmp . 'unwritable-a' => time() + 1 ] );
		$this->assertSame( [ $tmp . 'unwritable-a' ], $this->model->get_undismissed_unwritable_folders() );

		delete_option( Model_Install::UNWRITABLE_FOLDERS );
	}

	/**
	 * @since 6.17.3
	 */
	public function test_unwritable_folders_notice_returns_a_week_after_its_dismissal() {
		global $gfpdf;

		$dir = \GPDFAPI::get_data_class()->template_tmp_location . 'unwritable-a';

		update_option( Model_Install::UNWRITABLE_FOLDERS, [ $dir => time() - MONTH_IN_SECONDS ] );
		$this->model->dismiss_unwritable_folders();
		$this->assertSame( [], $this->model->get_undismissed_unwritable_folders() );

		$key = Model_Actions::UNWRITABLE_FOLDER_DISMISSAL . md5( $dir );
		$gfpdf->options->update_option( 'action_dismissal', [ $key => time() - Model_Actions::UNWRITABLE_FOLDER_SNOOZE - 1 ] );
		$this->assertSame( [ $dir ], $this->model->get_undismissed_unwritable_folders() );

		delete_option( Model_Install::UNWRITABLE_FOLDERS );
	}

	/**
	 * Check the core fonts installation prompt works as expected
	 *
	 * @since 5.0
	 */
	public function test_core_fonts_condition() {
		global $gfpdf;

		$path = $gfpdf->data->template_font_location;
		set_current_screen( 'edit.php' );

		$this->assertTrue( $this->model->core_font_condition() );

		touch( $path . 'DejaVuSansCondensed.ttf' );
		$this->assertFalse( $this->model->core_font_condition() );
		unlink( $path . 'DejaVuSansCondensed.ttf' );

		$_GET['page']    = 'gfpdf-page';
		$_GET['subview'] = 'PDF';
		$_GET['tab']     = 'tools';
		$this->assertFalse( $this->model->core_font_condition() );
	}

	/**
	 * Check our primary action button view generates correctly
	 *
	 * @since 4.0
	 */
	public function test_get_action_buttons() {

		$html = $this->view->get_action_buttons( 'review_plugin', 'Review' );

		$this->assertStringContainsString( 'Review</button>', $html );
		$this->assertStringContainsString( 'name="gfpdf-dismiss-notice"', $html );

		/* Check action button without dismissal button */
		$html = $this->view->get_action_buttons( 'review_plugin', 'Review', 'disabled' );

		$this->assertStringContainsString( 'Review</button>', $html );
		$this->assertStringNotContainsString( 'name="gfpdf-dismiss-notice"', $html );
	}
}
