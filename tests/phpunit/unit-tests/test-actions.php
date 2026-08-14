<?php

namespace GFPDF\Tests;

use Exception;
use GFPDF\Controller\Controller_Actions;
use GFPDF\Model\Model_Actions;
use GFPDF\View\View_Actions;
use WP_UnitTestCase;

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
class Test_Actions extends WP_UnitTestCase {
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
	public function set_up() {
		global $gfpdf;

		/* run parent method */
		parent::set_up();

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
	 * The deprecation notice styles itself from what was detected, which is a database read — so the route defers it
	 * rather than paying for it on every admin page that never shows the notice
	 *
	 * @since 6.17
	 */
	public function test_deprecated_feature_route_defers_its_notice_class() {
		$route = array_column( $this->controller->get_routes(), null, 'action' )['deprecated_features'];

		$this->assertIsCallable( $route['view_class'] );

		/* Every v3 feature still works until 7.0, so the notice is a warning rather than an error */
		$this->assertSame( 'notice-warning', call_user_func( $route['view_class'] ) );
	}

	/**
	 * `view_class` is public API carrying a CSS class, and plenty of one-word class names are also PHP function
	 * names — `link`, `key`, `header`. Resolving on `is_callable()` alone would call one and fatal the admin.
	 *
	 * @since 6.17
	 */
	public function test_route_notices_never_calls_a_string_view_class() {
		global $gfpdf;

		set_current_screen( 'edit.php' );

		add_filter(
			'gfpdf_one_time_action_routes',
			function( $routes ) {

				return [
					[
						'action'      => 'test_action',
						'action_text' => 'My Test Action',
						'condition'   => '__return_true',
						'process'     => '__return_true',
						'view'        => function() {
							return 'my test view';
						},
						'view_class'  => 'link',
						'capability'  => 'gravityforms_view_settings',
					],
				];
			}
		);

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$this->controller->route_notices();

		ob_start();
		$gfpdf->notices->process();
		$html = ob_get_clean();

		/* Carried through verbatim as a class, and no `notice-` prefix so it joins the default state */
		$this->assertStringContainsString( 'class="notice updated link"', $html );

		$gfpdf->notices->clear();
		wp_set_current_user( 0 );
	}

	/**
	 * A callable is resolved when the notice displays, so nothing queries the site to style one that never renders
	 *
	 * @since 6.17
	 */
	public function test_route_notices_resolves_a_callable_view_class() {
		global $gfpdf;

		set_current_screen( 'edit.php' );

		add_filter(
			'gfpdf_one_time_action_routes',
			function( $routes ) {

				return [
					[
						'action'      => 'test_action',
						'action_text' => 'My Test Action',
						'condition'   => '__return_true',
						'process'     => '__return_true',
						'view'        => function() {
							return 'my test view';
						},
						'view_class'  => function() {
							return 'notice-error';
						},
						'capability'  => 'gravityforms_view_settings',
					],
				];
			}
		);

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$this->controller->route_notices();

		ob_start();
		$gfpdf->notices->process();
		$html = ob_get_clean();

		/* A `notice-*` class replaces the default state rather than joining it */
		$this->assertStringContainsString( 'class="notice notice-error"', $html );

		$gfpdf->notices->clear();
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
		} catch ( Exception $e ) {
			/* Expected */
		}

		$this->assertEquals( 'You do not have permission to access this page', $e->getMessage() );

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
		$type = 'review_plugin';

		$this->assertFalse( $this->model->is_notice_already_dismissed( $type ) );
		$this->model->dismiss_notice( $type );
		$this->assertTrue( $this->model->is_notice_already_dismissed( $type ) );
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

		$this->assertNotFalse( strpos( $html, 'Review</button>' ) );
		$this->assertNotFalse( strpos( $html, 'name="gfpdf-dismiss-notice"' ) );

		/* Check action button without dismissal button */
		$html = $this->view->get_action_buttons( 'review_plugin', 'Review', 'disabled' );

		$this->assertNotFalse( strpos( $html, 'Review</button>' ) );
		$this->assertFalse( strpos( $html, 'name="gfpdf-dismiss-notice"' ) );
	}
}
