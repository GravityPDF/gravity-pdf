<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   uninstaller
 */
class Test_Controller_Uninstaller extends TestCase {

	/**
	 * @var Controller_Uninstaller
	 */
	private $controller;

	public function set_up() {
		parent::set_up();

		$this->controller = Controller_Uninstaller::get_instance();
	}

	public function test_get_instance_returns_singleton() {
		$this->assertSame( $this->controller, Controller_Uninstaller::get_instance() );
	}

	public function test_get_short_title_returns_plugin_name() {
		$this->assertSame( 'Gravity PDF', $this->controller->get_short_title() );
	}

	public function test_get_menu_icon_returns_gpdf_icon_class() {
		$this->assertSame( 'gform-icon--gravity-pdf', $this->controller->get_menu_icon() );
	}

	public function test_method_is_overridden_always_returns_false() {
		$this->assertFalse( $this->controller->method_is_overridden( 'any_method' ) );
		$this->assertFalse( $this->controller->method_is_overridden( '' ) );
	}

	public function test_current_user_can_uninstall_grants_admin_on_single_site() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Single-site path only.' );
		}

		$admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );

		$this->assertTrue( $this->controller->current_user_can_uninstall() );
	}

	public function test_current_user_can_uninstall_denies_subscriber_on_single_site() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Single-site path only.' );
		}

		$subscriber = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber );

		$this->assertFalse( $this->controller->current_user_can_uninstall() );
	}

	public function test_render_uninstall_outputs_nothing_when_capability_denied() {
		$subscriber = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber );

		ob_start();
		$this->controller->render_uninstall();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

}
