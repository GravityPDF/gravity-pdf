<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   install
 */
class Test_Controller_Install extends TestCase {

	/**
	 * @var Controller_Install
	 */
	private $controller;

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$this->controller = $gfpdf->singleton->get_class( 'Controller_Install' );
	}

	public function test_init_registers_action_and_filter_hooks() {
		remove_all_actions( 'wp_loaded' );
		remove_all_actions( 'init' );
		remove_all_filters( 'query_vars' );

		$this->controller->init();

		$this->assertNotFalse( has_action( 'wp_loaded', [ $this->controller, 'check_install_status' ] ) );
		$this->assertNotFalse( has_action( 'init' ) );
		$this->assertNotFalse( has_filter( 'query_vars' ) );
	}

	public function test_setup_defaults_populates_data_object() {
		global $gfpdf;

		$gfpdf->data->is_installed   = null;
		$gfpdf->data->permalink      = null;
		$gfpdf->data->working_folder = null;
		$gfpdf->data->upload_dir     = null;

		$this->controller->setup_defaults();

		$this->assertIsBool( $gfpdf->data->is_installed );
		$this->assertSame( 'pdf/([A-Za-z0-9]+)/([0-9]+)/?(download)?/?', $gfpdf->data->permalink );
		$this->assertNotEmpty( $gfpdf->data->working_folder );
		$this->assertNotEmpty( $gfpdf->data->upload_dir );
		$this->assertSame( 'gfpdf_template_info', $gfpdf->data->template_transient_cache );
	}

	public function test_check_install_status_short_circuits_for_unauthenticated_request() {
		wp_set_current_user( 0 );
		set_current_screen( 'edit.php' );

		$before = get_option( 'gfpdf_current_version' );
		$this->controller->check_install_status();

		$this->assertSame( $before, get_option( 'gfpdf_current_version' ) );
	}

	public function test_check_install_status_syncs_version_for_admin_when_version_mismatched() {
		set_current_screen( 'edit.php' );
		$admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );

		/* Multisite gates activate_plugins behind super admin; without the promotion
		   check_install_status() short-circuits on the capability check. No-op on
		   single-site. */
		grant_super_admin( $admin );

		update_option( 'gfpdf_current_version', '0.0.1' );

		$captured = [];
		add_action(
			'gfpdf_version_changed',
			static function ( $old, $new ) use ( &$captured ) {
				$captured = [ $old, $new ];
			},
			10,
			2
		);

		$this->controller->check_install_status();

		$this->assertSame( PDF_EXTENDED_VERSION, get_option( 'gfpdf_current_version' ) );
		$this->assertSame( [ '0.0.1', PDF_EXTENDED_VERSION ], $captured );
	}

	public function test_maybe_uninstall_emits_doing_it_wrong_notice() {
		$this->setExpectedDeprecated( 'GFPDF\Controller\Controller_Install::maybe_uninstall' );

		$this->controller->maybe_uninstall();
	}
}
