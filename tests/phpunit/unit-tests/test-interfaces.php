<?php

namespace GFPDF\Tests;

use WP_UnitTestCase;

use ReflectionClass;

/**
 * Test Gravity PDF Interfaces
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/*
	This file is part of Gravity PDF.

	Gravity PDF – Copyright (c) 2019, Blue Liquid Designs

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
 * Test all helper Interfaces are standardised
 *
 * @since 4.0
 * @group interfaces
 */
class Test_Interfaces extends WP_UnitTestCase {
	/**
	 * Ensure our actions interface
	 *
	 * @since 4.0
	 */
	public function test_actions_interface() {
		$actions = new ReflectionClass( 'GFPDF\Helper\Helper_Interface_Actions' );
		$methods = $actions->getMethods();
		$this->assertEquals( 'add_actions', $methods[0]->name );
	}

	/**
	 * Ensure our filter interface
	 *
	 * @since 4.0
	 */
	public function test_filters_interface() {
		$actions = new ReflectionClass( 'GFPDF\Helper\Helper_Interface_Filters' );
		$methods = $actions->getMethods();
		$this->assertEquals( 'add_filters', $methods[0]->name );
	}
}
