<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Abstract_Addon;
use GFPDF\Helper\Helper_Interface_Extension_Settings;
use GFPDF\Helper\Helper_Logger;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Singleton;
use GPDFAPI;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Abstract Addon functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.2
 */

/**
 * Class Test_Addon
 *
 * @package GFPDF\Tests
 *
 * @since   4.2
 *
 * @group   addon
 */
class Test_Addon extends WP_UnitTestCase {

	/**
	 * Our test class
	 *
	 * @var Helper_Abstract_Addon
	 *
	 * @since 4.2
	 */
	public $addon;

	/**
	 * Our 2nd test class
	 *
	 * @var Helper_Abstract_Addon
	 *
	 * @since 6.5
	 */
	public $addon2;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.2
	 */
	public function set_up() {
		/* run parent method */
		parent::set_up();

		/* Setup our test classes */
		$this->addon = new Addon(
			'my-custom-plugin',
			'My Custom Plugin',
			'Gravity PDF',
			'1.0',
			'/path/to/plugin/file.php',
			GPDFAPI::get_data_class(),
			GPDFAPI::get_options_class(),
			new Helper_Singleton(),
			new Helper_Logger( 'my-custom-plugin', 'My Custom Plugin' ),
			new Helper_Notices()
		);

		$this->addon2 = new Addon_Fields(
			'my-custom-plugin2',
			'My Custom Plugin2',
			'Gravity PDF',
			'1.0',
			'/path/to/plugin2/file.php',
			GPDFAPI::get_data_class(),
			GPDFAPI::get_options_class(),
			new Helper_Singleton(),
			new Helper_Logger( 'my-custom-plugin2', 'My Custom Plugin2' ),
			new Helper_Notices()
		);

		remove_all_actions( 'init' );
	}

	public function tear_down() {
		parent::tear_down();

		$data = \GPDFAPI::get_data_class();
		$data->addon = [];

		remove_all_filters( 'pre_http_request' );
	}

	/**
	 * @since 4.2
	 */
	public function test_get_slug() {
		$this->assertEquals( 'my-custom-plugin', $this->addon->get_slug() );
	}

	/**
	 * @since 4.2
	 */
	public function test_get_name() {
		$this->assertEquals( 'My Custom Plugin', $this->addon->get_name() );
	}

	/**
	 * @since 4.2
	 */
	public function test_get_version() {
		$this->assertEquals( '1.0', $this->addon->get_version() );
	}

	/**
	 * @since 4.2
	 */
	public function test_get_author() {
		$this->assertEquals( 'Gravity PDF', $this->addon->get_author() );
	}

	/**
	 * @since 4.2
	 */
	public function test_get_main_plugin_file() {
		$this->assertEquals( '/path/to/plugin/file.php', $this->addon->get_main_plugin_file() );
	}

	/**
	 * @since 4.2
	 */
	public function test_init() {
		global $gfpdf;

		$sub_addon = new SubAddon();
		$this->assertFalse( $sub_addon->run );

		$this->addon->init( [ $sub_addon ] );

		$this->assertTrue( $sub_addon->run );
		$this->assertEquals(
			10,
			has_action(
				'gfpdf_' . $this->addon->get_slug() . '_license_check',
				[
					$this->addon,
					'schedule_license_check',
				]
			)
		);

		$this->assertEquals( $this->addon, $gfpdf->data->addon[ $this->addon->get_slug() ] );

		$gfpdf->data->addon = [];
	}

	/**
	 * @since 4.2
	 */
	public function test_license_info() {
		global $gfpdf;

		$license = $this->addon->get_license_info();

		$this->assertArrayHasKey( 'license', $license );
		$this->assertArrayHasKey( 'status', $license );
		$this->assertArrayHasKey( 'message', $license );

		$this->addon->update_license_info(
			[
				'license' => 'my key',
				'status'  => 'active',
				'message' => 'Success!',
			]
		);

		$license = $this->addon->get_license_info();

		$this->assertEquals( 'my key', $license['license'] );
		$this->assertEquals( 'active', $license['status'] );
		$this->assertEquals( 'Success!', $license['message'] );

		$this->addon->delete_license_info();

		$settings = $gfpdf->options->get_settings();

		$this->assertArrayNotHasKey( 'license_' . $this->addon->get_slug(), $settings );
		$this->assertArrayNotHasKey( 'license_' . $this->addon->get_slug() . '_status', $settings );
		$this->assertArrayNotHasKey( 'license_' . $this->addon->get_slug() . '_message', $settings );
	}

