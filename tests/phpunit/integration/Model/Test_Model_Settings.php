<?php
declare( strict_types=1 );

namespace GFPDF\Model;

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
 * @package GFPDF\Model
 *
 * @group   model
 * @group   settings
 */
class Test_Model_Settings extends TestCase {

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

		remove_all_actions( 'init' );

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

		$data = \GPDFAPI::get_data_class();
		$data->updater = null;
		$data->addon = [];
	}

	public function tear_down() {
		parent::tear_down();

		$data = \GPDFAPI::get_data_class();
		$data->updater = null;
		$data->addon = [];
	}

	public function test_license_bulk_get_version_api_params_skipped() {
		/* Check skipped when not initialized */
		$this->assertTrue( $this->model->licensing_bulk_get_version_api_params( true ) );
	}

	public function test_license_bulk_get_version_api_params_core_plugin() {
		$this->addon->init();

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
		$this->addon->init();
		$this->addon1->init();

		do_action( 'init' );

		$params = $this->model->licensing_bulk_get_version_api_params( [] );
		$this->assertArrayHasKey( 'edd_action', $params );
		$this->assertArrayHasKey( 'products', $params );
		$this->assertCount( 2, $params['products'] );
		$this->assertArrayHasKey( 'license', $params['products'][0] );
		$this->assertArrayHasKey( 'item_id', $params['products'][0] );
		$this->assertArrayHasKey( 'url', $params['products'][0] );
		$this->assertArrayHasKey( 'license', $params['products'][1] );
		$this->assertArrayHasKey( 'item_id', $params['products'][1] );
		$this->assertArrayHasKey( 'url', $params['products'][1] );
	}

	public function test_licensing_bulk_license_check_success() {
		$this->addon->init();
		$this->addon1->init();

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
		$this->assertFalse( $this->model->licensing_bulk_license_check() );
	}

	public function test_licensing_bulk_license_check_bad_status_code() {
		$this->addon->init();
		$this->addon1->init();

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
		$this->addon->init();
		$this->addon1->init();

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

}

class ModelSettingsAddon extends Helper_Abstract_Addon {
}
