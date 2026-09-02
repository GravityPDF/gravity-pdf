<?php
declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Templates;
use GFPDF_Major_Compatibility_Checks;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Model_System_Report
 *
 * @package GFPDF\Model
 *
 * @group   model
 * @group   system-report
 */
class Test_Model_System_Report extends TestCase {

	/**
	 * @var Model_System_Report
	 */
	protected $model;

	/**
	 * The WP Unit Test Set up function
	 */
	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		/* Setup our test classes */
		$this->model = new Model_System_Report( $gfpdf->options, $gfpdf->data, $gfpdf->log, $gfpdf->misc, new GFPDF_Major_Compatibility_Checks, new Helper_Templates( $gfpdf->log, $gfpdf->data, $gfpdf->gform ) );

		add_filter( 'pre_http_request', [ $this, 'get_public_dir_api_response' ] );
	}

	public function tear_down(): void {
		remove_all_filters( 'pre_http_request' );

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

	public function test_get_report_structure() {
		$structure = $this->model->get_report_structure()[0];

		$this->assertArrayHasKey( 'title', $structure );
		$this->assertArrayHasKey( 'title_export', $structure );
		$this->assertArrayHasKey( 'tables', $structure );

		/* One section per deprecation group in use, then the four the report has always had */
		$this->assertSame(
			[ 'deprecated', 'php', 'directories', 'global', 'security' ],
			array_column( $structure['tables'], 'id' )
		);
	}

	public function test_move_gravitypdf_active_plugins_to_gf_addons() {
		$system_report[1]['tables'][2]['items'] = [];

		foreach ( $this->data_test_move_gravitypdf_active_plugins_to_gf_addons() as $plugin ) {
			$system_report[1]['tables'][2]['items'][] = [ 'label' => $plugin ];
		}

		$system_report[0]['tables'][1]['items'] = [];
		$addons                                 = $this->model->move_gravitypdf_active_plugins_to_gf_addons( $system_report );

		$this->assertCount( 3, $addons[0]['tables'][1]['items'] );
		$this->assertCount( 2, $addons[1]['tables'][2]['items'] );
	}

	protected function data_test_move_gravitypdf_active_plugins_to_gf_addons() {
		return [
			'Gravity PDF Plugin',
			'NotGravityPDF Plugin',
			'NotGravityPDF Plugin Again',
			'Gravity PDF Bulk Generator',
			'Gravity PDF Something Plugin',
		];
	}

	public function test_public_tmp_directory_access() {
		$this->assertFalse( $this->model->test_public_tmp_directory_access() );

		remove_all_filters( 'pre_http_request' );

		add_filter( 'pre_http_request', function() {
			return [
				'response' => [ 'code' => 403 ],
			];
		} );

		$this->assertTrue( $this->model->test_public_tmp_directory_access() );
	}

}
