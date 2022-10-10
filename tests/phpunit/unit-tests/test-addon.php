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
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
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
		$this->assertEquals( 10, has_action( 'init', [ $this->addon, 'plugin_updater' ] ) );
		$this->assertEquals( 10, has_action( 'admin_init', [ $this->addon, 'maybe_schedule_license_check' ] ) );
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
		$api_response = function() {
			return [
				'response' => [ 'code' => 201 ],
			];
		};

		add_filter( 'pre_http_request', $api_response );

		$this->addon->update_license_info( [
			'license' => '12345',
			'status'  => 'active',
			'message' => '',
		] );

		$this->assertFalse( wp_next_scheduled( 'gfpdf_' . $this->addon->get_slug() . '_license_check' ) );
		$this->assertFalse( $this->addon->schedule_license_check() );
		$this->assertNotFalse( wp_next_scheduled( 'gfpdf_' . $this->addon->get_slug() . '_license_check' ) );

		remove_filter( 'pre_http_request', $api_response );

		$api_response = function() {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [ 'license' => 'revoked' ] ),
			];
		};

		add_filter( 'pre_http_request', $api_response );

		$this->assertTrue( $this->addon->schedule_license_check() );
		$this->assertEquals( 'Your license key has been disabled', $this->addon->get_license_message() );

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
