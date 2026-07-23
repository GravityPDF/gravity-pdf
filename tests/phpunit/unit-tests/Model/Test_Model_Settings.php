<?php
declare( strict_types=1 );

namespace GFPDF\Model;

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
 * @package GFPDF\Model
 *
 * @group   model
 * @group   settings
 */
class Test_Model_Settings extends WP_UnitTestCase {

	/**
	 * @var Model_Settings
	 */
	protected $model;

	/**
	 * @var Helper_Abstract_Addon
	 */
	protected $addon;

	/**
	 * @var Helper_Abstract_Addon
	 */
	protected $addon1;

	/**
	 * The WP Unit Test Set up function
	 */
	public function set_up() {
		global $gfpdf;

		parent::set_up();

		$this->model = new Model_Settings( $gfpdf->gform, $gfpdf->log, $gfpdf->notices, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );

		$this->addon = new ModelSettingsAddon(
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

		$this->addon->set_edd_download_id( 5 );

		$this->addon1 = new ModelSettingsAddon(
			'my-other-plugin',
			'Other Plugin',
			'Gravity PDF',
			'1.2',
			'/path/to/plugin/file.php',
			\GPDFAPI::get_data_class(),
			\GPDFAPI::get_options_class(),
			new Helper_Singleton(),
			new Helper_Logger( 'my-other-plugin', 'My Custom Plugin' ),
			new Helper_Notices()
		);

		$this->addon1->set_edd_download_id( 10 );

		$this->addon->init();
		$this->addon1->init();
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

	public function test_license_bulk_get_version_api_params_skipped() {
		/* Check skipped when not initialized */
		$data          = \GPDFAPI::get_data_class();
		$data->updater = null;
		$data->addon   = [];
		$this->assertTrue( $this->model->licensing_bulk_get_version_api_params( true ) );
	}

	public function test_license_bulk_get_version_api_params_missing_updater() {
		/* Non-canonical build (updater key never registered): bulk the add-ons only, without throwing from Helper_Data::__get() */
		do_action( 'init' );

		$data = \GPDFAPI::get_data_class();
		unset( $data->updater );

		$this->assertNotEmpty( $data->addon );

		$params = $this->model->licensing_bulk_get_version_api_params( [] );
		$this->assertArrayHasKey( 'edd_action', $params );
		$this->assertCount( count( $data->addon ), $params['products'] );
	}

	public function test_license_bulk_get_version_api_params_skips_uninitialized_addon() {
		$this->setExpectedIncorrectUsage( 'GFPDF\Helper\Helper_Abstract_Addon::get_plugin_updater' );

		do_action( 'init' );

		/* Register an add-on whose updater was never initialized — get_plugin_updater() returns null */
		$data          = \GPDFAPI::get_data_class();
		$uninitialized = new ModelSettingsAddon(
			'uninitialized-plugin',
			'Uninitialized Plugin',
			'Gravity PDF',
			'1.0',
			'/path/to/plugin/file.php',
			\GPDFAPI::get_data_class(),
			\GPDFAPI::get_options_class(),
			new Helper_Singleton(),
			new Helper_Logger( 'uninitialized-plugin', 'Uninitialized Plugin' ),
			new Helper_Notices()
		);
		$data->add_addon( $uninitialized );

		/* Core + the two initialized add-ons; the uninitialized one is skipped rather than fataling */
		$params = $this->model->licensing_bulk_get_version_api_params( [] );
		$this->assertCount( 3, $params['products'] );
	}

	public function test_license_bulk_get_version_api_params_core_plugin() {
		$data        = \GPDFAPI::get_data_class();
		$data->addon = [];
		do_action( 'init' );

		$params = $this->model->licensing_bulk_get_version_api_params( [] );
		$this->assertArrayHasKey( 'edd_action', $params );
		$this->assertArrayHasKey( 'products', $params );
		$this->assertCount( 1, $params['products'] );
		$this->assertArrayHasKey( 'license', $params['products'][0] );
		$this->assertArrayHasKey( 'item_id', $params['products'][0] );
		$this->assertArrayHasKey( 'url', $params['products'][0] );
	}

	public function test_licensing_bulk_get_version_api_response() {
		do_action( 'init' );

		$params = $this->model->licensing_bulk_get_version_api_params( [] );
		$this->assertArrayHasKey( 'edd_action', $params );
		$this->assertArrayHasKey( 'products', $params );
		$this->assertCount( 3, $params['products'] );
		$this->assertArrayHasKey( 'license', $params['products'][0] );
		$this->assertArrayHasKey( 'item_id', $params['products'][0] );
		$this->assertArrayHasKey( 'url', $params['products'][0] );
		$this->assertArrayHasKey( 'license', $params['products'][1] );
		$this->assertArrayHasKey( 'item_id', $params['products'][1] );
		$this->assertArrayHasKey( 'url', $params['products'][1] );
		$this->assertArrayHasKey( 'license', $params['products'][2] );
		$this->assertArrayHasKey( 'item_id', $params['products'][2] );
		$this->assertArrayHasKey( 'url', $params['products'][2] );
	}

	public function test_licensing_bulk_get_version_api_response_maps_by_folder_slug() {
		$data          = \GPDFAPI::get_data_class();
		$data->addon   = [];
		$data->updater = null;

		/*
		 * Registered slug deliberately differs from the plugin-folder basename (e.g. a user-renamed folder). The API
		 * echoes back the folder slug the updater sent, not the registered slug — the exact mismatch L8 dropped.
		 */
		$initiator = new ModelSettingsAddon(
			'registered-initiator',
			'Initiator',
			'Gravity PDF',
			'1.0',
			'/plugins/folder-initiator/main.php',
			$data,
			\GPDFAPI::get_options_class(),
			new Helper_Singleton(),
			new Helper_Logger( 'registered-initiator', 'Initiator' ),
			new Helper_Notices()
		);

		$sibling = new ModelSettingsAddon(
			'registered-sibling',
			'Sibling',
			'Gravity PDF',
			'1.0',
			'/plugins/folder-sibling/main.php',
			$data,
			\GPDFAPI::get_options_class(),
			new Helper_Singleton(),
			new Helper_Logger( 'registered-sibling', 'Sibling' ),
			new Helper_Notices()
		);

		$initiator->init();
		$sibling->init();
		do_action( 'init' );

		$initiator_updater = $data->addon['registered-initiator']->get_plugin_updater();
		$sibling_updater   = $data->addon['registered-sibling']->get_plugin_updater();

		/* The sibling add-on is an active plugin in reality; mark it so the multisite cache gate doesn't skip its caching */
		update_option( 'active_plugins', [ plugin_basename( $sibling_updater->get_plugin_file() ) ] );

		/* The bulk response is a list of product objects identified only by the folder slug echoed back */
		$response = [
			(object) [ 'slug' => 'folder-initiator', 'new_version' => '9.9.9', 'stable_version' => '9.9.9' ],
			(object) [ 'slug' => 'folder-sibling', 'new_version' => '8.8.8', 'stable_version' => '8.8.8' ],
		];

		/* Fire the response filter as the initiator's own updater would — its plugin file identifies the initiator */
		$initial = $this->model->licensing_bulk_get_version_api_response( $response, [], $initiator_updater->get_plugin_file() );

		/* The initiator's own product is handed back for its normal caching path */
		$this->assertIsObject( $initial );
		$this->assertSame( 'folder-initiator', $initial->slug );
		$this->assertSame( '9.9.9', $initial->new_version );

		/* The sibling was linked by its folder slug (not its registered slug) and cached — this is what L8 dropped */
		$cached = $sibling_updater->get_cached_version_info();
		$this->assertIsObject( $cached );
		$this->assertSame( '8.8.8', $cached->new_version );
	}

	public function test_licensing_bulk_license_check_success() {
		do_action( 'init' );

		$data = \GPDFAPI::get_data_class();
		foreach ( $data->addon as $addon ) {
			$addon->update_license_info( [ 'license' => 'abc123', 'status' => 'valid' ] );
		}

		/* Do a good request */
		$api_response = function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [
					[
						'item_id' => 5,
						'license' => 'invalid',
					],

					[
						'item_id' => 10,
						'license' => 'valid',
					],
				] ),
			];
		};