	public function test_get_license_key_from_constant() {
		$this->assertFalse( $this->addon->get_license_key_from_constant() );
		$this->assertFalse( $this->addon2->get_license_key_from_constant() );

		add_filter( 'gfpdf_addon_hardcoded_license_key', function ( $key ) {
			return 'abc123';
		} );

		$this->assertSame( 'abc123', $this->addon->get_license_key_from_constant() );
		$this->assertSame( 'abc123', $this->addon2->get_license_key_from_constant() );

		remove_all_filters( 'gfpdf_addon_hardcoded_license_key' );

		add_filter( 'gfpdf_addon_hardcoded_license_key', function ( $key ) {
			return [ 'my-custom-plugin' => 'abc456', 'my-custom-plugin2' => 'xyz987' ];
		} );

		$this->assertSame( 'abc456', $this->addon->get_license_key_from_constant() );
		$this->assertSame( 'xyz987', $this->addon2->get_license_key_from_constant() );
	}

	public function test_license_constant_overrides_database() {
		$this->addon->update_license_info(
			[
				'license' => 'my key',
				'status'  => 'active',
				'message' => 'Success!',
			]
		);

		$license = $this->addon->get_license_info();

		$this->assertSame( 'my key', $license['license'] );

		add_filter( 'gfpdf_addon_hardcoded_license_key', function ( $key ) {
			return 'abc123';
		} );

		$license = $this->addon->get_license_info();

		$this->assertSame( 'abc123', $license['license'] );
	}

	/*
	 * @since 4.2
	 */
	public function test_get_license_key() {
		$this->addon->update_license_info(
			[
				'license' => 'my key',
				'status'  => 'active',
				'message' => 'Success!',
			]
		);

		$this->assertEquals( 'my key', $this->addon->get_license_key() );

		$this->addon->delete_license_info();
	}

	/*
	 * @since 4.2
	 */
	public function test_get_license_status() {
		$this->addon->update_license_info(
			[
				'license' => 'my key',
				'status'  => 'active',
				'message' => 'Success!',
			]
		);

		$this->assertEquals( 'active', $this->addon->get_license_status() );

		$this->addon->delete_license_info();
	}

	/*
	 * @since 4.2
	 */
	public function test_get_license_message() {
		$this->addon->update_license_info(
			[
				'license' => 'my key',
				'status'  => 'active',
				'message' => 'Success!',
			]
		);

		$this->assertEquals( 'Success!', $this->addon->get_license_message() );

		$this->addon->delete_license_info();
	}

	/**
	 * @since 4.2
	 */
	public function test_maybe_schedule_license_check() {
		$this->assertFalse( wp_next_scheduled( 'gfpdf_' . $this->addon->get_slug() . '_license_check' ) );
		$this->addon->maybe_schedule_license_check();
		$this->assertNotFalse( wp_next_scheduled( 'gfpdf_' . $this->addon->get_slug() . '_license_check' ) );
	}

