<?php

declare( strict_types=1 );

namespace GFPDF\Model;
use Exception;
use GFPDF\Controller\Controller_Install;
use GFPDF\Controller\Controller_Uninstaller;
use GFPDF\Helper\Helper_Pdf_Queue;
use GFPDF\Model\Model_Install;
use GFPDF\Model\Model_Uninstall;
use GFPDF\Tests\Integration\TestCase;

/**
 * Test Gravity PDF Uninstall functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the model / controller for the Uninstaller
 *
 * @since 6.0
 * @group uninstaller
 */
class Test_Uninstaller extends TestCase {

	/**
	 * Our Controller
	 *
	 * @var Controller_Uninstaller
	 *
	 * @since 6.0
	 */
	public $controller;

	/**
	 * Our Model
	 *
	 * @var Model_Uninstall
	 *
	 * @since 6.0
	 */
	public $model;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ] );
	}

	public static function tear_down_after_class(): void {
		global $gfpdf;

		/*
		 * test_remove_plugin_options() wipes the gfpdf_settings DB row but
		 * $gfpdf->options->settings stays at whatever it was before that test
		 * ran. Reloading from the DB after the class finishes keeps the
		 * in-memory state authoritative for later classes that don't
		 * defensively reload settings themselves.
		 */
		$gfpdf->options->set_plugin_settings();

		/* Null the Controller_Uninstaller singleton so its captured Model_Uninstall can't leak into other classes. */
		$instance = new \ReflectionProperty( \GFPDF\Controller\Controller_Uninstaller::class, 'instance' );
		if ( PHP_VERSION_ID < 80100 ) {
			$instance->setAccessible( true ); /* required on PHP <8.1 to write a protected static */
		}
		$instance->setValue( null, null );

		parent::tear_down_after_class();
	}

	public function set_up(): void {
		parent::set_up();

		$this->controller = Controller_Uninstaller::get_instance();
		$this->model      = $this->controller->model;

		/* Reseed the fixture's PDF settings per test — GFAPI writes bypass the WP test transaction, so a prior uninstall test in this class leaves them unset. */
		$fixture_form = $this->form( 'all-form-fields' );
		$current      = \GFAPI::get_form( $fixture_form['id'] );
		if ( $current && ! isset( $current['gfpdf_form_settings'] ) ) {
			$current['gfpdf_form_settings'] = $fixture_form['gfpdf_form_settings'];
			\GFAPI::update_form( $current );
		}
	}

	/**
	 * Check we are uninstalling correctly
	 *
	 * @since 4.0
	 */
	public function test_uninstall_plugin() {
		global $gfpdf;

		/* Set admin screen */
		set_current_screen( 'edit.php' );

		/* Set up authorized user */
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertIsInt( $user_id );

		if ( is_multisite() ) {
			grant_super_admin( $user_id );
		}

		wp_set_current_user( $user_id );

		/** @var Controller_Install $installer */
		global $gfpdf;
		$installer_model = new Model_Install( $gfpdf->log, $gfpdf->data, $gfpdf->misc, $gfpdf->notices, new Helper_Pdf_Queue( $gfpdf->log ), $this->model );
		$installer       = new Controller_Install( $installer_model, $gfpdf->gform, $gfpdf->log, $gfpdf->notices, $gfpdf->data, $gfpdf->misc );

		/* Force install_plugin() to run even when a prior test left $data->is_installed=true after wiping the DB option */
		$gfpdf->data->is_installed = false;
		$installer->check_install_status();

		/* Verify the plugin is installed correctly before removing */
		$this->assertDirectoryExists( $gfpdf->data->template_location );
		$this->assertNotFalse( get_option( 'gfpdf_current_version' ) );

		/* Uninstall */
		$this->model->uninstall_plugin();

		/* Check software was uninstalled */
		$this->assertDirectoryDoesNotExist( $gfpdf->data->template_location );
		$this->assertFalse( get_option( 'gfpdf_current_version' ) );

		/* Reinstall */
		$installer->setup_defaults();

		/* Verify the install works correctly */
		$this->assertDirectoryExists( $gfpdf->data->template_location );

		wp_set_current_user( 0 );
	}

	/**
	 * Check we are removing all traces of our gfpdf options
	 *
	 * @since 4.0
	 */
	public function test_remove_plugin_options() {

		/* Set admin screen */
		set_current_screen( 'edit.php' );

		/* Set up authorized user */
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertIsInt( $user_id );

		if ( is_multisite() ) {
			grant_super_admin( $user_id );
		}

		wp_set_current_user( $user_id );

		/** @var Controller_Install $installer */
		global $gfpdf;
		$installer_model = new Model_Install( $gfpdf->log, $gfpdf->data, $gfpdf->misc, $gfpdf->notices, new Helper_Pdf_Queue( $gfpdf->log ), $this->model );
		$installer       = new Controller_Install( $installer_model, $gfpdf->gform, $gfpdf->log, $gfpdf->notices, $gfpdf->data, $gfpdf->misc );

		/* Force install_plugin() to run even when a prior test left $data->is_installed=true after wiping the DB option */
		$gfpdf->data->is_installed = false;
		$installer->check_install_status();

		update_option( 'gfpdf_settings', [] );
		update_option( 'gpdf_sl_abc_123', true );
		update_option( 'gpdf_sl_failed_123', true );

		$this->assertNotFalse( get_option( 'gfpdf_is_installed' ) );
		$this->assertNotFalse( get_option( 'gfpdf_current_version' ) );
		$this->assertNotFalse( get_option( 'gfpdf_settings' ) );
		$this->assertNotFalse( get_option( 'gpdf_sl_abc_123' ) );
		$this->assertNotFalse( get_option( 'gpdf_sl_failed_123' ) );

		$this->model->remove_plugin_options();

		/* flush the options cache so fresh values can be checked from the database */
		wp_cache_delete( 'alloptions', 'options' );

		$this->assertFalse( get_option( 'gfpdf_is_installed' ) );
		$this->assertFalse( get_option( 'gfpdf_current_version' ) );
		$this->assertFalse( get_option( 'gfpdf_settings' ) );
		$this->assertFalse( get_option( 'gpdf_sl_abc_123' ) );
		$this->assertFalse( get_option( 'gpdf_sl_failed_123' ) );

		wp_set_current_user( 0 );
	}

	public function test_remove_plugin_network_options() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network options only exist on Multisite' );
		}

		update_site_option( 'gpdf_sl_net_abc123', [ 'timeout' => time(), 'value' => '{}' ] );
		$this->assertNotFalse( get_site_option( 'gpdf_sl_net_abc123' ) );

		$this->model->remove_plugin_network_options();

		/* No cache flush here on purpose — the uninstall must invalidate what it deletes */
		$this->assertFalse( get_site_option( 'gpdf_sl_net_abc123' ) );
	}

	/**
	 * Check we are successfully removing our GF PDF Settings
	 *
	 * @since 4.0
	 */
	public function test_remove_plugin_form_settings() {
		global $gfpdf;

		/* Verify the form data is there */
		$forms = $gfpdf->gform->get_forms();
		$found = false;
		foreach ( $forms as $form ) {
			if ( isset( $form['gfpdf_form_settings'] ) ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found );

		/* Verify the form data is removed */
		$this->model->remove_plugin_form_settings();

		$new_forms = $gfpdf->gform->get_forms();
		foreach ( $new_forms as $form ) {
			$this->assertFalse( isset( $form['gfpdf_form_settings'] ) );
		}

		/* Reset forms */
		foreach ( $forms as $form ) {
			$gfpdf->gform->update_form( $form );
		}
	}

	public function test_remove_plugin_transients_clears_settings_user_data_transient() {
		set_transient( 'gfpdf_settings_user_data', [ 'key' => 'value' ], HOUR_IN_SECONDS );

		$this->assertNotFalse( get_transient( 'gfpdf_settings_user_data' ) );

		$this->model->remove_plugin_transients();

		$this->assertFalse( get_transient( 'gfpdf_settings_user_data' ) );
	}

	public function test_deactivate_plugin_defaults_basename_when_empty() {
		$this->model->deactivate_plugin( '' );

		$this->assertFalse( is_plugin_active( PDF_PLUGIN_BASENAME ) );
	}

	public function test_deactivate_plugin_wraps_string_basename_in_array() {
		$this->model->deactivate_plugin( 'unknown/plugin.php' );

		$this->assertFalse( is_plugin_active( 'unknown/plugin.php' ) );
	}
}
