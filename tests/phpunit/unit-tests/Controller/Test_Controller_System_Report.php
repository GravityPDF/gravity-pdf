<?php

namespace GFPDF\Controller;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
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
}
