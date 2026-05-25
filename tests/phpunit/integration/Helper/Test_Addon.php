<?php

namespace GFPDF\Helper;
use GFPDF\Helper\Helper_Abstract_Addon;
use GFPDF\Helper\Helper_Interface_Extension_Settings;
use GFPDF\Helper\Helper_Logger;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Singleton;
use GPDFAPI;
use GFPDF\Tests\Integration\TestCase;

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
class Test_Addon extends TestCase {

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

	public function test_get_short_name_strips_gravity_pdf_prefix() {
		$prefixed = new Addon(
			'gpdf-sample',
			'Gravity PDF Sample',
			'Gravity PDF',
			'1.0',
			'/path/to/plugin/file.php',
			GPDFAPI::get_data_class(),
			GPDFAPI::get_options_class(),
			new Helper_Singleton(),
			new Helper_Logger( 'gpdf-sample', 'Gravity PDF Sample' ),
			new Helper_Notices()
		);

		$this->assertSame( 'Sample', $prefixed->get_short_name() );
		$this->assertSame( 'My Custom Plugin', $this->addon->get_short_name() );
	}

	public function test_edd_download_id_setter_and_getter() {
		$this->assertSame( '', $this->addon->get_edd_download_id() );

		$this->addon->set_edd_download_id( '12345' );
		$this->assertSame( '12345', $this->addon->get_edd_download_id() );
	}

	public function test_addon_documentation_slug_setter_and_getter() {
		$this->assertSame( '', $this->addon->get_addon_documentation_slug() );

		$this->addon->set_addon_documentation_slug( 'shop-plugin-sample' );
		$this->assertSame( 'shop-plugin-sample', $this->addon->get_addon_documentation_slug() );
	}

	public function test_get_default_api_params_returns_expected_keys() {
		$this->addon->set_edd_download_id( '777' );

		$params = $this->addon->get_default_api_params();

		$this->assertSame( '1.0', $params['version'] );
		$this->assertSame( '', $params['license'] );
		$this->assertSame( 'My Custom Plugin', $params['item_name'] );
		$this->assertSame( '777', $params['item_id'] );
		$this->assertSame( 'Gravity PDF', $params['author'] );
		$this->assertFalse( $params['beta'] );
	}

	public function test_plugin_row_meta_passes_through_for_non_matching_file() {
		$result = $this->addon->plugin_row_meta( [ 'existing' => 'link' ], 'unrelated/unrelated.php' );

		$this->assertSame( [ 'existing' => 'link' ], $result );
	}

	public function test_plugin_row_meta_appends_support_link_for_matching_file() {
		$result = $this->addon->plugin_row_meta(
			[ 'existing' => 'link' ],
			plugin_basename( $this->addon->get_main_plugin_file() )
		);

		$this->assertArrayHasKey( 'existing', $result );
		$this->assertArrayHasKey( 'support', $result );
		$this->assertStringContainsString( 'gravitypdf.com/help/', $result['support'] );
		$this->assertArrayNotHasKey( 'docs', $result );
	}

	public function test_plugin_row_meta_includes_docs_link_when_slug_set() {
		$this->addon->set_addon_documentation_slug( 'shop-plugin-sample-addon' );

		$result = $this->addon->plugin_row_meta(
			[],
			plugin_basename( $this->addon->get_main_plugin_file() )
		);

		$this->assertArrayHasKey( 'docs', $result );
		$this->assertStringContainsString( 'docs.gravitypdf.com/extensions/sample-addon/', $result['docs'] );
	}

	public function test_license_registration_short_circuits_when_license_active() {
		$this->addon->set_edd_download_id( '777' );
		$this->addon->update_license_info( [ 'license' => 'k', 'status' => 'active', 'message' => '' ] );

		ob_start();
		$this->addon->license_registration();
		$this->assertEmpty( ob_get_clean() );
	}

	public function test_license_registration_short_circuits_without_edd_id() {
		ob_start();
		$this->addon->license_registration();
		$this->assertEmpty( ob_get_clean() );
	}

	public function test_license_registration_outputs_prompt_when_inactive_with_edd_id() {
		$this->addon->set_edd_download_id( '777' );

		ob_start();
		$this->addon->license_registration();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Register your copy of My Custom Plugin', $output );
		$this->assertStringContainsString( 'edd_action=add_to_cart', $output );
		$this->assertStringContainsString( 'download_id=777', $output );
	}

