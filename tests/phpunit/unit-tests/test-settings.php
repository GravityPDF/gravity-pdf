<?php

namespace GFPDF\Tests;

use DateTimeImmutable;
use Exception;
use GFPDF\Controller\Controller_Settings;
use GFPDF\Helper\Helper_Abstract_Addon;
use GFPDF\Helper\Helper_Logger;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Singleton;
use GFPDF\Model\Model_Settings;
use GFPDF\View\View_Settings;
use GPDFAPI;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Settings Functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the model / view / controller for the Settings Page
 *
 * @since 4.0
 * @group settings
 */
class Test_Settings extends WP_UnitTestCase {

	/**
	 * Our Settings Controller
	 *
	 * @var Controller_Settings
	 * @since 4.0
	 */
	public $controller;

	/**
	 * Our Settings Model
	 *
	 * @var Model_Settings
	 * @since 4.0
	 */
	public $model;

	/**
	 * Our Settings View
	 *
	 * @var View_Settings
	 * @since 4.0
	 */
	public $view;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function setUp() {
		global $gfpdf;

		/* run parent method */
		parent::setUp();

		/* Setup our test classes */
		$this->model = new Model_Settings( $gfpdf->gform, $gfpdf->log, $gfpdf->notices, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );
		$this->view  = new View_Settings( [], $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );

		$this->controller = new Controller_Settings( $this->model, $this->view, $gfpdf->gform, $gfpdf->log, $gfpdf->notices, $gfpdf->data, $gfpdf->misc );
		$this->controller->init();
	}

	/**
	 * @since 4.2
	 */
	private function add_addon_1() {
		global $gfpdf;

		$gfpdf->data->add_addon(
			new Addon1(
				'my-custom-plugin',
				'My Custom Plugin',
				'Gravity PDF',
				'1.0',
				'/path/to/plugin/',
				GPDFAPI::get_data_class(),
				GPDFAPI::get_options_class(),
				new Helper_Singleton(),
				new Helper_Logger( 'my-custom-plugin', 'My Custom Plugin' ),
				new Helper_Notices()
			)
		);
	}

	/**
	 * @since 4.2
	 */
	private function add_addon_2() {
		global $gfpdf;

		$gfpdf->data->add_addon(
			new Addon2(
				'other-plugin',
				'Other Plugin',
				'Gravity PDF',
				'2.0',
				'/path/to/pluginv2/',
				GPDFAPI::get_data_class(),
				GPDFAPI::get_options_class(),
				new Helper_Singleton(),
				new Helper_Logger( 'other-plugin', 'Other Plugin' ),
				new Helper_Notices()
			)
		);
	}

