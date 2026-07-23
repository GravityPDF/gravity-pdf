<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Addon;
use GFPDF\Helper\Helper_Logger;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Singleton;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   settings
 */
class Test_Controller_Settings extends WP_UnitTestCase {
	/**
	 * @var Controller_Settings
	 */
	public $controller;

	/**
	 * The WP Unit Test Set up function
	 */
	public function set_up() {
		global $gfpdf;

		parent::set_up();

		$model = $gfpdf->singleton->get_class( 'Model_Settings' );
		$view  = $gfpdf->singleton->get_class( 'View_Settings' );

		$this->controller = new Controller_Settings( $model, $view, $gfpdf->gform, $gfpdf->log, $gfpdf->notices, $gfpdf->data, $gfpdf->misc );
	}

	public function tear_down() {
		parent::tear_down();
		$data        = \GPDFAPI::get_data_class();
		$data->addon = [];

		/* Creating a subsite dirties process globals WP_UnitTestCase won't roll back; reset so later tests aren't polluted */
		global $wp_settings_errors, $wp_rewrite;
		$wp_settings_errors = [];
		$wp_rewrite->init();
	}

	public function test_bulk_license_check_schedule_with_no_addons() {
		$this->controller->add_filters();
		do_action( 'init' );
		$this->assertFalse( wp_next_scheduled( 'gfpdf_bulk_license_check' ) );
	}

	public function test_bulk_license_check_schedule_with_addons() {
		$addon = new ControllerSettingsAddon(
			'my-custom-plugin',
			'My Custom Plugin',
			'Gravity PDF',
			'1.0',
			'/path/to/plugin/file.php',
			\GPDFAPI::get_data_class(),
			\GPDFAPI::get_options_class(),
			new Helper_Singleton(),
			new Helper_Logger( 'my-custom-plugin', 'My Custom Plugin' ),
			new Helper_Notices()
		);

		$addon->init();
		$this->controller->add_filters();
		do_action( 'init' );

		$this->assertNotFalse( wp_next_scheduled( 'gfpdf_bulk_license_check' ) );
	}

	public function test_bulk_license_check_not_scheduled_on_secondary_network_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		$addon = new ControllerSettingsAddon(
			'my-custom-plugin',
			'My Custom Plugin',
			'Gravity PDF',
			'1.0',
			'/path/to/plugin/file.php',
			\GPDFAPI::get_data_class(),
			\GPDFAPI::get_options_class(),
			new Helper_Singleton(),
			new Helper_Logger( 'my-custom-plugin', 'My Custom Plugin' ),
			new Helper_Notices()
		);
		$addon->init();

		/* Pose as a secondary site with Gravity PDF network-activated; scheduling reads the per-blog cron, so use a real blog */
		$network_plugins = static function () { return [ PDF_PLUGIN_BASENAME => time() ]; };
		add_filter( 'pre_site_option_active_sitewide_plugins', $network_plugins );
		switch_to_blog( $this->factory()->blog->create() );

		$this->controller->add_filters();
		do_action( 'init' );

		$this->assertFalse( wp_next_scheduled( 'gfpdf_bulk_license_check' ) );

		restore_current_blog();
		remove_filter( 'pre_site_option_active_sitewide_plugins', $network_plugins );
	}

	/*
	 * maybe_schedule_network_update_check() runs on every request via after_setup_theme, including the frontend and
	 * WP-Cron, where wp-admin/includes/plugin.php (which defines is_plugin_active_for_network()) isn't loaded. That
	 * fatal can't be reproduced here because the PHPUnit bootstrap always loads the file, so guard the contract by
	 * scanning the source. php_strip_whitespace() drops comments so only real code is matched.
	 */
	public function test_network_update_check_avoids_admin_only_plugin_functions() {
		$source = php_strip_whitespace( ( new \ReflectionClass( Controller_Settings::class ) )->getFileName() );
		$this->assertStringNotContainsString( 'is_plugin_active_for_network(', $source );
	}

	/*
	 * The updater fires gpdf_sl_plugin_updater_api_response with ($response, $api_data, $plugin_file). If the filter
	 * isn't registered with accepted_args of 3, $plugin_file arrives null, licensing_bulk_get_version_api_response()
	 * can't identify the initiating product, and that plugin's own update is silently lost while every response-shape
	 * test still passes. Pin the arg-count.
	 */
	public function test_bulk_get_version_response_filter_registered_with_plugin_file_arg() {
		$this->controller->add_filters();

		$hook = $GLOBALS['wp_filter']['gpdf_sl_plugin_updater_api_response'] ?? null;
		$this->assertNotNull( $hook );

		$registered = null;
		foreach ( $hook->callbacks[10] as $callback ) {
			if ( is_array( $callback['function'] ) && $callback['function'][1] === 'licensing_bulk_get_version_api_response' ) {
				$registered = $callback;
				break;
			}
		}

		$this->assertNotNull( $registered, 'The bulk get_version response filter was not registered' );
		$this->assertSame( 3, $registered['accepted_args'] );
	}
}

class ControllerSettingsAddon extends Helper_Abstract_Addon {
}