	/**
	 * @since 4.2
	 */
	public function test_schedule_license_check() {
		/* Test a bad request */
		$api_response = function() {
			return [
				'response' => [ 'code' => 301 ],
			];
		};

		add_filter( 'pre_http_request', $api_response );

		$this->addon->update_license_info( [
			'license' => '12345',
			'status'  => 'active',
			'message' => 'Your license key is valid!',
		] );

		$this->assertFalse( wp_next_scheduled( 'gfpdf_' . $this->addon->get_slug() . '_license_check' ) );
		$this->assertFalse( $this->addon->schedule_license_check() );
		$this->assertNotFalse( wp_next_scheduled( 'gfpdf_' . $this->addon->get_slug() . '_license_check' ) );

		remove_filter( 'pre_http_request', $api_response );

		/* Do a good request */
		$api_response = function() {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [ 'license' => 'valid' ] ),
			];
		};

		add_filter( 'pre_http_request', $api_response );

		$this->assertTrue( $this->addon->schedule_license_check() );
		$this->assertSame(  'Your license key is valid!', $this->addon->get_license_message() );

		remove_filter( 'pre_http_request', $api_response );

		/* Test with a revoked license */
		$api_response = function() {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [ 'license' => 'revoked', 'price_id' => 1 ] ),
			];
		};

		add_filter( 'pre_http_request', $api_response );

		$this->assertFalse( $this->addon->schedule_license_check() );
		$this->assertStringContainsString( 'This license key has been cancelled', $this->addon->get_license_message() );

		remove_filter( 'pre_http_request', $api_response );
		$this->addon->delete_license_info();
	}

	/**
	 * @since 6.16.0
	 */
	public function test_schedule_license_check_skipped_on_secondary_network_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		/* A valid key so the check would otherwise build params and POST — proving the gate is what stops it */
		$this->addon->update_license_info( [ 'license' => 'abc123', 'status' => 'valid' ] );

		$http_called = false;
		$spy         = function () use ( &$http_called ) {
			$http_called = true;
			return new \WP_Error( 'blocked', 'no HTTP in tests' );
		};
		add_filter( 'pre_http_request', $spy );

		/* Pose as a secondary site with Gravity PDF network-activated; only a network option is read, so no real blog is needed */
		$network_plugins = static function () { return [ PDF_PLUGIN_BASENAME => time() ]; };
		add_filter( 'pre_site_option_active_sitewide_plugins', $network_plugins );
		switch_to_blog( PHP_INT_MAX );

		$this->assertFalse( $this->addon->schedule_license_check() );
		$this->assertFalse( $http_called );

		restore_current_blog();
		remove_filter( 'pre_site_option_active_sitewide_plugins', $network_plugins );
		remove_filter( 'pre_http_request', $spy );
		$this->addon->delete_license_info();
	}

	/**
	 * @since 4.2
	 */
	public function test_auto_register_global_fields_fallback() {
		global $gfpdf;

		$this->assertEmpty( $this->addon2->get_addon_settings_key() );
		$this->addon2->init();

		$this->assertEquals( 10, has_filter( 'gfpdf_settings_extensions', [ $this->addon2, 'register_addon_fields' ] ) );

		$settings = $gfpdf->options->get_registered_fields();
		$this->assertArrayHasKey( 'addon_field', $settings['extensions'] );

		$gfpdf->data->addon = [];
	}

	/**
	 * @since 6.5
	 */
	public function test_auto_register_global_fields_with_prefix() {
		global $gfpdf;

		$this->addon2->enable_settings_prefix();
		$this->assertNotEmpty( $this->addon2->get_addon_settings_key() );
		$this->addon2->init();

		$this->assertEquals( 10, has_filter( 'gfpdf_settings_extensions', [ $this->addon2, 'register_addon_fields' ] ) );

		$settings = $gfpdf->options->get_registered_fields();
		$this->assertArrayHasKey( $this->addon2->get_addon_settings_key() . 'addon_field', $settings['extensions'] );

		$gfpdf->data->addon = [];
	}

	/**
	 * @since 6.5
	 */
	public function test_get_addon_settings_defaults() {
		$this->addon2->enable_settings_prefix();

		$defaults = $this->addon2->get_addon_settings_defaults();
		$this->assertArrayNotHasKey('addon_field', $defaults );
		$this->assertSame( 'Generating PDF...', $defaults['string_loading_title'] );
		$this->assertSame( 'close', $defaults['string_close'] );
	}

	/**
	 * @since 6.5
	 */
	public function test_get_addon_settings_values() {

		$this->addon2->enable_settings_prefix();

		/* Save some test data to the global settings */
		$options      = \GPDFAPI::get_options_class();
		$all_settings = $options->get_settings();

		$all_settings[ $this->addon2->get_addon_settings_key() . 'addon_field' ]          = 'Generic Value';
		$all_settings[ $this->addon2->get_addon_settings_key() . 'string_loading_title' ] = 'Loading';
		$all_settings[ $this->addon2->get_addon_settings_key() . 'string_close' ]         = 'Remove';

		$options->update_settings( $all_settings );

		/* Check we can access the saved settings without using the prefix */
		$settings = $this->addon2->get_addon_settings_values();
		$this->assertSame( 'Generic Value', $settings['addon_field'] );
		$this->assertSame( 'Loading', $settings['string_loading_title'] );
		$this->assertSame( 'Remove', $settings['string_close'] );
	}

	/**
	 * @since 6.5
	 */
	public function test_get_addon_setting_value() {

		$this->addon2->enable_settings_prefix();

		/* Save some test data to the global settings */
		$options      = \GPDFAPI::get_options_class();
		$all_settings = $options->get_settings();

		$all_settings[ $this->addon2->get_addon_settings_key() . 'string_loading_title' ] = 'Loading1';

		$options->update_settings( $all_settings );

		/* Check we can access the saved settings without using the prefix */
		$this->assertSame( 'Use Fallback', $this->addon2->get_addon_setting_value( 'addon_field', 'Use Fallback' ) );
		$this->assertSame( 'Loading1', $this->addon2->get_addon_setting_value( 'string_loading_title' ) );
	}

	public function test_central_plugin_updater() {
		$this->setExpectedIncorrectUsage( 'GFPDF\Helper\Helper_Abstract_Addon::get_plugin_updater' );
		$this->assertNull( $this->addon->get_plugin_updater() );

		$this->addon->init();
		do_action( 'init' );
		$this->assertNotNull( $this->addon->get_plugin_updater() );
	}

	public function test_auto_activate_license_constant() {
		/* Set admin screen */
		set_current_screen( 'index.php' );

		$this->assertEmpty( $this->addon->get_license_status() );

		add_filter( 'gfpdf_addon_hardcoded_license_key', function ( $key ) {
			return 'abc123';
		} );

		$api_response = function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode(
					[
						'error' => 'missing',
					]
				),
			];
		};

		add_filter( 'pre_http_request', $api_response );

		$this->addon->init();
		do_action( 'init' );

		$this->assertNotEmpty( $this->addon->get_license_key() );
		$this->assertNotEmpty( $this->addon->get_license_status() );
	}

	public function test_hardcoded_license_retries_after_failed_activation() {
		set_current_screen( 'index.php' );

		add_filter( 'gfpdf_addon_hardcoded_license_key', function () {
			return 'abc123';
		} );

		$http_calls = 0;
		$ok         = false;
		$api        = function () use ( &$http_calls, &$ok ) {
			$http_calls++;

			return $ok
				? [ 'response' => [ 'code' => 200 ], 'body' => json_encode( [ 'license' => 'valid' ] ) ]
				: new \WP_Error( 'down', 'API unreachable' );
		};

		add_filter( 'pre_http_request', $api );

		$this->addon->init();
		$backoff = 'gfpdf_license_activation_' . $this->addon->get_slug();

		/* First attempt: the API is down, so activation fails and isn't stored as active */
		do_action( 'init' );
		$this->assertSame( 1, $http_calls );
		$this->assertNotContains( $this->addon->get_license_status(), [ 'active', 'valid' ] );

		/* Backoff blocks an immediate retry so a bad/unreachable key doesn't POST on every request */
		do_action( 'init' );
		$this->assertSame( 1, $http_calls );

		/* Once the backoff elapses the activation is retried — the old key-equality guard never retried */
		delete_transient( $backoff );
		$ok = true;
		do_action( 'init' );
		$this->assertSame( 2, $http_calls );
		$this->assertSame( 'valid', $this->addon->get_license_status() );

		remove_all_filters( 'gfpdf_addon_hardcoded_license_key' );
	}

	/**
	 * @since 6.16.0
	 */
	public function test_hardcoded_license_activation_skipped_on_secondary_network_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		set_current_screen( 'index.php' );

		add_filter( 'gfpdf_addon_hardcoded_license_key', static function () {
			return 'abc123';
		} );

		$http_called = false;
		$spy         = function () use ( &$http_called ) {
			$http_called = true;
			return new \WP_Error( 'blocked', 'no HTTP in tests' );
		};
		add_filter( 'pre_http_request', $spy );

		/* Pose as a secondary site with Gravity PDF network-activated; the primary handles activation */
		$network_plugins = static function () { return [ PDF_PLUGIN_BASENAME => time() ]; };
		add_filter( 'pre_site_option_active_sitewide_plugins', $network_plugins );
		switch_to_blog( PHP_INT_MAX );

		$this->addon->init();
		do_action( 'init' );

		$this->assertFalse( $http_called );
		$this->assertEmpty( $this->addon->get_license_status() );

		restore_current_blog();
		remove_filter( 'pre_site_option_active_sitewide_plugins', $network_plugins );
		remove_filter( 'pre_http_request', $spy );
		remove_all_filters( 'gfpdf_addon_hardcoded_license_key' );
	}

	/**
	 * @since 6.16.0
	 */
	public function test_license_registration_notice_hidden_on_secondary_network_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		/* Preconditions that would otherwise render the notice: a known EDD ID and an unregistered license */
		$this->addon->set_edd_download_id( '123' );

		/* Primary site shows the "Register your copy" prompt */
		ob_start();
		$this->addon->license_registration();
		$this->assertStringContainsString( 'Register your copy', ob_get_clean() );

		/* Pose as a secondary site with Gravity PDF network-activated; the primary handles licensing */
		$network_plugins = static function () { return [ PDF_PLUGIN_BASENAME => time() ]; };
		add_filter( 'pre_site_option_active_sitewide_plugins', $network_plugins );
		switch_to_blog( PHP_INT_MAX );

		ob_start();
		$this->addon->license_registration();
		$this->assertEmpty( ob_get_clean() );

		restore_current_blog();
		remove_filter( 'pre_site_option_active_sitewide_plugins', $network_plugins );
	}

	/**
	 * An Access Pass activation on one add-on shares the license with every sibling whose EDD id is in the pass.
	 *
	 * @since 6.16.0
	 */
	public function test_maybe_auto_activate_license_shares_across_access_pass() {
		$this->addon->set_edd_download_id( 10 );
		$this->addon2->set_edd_download_id( 20 );

		$this->addon->update_license_info( [ 'license' => 'AP-KEY', 'status' => 'active', 'message' => 'ok' ] );

		$response = [ 'response' => [ 'code' => 200 ], 'body' => wp_json_encode( [ 'license' => 'valid', 'products' => [ 10, 20 ] ] ) ];

		$this->assertFalse( $this->addon2->has_license_auto_activated() );
		$this->addon2->maybe_auto_activate_license( $response, $this->addon, false );

		$this->assertTrue( $this->addon2->has_license_auto_activated() );
		$this->assertTrue( $this->addon2->is_license_admin_managed() );
		$this->assertSame( 'AP-KEY', $this->addon2->get_license_key() );
		$this->assertSame( 'active', $this->addon2->get_license_status() );

		$this->addon->delete_license_info();
		$this->addon2->delete_license_info();
	}

	/**
	 * @since 6.16.0
	 */
	public function test_maybe_auto_activate_license_skips_addon_not_in_access_pass() {
		$this->addon->set_edd_download_id( 10 );
		$this->addon2->set_edd_download_id( 99 );

		$this->addon->update_license_info( [ 'license' => 'AP-KEY', 'status' => 'active', 'message' => 'ok' ] );

		/* addon2's EDD id (99) isn't among the pass's products, so it must not adopt the license */
		$response = [ 'response' => [ 'code' => 200 ], 'body' => wp_json_encode( [ 'license' => 'valid', 'products' => [ 10, 20 ] ] ) ];
		$this->addon2->maybe_auto_activate_license( $response, $this->addon, false );

		$this->assertFalse( $this->addon2->has_license_auto_activated() );
		$this->assertEmpty( $this->addon2->get_license_status() );

		$this->addon->delete_license_info();
	}

	/**
	 * A plain (non-Access-Pass) activation response has no products array, so nothing is shared.
	 *
	 * @since 6.16.0
	 */
	public function test_maybe_auto_activate_license_ignores_non_access_pass_response() {
		$this->addon->set_edd_download_id( 10 );
		$this->addon2->set_edd_download_id( 20 );
		$this->addon->update_license_info( [ 'license' => 'KEY', 'status' => 'active', 'message' => 'ok' ] );

		$response = [ 'response' => [ 'code' => 200 ], 'body' => wp_json_encode( [ 'license' => 'valid' ] ) ];
		$this->addon2->maybe_auto_activate_license( $response, $this->addon, false );

		$this->assertFalse( $this->addon2->has_license_auto_activated() );

		$this->addon->delete_license_info();
	}

	/**
	 * The gfpdf_addon_post_license_activation action is public: a third-party do_action() may pass a non-addon second
	 * arg (or omit the third), which must not fatal.
	 *
	 * @since 6.16.0
	 */
	public function test_maybe_auto_activate_license_ignores_non_addon_arg() {
		$this->addon->set_edd_download_id( 10 );

		$response = [ 'response' => [ 'code' => 200 ], 'body' => wp_json_encode( [ 'license' => 'valid', 'products' => [ 10 ] ] ) ];
		$this->addon->maybe_auto_activate_license( $response, 'not-an-addon' );

		$this->assertFalse( $this->addon->has_license_auto_activated() );
	}

	/**
	 * An Access Pass deactivation on one add-on cascades to every sibling in the pass.
	 *
	 * @since 6.16.0
	 */
	public function test_maybe_auto_deactivate_license_shares_across_access_pass() {
		$this->addon->set_edd_download_id( 10 );
		$this->addon2->set_edd_download_id( 20 );

		$this->addon2->update_license_info( [ 'license' => 'AP-KEY', 'status' => 'active', 'message' => 'ok' ] );

		/* The initiator has already wiped its own license before firing the action */
		$this->addon->delete_license_info();

		$response = [ 'response' => [ 'code' => 200 ], 'body' => wp_json_encode( [ 'license' => 'deactivated', 'products' => [ 10, 20 ] ] ) ];

		$this->assertFalse( $this->addon2->has_license_auto_deactivated() );
		$this->addon2->maybe_auto_deactivate_license( $response, $this->addon );

		$this->assertTrue( $this->addon2->has_license_auto_deactivated() );
		$this->assertEmpty( $this->addon2->get_license_status() );

		$this->addon2->delete_license_info();
	}

	/**
	 * @since 6.16.0
	 */
	public function test_maybe_auto_deactivate_license_ignores_non_addon_arg() {
		$this->addon->set_edd_download_id( 10 );

		$response = [ 'response' => [ 'code' => 200 ], 'body' => wp_json_encode( [ 'license' => 'deactivated', 'products' => [ 10 ] ] ) ];
		$this->addon->maybe_auto_deactivate_license( $response, 'not-an-addon' );

		$this->assertFalse( $this->addon->has_license_auto_deactivated() );
	}

	/**
	 * The sibling's cached update info was fetched unlicensed (so it holds no package) and must not survive the
	 * Access Pass adopting a key.
	 *
	 * @since 6.16.0
	 */
	public function test_maybe_auto_activate_license_flushes_update_cache() {
		$this->addon->set_edd_download_id( 10 );
		$this->addon2->set_edd_download_id( 20 );

		/* Seed first — init() re-reads the license info from the database, overwriting the in-memory copy */
		$updater = $this->seed_update_cache( $this->addon2 );

		$this->addon->update_license_info( [ 'license' => 'AP-KEY', 'status' => 'active', 'message' => 'ok' ] );

		$response = [ 'response' => [ 'code' => 200 ], 'body' => wp_json_encode( [ 'license' => 'valid', 'products' => [ 10, 20 ] ] ) ];
		$this->addon2->maybe_auto_activate_license( $response, $this->addon, false );

		$this->assertFalse( $updater->get_cached_version_info() );

		$this->addon->delete_license_info();
		$this->addon2->delete_license_info();
	}

	/**
	 * @since 6.16.0
	 */
	public function test_maybe_auto_deactivate_license_flushes_update_cache() {
		$this->addon->set_edd_download_id( 10 );
		$this->addon2->set_edd_download_id( 20 );
		$this->addon->delete_license_info();

		$updater = $this->seed_update_cache( $this->addon2 );

		$this->addon2->update_license_info( [ 'license' => 'AP-KEY', 'status' => 'active', 'message' => 'ok' ] );

		$response = [ 'response' => [ 'code' => 200 ], 'body' => wp_json_encode( [ 'license' => 'deactivated', 'products' => [ 10, 20 ] ] ) ];
		$this->addon2->maybe_auto_deactivate_license( $response, $this->addon );

		$this->assertFalse( $updater->get_cached_version_info() );

		$this->addon2->delete_license_info();
	}

	/**
	 * A store rejection arrives with the key still populated, so the status has to carry the withdrawal end to end.
	 *
	 * @since 6.16.0
	 */
	public function test_rejected_license_response_withdraws_network_package() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		$this->addon->init();
		do_action( 'init' );

		$updater = $this->addon->get_plugin_updater();
		$package = (object) [ 'new_version' => '2.0', 'package' => 'https://store.com/download/123' ];

		update_site_option(
			$updater->get_network_cache_key(),
			[ 'timeout' => strtotime( '+3 days' ), 'value' => wp_json_encode( $package ), 'blog_id' => get_current_blog_id() ]
		);

		$response = [ 'response' => [ 'code' => 200 ], 'body' => wp_json_encode( [ 'license' => 'expired', 'expires' => '2020-01-01 00:00:00' ] ) ];
		$this->addon->update_license_status_from_response( 'unchanged-key', $response );

		$this->assertSame( 'expired', $this->addon->get_license_status() );
		$this->assertFalse( get_site_option( $updater->get_network_cache_key() ) );
	}

	/**
	 * Boot the add-on's updater and prime its version-info cache
	 *
	 * @return \GFPDF\Helper\Licensing\EDD_SL_Plugin_Updater
	 */
	private function seed_update_cache( $addon ) {
		$addon->init();
		do_action( 'init' );

		$updater = $addon->get_plugin_updater();
		update_option(
			$updater->get_cache_key(),
			[ 'timeout' => strtotime( '+3 hours' ), 'value' => wp_json_encode( (object) [ 'new_version' => '2.0', 'package' => '' ] ) ]
		);

		return $updater;
	}
}

/**
 * Test class which extends Helper_Abstract_Addon
 */
class Addon extends Helper_Abstract_Addon {
	public function plugin_updater() {
	}
}

class Addon_Fields extends Helper_Abstract_Addon implements Helper_Interface_Extension_Settings {
	public function plugin_updater() {

	}

	public function get_global_addon_fields() {
		return [
			'addon_field' => [
				'id'   => 'addon_field',
				'name' => 'Addon Field',
				'type' => 'text',
			],

			'string_loading_title'     => [
				'id'   => 'string_loading_title',
				'name' => 'Loading Title',
				'desc' => 'Announced to screen readers when the PDF starts generating',
				'type' => 'text',
				'std'  => 'Generating PDF...',
			],

			'string_close'     => [
				'id'   => 'string_close',
				'type' => 'text',
				'std'  => 'close',
			],
		];
	}
}

class SubAddon {
	public $run = false;

	public function init() {
		$this->run = true;
	}
}