	/**
	 * Test the appropriate actions are set up
	 *
	 * @since 4.0
	 */
	public function test_actions() {
		$this->assertFalse( has_action( 'gfpdf_post_tools_settings_page', [ $this->view, 'uninstaller' ] ) );

		$this->assertEquals( 10, has_action( 'wp_ajax_gfpdf_font_save', [ $this->model, 'save_font' ] ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_gfpdf_font_delete', [ $this->model, 'delete_font' ] ) );
		$this->assertEquals(
			10,
			has_action(
				'wp_ajax_gfpdf_deactivate_license',
				[
					$this->model,
					'process_license_deactivation',
				]
			)
		);

	}

	/**
	 * Test the appropriate filters are set up
	 *
	 * @since 4.0
	 */
	public function test_filters() {
		global $gfpdf;

		$this->assertEquals( 10, has_filter( 'gform_tooltips', [ $this->view, 'add_tooltips' ] ) );
		$this->assertEquals( 10, has_filter( 'gfpdf_capability_name', [ $this->model, 'style_capabilities' ] ) );
		$this->assertEquals(
			10,
			has_filter(
				'option_page_capability_gfpdf_settings',
				[
					$this->controller,
					'edit_options_cap',
				]
			)
		);
		$this->assertEquals(
			10,
			has_filter(
				'gravitypdf_settings_navigation',
				[
					$this->controller,
					'disable_tools_on_view_cap',
				]
			)
		);
		$this->assertEquals( 10, has_filter( 'wp_check_filetype_and_ext', [ $this->controller, 'validate_font_uploads' ] ) );

		$this->assertFalse( has_filter( 'gfpdf_registered_settings', [ $gfpdf->options, 'highlight_errors' ] ) );
		/* retest the gfpdf_register_settings filter is added when on the correct screen */
		set_current_screen( 'edit.php' );
		$_GET['page'] = 'gfpdf-admin';

		$this->controller->add_filters();
		$this->assertEquals( 10, has_filter( 'gfpdf_registered_fields', [ $this->model, 'highlight_errors' ] ) );

		/* Add licensing filter tests */
		$this->assertEquals(
			10,
			has_filter(
				'gfpdf_settings_licenses',
				[
					$this->model,
					'register_addons_for_licensing',
				]
			)
		);

		$this->assertEquals(
			10,
			has_filter(
				'gfpdf_settings_license_sanitize',
				[
					$this->model,
					'maybe_active_licenses',
				]
			)
		);
	}

	/**
	 * Check the appropriate settings pages are loading
	 *
	 * @since 4.0
	 */
	public function test_display_page() {

		/* Test for missing tab */
		ob_start();
		$_GET['tab'] = 'test';
		$this->controller->display_page();
		$html = ob_get_clean();

		$this->assertEquals( null, $html );

		/* test help tab */
		ob_start();
		$_GET['tab'] = 'help';
		$this->controller->display_page();
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, '<div id="search-knowledgebase">' ) );

		/* test help tab */
		$_GET['tab'] = 'tools';

		try {
			$this->controller->display_page();
		} catch ( Exception $e ) {
			/* Expected */
		}

		$this->assertEquals( 'You do not have permission to access this page', $e->getMessage() );

	}

	/**
	 * Check only users with the appropriate permissions can edit the Gravity PDF options
	 *
	 * @since 4.0
	 */
	public function test_edit_options_cap() {

		try {
			$this->controller->edit_options_cap();
		} catch ( Exception $e ) {
			/* Expected */
		}

		$this->assertEquals( 'Access Denied', $e->getMessage() );

		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertIsInt( $user_id );
		wp_set_current_user( $user_id );

		$this->assertEquals( 'read', $this->controller->edit_options_cap() );

		wp_set_current_user( 0 );
	}