		add_filter( 'pre_http_request', $api_response );

		$this->assertTrue( $this->model->licensing_bulk_license_check() );
		$this->assertSame( 'invalid', $this->addon->get_license_status() );
		$this->assertSame( 'valid', $this->addon1->get_license_status() );

		remove_filter( 'pre_http_request', $api_response );
	}

	public function test_licensing_bulk_license_check_no_addons() {
		$data        = \GPDFAPI::get_data_class();
		$data->addon = [];

		$this->assertFalse( $this->model->licensing_bulk_license_check() );
	}

	public function test_licensing_bulk_license_check_bad_status_code() {
		do_action( 'init' );

		wp_clear_scheduled_hook( 'gfpdf_bulk_license_check' );
		$this->assertFalse( wp_next_scheduled( 'gfpdf_bulk_license_check' ) );

		$data = \GPDFAPI::get_data_class();
		foreach ( $data->addon as $addon ) {
			$addon->update_license_info( [ 'license' => 'abc123', 'status' => 'valid' ] );
		}

		/* Do a bad request */
		$api_response = function () {
			return [
				'response' => [ 'code' => 401 ],
				'body'     => '',
			];
		};

		add_filter( 'pre_http_request', $api_response );

		$this->assertFalse( $this->model->licensing_bulk_license_check() );
		$this->assertNotFalse( wp_next_scheduled( 'gfpdf_bulk_license_check' ) );

		remove_filter( 'pre_http_request', $api_response );
	}

	public function test_licensing_bulk_license_check_bad_response() {
		do_action( 'init' );

		wp_clear_scheduled_hook( 'gfpdf_bulk_license_check' );
		$this->assertFalse( wp_next_scheduled( 'gfpdf_bulk_license_check' ) );

		$data = \GPDFAPI::get_data_class();
		foreach ( $data->addon as $addon ) {
			$addon->update_license_info( [ 'license' => 'abc123', 'status' => 'valid' ] );
		}

		/* Do a malformed request */
		$api_response = function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => '<!DOCTYPE />',
			];
		};

		add_filter( 'pre_http_request', $api_response );

		$this->assertFalse( $this->model->licensing_bulk_license_check() );
		$this->assertNotFalse( wp_next_scheduled( 'gfpdf_bulk_license_check' ) );

		remove_filter( 'pre_http_request', $api_response );
	}

	public function test_licensing_bulk_license_check_skipped_on_secondary_network_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		do_action( 'init' );

		$data = \GPDFAPI::get_data_class();
		foreach ( $data->addon as $addon ) {
			/* In-memory keys + updaters so the check would otherwise build params and POST — proving the gate is what stops it */
			$addon->update_license_info( [ 'license' => 'abc123', 'status' => 'valid' ] );
		}

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

		$this->assertFalse( $this->model->licensing_bulk_license_check() );
		$this->assertFalse( $http_called );

		restore_current_blog();
		remove_filter( 'pre_site_option_active_sitewide_plugins', $network_plugins );
		remove_filter( 'pre_http_request', $spy );
	}

	public function test_schedule_network_update_check_uses_single_event_synced_to_wp_update_plugins() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		wp_clear_scheduled_hook( 'wp_update_plugins' );
		wp_clear_scheduled_hook( 'gfpdf_network_update_check' );

		$wp_check = time() + 3 * HOUR_IN_SECONDS;
		wp_schedule_event( $wp_check, 'twicedaily', 'wp_update_plugins' );

		$this->model->schedule_network_update_check();

		/* One minute after the primary site's plugin update check */
		$this->assertSame( $wp_check + 60, wp_next_scheduled( 'gfpdf_network_update_check' ) );

		/* A one-off event (wp_get_schedule() is false for single events) so the offset is recomputed each run */
		$this->assertFalse( wp_get_schedule( 'gfpdf_network_update_check' ) );
	}

	public function test_schedule_network_update_check_falls_back_when_wp_update_plugins_unscheduled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		wp_clear_scheduled_hook( 'wp_update_plugins' );
		wp_clear_scheduled_hook( 'gfpdf_network_update_check' );

		$before = time();
		$this->model->schedule_network_update_check();

		/* Falls back to ~12 hours out so the self-rescheduling chain survives a missing primary-site check */
		$this->assertGreaterThanOrEqual( $before + 12 * HOUR_IN_SECONDS, wp_next_scheduled( 'gfpdf_network_update_check' ) );
		$this->assertFalse( wp_get_schedule( 'gfpdf_network_update_check' ) );
	}

	public function test_schedule_network_update_check_floors_overdue_wp_update_plugins_to_future() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		wp_clear_scheduled_hook( 'wp_update_plugins' );
		wp_clear_scheduled_hook( 'gfpdf_network_update_check' );

		/* wp_update_plugins only advances when the primary site runs cron, so it reports a PAST time when that site is
		   quiet. Left as-is the offset would be in the past and the self-rescheduling event would fire every cron spawn. */
		wp_schedule_event( time() - HOUR_IN_SECONDS, 'twicedaily', 'wp_update_plugins' );

		$before = time();
		$this->model->schedule_network_update_check();

		/* Floored to the future rather than immediately due */
		$this->assertGreaterThan( $before, wp_next_scheduled( 'gfpdf_network_update_check' ) );
	}

	public function test_run_network_update_check_reinjects_transient_in_subsite_context() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		/* A per-site-activated subsite (not network-activated) is the path this method targets */
		$blog_id = $this->factory()->blog->create();
		switch_to_blog( $blog_id );

		wp_clear_scheduled_hook( 'gfpdf_network_update_check' );

		/* Isolate the transient round-trip to our own spy: real listeners (Gravity Forms' check_update on the read
		   filter, stale add-on updaters) would otherwise run their own version checks and hit the network. We only
		   care which blog the re-injection fires in. */
		remove_all_filters( 'site_transient_update_plugins' );
		remove_all_filters( 'transient_update_plugins' );
		remove_all_filters( 'pre_set_site_transient_update_plugins' );

		/* Seed a non-false update_plugins transient so the round-trip has something to re-inject */
		set_site_transient( 'update_plugins', (object) [ 'checked' => [] ] );

		/* Record the blog context in which check_update() (pre_set_site_transient_update_plugins) fires */
		$fired_on_blog = null;
		$spy           = function ( $value ) use ( &$fired_on_blog ) {
			$fired_on_blog = get_current_blog_id();
			return $value;
		};
		add_filter( 'pre_set_site_transient_update_plugins', $spy );

		$this->model->run_network_update_check();

		remove_filter( 'pre_set_site_transient_update_plugins', $spy );

		/* The re-injection must fire in the subsite context (where the per-site add-on's cache lives), not the main
		   site — switching to the main site would miss that cache and force an uncached API request per updater (H1) */
		$this->assertSame( $blog_id, $fired_on_blog );
		$this->assertNotSame( get_main_site_id(), $fired_on_blog );

		/* And it re-arms the self-rescheduling event into the future (H2) */
		$this->assertGreaterThan( time(), wp_next_scheduled( 'gfpdf_network_update_check' ) );

		restore_current_blog();
	}

	public function test_run_network_update_check_skips_on_main_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		wp_clear_scheduled_hook( 'gfpdf_network_update_check' );
		remove_all_filters( 'pre_set_site_transient_update_plugins' );
		set_site_transient( 'update_plugins', (object) [ 'checked' => [] ] );

		$fired = false;
		$spy   = function ( $value ) use ( &$fired ) {
			$fired = true;
			return $value;
		};
		add_filter( 'pre_set_site_transient_update_plugins', $spy );

		/* The main site receives update checks through the normal flow, so the forced check is skipped */
		$this->model->run_network_update_check();

		remove_filter( 'pre_set_site_transient_update_plugins', $spy );

		$this->assertFalse( $fired );
		$this->assertFalse( wp_next_scheduled( 'gfpdf_network_update_check' ) );
	}

	public function test_run_network_update_check_skips_on_network_activated_secondary_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		wp_clear_scheduled_hook( 'gfpdf_network_update_check' );

		/* Network-activated secondary sites are served by the normal flow, so the forced check must not re-arm here */
		$network_plugins = static function () { return [ PDF_PLUGIN_BASENAME => time() ]; };
		add_filter( 'pre_site_option_active_sitewide_plugins', $network_plugins );
		switch_to_blog( PHP_INT_MAX );

		remove_all_filters( 'pre_set_site_transient_update_plugins' );
		set_site_transient( 'update_plugins', (object) [ 'checked' => [] ] );

		$fired = false;
		$spy   = function ( $value ) use ( &$fired ) {
			$fired = true;
			return $value;
		};
		add_filter( 'pre_set_site_transient_update_plugins', $spy );

		$this->model->run_network_update_check();

		remove_filter( 'pre_set_site_transient_update_plugins', $spy );

		$this->assertFalse( $fired );
		$this->assertFalse( wp_next_scheduled( 'gfpdf_network_update_check' ) );

		restore_current_blog();
		remove_filter( 'pre_site_option_active_sitewide_plugins', $network_plugins );
	}

	public function test_maybe_active_licenses_ignores_submitted_value_for_constant_managed_addon() {
		$slug = $this->addon->get_slug();

		/* A hardcoded constant key makes the add-on admin-managed: the submitted value must be ignored and the
		   authoritative constant value persisted, without burning an activation against the key */
		add_filter( 'gfpdf_addon_hardcoded_license_key', static function () { return 'CONSTANT-KEY'; } );
		$this->addon->update_license_info( [ 'license' => 'CONSTANT-KEY', 'status' => 'active', 'message' => 'ok' ] );

		$http_called = false;
		$spy         = function () use ( &$http_called ) {
			$http_called = true;
			return new \WP_Error( 'blocked', 'no HTTP' );
		};
		add_filter( 'pre_http_request', $spy );

		$input = $this->model->maybe_active_licenses(
			[
				"license_$slug"           => 'forged-attacker-key',
				"license_{$slug}_status"  => 'active',
				"license_{$slug}_message" => 'forged',
			]
		);

		remove_filter( 'pre_http_request', $spy );
		remove_all_filters( 'gfpdf_addon_hardcoded_license_key' );

		$this->assertFalse( $http_called );
		$this->assertSame( 'CONSTANT-KEY', $input[ "license_$slug" ] );
		$this->assertSame( 'active', $input[ "license_{$slug}_status" ] );
	}

	public function test_maybe_active_licenses_ignores_submitted_value_for_auto_activated_addon() {
		$slug = $this->addon->get_slug();

		/* Give the sibling a real Access Pass license, then auto-activate our add-on off it (its EDD id is in the pass) */
		$this->addon1->update_license_info( [ 'license' => 'AP-KEY', 'status' => 'active', 'message' => 'ok' ] );

		$response = [ 'response' => [ 'code' => 200 ], 'body' => wp_json_encode( [ 'license' => 'valid', 'products' => [ 5 ] ] ) ];
		$this->addon->maybe_auto_activate_license( $response, $this->addon1, false );
		$this->assertTrue( $this->addon->is_license_admin_managed() );

		$http_called = false;
		$spy         = function () use ( &$http_called ) {
			$http_called = true;
			return new \WP_Error( 'blocked', 'no HTTP' );
		};
		add_filter( 'pre_http_request', $spy );

		$input = $this->model->maybe_active_licenses(
			[
				"license_$slug"           => 'forged-attacker-key',
				"license_{$slug}_status"  => 'active',
				"license_{$slug}_message" => 'forged',
			]
		);

		remove_filter( 'pre_http_request', $spy );

		/* The auto-activated (admin-managed) key is authoritative — no activation POST, forged value overwritten */
		$this->assertFalse( $http_called );
		$this->assertSame( 'AP-KEY', $input[ "license_$slug" ] );
		$this->assertSame( 'active', $input[ "license_{$slug}_status" ] );
	}

	public function test_maybe_active_licenses_clears_in_memory_status_on_empty_key() {
		$slug = $this->addon->get_slug();

		$this->addon->update_license_info( [ 'license' => 'old-key', 'status' => 'active', 'message' => 'ok' ] );

		$this->model->maybe_active_licenses(
			[
				"license_$slug"          => '   ',
				"license_{$slug}_status" => 'active',
			]
		);

		/* Clearing the field must sync the cached model, not just the persisted array (L5) */
		$this->assertSame( '', $this->addon->get_license_status() );
		$this->assertSame( '', $this->addon->get_license_key() );
	}

	public function test_licensing_bulk_license_check_skips_malformed_and_unknown_items() {
		do_action( 'init' );

		$data = \GPDFAPI::get_data_class();
		foreach ( $data->addon as $addon ) {
			$addon->update_license_info( [ 'license' => 'abc123', 'status' => 'valid' ] );
		}

		/* item 5 = our add-on (valid → expired); the other two rows are malformed / unknown and must be skipped
		   without fataling, while the valid row still applies */
		$api_response = function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [
					[ 'item_id' => 5, 'license' => 'expired' ],
					[ 'license' => 'valid' ],                       // missing item_id
					[ 'item_id' => 99999, 'license' => 'valid' ],   // unknown add-on
				] ),
			];
		};
		add_filter( 'pre_http_request', $api_response );

		$this->assertTrue( $this->model->licensing_bulk_license_check() );
		$this->assertSame( 'expired', $this->addon->get_license_status() );

		remove_filter( 'pre_http_request', $api_response );
	}

}

class ModelSettingsAddon extends Helper_Abstract_Addon {
}
