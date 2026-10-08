<?php

declare( strict_types=1 );

namespace GFPDF\Model;
use GFPDF\Controller\Controller_Install;
use GFPDF\Controller\Controller_Uninstaller;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Model\Model_Install;
use GFPDF\Tests\Integration\TestCase;

/**
 * Test Gravity PDF Installer functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the model / controller for the Installer
 *
 * @since 4.0
 * @group installer
 */
class Test_Installer extends TestCase {
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

	public static function tear_down_after_class(): void {
		/* Null the Controller_Uninstaller singleton so its captured Model_Uninstall can't leak into other classes. */
		$instance = new \ReflectionProperty( Controller_Uninstaller::class, 'instance' );
		if ( PHP_VERSION_ID < 80100 ) {
			$instance->setAccessible( true ); /* required on PHP <8.1 to write a protected static */
		}
		$instance->setValue( null, null );

		parent::tear_down_after_class();
	}

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function set_up(): void {
		global $gfpdf;

		/* run parent method */
		parent::set_up();

		$uninstaller = Controller_Uninstaller::get_instance();

		/* Setup our test classes */
		$this->model = new Model_Install( $gfpdf->log, $gfpdf->data, $gfpdf->misc, $gfpdf->notices, $uninstaller->model );

		$this->controller = new Controller_Install( $this->model, $gfpdf->gform, $gfpdf->log, $gfpdf->notices, $gfpdf->data, $gfpdf->misc );
		$this->controller->init();
	}

	/**
	 * Test the appropriate actions are set up
	 *
	 * @since 4.0
	 */
	public function test_actions() {
		$this->assertSame( 9999, has_action( 'wp_loaded', [ $this->controller, 'check_install_status' ] ) );

		$this->assertSame( 10, has_action( 'init', [ $this->model, 'register_rewrite_rules' ] ) );
		$this->assertSame( 5, has_action( 'gfpdf_version_changed', [ $this->model, 'create_folder_structures' ] ) );
		$this->assertSame( 10, has_action( 'gfpdf_cleanup_tmp_dir', [ $this->model, 'create_folder_structures' ] ) );
	}

	/**
	 * @since 6.17.3
	 */
	public function test_setup_defaults_leaves_the_folders_to_the_installer() {
		global $gfpdf;

		$gfpdf->misc->rmdir( $gfpdf->data->template_location );

		$this->controller->setup_defaults();
		$this->assertDirectoryDoesNotExist( $gfpdf->data->template_location );

		$this->model->create_folder_structures();
	}

	/**
	 * @since 6.17.3
	 */
	public function test_create_folder_structures_stores_the_folders_it_cant_create_or_write_to() {
		global $gfpdf;

		$font_location = $gfpdf->data->template_font_location;
		$missing       = '/srv/gravity-pdf-addon/';

		$misc = $this->getMockBuilder( Helper_Misc::class )
			->setConstructorArgs( [ $gfpdf->log, $gfpdf->gform, $gfpdf->data ] )
			->onlyMethods( [ 'create_folder', 'is_directory_writable' ] )
			->getMock();

		$misc->method( 'create_folder' )->willReturnCallback(
			function( $dir ) use ( $missing ) {
				return $dir !== $missing;
			}
		);

		$misc->method( 'is_directory_writable' )->willReturnCallback(
			function( $dir ) use ( $font_location ) {
				return $dir !== $font_location;
			}
		);

		$add_folder = function( $folders ) use ( $missing ) {
			$folders[] = $missing;

			return $folders;
		};

		add_filter( 'gfpdf_installer_create_folders', $add_folder );

		$model = new Model_Install( $gfpdf->log, $gfpdf->data, $misc, $gfpdf->notices, Controller_Uninstaller::get_instance()->model );
		$this->assertSame( [ $font_location, $missing ], $model->create_folder_structures() );

		/* A folder that keeps failing keeps the time it started failing */
		update_option( Model_Install::UNWRITABLE_FOLDERS, [ $font_location => 1000 ] );
		$model->create_folder_structures();
		$this->assertSame( 1000, get_option( Model_Install::UNWRITABLE_FOLDERS )[ $font_location ] );

		remove_filter( 'gfpdf_installer_create_folders', $add_folder );
		delete_option( Model_Install::UNWRITABLE_FOLDERS );
	}


