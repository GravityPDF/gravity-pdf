<?php

namespace GFPDF\Controller;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
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
class Test_Controller_System_Report extends WP_UnitTestCase {

	/**
	 * @dataProvider data_gfpdf_system_status_items_php
	 */
	public function test_system_report_php( $table_index, $key ) {
		$system_report = apply_filters( 'gform_system_report', [] );
		$this->assertArrayHasKey( $key, $system_report[0]['tables'][ $table_index ]['items'] );

		/* Test that our data is spliced into the correct location in the array */
		$system_report = apply_filters( 'gform_system_report', [ [] ] );
		$this->assertArrayHasKey( $key, $system_report[1]['tables'][ $table_index ]['items'] );
	}

	public function data_gfpdf_system_status_items_php() {
		return [
			[ 0, 'memory' ],
			[ 0, 'allow_url_fopen' ],
			[ 0, 'default_charset' ],
			[ 0, 'internal_encoding' ],

			[ 1, 'pdf_working_directory' ],
			[ 1, 'pdf_working_directory_url' ],
			[ 1, 'font_folder_location' ],
			[ 1, 'temp_folder_location' ],
			[ 1, 'temp_folder_permission' ],
			[ 1, 'temp_folder_protected' ],
			[ 1, 'mpdf_temp_folder_location' ],

			[ 2, 'pdf_entry_list_action' ],
			[ 2, 'background_processing_enabled' ],
			[ 2, 'debug_mode_enabled' ],

			[ 3, 'user_restrictions' ],
			[ 3, 'logged_out_timeout' ],
		];
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
