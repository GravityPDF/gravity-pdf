<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Field_Container;

use WP_UnitTestCase;
use StdClass;

/**
 * Test Gravity PDF Helper_Field_Container class
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

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
 * @since 4.0
 * @group field-container
 */
class Test_Field_Container extends WP_UnitTestCase
{

	/**
	 * Our Helper_Field_Container
	 * @var Object
	 * @since 4.0
	 */
	public $container;

	/**
	 * The WP Unit Test Set up function
	 * @since 4.0
	 */
	public function setUp() {

		/* run parent method */
		parent::setUp();

		/* Setup our test classes */
		$this->container = new Helper_Field_Container();
	}

	/**
	 * Buffers our "generate" output and returns it for testing
	 * @param  Object $field A mockup of the Gravity Form field
	 * @return String
	 * @since  4.0
	 */
	private function generate( $field ) {
		ob_start();
		$this->container->generate( $field );
		return ob_get_clean();
	}

	/**
	 * Buffers our "close" output and returns it for testing
	 * @return String
	 * @since  4.0
	 */
	private function close() {
		ob_start();
		$this->container->close();
		return ob_get_clean();
	}

	/**
	 * Check that full rows give the correct output
	 * @since 4.0
	 */
	public function test_row() {

		$field = new StdClass;
		$field->cssClass = 'normal';

		/* Check it opens correctly */
		$this->assertEquals( '<div class="row-separator">', $this->generate( $field ) );

		/* Check it closes / opens correctly */
		$this->assertEquals( '</div><div class="row-separator">', $this->generate( $field ) );
		
		/* Check it now closes correctly */
		$this->assertEquals( '</div>', $this->close() );
	}

	/**
	 * Check that two-columns give the correct output
	 * @since 4.0
	 */
	public function test_two_columns() {
		
		$field = new StdClass;
		$field->cssClass = 'gf_left_half';

		/* Check it opens correctly */
		$this->assertEquals( '<div class="row-separator">', $this->generate( $field ) );

		$field = new StdClass;
		$field->cssClass = 'gf_right_half';

		/* Check it does nothing */
		$this->assertEquals( '', $this->generate( $field ) );
		
		/* Check the row closes / opens new row correctly */
		$this->assertEquals( '</div><div class="row-separator">', $this->generate( $field ) );

		/* Check it now closes correctly */
		$this->assertEquals( '</div>', $this->close() );
	}

	/**
	 * Check that three-columns give the correct output
	 * @since 4.0
	 */
	public function test_three_columns() {
		
		$field = new StdClass;
		$field->cssClass = 'gf_left_third';

		/* Check it opens correctly */
		$this->assertEquals( '<div class="row-separator">', $this->generate( $field ) );

		$field = new StdClass;
		$field->cssClass = 'gf_middle_third';

		/* Check it does nothing */
		$this->assertEquals( '', $this->generate( $field ) );
		
		$field = new StdClass;
		$field->cssClass = 'gf_right_third';

		/* Check it does nothing */
		$this->assertEquals( '', $this->generate( $field ) );

		/* Check the row closes / opens new row correctly */
		$this->assertEquals( '</div><div class="row-separator">', $this->generate( $field ) );

		/* Check it now closes correctly */
		$this->assertEquals( '</div>', $this->close() );
	}

	/**
	 * Check that two and three column layouts can intermingle
	 * @since 4.0
	 */
	public function test_mixture() {
		
		$field = new StdClass;
		$field->cssClass = 'gf_left_third';

		/* Check it opens correctly */
		$this->assertEquals( '<div class="row-separator">', $this->generate( $field ) );

		$field = new StdClass;
		$field->cssClass = 'gf_left_half';

		/* Check it does nothing */
		$this->assertEquals( '', $this->generate( $field ) );
		
		/* Check the row closes / opens new row correctly */
		$this->assertEquals( '</div><div class="row-separator">', $this->generate( $field ) );

		/* Check it now closes correctly */
		$this->assertEquals( '</div>', $this->close() );
	}

	/**
	 *
	 * @since 4.0
	 */
	public function test_skipped_fields() {
		$field = new StdClass;
		$field->cssClass = 'gf_left_third';

		/* Check it opens correctly */
		$this->assertEquals( '<div class="row-separator">', $this->generate( $field ) );

		/* Create a skipped field and verify the container closes correctly */
		$field = new StdClass;
		$field->cssClass = 'gf_left_third';
		$field->type = 'html';

		$this->assertEquals( '</div>', $this->generate( $field ) );
	}
}
