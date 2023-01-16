<?php

namespace GFPDF\Tests;

use Exception;
use GFPDF\Controller\Controller_Install;
use GFPDF\Controller\Controller_Uninstaller;
use GFPDF\Helper\Helper_Pdf_Queue;
use GFPDF\Model\Model_Install;
use GFPDF\Model\Model_Uninstall;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Uninstall functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the model / controller for the Uninstaller
 *
 * @since 6.0
 * @group uninstaller
 */
class Test_Uninstaller extends WP_UnitTestCase {

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

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 6.0
	 */
	public function set_up() {
		parent::set_up();

		$this->controller = Controller_Uninstaller::get_instance();
		$this->model      = $this->controller->model;
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
		$installer->check_install_status();

		/* Verify the plugin is installed correctly before removing */
		$this->assertTrue( is_dir( $gfpdf->data->template_location ) );
		$this->assertNotFalse( get_option( 'gfpdf_current_version' ) );

		/* Uninstall */
		$this->model->uninstall_plugin();

		/* Check software was uninstalled */
		$this->assertFalse( is_dir( $gfpdf->data->template_location ) );
		$this->assertFalse( get_option( 'gfpdf_current_version' ) );

		/* Reinstall */
		$installer->setup_defaults();

		/* Verify the install works correctly */
		$this->assertTrue( is_dir( $gfpdf->data->template_location ) );

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
		$installer->check_install_status();

		update_option( 'gfpdf_settings', [] );

		$this->assertNotFalse( get_option( 'gfpdf_is_installed' ) );
		$this->assertNotFalse( get_option( 'gfpdf_current_version' ) );
		$this->assertNotFalse( get_option( 'gfpdf_settings' ) );

		$this->model->remove_plugin_options();

		$this->assertFalse( get_option( 'gfpdf_is_installed' ) );
		$this->assertFalse( get_option( 'gfpdf_current_version' ) );
		$this->assertFalse( get_option( 'gfpdf_settings' ) );

		wp_set_current_user( 0 );
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
}
