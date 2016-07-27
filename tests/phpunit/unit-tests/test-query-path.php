<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_QueryPath;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Helper_QueryPath class
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2016, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2016, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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
		$this->assertEquals( 'QueryPath\DOMQuery', get_class( $qp->html5( '<div>Test</div>' ) ) );
	}

	public function test_utf8() {
		$html = '<div>ã   Ã   ╚   ╔   ╩   ╦   ╠   ═   ╬   ¤</div>';

		/* Check for UTF8 support using HTML5 module */
		$qp = new Helper_QueryPath();
		$this->assertEquals( 'ã   Ã   ╚   ╔   ╩   ╦   ╠   ═   ╬   ¤', $qp->html5( $html, 'div' )->innerHTML5() );

		/* Using the standard HTML parser these characters will not be correctly displayed when output */
		$this->assertNotEquals( 'ã   Ã   ╚   ╔   ╩   ╦   ╠   ═   ╬   ¤', htmlqp( $html, 'div' )->innerHTML5() );
	}
}
