<?php

namespace GFPDF\Tests;

use ReflectionClass;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Interfaces
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
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
