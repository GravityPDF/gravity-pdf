<?php
declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Model_System_Report_Layout
 *
 * @package GFPDF\Model
 *
 * @group   model
 * @group   system-report
 */
class Test_Model_System_Report_Layout extends TestCase {

	public function test_failing_checks_come_first_and_the_rest_share_one_row() {
		$tables = ( new Model_System_Report_Layout() )->get_tables(
			[
				'unsupported' => [ 'none' => $this->check( 'Unsupported Features', true ) ],
				'php'         => [
					'memory'          => $this->check( 'PDF Memory Limit', false ),
					'allow_url_fopen' => $this->check( 'allow_url_fopen', true ),
				],
				'cache'       => [ 'cache_cleanup' => $this->check( 'PDF Cache Cleanup', true ) ],
			]
		);

		$checks = $tables['checks'];

		$this->assertSame( [ 'PDF Memory Limit', '3 of 4 checks passed', 'allow_url_fopen', 'PDF Cache Cleanup' ], array_column( $checks, 'label' ) );
		$this->assertSame( 'Unsupported Features summary · allow_url_fopen summary · PDF Cache Cleanup summary', $checks[1]['value'] );
		$this->assertSame( 'Unsupported Features', $checks[1]['label_export'] );
		$this->assertSame( 'Unsupported Features export', $checks[1]['value_export'] );
		$this->assertSame( [ true, true ], array_column( array_slice( $checks, 2 ), 'export_only' ) );
		$this->assertSame( [], $tables['configuration'] );
	}

	public function test_configuration_rows_keep_their_order_and_add_on_rows_go_last() {
		$tables = ( new Model_System_Report_Layout() )->get_tables(
			[
				'cache'       => [ 'pdf_cache' => [ 'label' => 'PDF Cache' ] ],
				'global'      => [ 'add_on_row' => [ 'label' => 'Add-on' ] ],
				'directories' => [
					'folders'                   => [ 'label' => 'Folders' ],
					'pdf_working_directory'     => [ 'label' => 'PDF Working Directory' ],
					'add_on_outdated_templates' => [
						'label' => 'Add-on Templates',
						'value' => 'a.php<br>',
					],
				],
				'security'    => [ 'add_on_check' => [ 'label' => 'Add-on Check', 'is_valid' => false ] ],
			]
		);

		$this->assertSame( [ 'PDF Working Directory', 'Folders', 'PDF Cache', 'Add-on' ], array_column( $tables['configuration'], 'label' ) );
		$this->assertSame( [ 'Add-on Templates', 'Add-on Check' ], array_column( $tables['checks'], 'label' ) );
		$this->assertSame( 'a.php', $tables['checks'][0]['value'] );
	}

	public function test_every_check_passing_says_so() {
		$tables = ( new Model_System_Report_Layout() )->get_tables(
			[
				'php' => [
					'memory'          => $this->check( 'PDF Memory Limit', true ),
					'allow_url_fopen' => $this->check( 'allow_url_fopen', true ),
				],
			]
		);

		$this->assertSame( 'All 2 checks passed', $tables['checks'][0]['label'] );
		$this->assertTrue( $tables['checks'][0]['is_valid'] );
	}

	/**
	 * A check row that passed or failed
	 */
	private function check( string $label, bool $passed ): array {
		return [
			'label'        => $label,
			'label_export' => $label,
			'value'        => $label . ' value',
			'value_export' => $label . ' export',
			'is_valid'     => $passed,
			'summary'      => $label . ' summary',
		];
	}
}
