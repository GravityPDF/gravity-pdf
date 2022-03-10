<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_QueryPath;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Helper_QueryPath class
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/**
 * @since 4.0.3
 * @group querypath
 */
class Test_QueryPath extends WP_UnitTestCase {

	/**
	 * Ensure we get a QueryPath object returned
	 *
	 * @since 4.0
	 */
	public function test_QueryPath() {
		$qp = new Helper_QueryPath();
		$this->assertEquals( 'GFPDF_Vendor\QueryPath\DOMQuery', get_class( $qp->html5( '<div>Test</div>' ) ) );
	}

	public function test_utf8() {
		$html = '<div>ã   Ã   ╚   ╔   ╩   ╦   ╠   ═   ╬   ¤</div>';

		/* Check for UTF8 support using HTML5 module */
		$qp = new Helper_QueryPath();
		$this->assertEquals( 'ã   Ã   ╚   ╔   ╩   ╦   ╠   ═   ╬   ¤', $qp->html5( $html, 'div' )->innerHTML5() );

		/* Using the standard HTML parser these characters will not be correctly displayed when output */
		$this->assertNotEquals( 'ã   Ã   ╚   ╔   ╩   ╦   ╠   ═   ╬   ¤', \GFPDF_Vendor\htmlqp( $html, 'div' )->innerHTML5() );
	}
}