	public function test_update_license_status_expired_response_includes_date_link() {
		$this->addon->set_edd_download_id( '777' );

		$response = [
			'response' => [ 'code' => 200 ],
			'body'     => json_encode( [
				'license' => 'expired',
				'expires' => '2030-01-15 00:00:00',
			] ),
		];

		$this->addon->update_license_status_from_response( 'abc', $response );

		$message = $this->addon->get_license_message();
		$this->assertStringContainsString( 'expired', strtolower( $message ) );
		$this->assertStringContainsString( 'gravitypdf.com/checkout/', $message );
		$this->assertStringContainsString( 'download_id=777', $message );
		$this->assertSame( 'expired', $this->addon->get_license_status() );
	}

	public function test_update_license_status_revoked_response_includes_cart_link() {
		$this->addon->set_edd_download_id( '777' );

		$response = [
			'response' => [ 'code' => 200 ],
			'body'     => json_encode( [
				'license'  => 'revoked',
				'price_id' => 9,
			] ),
		];

		$this->addon->update_license_status_from_response( 'abc', $response );

		$message = $this->addon->get_license_message();
		$this->assertStringContainsString( 'edd_action=add_to_cart', $message );
		$this->assertStringContainsString( 'edd_options', $message );
		$this->assertSame( 'revoked', $this->addon->get_license_status() );
	}

	public function test_update_license_status_no_activations_left_response_includes_account_link() {
		$this->addon->set_edd_download_id( '777' );

		$response = [
			'response' => [ 'code' => 200 ],
			'body'     => json_encode( [
				'license'    => 'no_activations_left',
				'license_id' => 42,
				'payment_id' => 13,
			] ),
		];

		$this->addon->update_license_status_from_response( 'abc', $response );

		$message = $this->addon->get_license_message();
		$this->assertStringContainsString( 'gravitypdf.com/account/', $message );
		$this->assertStringContainsString( 'license_id=42', $message );
		$this->assertStringContainsString( 'payment_id=13', $message );
		$this->assertSame( 'no_activations_left', $this->addon->get_license_status() );
	}

	public function test_update_license_status_rate_limit_response_sets_error_status() {
		$response = [
			'response' => [ 'code' => 429 ],
			'body'     => '',
		];

		$this->addon->update_license_status_from_response( 'abc', $response );

		$this->assertSame( 'rate_limit', $this->addon->get_license_status() );
	}

