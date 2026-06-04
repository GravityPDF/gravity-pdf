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

	public function set_up(): void {
		parent::set_up();

		$this->controller = Controller_Uninstaller::get_instance();
	}

	public function test_get_instance_returns_singleton() {
		$this->assertSame( $this->controller, Controller_Uninstaller::get_instance() );
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

	public function test_render_uninstall_outputs_button_markup_for_authorised_user() {
		$admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}
		wp_set_current_user( $admin );

		ob_start();
		$this->controller->render_uninstall();
		$output = ob_get_clean();

		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( 'gform-settings-panel__addon-uninstall', $output );
		$this->assertStringContainsString( 'name="uninstall_addon"', $output );
		$this->assertStringContainsString( 'Gravity PDF', $output );
	}

}