	/**
	 * Test the appropriate filters are set up
	 *
	 * @since 4.0
	 */
	public function test_filters() {
		$this->assertSame( 10, has_filter( 'query_vars', [ $this->model, 'register_rewrite_tags' ] ) );
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
		$this->assertSame( PDF_EXTENDED_VERSION, get_option( 'gfpdf_current_version' ) );

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

		$this->assertDirectoryExists( $gfpdf->data->multisite_template_location );
	}

	/**
	 * mPDF's tempDir is the tmp folder, and mPDF adds the mpdf folder its cache lives in
	 *
	 * @since 6.17.3
	 */
	public function test_mpdf_tmp_location() {
		global $gfpdf;

		$this->assertSame( untrailingslashit( $gfpdf->data->template_tmp_location ), $gfpdf->data->mpdf_tmp_location );
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
		$this->assertFileDoesNotExist( $gfpdf->data->template_location );
		$this->model->create_folder_structures();

		/* Test the results */
		$this->assertDirectoryExists( $gfpdf->data->template_location );
		$this->assertDirectoryExists( $gfpdf->data->template_font_location );
		$this->assertDirectoryExists( $gfpdf->data->template_tmp_location );
		$this->assertDirectoryExists( $gfpdf->data->mpdf_tmp_location );

		$this->assertStringEqualsFile( $gfpdf->data->template_tmp_location . '.htaccess', Model_Install::TMP_HTACCESS );
		$this->assertFileExists( $gfpdf->data->template_tmp_location . 'index.html' );
		$this->assertFileExists( $gfpdf->data->template_font_location . 'index.html' );
		$this->assertFileExists( $gfpdf->data->template_location . 'index.html' );

		/* Test our directory filters */
		add_filter(
			'gfpdf_template_location',
			function( $path, $folder ) {
				return '/tmp/' . $folder;
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
				return '/tmp/wp-content/tmp/';
			}
		);

		add_filter(
			'gfpdf_font_location',
			function( $path ) {
				return '/tmp/wp-content/pdf-fonts/';
			}
		);

		/* Apply our new filters */
		$this->model->setup_template_location();

		/* Remove folder structure */
		$gfpdf->misc->rmdir( $gfpdf->data->template_location );

		/* Create our folder structure */
		$this->model->create_folder_structures();

		/* Test the results */
		$this->assertDirectoryExists( '/tmp/PDF_EXTENDED_TEMPLATES' );
		$this->assertDirectoryExists( '/tmp/wp-content/pdf-fonts' );
		$this->assertDirectoryExists( '/tmp/wp-content/tmp' );

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

		$this->assertSame( 'index.php?gpdf=1&pid=$matches[1]&lid=$matches[2]&action=$matches[3]', $wp_rewrite->extra_rules_top[ '^' . $gfpdf->data->permalink ] );
		$this->assertSame( 'index.php?gpdf=1&pid=$matches[1]&lid=$matches[2]&action=$matches[3]', $wp_rewrite->extra_rules_top[ '^' . $wp_rewrite->root . $gfpdf->data->permalink ] );
	}

	public function test_register_rewrite_tags_appends_pdf_query_vars_when_gpdf_get_present() {
		$_GET['gpdf'] = '1';
		$tags         = $this->model->register_rewrite_tags( [ 'existing' ] );
		unset( $_GET['gpdf'] );

		$this->assertSame( [ 'existing', 'gpdf', 'pid', 'lid', 'action' ], $tags );
	}

	public function test_register_rewrite_tags_returns_input_unchanged_without_pdf_query() {
		$tags = $this->model->register_rewrite_tags( [ 'existing' ] );

		$this->assertSame( [ 'existing' ], $tags );
	}

	public function test_maybe_flush_rewrite_rules_triggers_flush_only_when_rule_absent() {
		$sentinel = [ '^existing/rule/' => 'index.php?a=1' ];
		update_option( 'rewrite_rules', $sentinel );

		$this->model->maybe_flush_rewrite_rules( [ '^existing/rule/' ] );
		$this->assertSame( $sentinel, get_option( 'rewrite_rules' ) );

		$this->model->maybe_flush_rewrite_rules( [ '^never/exists/' ] );
		$this->assertNotSame( $sentinel, get_option( 'rewrite_rules' ) );
	}
}
