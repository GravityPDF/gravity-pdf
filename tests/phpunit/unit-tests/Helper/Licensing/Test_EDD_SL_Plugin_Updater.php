<?php

namespace GFPDF\Helper\Licensing;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   licensing
 */
class Test_EDD_SL_Plugin_Updater extends WP_UnitTestCase {
	/**
	 * @var EDD_SL_Plugin_Updater
	 */
	protected $class;

	public function set_up() {
		parent::set_up();

		$this->class = new EDD_SL_Plugin_Updater(
			'http://store.com',
			'test-plugin/test-plugin.php',
			[
				'version'   => '0.1',
				'license'   => 'abc123',
				'item_name' => 'Test Plugin',
				'item_id'   => 57,
				'author'    => 'Gravity PDF',
				'beta'      => false,
			] );

		remove_all_filters( 'pre_set_site_transient_update_plugins' );
		remove_all_filters( 'plugins_api' );
		remove_all_filters( 'pre_http_request' );

		$active_plugins = get_option( 'active_plugins', array() );
		$active_plugins[] = 'test-plugin/test-plugin.php';
		update_option( 'active_plugins', $active_plugins );
	}

	public function test_check_update_with_new_version() {
		$this->class->init();

		$api_response = function ( $pre, $parsed_args, $url ) {
			/* API response */
			if ( $url === 'http://store.com/' ) {
				return [
					'response' => [ 'code' => 200 ],
					'body'     => json_encode( [
						'new_version'    => '0.2',
						'stable_version' => '0.2',
						'name'           => 'Test Plugin',
						'slug'           => 'test-plugin',
						'package'        => 'https://store.com/download/123',
						'sections'       => [
							'description' => 'Excerpt here',
							'changelog'   => 'Changelog here',
						],
						'banners'        => [
							'high' => 'https://store.com/banner-large.png',
							'low'  => 'https://store.com/banner-small.png',
						],
						'icons'          => [
							'1x' => 'https://store.com/icon-1.png',
							'2x' => 'https://store.com/icon-2.png',
						],
						'requires'       => '6.4',
						'requires_php'   => '7.3',
						'tested'         => '10.1',
					] ),
				];
			}

			/* WP.org response */

			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [
					'plugins'      => [],
					'translations' => [],
					'no_update'    => [],
				] ),
			];
		};

		add_filter( 'pre_http_request', $api_response, 10, 3 );

		wp_update_plugins();

		/* Verify expected result */
		$updates = get_site_transient( 'update_plugins' );

		$this->assertSame( '0.1', $updates->checked['test-plugin/test-plugin.php'] );
		$this->assertArrayHasKey( 'test-plugin/test-plugin.php', $updates->response );
		$this->assertSame( '0.2', $updates->response['test-plugin/test-plugin.php']->new_version );
		$this->assertSame( '0.2', $updates->response['test-plugin/test-plugin.php']->stable_version );
		$this->assertSame( 'Test Plugin', $updates->response['test-plugin/test-plugin.php']->name );
		$this->assertSame( 'test-plugin', $updates->response['test-plugin/test-plugin.php']->slug );
		$this->assertSame( 'https://store.com/download/123', $updates->response['test-plugin/test-plugin.php']->package );
		$this->assertSame( 'https://store.com/banner-large.png', $updates->response['test-plugin/test-plugin.php']->banners['high'] );
		$this->assertSame( 'https://store.com/icon-1.png', $updates->response['test-plugin/test-plugin.php']->icons['1x'] );
		$this->assertSame( '6.4', $updates->response['test-plugin/test-plugin.php']->requires );
		$this->assertSame( '7.3', $updates->response['test-plugin/test-plugin.php']->requires_php );
		$this->assertSame( '10.1', $updates->response['test-plugin/test-plugin.php']->tested );
		$this->assertSame( 'Excerpt here', $updates->response['test-plugin/test-plugin.php']->sections['description'] );
		$this->assertSame( 'Changelog here', $updates->response['test-plugin/test-plugin.php']->sections['changelog'] );
		$this->assertSame( 'Excerpt here', $updates->response['test-plugin/test-plugin.php']->description[0] );
		$this->assertSame( 'Changelog here', $updates->response['test-plugin/test-plugin.php']->changelog[0] );

		/* Verify cleanup */
		$this->class->delete_transient_plugin_info();

		$updates = get_site_transient( 'update_plugins' );

		$this->assertArrayNotHasKey( 'test-plugin/test-plugin.php', $updates->checked );
		$this->assertArrayNotHasKey( 'test-plugin/test-plugin.php', $updates->response );
		$this->assertArrayNotHasKey( 'test-plugin/test-plugin.php', $updates->no_update );

		$this->assertNotEmpty( get_option( $this->class->get_cache_key() ) );
		$this->class->delete_version_info_cache();
		$this->assertEmpty( get_option( $this->class->get_cache_key() ) );
	}

	public function test_check_update_with_current_version() {
		$this->class->init();

		$api_response = function ( $pre, $parsed_args, $url ) {
			/* API response */
			if ( $url === 'http://store.com/' ) {
				return [
					'response' => [ 'code' => 200 ],
					'body'     => json_encode( [
						'new_version'    => '0.1',
						'stable_version' => '0.1',
						'name'           => 'Test Plugin',
						'slug'           => 'test-plugin',
						'sections'       => [
							'description' => 'Excerpt here',
							'changelog'   => 'Changelog here',
						],
						'banners'        => [
							'high' => 'https://store.com/banner-large.png',
							'low'  => 'https://store.com/banner-small.png',
						],
						'icons'          => [
							'1x' => 'https://store.com/icon-1.png',
							'2x' => 'https://store.com/icon-2.png',
						],
						'requires'       => '6.4',
						'requires_php'   => '7.3',
						'tested'         => '10.1',
					] ),
				];
			}

			/* WP.org response */

			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [
					'plugins'      => [],
					'translations' => [],
					'no_update'    => [],
				] ),
			];
		};

		add_filter( 'pre_http_request', $api_response, 10, 3 );

		wp_update_plugins();

		/* Verify expected result */
		$updates = get_site_transient( 'update_plugins' );

		$this->assertSame( '0.1', $updates->checked['test-plugin/test-plugin.php'] );
		$this->assertArrayNotHasKey( 'test-plugin/test-plugin.php', $updates->response );
		$this->assertSame( '0.1', $updates->no_update['test-plugin/test-plugin.php']->new_version );
		$this->assertSame( '0.1', $updates->no_update['test-plugin/test-plugin.php']->stable_version );
		$this->assertSame( 'Test Plugin', $updates->no_update['test-plugin/test-plugin.php']->name );
		$this->assertSame( 'test-plugin', $updates->no_update['test-plugin/test-plugin.php']->slug );
		$this->assertSame( 'https://store.com/banner-large.png', $updates->no_update['test-plugin/test-plugin.php']->banners['high'] );
		$this->assertSame( 'https://store.com/icon-1.png', $updates->no_update['test-plugin/test-plugin.php']->icons['1x'] );
		$this->assertSame( '6.4', $updates->no_update['test-plugin/test-plugin.php']->requires );
		$this->assertSame( '7.3', $updates->no_update['test-plugin/test-plugin.php']->requires_php );
		$this->assertSame( '10.1', $updates->no_update['test-plugin/test-plugin.php']->tested );
		$this->assertSame( 'Excerpt here', $updates->no_update['test-plugin/test-plugin.php']->sections['description'] );
		$this->assertSame( 'Changelog here', $updates->no_update['test-plugin/test-plugin.php']->sections['changelog'] );
		$this->assertSame( 'Excerpt here', $updates->no_update['test-plugin/test-plugin.php']->description[0] );
		$this->assertSame( 'Changelog here', $updates->no_update['test-plugin/test-plugin.php']->changelog[0] );
	}

	public function test_check_update_with_api_failure() {
		$this->class->init();

		$api_response = function ( $pre, $parsed_args, $url ) {
			/* API response */
			if ( $url === 'http://store.com/' ) {
				return [
					'response' => [ 'code' => 500 ],
				];
			}

			/* WP.org response */

			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [
					'plugins'      => [],
					'translations' => [],
					'no_update'    => [],
				] ),
			];
		};

		add_filter( 'pre_http_request', $api_response, 10, 3 );

		wp_update_plugins();

		/* Verify expected result */
		$updates = get_site_transient( 'update_plugins' );

		$this->assertSame( '0.1', $updates->checked['test-plugin/test-plugin.php'] );
		$this->assertArrayNotHasKey( 'test-plugin/test-plugin.php', $updates->response );
		$this->assertArrayNotHasKey( 'test-plugin/test-plugin.php', $updates->no_update );

		$this->assertTrue( $this->class->request_recently_failed() );

		/* test expired */
		update_option( 'gpdf_sl_failed_http_' . md5( 'http://store.com/' ), time() - 60 );

		$this->assertFalse( $this->class->request_recently_failed() );
	}

	public function test_get_version_from_remote_backs_off_on_malformed_200() {
		$this->class->init();

		/* 200 status but an empty body standardizes to false; without a recorded failure it would re-POST every call */
		$api_response = function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => '',
			];
		};

		add_filter( 'pre_http_request', $api_response );

		$this->assertFalse( $this->class->request_recently_failed() );
		$this->assertFalse( $this->class->get_version_from_remote() );
		$this->assertTrue( $this->class->request_recently_failed() );
	}

	public function test_standardize_api_response_does_not_unserialize_sections() {
		/* A serialized-object string in a JSON field is an object-injection payload; it must not be unserialized */
		$response           = new \stdClass();
		$response->sections = 'O:8:"stdClass":1:{s:3:"foo";s:3:"bar";}';

		$result = $this->class->standardize_api_response( $response );

		$this->assertSame( [], $result->sections );
	}

	public function test_standardize_api_response_unserializes_serialized_array_sections() {
		/* Regression: serialized-array sections were dropped by the object-injection hardening, blanking the changelog modal */
		$response           = new \stdClass();
		$response->sections = serialize( [ 'description' => 'Excerpt here', 'changelog' => 'Changelog here' ] );
		$response->banners  = serialize( [ 'high' => 'https://store.com/banner-large.png' ] );
		$response->icons    = serialize( [ '1x' => 'https://store.com/icon-1.png' ] );

		$result = $this->class->standardize_api_response( $response );

		$this->assertSame( 'Excerpt here', $result->sections['description'] );
		$this->assertSame( 'Changelog here', $result->sections['changelog'] );
		$this->assertSame( 'https://store.com/banner-large.png', $result->banners['high'] );
		$this->assertSame( 'https://store.com/icon-1.png', $result->icons['1x'] );
		$this->assertSame( 'Excerpt here', $result->description[0] );
		$this->assertSame( 'Changelog here', $result->changelog[0] );
	}

	public function test_standardize_api_response_serialized_array_neutralizes_nested_objects() {
		/* A serialized array that nests an object must still not instantiate the class — objects stay disallowed */
		$response           = new \stdClass();
		$response->sections = 'a:1:{s:9:"changelog";O:8:"stdClass":1:{s:3:"foo";s:3:"bar";}}';

		$result = $this->class->standardize_api_response( $response );

		$this->assertArrayHasKey( 'changelog', $result->sections );
		$this->assertNotInstanceOf( \stdClass::class, $result->sections['changelog'] );
		$this->assertIsArray( $result->sections['changelog'] );
	}

	public function test_standardize_api_response_decodes_json_string_sections() {
		/* The store may JSON-encode sections/banners/icons instead of serializing them; both must resolve to arrays */
		$response           = new \stdClass();
		$response->sections = wp_json_encode( [ 'description' => 'Excerpt here', 'changelog' => 'Changelog here' ] );
		$response->banners  = wp_json_encode( [ 'high' => 'https://store.com/banner-large.png' ] );
		$response->icons    = wp_json_encode( [ '1x' => 'https://store.com/icon-1.png' ] );

		$result = $this->class->standardize_api_response( $response );

		$this->assertSame( 'Excerpt here', $result->sections['description'] );
		$this->assertSame( 'Changelog here', $result->sections['changelog'] );
		$this->assertSame( 'https://store.com/banner-large.png', $result->banners['high'] );
		$this->assertSame( 'https://store.com/icon-1.png', $result->icons['1x'] );
		$this->assertSame( 'Changelog here', $result->changelog[0] );
	}

	public function test_standardize_api_response_returns_false_for_non_object_payload() {
		/* A non-empty, non-object payload passes empty() but the property writes below fatal on PHP 8; each must bail
		   to false. Reachable via a third-party gpdf_sl_plugin_updater_api_response filter or a malformed 200 body. */
		$this->assertFalse( $this->class->standardize_api_response( json_decode( '"a bare string"' ) ) );
		$this->assertFalse( $this->class->standardize_api_response( json_decode( '[1,2,3]' ) ) );
		$this->assertFalse( $this->class->standardize_api_response( 42 ) );
		$this->assertFalse( $this->class->standardize_api_response( true ) );
	}

	public function test_get_cached_version_info_returns_false_for_corrupted_scalar_option() {
		/* A corrupted scalar-string option (e.g. a network option edited by a super-admin) would throw a TypeError on
		   the array access inside read_timed_cache() without the is_array() guard */
		update_option( $this->class->get_cache_key(), 'corrupted-not-an-array' );

		$this->assertFalse( $this->class->get_cached_version_info() );
	}

	public function test_check_update_already_exists() {
		$updates           = new \stdClass();
		$updates->response = [
			'test-plugin' => 'yes',
		];

		$this->assertSame( $updates, $this->class->check_update( $updates ) );
		$this->assertEmpty( get_option( $this->class->get_cache_key() ) );
	}

	public function test_check_update_override() {
		$updater = new EDD_SL_Plugin_Updater(
			'http://store.com',
			'test-plugin/test-plugin.php',
			[
				'version'     => '0.1',
				'license'     => 'abc123',
				'item_name'   => 'Test Plugin',
				'item_id'     => 57,
				'author'      => 'Gravity PDF',
				'beta'        => false,
				'wp_override' => true,
			] );

		$api_response = function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [
					'new_version'    => '0.1',
					'stable_version' => '0.1',
					'name'           => 'Test Plugin',
					'slug'           => 'test-plugin',
					'sections'       => [
						'description' => 'Excerpt here',
						'changelog'   => 'Changelog here',
					],
					'banners'        => [
						'high' => 'https://store.com/banner-large.png',
						'low'  => 'https://store.com/banner-small.png',
					],
					'icons'          => [
						'1x' => 'https://store.com/icon-1.png',
						'2x' => 'https://store.com/icon-2.png',
					],
					'requires'       => '6.4',
					'requires_php'   => '7.3',
					'tested'         => '10.1',
				] ),
			];
		};

		add_filter( 'pre_http_request', $api_response, 10, 3 );

		$updates           = new \stdClass();
		$updates->response = [
			'test-plugin/test-plugin.php' => 'yes',
		];

		/* Verify expected result */
		$updates = $updater->check_update( $updates );

		$this->assertSame( '0.1', $updates->checked['test-plugin/test-plugin.php'] );
		$this->assertArrayNotHasKey( 'test-plugin/test-plugin.php', $updates->response );
		$this->assertSame( '0.1', $updates->no_update['test-plugin/test-plugin.php']->new_version );
		$this->assertSame( '0.1', $updates->no_update['test-plugin/test-plugin.php']->stable_version );
		$this->assertSame( 'Test Plugin', $updates->no_update['test-plugin/test-plugin.php']->name );
		$this->assertSame( 'test-plugin', $updates->no_update['test-plugin/test-plugin.php']->slug );
	}

	public function test_get_version_info_with_matching_store_url() {
		$updater = new EDD_SL_Plugin_Updater( home_url(), 'test-plugin/test-plugin.php' );

		$this->assertFalse( $updater->get_version_info() );
	}

	public function test_get_version_info_skipped_on_secondary_network_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		$http_called = false;
		$spy         = function () use ( &$http_called ) {
			$http_called = true;
			return new \WP_Error( 'blocked', 'no HTTP in tests' );
		};
		add_filter( 'pre_http_request', $spy );

		/* Pose as a secondary site with the add-on network-activated; only a network option is read, so no real blog is needed */
		$network_plugins = static function () { return [ 'test-plugin/test-plugin.php' => time() ]; };
		add_filter( 'pre_site_option_active_sitewide_plugins', $network_plugins );
		switch_to_blog( PHP_INT_MAX );

		$this->assertFalse( $this->class->get_version_info() );
		$this->assertFalse( $http_called );

		restore_current_blog();
		remove_filter( 'pre_site_option_active_sitewide_plugins', $network_plugins );
		remove_filter( 'pre_http_request', $spy );
	}

	public function test_get_tested_version() {
		global $wp_version;

		$wp_version = '6.5.6';

		$version_info         = new \stdClass();
		$version_info->tested = '6.5';
		$this->assertSame( $wp_version, $this->class->get_tested_version( $version_info ) );

		$version_info->tested = '6.5.2';
		$this->assertSame( $wp_version, $this->class->get_tested_version( $version_info ) );

		$version_info->tested = '6.5.8';
		$this->assertSame( '6.5.8', $this->class->get_tested_version( $version_info ) );

		$version_info->tested = '6.4';

		$this->assertSame( '6.4', $this->class->get_tested_version( $version_info ) );
	}

	public function test_set_license_key() {
		$params = $this->class->get_version_api_params();
		$this->assertSame( 'abc123', $params['license'] );

		$this->class->set_license_key( 'zxy987' );

		$params = $this->class->get_version_api_params();
		$this->assertSame( 'zxy987', $params['license'] );
	}

	public function test_get_plugin_file() {
		$this->assertSame( 'test-plugin/test-plugin.php', $this->class->get_plugin_file() );
	}

	public function test_plugins_api_filter() {
		$this->class->init();

		$args       = new \stdClass();
		$args->slug = 'test-plugin';

		$results = apply_filters( 'plugins_api', 'input123', 'hot_tags', $args );
		$this->assertSame( 'input123', $results );

		$api_response = function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [
					'new_version'    => '0.2',
					'stable_version' => '0.2',
					'name'           => 'Test Plugin',
					'slug'           => 'test-plugin',
					'package'        => 'https://store.com/download/123',
					'sections'       => [
						'description' => 'Excerpt here',
						'changelog'   => 'Changelog here',
					],
					'banners'        => [
						'high' => 'https://store.com/banner-large.png',
						'low'  => 'https://store.com/banner-small.png',
					],
					'icons'          => [
						'1x' => 'https://store.com/icon-1.png',
						'2x' => 'https://store.com/icon-2.png',
					],
					'requires'       => '6.4',
					'requires_php'   => '7.3',
					'tested'         => '10.1',
				] ),
			];
		};

		add_filter( 'pre_http_request', $api_response );

		$results = apply_filters( 'plugins_api', 'input123', 'plugin_information', $args );

		$this->assertSame( '0.2', $results->version );
		$this->assertSame( 'test-plugin/test-plugin.php', $results->plugin );
		$this->assertSame( '0.2', $results->new_version );
		$this->assertSame( '0.2', $results->stable_version );
		$this->assertSame( 'Test Plugin', $results->name );
		$this->assertSame( 'test-plugin', $results->slug );
		$this->assertSame( 'https://store.com/banner-large.png', $results->banners['high'] );
		$this->assertSame( 'https://store.com/icon-1.png', $results->icons['1x'] );
		$this->assertSame( '6.4', $results->requires );
		$this->assertSame( '7.3', $results->requires_php );
		$this->assertSame( '10.1', $results->tested );
		$this->assertSame( 'Excerpt here', $results->sections['description'] );
		$this->assertSame( 'Changelog here', $results->sections['changelog'] );
	}

	public function test_plugins_api_filter_api_failed() {
		$this->class->init();

		$args       = new \stdClass();
		$args->slug = 'test-plugin';

		$results = apply_filters( 'plugins_api', 'input123', 'hot_tags', $args );
		$this->assertSame( 'input123', $results );

		$api_response = function () {
			return [
				'response' => [ 'code' => 500 ],
				'body'     => '',
			];
		};

		add_filter( 'pre_http_request', $api_response );

		$results = apply_filters( 'plugins_api', new \stdClass(), 'plugin_information', $args );

		$this->assertCount( 1, (array) $results ); // "plugin" key
	}

	public function test_plugins_api_filter_returns_false_when_api_unreachable() {
		$this->class->init();

		$args       = new \stdClass();
		$args->slug = 'test-plugin';

		$api_response = function () {
			return [
				'response' => [ 'code' => 500 ],
				'body'     => '',
			];
		};

		add_filter( 'pre_http_request', $api_response );

		/* WP core passes $_data = false by default; a failed API leaves it false — assigning $_data->plugin on that bool fatals on PHP 8 */
		$results = apply_filters( 'plugins_api', false, 'plugin_information', $args );

		$this->assertFalse( $results );
	}

	public function test_plugins_api_filter_with_cache() {
		$this->class->init();

		$args       = new \stdClass();
		$args->slug = 'test-plugin';

		$results = apply_filters( 'plugins_api', 'input123', 'hot_tags', $args );
		$this->assertSame( 'input123', $results );

		$results = apply_filters( 'plugins_api', 'input123', 'plugin_information', new \stdClass() );
		$this->assertSame( 'input123', $results );

		update_option( $this->class->get_cache_key(), [
			'timeout' => time() + 60,
			'value'   => json_encode( [
				'new_version'    => '0.1',
				'stable_version' => '0.1',
				'name'           => 'Test Plugin',
				'slug'           => 'test-plugin',
				'sections'       => [
					'description' => 'Excerpt here',
					'changelog'   => 'Changelog here',
				],
				'banners'        => [
					'high' => 'https://store.com/banner-large.png',
					'low'  => 'https://store.com/banner-small.png',
				],
				'icons'          => [
					'1x' => 'https://store.com/icon-1.png',
					'2x' => 'https://store.com/icon-2.png',
				],
				'requires'       => '6.4',
				'requires_php'   => '7.3',
				'tested'         => '10.1',
			] ),
		] );

		$results = apply_filters( 'plugins_api', 'input123', 'plugin_information', $args );

		$this->assertSame( '0.1', $results->version );
		$this->assertSame( 'test-plugin/test-plugin.php', $results->plugin );
		$this->assertSame( '0.1', $results->new_version );
		$this->assertSame( '0.1', $results->stable_version );
		$this->assertSame( 'Test Plugin', $results->name );
		$this->assertSame( 'test-plugin', $results->slug );
		$this->assertSame( 'https://store.com/banner-large.png', $results->banners['high'] );
		$this->assertSame( 'https://store.com/icon-1.png', $results->icons['1x'] );
		$this->assertSame( '6.4', $results->requires );
		$this->assertSame( '7.3', $results->requires_php );
		$this->assertSame( '10.1', $results->tested );
		$this->assertSame( 'Excerpt here', $results->sections['description'] );
		$this->assertSame( 'Changelog here', $results->sections['changelog'] );
	}

	public function test_show_update_notification_non_multisite() {
		if ( is_multisite() ) {
			$this->markTestSkipped(
				'Not running multisite tests'
			);
		}

		$this->class->init();

		ob_start();
		do_action( 'after_plugin_row', 'test-plugin/test-plugin.php', [] );
		$this->assertEmpty( ob_get_clean() );
	}

	public function test_show_update_notification_no_privs() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped(
				'Not running multisite tests'
			);
		}

		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertIsInt( $user_id );
		wp_set_current_user( $user_id );

		$this->class->init();

		ob_start();
		do_action( 'after_plugin_row', 'test-plugin/test-plugin.php', [ 'Name' => 'Test Plugin' ] );

		$this->assertEmpty( ob_get_clean() );
	}

	public function test_show_update_notification_no_update() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped(
				'Not running multisite tests'
			);
		}

		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertIsInt( $user_id );
		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );

		$this->class->init();

		$api_response = function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [
					'new_version'    => '0.1',
					'stable_version' => '0.1',
					'name'           => 'Test Plugin',
					'slug'           => 'test-plugin',
					'package'        => 'https://store.com/download/123',
					'sections'       => [
						'description' => 'Excerpt here',
						'changelog'   => 'Changelog here',
					],
					'banners'        => [
						'high' => 'https://store.com/banner-large.png',
						'low'  => 'https://store.com/banner-small.png',
					],
					'icons'          => [
						'1x' => 'https://store.com/icon-1.png',
						'2x' => 'https://store.com/icon-2.png',
					],
					'requires'       => '6.4',
					'requires_php'   => '7.3',
					'tested'         => '10.1',
				] ),
			];
		};

		add_filter( 'pre_http_request', $api_response );

		ob_start();
		do_action( 'after_plugin_row', 'test-plugin/test-plugin.php', [] );
		$this->assertEmpty( ob_get_clean() );
	}

	public function test_show_update_notification_with_update_no_privs() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped(
				'Not running multisite tests'
			);
		}

		update_site_option( 'menu_items', [ 'plugins' => true ] );

		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertIsInt( $user_id );
		wp_set_current_user( $user_id );

		$this->class->init();

		$api_response = function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [
					'new_version'    => '0.2',
					'stable_version' => '0.2',
					'name'           => 'Test Plugin',
					'slug'           => 'test-plugin',
					'package'        => 'https://store.com/download/123',
					'sections'       => [
						'description' => 'Excerpt here',
						'changelog'   => 'Changelog here',
					],
					'banners'        => [
						'high' => 'https://store.com/banner-large.png',
						'low'  => 'https://store.com/banner-small.png',
					],
					'icons'          => [
						'1x' => 'https://store.com/icon-1.png',
						'2x' => 'https://store.com/icon-2.png',
					],
					'requires'       => '6.4',
					'requires_php'   => '7.3',
					'tested'         => '10.1',
				] ),
			];
		};

		add_filter( 'pre_http_request', $api_response );

		ob_start();
		do_action( 'after_plugin_row', 'test-plugin/test-plugin.php', [ 'Name' => 'Test Plugin' ] );

		$output = ob_get_clean();
		$this->assertStringContainsString( 'There is a new version of Test Plugin available.', $output );
		$this->assertStringContainsString( 'Contact your network administrator to install the update.', $output );
	}

	public function test_show_update_notification_with_update() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped(
				'Not running multisite tests'
			);
		}

		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertIsInt( $user_id );
		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );

		$this->class->init();

		$api_response = function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [
					'new_version'    => '0.2',
					'stable_version' => '0.2',
					'name'           => 'Test Plugin',
					'slug'           => 'test-plugin',
					'package'        => 'https://store.com/download/123',
					'sections'       => [
						'description' => 'Excerpt here',
						'changelog'   => 'Changelog here',
					],
					'banners'        => [
						'high' => 'https://store.com/banner-large.png',
						'low'  => 'https://store.com/banner-small.png',
					],
					'icons'          => [
						'1x' => 'https://store.com/icon-1.png',
						'2x' => 'https://store.com/icon-2.png',
					],
					'requires'       => '6.4',
					'requires_php'   => '7.3',
					'tested'         => '10.1',
				] ),
			];
		};

		add_filter( 'pre_http_request', $api_response );

		ob_start();
		do_action( 'after_plugin_row', 'test-plugin/test-plugin.php', [ 'Name' => 'Test Plugin' ] );

		$output = ob_get_clean();
		$this->assertStringContainsString( 'There is a new version of Test Plugin available.', $output );
		$this->assertStringContainsString( 'View version 0.2 details', $output );
		$this->assertStringContainsString( 'update now', $output );
	}

	public function test_show_update_notification_with_update_no_changelog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped(
				'Not running multisite tests'
			);
		}

		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertIsInt( $user_id );
		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );

		$this->class->init();

		$api_response = function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [
					'new_version'    => '0.2',
					'stable_version' => '0.2',
					'name'           => 'Test Plugin',
					'slug'           => 'test-plugin',
					'package'        => 'https://store.com/download/123',
					'sections'       => [
						'description' => 'Excerpt here',
					],
					'banners'        => [
						'high' => 'https://store.com/banner-large.png',
						'low'  => 'https://store.com/banner-small.png',
					],
					'icons'          => [
						'1x' => 'https://store.com/icon-1.png',
						'2x' => 'https://store.com/icon-2.png',
					],
					'requires'       => '6.4',
					'requires_php'   => '7.3',
					'tested'         => '10.1',
				] ),
			];
		};

		add_filter( 'pre_http_request', $api_response );

		ob_start();
		do_action( 'after_plugin_row', 'test-plugin/test-plugin.php', [ 'Name' => 'Test Plugin' ] );

		$output = ob_get_clean();
		$this->assertStringContainsString( 'There is a new version of Test Plugin available.', $output );
		$this->assertStringNotContainsString( 'View version 0.2 details', $output );
		$this->assertStringContainsString( 'Update now.', $output );
	}

	public function test_show_update_notification_with_update_with_changelog_no_package() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped(
				'Not running multisite tests'
			);
		}

		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertIsInt( $user_id );
		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );

		$this->class->init();

		$api_response = function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [
					'new_version'    => '0.2',
					'stable_version' => '0.2',
					'name'           => 'Test Plugin',
					'slug'           => 'test-plugin',
					'sections'       => [
						'description' => 'Excerpt here',
						'changelog'   => 'Changelog here',
					],
					'banners'        => [
						'high' => 'https://store.com/banner-large.png',
						'low'  => 'https://store.com/banner-small.png',
					],
					'icons'          => [
						'1x' => 'https://store.com/icon-1.png',
						'2x' => 'https://store.com/icon-2.png',
					],
					'requires'       => '6.4',
					'requires_php'   => '7.3',
					'tested'         => '10.1',
				] ),
			];
		};

		add_filter( 'pre_http_request', $api_response );

		ob_start();
		do_action( 'after_plugin_row', 'test-plugin/test-plugin.php', [ 'Name' => 'Test Plugin' ] );

		$output = ob_get_clean();
		$this->assertStringContainsString( 'There is a new version of Test Plugin available.', $output );
		$this->assertStringContainsString( 'View version 0.2 details', $output );
		$this->assertStringNotContainsString( 'update now', $output );
		$this->assertStringNotContainsString( 'Update now.', $output );
	}

	public function test_is_non_active_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not running multisite tests' );
		}

		$plugin          = 'test-plugin/test-plugin.php';
		$active_plugins  = [];
		$network_plugins = [];

		/* Fake the per-site and network plugin lists without touching the shared options */
		$site_filter = static function () use ( &$active_plugins ) {
			return $active_plugins;
		};
		$network_filter = static function () use ( &$network_plugins ) {
			return $network_plugins;
		};
		add_filter( 'pre_option_active_plugins', $site_filter );
		add_filter( 'pre_site_option_active_sitewide_plugins', $network_filter );

		$method = new \ReflectionMethod( $this->class, 'is_non_active_multisite' );
		$method->setAccessible( true );

		/* Active on the current site */
		$active_plugins = [ $plugin ];
		$this->assertFalse( $method->invoke( $this->class ) );

		/* Network activated */
		$active_plugins  = [];
		$network_plugins = [ $plugin => time() ];
		$this->assertFalse( $method->invoke( $this->class ) );

		/* Not active anywhere -> non-active multisite */
		$active_plugins  = [];
		$network_plugins = [];
		$this->assertTrue( $method->invoke( $this->class ) );

		remove_filter( 'pre_option_active_plugins', $site_filter );
		remove_filter( 'pre_site_option_active_sitewide_plugins', $network_filter );
	}

	/*
	 * The update check runs under WP-Cron and on the frontend, where wp-admin/includes/plugin.php (which defines
	 * is_plugin_active()) isn't loaded. That fatal can't be reproduced here because the PHPUnit bootstrap always loads
	 * the file, so guard the contract by scanning the source. php_strip_whitespace() drops comments so only real code
	 * is matched.
	 */
	public function test_multisite_check_avoids_admin_only_plugin_functions() {
		$source = php_strip_whitespace( ( new \ReflectionClass( EDD_SL_Plugin_Updater::class ) )->getFileName() );
		$this->assertStringNotContainsString( 'is_plugin_active(', $source );
	}

	public function test_set_version_info_cache_promotes_active_licensed_package_to_network() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		delete_site_option( $this->class->get_network_cache_key() );
		$this->class->set_license_status( 'valid' );

		$licensed              = new \stdClass();
		$licensed->new_version = '0.2';
		$licensed->package     = 'https://store.com/download/licensed-123';
		$this->class->set_version_info_cache( $licensed );

		$cache = get_site_option( $this->class->get_network_cache_key() );
		$this->assertNotEmpty( $cache );
		$this->assertSame( 'https://store.com/download/licensed-123', json_decode( $cache['value'] )->package );
	}

	public function test_set_version_info_cache_skips_promotion_when_license_inactive() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		delete_site_option( $this->class->get_network_cache_key() );
		$this->class->set_license_status( 'expired' );

		/* The store can return a package even for an inactive license; that URL errors, so it must not be shared */
		$response              = new \stdClass();
		$response->new_version = '0.2';
		$response->package     = 'https://store.com/download/inactive-123';
		$this->class->set_version_info_cache( $response );

		$this->assertFalse( get_site_option( $this->class->get_network_cache_key() ) );
	}

	public function test_get_repo_api_data_borrows_network_package_when_site_unlicensed() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		/* A licensed site elsewhere on the network has shared its package */
		$network              = new \stdClass();
		$network->new_version = '0.2';
		$network->package     = 'https://store.com/download/licensed-123';
		update_site_option(
			$this->class->get_network_cache_key(),
			[ 'timeout' => strtotime( '+3 hours' ), 'value' => wp_json_encode( $network ) ]
		);

		/* This site sees the update but has no package of its own (missing/invalid license) */
		$local              = new \stdClass();
		$local->new_version = '0.2';
		$local->package     = '';
		$this->class->set_version_info_cache( $local );

		$result = $this->class->get_repo_api_data();

		$this->assertSame( '0.2', $result->new_version );
		$this->assertSame( 'https://store.com/download/licensed-123', $result->package );
	}

	public function test_set_version_info_cache_network_ttl_outlives_per_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		delete_option( $this->class->get_cache_key() );
		delete_site_option( $this->class->get_network_cache_key() );
		$this->class->set_license_status( 'valid' );

		$licensed              = new \stdClass();
		$licensed->new_version = '0.2';
		$licensed->package     = 'https://store.com/download/licensed-123';
		$this->class->set_version_info_cache( $licensed );

		$per_site = get_option( $this->class->get_cache_key() );
		$network  = get_site_option( $this->class->get_network_cache_key() );

		/* The shared package must survive quiet stretches between licensed-site checks, so it outlives the per-site cache */
		$this->assertGreaterThan( $per_site['timeout'], $network['timeout'] );
	}

	public function test_get_repo_api_data_does_not_borrow_expired_network_package() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		/* A licensed site shared a package, but the network cache has since expired */
		$network              = new \stdClass();
		$network->new_version = '0.2';
		$network->package     = 'https://store.com/download/licensed-123';
		update_site_option(
			$this->class->get_network_cache_key(),
			[ 'timeout' => time() - 60, 'value' => wp_json_encode( $network ) ]
		);

		/* This site sees the update but has no package of its own (missing/invalid license) */
		$local              = new \stdClass();
		$local->new_version = '0.2';
		$local->package     = '';
		$this->class->set_version_info_cache( $local );

		$result = $this->class->get_repo_api_data();

		$this->assertSame( '0.2', $result->new_version );
		$this->assertEmpty( $result->package );
	}

	public function test_delete_network_version_info_cache_withdraws_own_promotion() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		delete_site_option( $this->class->get_network_cache_key() );
		$this->class->set_license_status( 'valid' );

		$licensed              = new \stdClass();
		$licensed->new_version = '0.2';
		$licensed->package     = 'https://store.com/download/licensed-123';
		$this->class->set_version_info_cache( $licensed );

		$cache = get_site_option( $this->class->get_network_cache_key() );
		$this->assertSame( get_current_blog_id(), $cache['blog_id'] );

		/* A failed or throttled check is not a verdict on the license, so the shared package stands */
		foreach ( [ 'error', 'rate_limit' ] as $status ) {
			$this->class->set_license_status( $status );
			$this->assertFalse( $this->class->delete_network_version_info_cache(), $status );
		}

		/* Removing the key does withdraw it */
		$this->class->set_license_key( '' );
		$this->assertTrue( $this->class->delete_network_version_info_cache() );
		$this->assertFalse( get_site_option( $this->class->get_network_cache_key() ) );
	}

	/**
	 * @dataProvider providerUnentitledLicenseStatus
	 */
	public function test_delete_network_version_info_cache_withdraws_on_store_rejection( $status ) {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		delete_site_option( $this->class->get_network_cache_key() );
		$this->class->set_license_status( 'valid' );

		$licensed              = new \stdClass();
		$licensed->new_version = '0.2';
		$licensed->package     = 'https://store.com/download/licensed-123';
		$this->class->set_version_info_cache( $licensed );

		/* The key stays populated on a rejection, so the status is the only signal the entitlement ended */
		$this->class->set_license_status( $status );

		$this->assertTrue( $this->class->delete_network_version_info_cache() );
		$this->assertFalse( get_site_option( $this->class->get_network_cache_key() ) );
	}

	public function providerUnentitledLicenseStatus() {
		return [
			[ 'expired' ],
			[ 'revoked' ],
			[ 'disabled' ],
			[ 'missing' ],
			[ 'invalid' ],
			[ 'site_inactive' ],
			[ 'item_name_mismatch' ],
			[ 'invalid_item_id' ],
			[ 'no_activations_left' ],
		];
	}

	public function test_delete_network_version_info_cache_keeps_another_sites_promotion() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		/* Another site promoted this package, so its license is still active and the package must survive */
		$network              = new \stdClass();
		$network->new_version = '0.2';
		$network->package     = 'https://store.com/download/licensed-123';
		update_site_option(
			$this->class->get_network_cache_key(),
			[ 'timeout' => strtotime( '+3 hours' ), 'value' => wp_json_encode( $network ), 'blog_id' => get_current_blog_id() + 1 ]
		);

		$this->class->set_license_key( '' );

		$this->assertFalse( $this->class->delete_network_version_info_cache() );
		$this->assertNotEmpty( get_site_option( $this->class->get_network_cache_key() ) );

		delete_site_option( $this->class->get_network_cache_key() );
	}
}
