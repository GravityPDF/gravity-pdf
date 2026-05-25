<?php

namespace GFPDF\Controller;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Controller_System_Report
 *
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   system-report
 */
class Test_Controller_System_Report extends TestCase {

	public function set_up() {
		parent::set_up();

		add_filter( 'pre_http_request', [ $this, 'get_public_dir_api_response' ] );
	}

	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'get_public_dir_api_response' ] );

		parent::tear_down();
	}

	/**
	 * Override API request to speed up unit test
	 *
	 * @return array
	 */
	public function get_public_dir_api_response() {
		return [
			'response' => [ 'code' => 200 ],
			'body'     => 'failed-if-read',
		];
	}

	public function test_system_report_php() {
		/* Test that our data is spliced into the correct location in the array */
		$system_report = apply_filters( 'gform_system_report', [ [] ] );

		$this->assertArrayHasKey( 'memory', $system_report[1]['tables'][0]['items'] );
		$this->assertArrayHasKey( 'allow_url_fopen', $system_report[1]['tables'][0]['items'] );
		$this->assertArrayHasKey( 'default_charset', $system_report[1]['tables'][0]['items'] );
		$this->assertArrayHasKey( 'internal_encoding', $system_report[1]['tables'][0]['items'] );

		$this->assertArrayHasKey( 'pdf_working_directory', $system_report[1]['tables'][1]['items'] );
		$this->assertArrayHasKey( 'pdf_working_directory_url', $system_report[1]['tables'][1]['items'] );
		$this->assertArrayHasKey( 'font_folder_location', $system_report[1]['tables'][1]['items'] );
		$this->assertArrayHasKey( 'temp_folder_location', $system_report[1]['tables'][1]['items'] );
		$this->assertArrayHasKey( 'temp_folder_permission', $system_report[1]['tables'][1]['items'] );
		$this->assertArrayHasKey( 'temp_folder_protected', $system_report[1]['tables'][1]['items'] );
		$this->assertArrayHasKey( 'mpdf_temp_folder_location', $system_report[1]['tables'][1]['items'] );

		$this->assertArrayHasKey( 'pdf_entry_list_action', $system_report[1]['tables'][2]['items'] );
		$this->assertArrayHasKey( 'background_processing_enabled', $system_report[1]['tables'][2]['items'] );
		$this->assertArrayHasKey( 'debug_mode_enabled', $system_report[1]['tables'][2]['items'] );

		$this->assertArrayHasKey( 'user_restrictions', $system_report[1]['tables'][3]['items'] );
		$this->assertArrayHasKey( 'logged_out_timeout', $system_report[1]['tables'][3]['items'] );
	}

	public function test_system_report_outdated_template() {
		/* verify no outdated template info */
		$system_report = apply_filters( 'gform_system_report', [] );
		$this->assertArrayNotHasKey( 'outdated_templates', $system_report[0]['tables'][1]['items'] );

		/* copy core template to override location and adjust version number, then verify outdated message is included */
		$data          = \GPDFAPI::get_data_class();
		$override_path = $data->template_location . 'zadani.php';

		$template = file_get_contents( PDF_PLUGIN_DIR . 'src/templates/zadani.php' );
		file_put_contents( $override_path, preg_replace( '/Version: (.+?)/', 'Version: 1.5.2', $template ) );

		$system_report = apply_filters( 'gform_system_report', [] );
		$this->assertArrayHasKey( 'outdated_templates', $system_report[0]['tables'][1]['items'] );

		@unlink( $override_path );
	}
}
