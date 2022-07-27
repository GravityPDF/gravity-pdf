<?php

namespace GFPDF\Controller;

use GFPDF\View\View_Update_Dependency_Manager;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Controller_Update_Dependency_Manager
 *
 * @package GFPDF\Controller
 *
 * @group   update-dependency-manager
 */
class Test_Controller_Update_Dependency_Manager extends WP_UnitTestCase {

	/**
	 * @var View_Update_Dependency_Manager
	 */
	protected $view;

	protected $site_transient_value_update;
	protected $site_transient_value_no_update;

	public function setUp() {
		parent::setUp();

		$this->view = new View_Update_Dependency_Manager( [] );

		$plugin                 = new \stdClass();
		$plugin->new_version    = '6.0.0';
		$plugin->package        = '6.0.0.zip';
		$plugin->requires_php   = '7.4';
		$plugin->upgrade_notice = 'This is a major release.';

		$site_transient_value            = new \stdClass();
		$site_transient_value->response  = [];
		$site_transient_value->no_update = [];

		$this->site_transient_value_update    = clone $site_transient_value;
		$this->site_transient_value_no_update = clone $site_transient_value;

		$this->site_transient_value_update->response[ PDF_PLUGIN_BASENAME ]     = $plugin;
		$this->site_transient_value_no_update->no_update[ PDF_PLUGIN_BASENAME ] = $plugin;
	}

	public function tearDown() {
		remove_all_filters( 'pre_http_request' );

		parent::tearDown();
	}

	protected function deepClone( $object ) {
		return unserialize( serialize( $object ) );
	}

	public function test_pre_set_site_transient() {
		add_filter( 'pre_http_request', '__return_true' );
		$controller = new Controller_Update_Dependency_Manager( $this->view, '7.4', '5.7', '2.5', '5.4' );

		/* Test we exit very early if no update available */
		$this->assertNull( $controller->pre_set_site_transient( null ) );

		/* Test we exit early if the update is for v5 */
		$transient = $this->deepClone( $this->site_transient_value_update );
		$transient->response[ PDF_PLUGIN_BASENAME ]->new_version = '5.4.0';

		$this->assertSame( $transient, $controller->pre_set_site_transient( $transient ) );

		/* Test we don't make any changes if the update is compatible */
		$this->assertEquals( $this->site_transient_value_update, $controller->pre_set_site_transient( $this->site_transient_value_update ) );

		/* Test we move the plugin to the no_update list when not compatible (GF) */
		$controller = new Controller_Update_Dependency_Manager( $this->view, '7.4', '5.7', '2.4', '5.4' );
		$this->assertEquals( $this->site_transient_value_no_update, $controller->pre_set_site_transient( $this->site_transient_value_update ) );

		/* Test we move the plugin to the no_update list when not compatible (WP) */
		$controller = new Controller_Update_Dependency_Manager( $this->view, '7.4', '5.0', '2.5', '5.8' );
		$this->assertEquals( $this->site_transient_value_no_update, $controller->pre_set_site_transient( $this->site_transient_value_update ) );

		/* Test we move the plugin to the update list if new minor or patch available when major update not compatible */
		remove_filter( 'pre_http_request', '__return_true' );
		add_filter(
			'pre_http_request',
			function( $pre, $parsed_args, $url ) {
				return [
					'response' => [
						'code' => 200,
					],

					'body'     => json_encode(
						[
							'versions' => [
								'5.5' => 'current-major-download.zip',
							],
						]
					),
				];
			},
			10,
			3
		);

		$controller = new Controller_Update_Dependency_Manager( $this->view, '7.4', '5.0', '2.5', '5.2' );
		$output     = $controller->pre_set_site_transient( $this->site_transient_value_no_update );

		$this->assertSame( '5.5', $output->response[ PDF_PLUGIN_BASENAME ]->new_version );
		$this->assertSame( 'current-major-download.zip', $output->response[ PDF_PLUGIN_BASENAME ]->package );
	}

	public function test_after_plugin_row() {
		/* Test for no output when version 5 update */
		$controller = new Controller_Update_Dependency_Manager( $this->view, '7.2', '5.0', '2.5', '5.2' );
		$this->assertNull( $controller->after_plugin_row( '', [ 'new_version' => '5.0.0' ] ) );

		/* Test for no output when PHP not compatible (handled by WP) */
		$controller = new Controller_Update_Dependency_Manager( $this->view, '7.2', '5.0', '2.5', '5.2' );
		$this->assertNull( $controller->after_plugin_row( '', [ 'new_version' => '6.0.0' ] ) );

		/* Test for output when WP not compatible */
		$controller = new Controller_Update_Dependency_Manager( $this->view, '7.4', '5.0', '2.5', '5.2' );
		ob_start();
		$controller->after_plugin_row(
			'',
			[
				'plugin'      => '',
				'Name'        => '',
				'slug'        => '',
				'new_version' => '6.0.0',
			]
		);

		$this->assertNotFalse( strpos( ob_get_clean(), 'There is a new version' ) );

		/* Test for output when GF not compatible */
		$controller = new Controller_Update_Dependency_Manager( $this->view, '7.4', '5.7', '2.4', '5.2' );
		ob_start();
		$controller->after_plugin_row(
			'',
			[
				'plugin'      => '',
				'Name'        => '',
				'slug'        => '',
				'new_version' => '6.0.0',
			]
		);

		$this->assertNotFalse( strpos( ob_get_clean(), 'There is a new version' ) );
	}
}
