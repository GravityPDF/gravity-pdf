<?php

namespace GFPDF\Tests;

use Exception;
use GFPDF\Controller\Controller_Install;
use GFPDF\Controller\Controller_Uninstaller;
use GFPDF\Helper\Helper_Pdf_Queue;
use GFPDF\Model\Model_Install;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Installer functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the model / controller for the Installer
 *
 * @since 4.0
 * @group installer
 */
class Test_Installer extends WP_UnitTestCase {
	/**
	 * Our Controller
	 *
	 * @var Controller_Install
	 *
	 * @since 4.0
	 */
	public $controller;

	/**
	 * Our Model
	 *
	 * @var Model_Install
	 *
	 * @since 4.0
	 */
	public $model;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function setUp() {
		global $gfpdf;

		/* run parent method */
		parent::setUp();

		$uninstaller = Controller_Uninstaller::get_instance();

		/* Setup our test classes */
		$this->model = new Model_Install( $gfpdf->log, $gfpdf->data, $gfpdf->misc, $gfpdf->notices, new Helper_Pdf_Queue( $gfpdf->log ), $uninstaller->model );

		$this->controller = new Controller_Install( $this->model, $gfpdf->gform, $gfpdf->log, $gfpdf->notices, $gfpdf->data, $gfpdf->misc );
		$this->controller->init();
	}

	/**
	 * Test the appropriate actions are set up
	 *
	 * @since 4.0
	 */
	public function test_actions() {
		$this->assertEquals( 9999, has_action( 'wp_loaded', [ $this->controller, 'check_install_status' ] ) );

		$this->assertEquals( 10, has_action( 'init', [ $this->model, 'register_rewrite_rules' ] ) );
	}

	/**
	 * Test the appropriate filters are set up
	 *
	 * @since 4.0
	 */
	public function test_filters() {
		$this->assertEquals( 10, has_filter( 'query_vars', [ $this->model, 'register_rewrite_tags' ] ) );
	}

	/**
	 * Check if the plugin has been installed (otherwise run installer) and the version number is up to date
	 *
	 * @since 4.0
	 */
	public function test_install_status() {
		global $gfpdf;

		/* Check the plugin marks the appropriate data key as true when installed */
		$gfpdf->data->is_installed = false;

		/* Set admin screen */
		set_current_screen( 'edit.php' );

		/* Set up authorized user */
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertIsInt( $user_id );

		if ( is_multisite() ) {
			grant_super_admin( $user_id );
		}

		wp_set_current_user( $user_id );

		$this->controller->check_install_status();
		$this->assertTrue( $gfpdf->data->is_installed );

		/* Check the current version is tracked correctly */
		delete_option( 'gfpdf_current_version' );
		$this->controller->check_install_status();
		$this->assertEquals( PDF_EXTENDED_VERSION, get_option( 'gfpdf_current_version' ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Test we are marking the plugin as installed correctly
	 *
	 * @since 4.0
	 */
	public function test_install_plugin() {
		global $gfpdf;

		delete_option( 'gfpdf_is_installed' );
		$gfpdf->data->is_installed = false;
		$this->assertFalse( get_option( 'gfpdf_is_installed' ) );
		$this->assertFalse( $gfpdf->data->is_installed );

		$this->model->install_plugin();

		$this->assertTrue( get_option( 'gfpdf_is_installed' ) );
		$this->assertTrue( $gfpdf->data->is_installed );
	}

	/**
	 * Check the multisite template location is set up correctly
	 *
	 * @since 4.0
	 */
	public function test_multisite_template_location() {
		global $gfpdf;
		if ( ! is_multisite() ) {
			$this->markTestSkipped(
				'Not running multisite tests'
			);
		}

		$this->assertTrue( is_dir( $gfpdf->data->multisite_template_location ) );
	}

	/**
	 * Check our folder structure is created as expected
	 *
	 * @since 4.0
	 */
	public function test_create_folder_structures() {
		global $gfpdf;

		/* Remove folder structure */
		$gfpdf->misc->rmdir( $gfpdf->data->template_location );

		/* Verify folder structure is nonexistent and then create */
		$this->assertFileNotExists( $gfpdf->data->template_location );
		$this->model->create_folder_structures();

		/* Test the results */
		$this->assertTrue( is_dir( $gfpdf->data->template_location ) );
		$this->assertTrue( is_dir( $gfpdf->data->template_font_location ) );
		$this->assertTrue( is_dir( $gfpdf->data->template_tmp_location ) );
		$this->assertTrue( is_dir( $gfpdf->data->mpdf_tmp_location ) );
		$this->assertTrue( is_dir( $gfpdf->data->mpdf_tmp_location . '/ttfontdata' ) );

		$this->assertTrue( is_file( $gfpdf->data->template_tmp_location . '.htaccess' ) );
		$this->assertTrue( is_file( $gfpdf->data->template_tmp_location . 'index.html' ) );
		$this->assertTrue( is_file( $gfpdf->data->template_font_location . 'index.html' ) );
		$this->assertTrue( is_file( $gfpdf->data->template_location . 'index.html' ) );

		/* Test our directory filters */
		add_filter(
			'gfpdf_template_location',
			function( $path, $folder ) {
				return ABSPATH . $folder;
			},
			10,
			2
		);

		add_filter(
			'gfpdf_template_location_uri',
			function( $url, $folder ) {
				return home_url( '/' ) . $folder;
			},
			10,
			2
		);

		add_filter(
			'gfpdf_tmp_location',
			function( $path ) {
				return ABSPATH . 'wp-content/tmp/';
			}
		);

		add_filter(
			'gfpdf_font_location',
			function( $path ) {
				return ABSPATH . 'wp-content/pdf-fonts/';
			}
		);

		/* Apply our new filters */
		$this->model->setup_template_location();

		/* Remove folder structure */
		$gfpdf->misc->rmdir( $gfpdf->data->template_location );

		/* Create our folder structure */
		$this->model->create_folder_structures();

		/* Test the results */
		$this->assertTrue( is_dir( ABSPATH . 'PDF_EXTENDED_TEMPLATES' ) );
		$this->assertTrue( is_dir( ABSPATH . 'wp-content/pdf-fonts' ) );
		$this->assertTrue( is_dir( ABSPATH . 'wp-content/tmp' ) );

		/* Cleanup folder structure and reset the template location */
		$gfpdf->misc->rmdir( $gfpdf->data->template_location );
		$gfpdf->misc->rmdir( $gfpdf->data->template_font_location );
		$gfpdf->misc->rmdir( $gfpdf->data->template_tmp_location );

		remove_all_filters( 'gfpdf_template_location' );
		remove_all_filters( 'gfpdf_template_location_uri' );
		remove_all_filters( 'gfpdf_tmp_location' );
		remove_all_filters( 'gfpdf_font_location' );

		$this->model->setup_template_location();
	}

	/**
	 * Check our rewrite rules get registered correctly
	 *
	 * @since 4.0
	 */
	public function test_register_rewrite_rules() {
		global $wp_rewrite, $gfpdf;

		$this->assertEquals( 'index.php?gpdf=1&pid=$matches[1]&lid=$matches[2]&action=$matches[3]', $wp_rewrite->extra_rules_top[ '^' . $gfpdf->data->permalink ] );
		$this->assertEquals( 'index.php?gpdf=1&pid=$matches[1]&lid=$matches[2]&action=$matches[3]', $wp_rewrite->extra_rules_top[ '^' . $wp_rewrite->root . $gfpdf->data->permalink ] );
	}
}