	/**
	 * Check the tools tab gets hidden when the user permissions aren't high enough
	 *
	 * @since 4.0
	 */
	public function test_disable_tools_on_view_cap() {

		$nav = [
			10  => 'General',
			100 => 'Tools',
		];

		/* Ensure tools tab isn't present when permissions aren't set */
		$results = $this->controller->disable_tools_on_view_cap( $nav );
		$this->assertTrue( ! isset( $results[100] ) );

		/* Setup appropriate permissions and recheck */
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertIsInt( $user_id );
		wp_set_current_user( $user_id );

		$results = $this->controller->disable_tools_on_view_cap( $nav );
		$this->assertTrue( isset( $results[100] ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Verify TTF font file is validated correctly
	 *
	 * @since 4.0
	 */
	public function test_validate_font_uploads() {
		global $gfpdf;

		/* Create subscriber */
		$user_id = $this->factory->user->create();
		$this->assertIsInt( $user_id );
		wp_set_current_user( $user_id );

		$font = $gfpdf->data->template_tmp_location . '/DejaVuSans.ttf';
		copy( __DIR__ . '/fonts/DejaVuSans.ttf', $gfpdf->data->template_tmp_location . '/DejaVuSans.ttf' );

		/* Test .ttf filename failure */
		$this->assertEmpty( $this->controller->validate_font_uploads( [], $font, 'test.txt' ) );

		/* Test not exist failure */
		$this->assertEmpty( $this->controller->validate_font_uploads( [], 'test', 'DejaVuSans.ttf' ) );

		/* Test invalid font file failure */
		$this->assertEmpty( $this->controller->validate_font_uploads( [], __DIR__ . '/json/form-settings.json', 'DejaVuSans.ttf' ) );

		/* Test for invalid capabilities */
		$this->assertEmpty( $this->controller->validate_font_uploads( [], $font, 'DejaVuSans.ttf' ) );

		/* Elevate user to administrator */
		$user = wp_get_current_user();
		$user->remove_role( 'subscriber' );
		$user->add_role( 'administrator' );

		/* Do valid font check */
		$results = $this->controller->validate_font_uploads( [], $font, 'DejaVuSans.ttf' );

		$this->assertEquals( 'ttf', $results['ext'] );
		$this->assertEquals( 'font/ttf', $results['type'] );
	}

	/**
	 * Verify errors are highlighted appropriately
	 *
	 * @since 4.0
	 */
	public function test_highlight_errors() {

		/* Setup an error to match */
		set_transient(
			'settings_errors',
			[
				[
					'type' => 'error',
					'code' => 'rtl',
				],
				[
					'type' => 'error',
					'code' => 'name',
				],
			]
		);

		/* Setup settings fields */
		$settings = [
			'general' => [
				[
					'id'    => 'item',
					'class' => 'normal',
				],
				[
					'id'    => 'rtl',
					'class' => 'hello',
				],
				[
					'id'    => 'item2',
					'class' => '',
				],
				[ 'id' => 'name' ],
			],
		];

		$results = $this->model->highlight_errors( $settings );

		$this->assertEquals( 'normal', $results['general'][0]['class'] );
		$this->assertEquals( 'hello gfield_error', $results['general'][1]['class'] );
		$this->assertEquals( '', $results['general'][2]['class'] );
		$this->assertEquals( 'gfield_error', $results['general'][3]['class'] );
	}

	/**
	 * Check the font removal method works
	 *
	 * @since 4.0
	 */
	public function test_remove_font_file() {
		global $gfpdf;

		/* Create font array */
		$font = [
			'regular' => 'MyFont.ttf',
			'bold'    => 'MyFont-Bold.ttf',
		];

		/* Create our tmp font files */
		array_walk(
			$font,
			function( $value ) use ( $gfpdf ) {
				touch( $gfpdf->data->template_font_location . $value );
			}
		);

		/* Verify they exist */
		$this->assertFileExists( $gfpdf->data->template_font_location . 'MyFont.ttf' );
		$this->assertFileExists( $gfpdf->data->template_font_location . 'MyFont-Bold.ttf' );

		/* Remove the fonts and verify they are both removed */
		$this->model->remove_font_file( $font );

		$this->assertFileNotExists( $gfpdf->data->template_font_location . 'MyFont.ttf' );
		$this->assertFileNotExists( $gfpdf->data->template_font_location . 'MyFont-Bold.ttf' );
	}

	/**
	 * Check if we have a valid font name
	 *
	 * @param bool $expected
	 * @param string $name
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_is_font_name_valid
	 */
	public function test_is_font_name_valid( $expected, $name ) {
		$this->assertSame( $expected, $this->model->is_font_name_valid( $name ) );
	}

	/**
	 * Dataprovider for test_is_font_name_valid
	 *
	 * @since 4.0
	 */
	public function provider_is_font_name_valid() {
		return [
			[ true, 'My font name' ],
			[ false, 'My f@nt name' ],
			[ false, 'Calibri-pro' ],
			[ true, 'Calibri Pro' ],
			[ true, '123Roman' ],
			[ false, '123_Roman' ],
		];
	}

	/**
	 * Verify we have a unique font name
	 *
	 * @since 4.0
	 */
	public function test_is_font_name_unique() {
		global $gfpdf;
		$gfpdf->options->update_option( 'custom_fonts', [] );

		/* Check the name is unique */
		$this->assertTrue( $this->model->is_font_name_unique( 'Calibri' ) );

		/* Insert that name into the database and recheck for uniqueness */
		$font = [
			'font_name' => 'Calibri',
		];

		$results = $this->model->install_fonts( $font );

		$this->assertFalse( $this->model->is_font_name_unique( 'Calibri' ) );

		/* Ensure we skip over itself */
		$id = $results['id'];

		$this->assertTrue( $this->model->is_font_name_unique( 'Calibri', $id ) );
	}

	/**
	 * Test the install_fonts() method
	 *
	 * @since 4.0
	 */
	public function test_install_fonts() {
		global $gfpdf;

		$uploads = wp_upload_dir();

		/* Create font array */
		$font = [
			'name'        => 'Custom Font',
			'regular'     => $uploads['url'] . '/MyFont.ttf',
			'bold'        => $uploads['url'] . '/MyFont-Bold.ttf',
			'italics'     => $uploads['url'] . '/MyFont-Italics.otf',
			'bolditalics' => $uploads['url'] . '/MyFont-BI.ttf',
		];

		/* Create our tmp font files */
		array_walk(
			$font,
			function( $value ) use ( $uploads ) {
				touch( $uploads['path'] . '/' . basename( $value ) );
			}
		);

		/* Install our fonts */
		$results = $this->model->install_fonts( $font );

		/* Verify the results */
		$this->assertFileExists( $gfpdf->data->template_font_location . 'MyFont.ttf' );
		$this->assertFileExists( $gfpdf->data->template_font_location . 'MyFont-Bold.ttf' );
		$this->assertFileExists( $gfpdf->data->template_font_location . 'MyFont-Italics.otf' );
		$this->assertFileExists( $gfpdf->data->template_font_location . 'MyFont-BI.ttf' );

		$default_fonts = $gfpdf->options->get_option( 'custom_fonts' );

		$this->assertEquals( 'Custom Font', $default_fonts[ $results['id'] ]['name'] );

		/* Cleanup the fonts */
		$this->model->remove_font_file( $font );

	}

	/**
	 * @since 4.2
	 */
	public function test_register_addons_for_licensing() {
		global $gfpdf;

		$this->assertSame( [], $this->model->register_addons_for_licensing( [] ) );

		$this->add_addon_1();
		$this->add_addon_2();

		$results = $this->model->register_addons_for_licensing( [] );

		$gfpdf->data->addon = [];

		$this->assertCount( 6, $results );
		$this->assertArrayHasKey( 'license_my-custom-plugin', $results );
		$this->assertArrayHasKey( 'license_my-custom-plugin_message', $results );
		$this->assertArrayHasKey( 'license_my-custom-plugin_status', $results );
	}

	/**
	 * @since 4.2
	 */
	public function test_maybe_activate_licenses() {
		global $gfpdf;

		$this->add_addon_1();
		$this->add_addon_2();

		$this->assertSame( [], $this->model->maybe_active_licenses( [] ) );

		$api_response = function() {
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

		/* Ensure our license check runs when we provide a license key */
		$results = $this->model->maybe_active_licenses(
			[
				'license_other-plugin'         => 'user license key',
				'license_other-plugin_message' => '',
				'license_other-plugin_status'  => '',
			]
		);

		$this->assertEquals( 'Invalid license key provided', $results['license_other-plugin_message'] );
		$this->assertEquals( 'missing', $results['license_other-plugin_status'] );

		/* Ensure add-on message and status are reset when license key is empty */
		$results = $this->model->maybe_active_licenses(
			[
				'license_other-plugin'         => '',
				'license_other-plugin_message' => 'message',
				'license_other-plugin_status'  => 'status',
			]
		);

		$this->assertEquals( '', $results['license_other-plugin_message'] );
		$this->assertEquals( '', $results['license_other-plugin_status'] );

		/* Check we don't do anything when the license is active */
		$results = $this->model->maybe_active_licenses(
			[
				'license_other-plugin'         => 'license key',
				'license_other-plugin_message' => 'message',
				'license_other-plugin_status'  => 'active',
			]
		);

		$this->assertEquals( 'message', $results['license_other-plugin_message'] );
		$this->assertEquals( 'active', $results['license_other-plugin_status'] );

		/* Ensure we check the license if the license key differs from the one saved */
		$old_settings                     = $gfpdf->options->get_settings();
		$settings                         = $old_settings;
		$settings['license_other-plugin'] = 'license key';
		$gfpdf->options->update_settings( $settings );

		$results = $this->model->maybe_active_licenses(
			[
				'license_other-plugin'         => 'license key1',
				'license_other-plugin_message' => 'message',
				'license_other-plugin_status'  => 'active',
			]
		);

		$this->assertEquals( 'Invalid license key provided', $results['license_other-plugin_message'] );
		$this->assertEquals( 'missing', $results['license_other-plugin_status'] );

		/* Reset our work */
		remove_filter( 'pre_http_request', $api_response );
		$gfpdf->data->addon = [];
		$gfpdf->options->update_settings( $old_settings );
	}

	/**
	 * @param string $expected
	 * @param array  $api
	 *
	 * @since        4.2
	 * @dataProvider providerActivateLicense
	 */
	public function test_activate_license( $expected, $api ) {
		global $gfpdf;

		$this->add_addon_1();

		$api_response = function() use ( $api ) {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( $api ),
			];
		};

		add_filter( 'pre_http_request', $api_response );

		$results = $this->model->maybe_active_licenses(
			[
				'license_my-custom-plugin'         => 'user license key',
				'license_my-custom-plugin_message' => '',
				'license_my-custom-plugin_status'  => '',
			]
		);

		$this->assertEquals( $expected, $results['license_my-custom-plugin_message'] );

		remove_filter( 'pre_http_request', $api_response );
		$gfpdf->data->addon = [];
	}

	/**
	 * @return array
	 *
	 * @throws Exception
	 * @since 4.2
	 */
	public function providerActivateLicense() {
		$date_format = get_option( 'date_format' );
		$dt          = new DateTimeImmutable( '', wp_timezone() );
		$date        = $dt === false ? gmdate( $date_format, false ) : $dt->format( $date_format );

		return [
			[
				'Your license key expired on ' . $date . '.',
				[
					'error'   => 'expired',
					'expires' => '',
				],
			],

			[
				'Your license key has been disabled',
				[ 'error' => 'revoked' ],
			],

			[
				'Invalid license key provided',
				[ 'error' => 'missing' ],
			],

			[
				'Your license is not active for this URL',
				[ 'error' => 'invalid' ],
			],

			[
				'Your license is not active for this URL',
				[ 'error' => 'site_inactive' ],
			],

			[
				'This appears to be an invalid license key for My Custom Plugin',
				[ 'error' => 'item_name_mismatch' ],
			],

			[
				'Your license key has reached its activation limit',
				[ 'error' => 'no_activations_left' ],
			],

			[
				'An error occurred, please try again',
				[ 'error' => 'default' ],
			],

			[
				'An error occurred during activation, please try again',
				[ 'error' => 'generic' ],
			],

			[
				'Your support license key has been successfully validated.',
				[ 'success' => 'true' ],
			],
		];
	}

	/**
	 * @param bool  $expected
	 * @param array $api
	 * @param int   $status
	 *
	 * @since        4.2
	 * @dataProvider provider_deactivate_license_key
	 */
	public function test_deactivate_license_key( $expected, $api, $status ) {
		global $gfpdf;

		$this->add_addon_1();

		$api_response = function() use ( $api, $status ) {
			return [
				'response' => [ 'code' => $status ],
				'body'     => json_encode( $api ),
			];
		};

		add_filter( 'pre_http_request', $api_response );

		$results = $this->model->deactivate_license_key( $gfpdf->data->addon['my-custom-plugin'], '' );
		$this->assertSame( $expected, $results );

		remove_filter( 'pre_http_request', $api_response );
		$gfpdf->data->addon = [];
	}

	/**
	 * @return array
	 *
	 * @since 4.2
	 */
	public function provider_deactivate_license_key() {
		return [
			[ true, [ 'license' => 'deactivated' ], 200 ],
			[ false, [ 'license' => '' ], 200 ],
			[ false, [ 'license' => 'deactivated' ], 500 ],
		];
	}
}

class Addon1 extends Helper_Abstract_Addon {
	public function plugin_updater() {
	}
}

class Addon2 extends Helper_Abstract_Addon {
	public function plugin_updater() {
	}
}