	public function test_activate_license_fires_action_and_returns_license_info() {
		$captured = null;
		add_action( 'gfpdf_addon_post_license_activation', function ( $response, $addon, $use_database ) use ( &$captured ) {
			$captured = [ 'response' => $response, 'addon' => $addon, 'use_database' => $use_database ];
		}, 10, 3 );

		add_filter( 'pre_http_request', function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [ 'license' => 'valid' ] ),
			];
		} );

		$result = $this->addon->activate_license( 'xyz789' );

		$this->assertSame( 'xyz789', $result['license'] );
		$this->assertSame( 'valid', $result['status'] );
		$this->assertNotNull( $captured );
		$this->assertSame( $this->addon, $captured['addon'] );
	}

	public function test_deactivate_license_clears_db_and_fires_action_on_success() {
		$this->addon->update_license_info( [ 'license' => 'k', 'status' => 'valid', 'message' => 'ok' ], true );

		$captured = null;
		add_action( 'gfpdf_addon_post_license_deactivation', function ( $response, $addon ) use ( &$captured ) {
			$captured = $addon;
		}, 10, 2 );

		add_filter( 'pre_http_request', function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [ 'license' => 'deactivated' ] ),
			];
		} );

		$this->assertTrue( $this->addon->deactivate_license() );
		$this->assertSame( '', $this->addon->get_license_status() );
		$this->assertSame( $this->addon, $captured );
	}

	public function test_deactivate_license_returns_false_on_api_error() {
		$this->addon->update_license_info( [ 'license' => 'k', 'status' => 'valid', 'message' => 'ok' ], true );

		add_filter( 'pre_http_request', function () {
			return [
				'response' => [ 'code' => 500 ],
				'body'     => '',
			];
		} );

		$this->assertFalse( $this->addon->deactivate_license() );
		/* DB is cleared regardless of API outcome */
		$this->assertSame( '', $this->addon->get_license_status() );
	}

	public function test_deactivate_license_returns_false_when_response_not_deactivated() {
		$this->addon->update_license_info( [ 'license' => 'k', 'status' => 'valid', 'message' => 'ok' ], true );

		add_filter( 'pre_http_request', function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [ 'license' => 'still-active' ] ),
			];
		} );

		$this->assertFalse( $this->addon->deactivate_license() );
	}

	public function test_maybe_auto_activate_license_skipped_for_same_addon() {
		$this->addon->set_edd_download_id( '777' );

		$response = [
			'response' => [ 'code' => 200 ],
			'body'     => json_encode( [ 'products' => [ 777 ] ] ),
		];

		$this->addon->maybe_auto_activate_license( $response, $this->addon, false );

		$this->assertFalse( $this->addon->has_license_auto_activated() );
	}

	public function test_maybe_auto_activate_license_when_addon_in_access_pass() {
		$other = new Addon(
			'another-plugin',
			'Another Plugin',
			'Gravity PDF',
			'1.0',
			'/path/to/another/file.php',
			GPDFAPI::get_data_class(),
			GPDFAPI::get_options_class(),
			new Helper_Singleton(),
			new Helper_Logger( 'another-plugin', 'Another Plugin' ),
			new Helper_Notices()
		);
		$other->set_edd_download_id( '888' );
		$other->update_license_info( [ 'license' => 'other-key', 'status' => 'valid', 'message' => '' ] );

		$this->addon->set_edd_download_id( '777' );

		$response = [
			'response' => [ 'code' => 200 ],
			'body'     => json_encode( [ 'products' => [ 777, 888 ] ] ),
		];

		$this->addon->maybe_auto_activate_license( $response, $other, false );

		$this->assertTrue( $this->addon->has_license_auto_activated() );
		$this->assertSame( 'other-key', $this->addon->get_license_key() );
		$this->assertSame( 'valid', $this->addon->get_license_status() );
	}

	public function test_maybe_auto_activate_license_skipped_when_addon_not_in_products() {
		$other = new Addon(
			'another-plugin',
			'Another Plugin',
			'Gravity PDF',
			'1.0',
			'/path/to/another/file.php',
			GPDFAPI::get_data_class(),
			GPDFAPI::get_options_class(),
			new Helper_Singleton(),
			new Helper_Logger( 'another-plugin', 'Another Plugin' ),
			new Helper_Notices()
		);
		$other->set_edd_download_id( '888' );

		$this->addon->set_edd_download_id( '777' );

		$response = [
			'response' => [ 'code' => 200 ],
			'body'     => json_encode( [ 'products' => [ 888, 999 ] ] ),
		];

		$this->addon->maybe_auto_activate_license( $response, $other, false );

		$this->assertFalse( $this->addon->has_license_auto_activated() );
	}

	public function test_maybe_auto_deactivate_license_when_addon_in_access_pass() {
		$other = new Addon(
			'another-plugin',
			'Another Plugin',
			'Gravity PDF',
			'1.0',
			'/path/to/another/file.php',
			GPDFAPI::get_data_class(),
			GPDFAPI::get_options_class(),
			new Helper_Singleton(),
			new Helper_Logger( 'another-plugin', 'Another Plugin' ),
			new Helper_Notices()
		);
		$other->set_edd_download_id( '888' );

		$this->addon->set_edd_download_id( '777' );

		$response = [
			'response' => [ 'code' => 200 ],
			'body'     => json_encode( [ 'products' => [ 777, 888 ] ] ),
		];

		$this->addon->maybe_auto_deactivate_license( $response, $other );

		$this->assertTrue( $this->addon->has_license_auto_deactivated() );
	}

	public function test_flush_update_cache_is_noop_without_plugin_updater() {
		$this->assertNull( $this->addon->flush_update_cache() );
	}

	public function test_flush_update_cache_clears_caches_after_init() {
		$this->addon->init();
		do_action( 'init' );

		update_option( $this->addon->get_plugin_updater()->get_cache_key(), [ 'timeout' => time() + 60, 'value' => '{}' ] );
		$this->assertNotEmpty( get_option( $this->addon->get_plugin_updater()->get_cache_key() ) );

		$this->addon->flush_update_cache();

		$this->assertEmpty( get_option( $this->addon->get_plugin_updater()->get_cache_key() ) );
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
