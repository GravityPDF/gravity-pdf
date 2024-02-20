<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Mpdf;
use WP_UnitTestCase;

/**
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.5
 */

/**
 * @since 5.2
 * @group mpdf
 */
class Test_Helper_Mpdf extends WP_UnitTestCase {

	/**
	 * @since 5.2
	 */
	public function test_extends() {
		$this->assertInstanceOf(
			'\GFPDF_Vendor\Mpdf\Mpdf',
			new Helper_Mpdf(
				[
					'mode'    => 'c',
					'tempDir' => sys_get_temp_dir(),
				]
			)
		);
	}

	/**
	 * @since 5.2
	 */
	public function test_set_import_use() {
		$this->assertTrue( method_exists( Helper_Mpdf::class, 'SetImportUse' ) );
	}

	/**
	 * @since 5.2
	 */
	public function test_write_html() {
		$e   = null;
		$pdf = new Helper_Mpdf(
			[
				'mode'    => 'c',
				'tempDir' => sys_get_temp_dir(),
			]
		);
		try {
			$pdf->WriteHTML( 'test', 10 );
		} catch ( \Exception $e ) {

		}

		$this->assertNull( $e );
	}
}
