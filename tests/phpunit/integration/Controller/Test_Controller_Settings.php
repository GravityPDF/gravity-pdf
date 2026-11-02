<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Addon;
use GFPDF\Helper\Helper_Logger;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Singleton;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   settings
 */
class Test_Controller_Settings extends TestCase {
	/**
	 * @var Controller_Settings
	 */
	public $controller;

	/**
	 * The WP Unit Test Set up function
	 */
	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		remove_all_actions( 'init' );

		$model = $gfpdf->singleton->get_class( 'Model_Settings' );
		$view  = $gfpdf->singleton->get_class( 'View_Settings' );

		$this->controller = new Controller_Settings( $model, $view, $gfpdf->gform, $gfpdf->log, $gfpdf->notices, $gfpdf->data, $gfpdf->misc );
	}

	public function tear_down(): void {
		$data        = \GPDFAPI::get_data_class();
		$data->addon = [];

		/* Creating a subsite dirties process globals WP_UnitTestCase won't roll back; reset so later tests aren't polluted */
		global $wp_settings_errors, $wp_rewrite;
		$wp_settings_errors = [];
		$wp_rewrite->init();

		parent::tear_down();
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
		$this->controller->init();
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
		$network_plugins = static function () {
			return [ PDF_PLUGIN_BASENAME => time() ];
		};
		add_filter( 'pre_site_option_active_sitewide_plugins', $network_plugins );
		switch_to_blog( $this->factory()->blog->create() );

		$this->controller->add_actions();
		do_action( 'init' );

		$this->assertFalse( wp_next_scheduled( 'gfpdf_bulk_license_check' ) );

		restore_current_blog();
		remove_filter( 'pre_site_option_active_sitewide_plugins', $network_plugins );
	}

	/**
	 * Run the Tools tab redirect for a request, returning where it sent the user or null when it let the page load
	 */
	protected function get_tools_tab_redirect( array $query, bool $admin = true ): ?string {
		$_GET = $query;
		set_current_screen( $admin ? 'dashboard' : 'front' );

		$location = null;
		$capture  = static function ( $url ) use ( &$location ) {
			$location = $url;

			throw new \RuntimeException( 'redirected' );
		};

		add_filter( 'wp_redirect', $capture );

		try {
			$this->controller->redirect_tools_tab();
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'redirected', $e->getMessage() );
		} finally {
			remove_filter( 'wp_redirect', $capture );
			set_current_screen( 'front' );
		}

		return $location;
	}

	public function test_the_tools_tab_redirect_is_registered() {
		$this->controller->add_actions();

		$this->assertSame( 10, has_action( 'admin_init', [ $this->controller, 'redirect_tools_tab' ] ) );
	}

	public function provider_tools_tab_links(): array {
		return [
			'tools tab'       => [ [ 'tab' => 'tools' ] ],
			'mixed case'      => [ [ 'tab' => 'Tools' ] ],
			'lowercase PDF'   => [ [ 'subview' => 'pdf', 'tab' => 'tools' ] ],
			'other query args' => [ [ 'tab' => 'tools', 'foo' => 'bar' ] ],
		];
	}

	/**
	 * @dataProvider provider_tools_tab_links
	 */
	public function test_a_tools_tab_link_redirects_to_the_general_tab( array $query ) {
		$query += [
			'page'    => 'gf_settings',
			'subview' => 'PDF',
		];

		$this->assertSame( admin_url( 'admin.php?page=gf_settings&subview=PDF&tab=general' ), $this->get_tools_tab_redirect( $query ) );
	}

	public function provider_untouched_pages(): array {
		$settings = [
			'page'    => 'gf_settings',
			'subview' => 'PDF',
		];

		return [
			'general tab'           => [ $settings + [ 'tab' => 'general' ] ],
			'default tab'           => [ $settings ],
			'tab as an array'       => [ $settings + [ 'tab' => [ 'tools' ] ] ],
			'another settings page' => [ [ 'page' => 'gf_settings', 'subview' => 'gravityformsuserregistration', 'tab' => 'tools' ] ],
			'not a settings page'   => [ [ 'page' => 'gf_entries', 'tab' => 'tools' ] ],
		];
	}

	/**
	 * @dataProvider provider_untouched_pages
	 */
	public function test_other_pages_are_not_redirected( array $query ) {
		$this->assertNull( $this->get_tools_tab_redirect( $query ) );
	}

	public function test_the_front_end_is_not_redirected() {
		$query = [
			'page'    => 'gf_settings',
			'subview' => 'PDF',
			'tab'     => 'tools',
		];

		$this->assertNull( $this->get_tools_tab_redirect( $query, false ) );
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
