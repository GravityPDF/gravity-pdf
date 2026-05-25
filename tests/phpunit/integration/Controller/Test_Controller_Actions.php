<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   actions
 */
class Test_Controller_Actions extends TestCase {

	/**
	 * @var Controller_Actions
	 */
	private $controller;

	public function set_up() {
		global $gfpdf;

		parent::set_up();

		$this->controller = $gfpdf->singleton->get_class( 'Controller_Actions' );
	}

	public function tear_down() {
		unset( $_POST['gfpdf_action'], $_POST['gfpdf-dismiss-notice'], $_GET['page'] );

		parent::tear_down();
	}

	public function test_init_registers_admin_init_hooks() {
		remove_all_actions( 'admin_init' );

		$this->controller->init();

		$this->assertNotFalse( has_action( 'admin_init', [ $this->controller, 'route' ] ) );
		$this->assertNotFalse( has_action( 'admin_init', [ $this->controller, 'route_notices' ] ) );
	}

	public function test_get_routes_includes_default_core_fonts_route() {
		$routes = $this->controller->get_routes();

		$this->assertCount( 1, $routes );
		$this->assertSame( 'install_core_fonts', $routes[0]['action'] );
		$this->assertSame( 'gravityforms_edit_settings', $routes[0]['capability'] );
		$this->assertIsCallable( $routes[0]['condition'] );
		$this->assertIsCallable( $routes[0]['process'] );
		$this->assertIsCallable( $routes[0]['view'] );
	}

	public function test_get_routes_is_filterable() {
		add_filter(
			'gfpdf_one_time_action_routes',
			static function ( $routes ) {
				$routes[] = [ 'action' => 'custom' ];

				return $routes;
			}
		);

		$routes = $this->controller->get_routes();
		remove_all_filters( 'gfpdf_one_time_action_routes' );

		$this->assertCount( 2, $routes );
		$this->assertSame( 'custom', $routes[1]['action'] );
	}

	public function test_route_notices_short_circuits_on_getting_started_page() {
		global $gfpdf;

		$gfpdf->notices->clear();
		$_GET['page'] = 'gfpdf-getting-started';
		set_current_screen( 'gf_settings' );

		$this->controller->route_notices();

		$this->assertFalse( $gfpdf->notices->has_notice() );
	}

	public function test_route_does_nothing_when_post_action_absent() {
		$this->controller->route();

		$this->assertTrue( true );
	}

	public function test_route_dismisses_notice_when_dismiss_flag_set() {
		global $gfpdf;

		$admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );
		set_current_screen( 'edit.php' );

		add_filter(
			'gfpdf_one_time_action_routes',
			static function () {
				return [
					[
						'action'      => 'always_true',
						'action_text' => 'Always',
						'condition'   => '__return_true',
						'process'     => static function () {},
						'view'        => static function () { return ''; },
						'capability'  => 'gravityforms_edit_settings',
					],
				];
			}
		);

		$_POST['gfpdf_action']            = 'gfpdf_always_true';
		$_POST['gfpdf_action_always_true'] = wp_create_nonce( 'gfpdf_action_always_true' );
		$_POST['gfpdf-dismiss-notice']    = '1';

		$model = $gfpdf->singleton->get_class( 'Model_Actions' );
		$this->controller->route();

		remove_all_filters( 'gfpdf_one_time_action_routes' );

		$this->assertTrue( $model->is_notice_already_dismissed( 'always_true' ) );
	}
}
