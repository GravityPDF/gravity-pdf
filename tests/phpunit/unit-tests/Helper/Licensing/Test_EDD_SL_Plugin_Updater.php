<?php

namespace GFPDF\Helper\Licensing;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
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
}
