<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Notices;

use WP_UnitTestCase;

/**
 * Test Gravity PDF Actions functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2016, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
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
 * Test the Helper_Notices class
 *
 * @since 4.0
 * @group notices
 */
class Test_Notices extends WP_UnitTestCase {
	/**
	 * Our notice object
	 *
	 * @var \GFPDF\Helper\Helper_Notices
	 *
	 * @since 4.0
	 */
	public $notices;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function setUp() {

		/* run parent method */
		parent::setUp();

		/* Setup our test classes */
		$this->notices = new Helper_Notices();
		$this->notices->init();
	}

	/**
	 * Test the appropriate actions are set up
	 *
	 * @since 4.0
	 */
	public function test_actions() {
		$this->assertEquals( 10, has_action( 'admin_notices', array( $this->notices, 'process' ) ) );
	}

	/**
	 * Check we can correctly add a notice
	 *
	 * @since 4.0
	 */
	public function test_add_notice() {

		$this->assertFalse( $this->notices->has_notice() );
		$this->notices->add_notice( 'My First Notice' );
		$this->assertTrue( $this->notices->has_notice() );

		/* Cleanup notices */
		$this->notices->clear();
	}

	/**
	 * Check we can correctly add an error
	 *
	 * @since 4.0
	 */
	public function test_add_error() {

		$this->assertFalse( $this->notices->has_error() );
		$this->notices->add_error( 'My First Error' );
		$this->assertTrue( $this->notices->has_error() );

		/* Cleanup notices */
		$this->notices->clear();
	}

	/**
	 * Ensure we can clear notices correctly
	 *
	 * @since 4.0
	 */
	public function test_clear() {

		/* Load some data */
		$this->notices->add_notice( 'My First Notice' );
		$this->notices->add_notice( 'My First Notice' );
		$this->notices->add_error( 'My First Error' );
		$this->notices->add_error( 'My First Error' );

		/* Verify that data */
		$this->assertTrue( $this->notices->has_notice() );
		$this->assertTrue( $this->notices->has_error() );

		/* Clear all notices */
		$this->notices->clear( 'all' );

		$this->assertFalse( $this->notices->has_notice() );
		$this->assertFalse( $this->notices->has_error() );

		/* Test clearing errors only */
		$this->notices->add_notice( 'My First Notice' );
		$this->notices->add_notice( 'My First Notice' );
		$this->notices->add_error( 'My First Error' );
		$this->notices->add_error( 'My First Error' );

		$this->notices->clear( 'errors' );

		$this->assertTrue( $this->notices->has_notice() );
		$this->assertFalse( $this->notices->has_error() );

		/* Test clearning notices only */
		$this->notices->add_notice( 'My First Notice' );
		$this->notices->add_notice( 'My First Notice' );
		$this->notices->add_error( 'My First Error' );
		$this->notices->add_error( 'My First Error' );

		$this->notices->clear( 'notices' );

		$this->assertFalse( $this->notices->has_notice() );
		$this->assertTrue( $this->notices->has_error() );
	}

	/**
	 * Ensure we display / process errors and notices correctly
	 *
	 * @since 4.0
	 */
	public function test_process() {

		$this->notices->add_notice( 'My First Notice' );
		$this->notices->add_error( 'My First Error' );

		ob_start();
		$this->notices->process();
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, '<p>My First Notice</p>' ) );
		$this->assertNotFalse( strpos( $html, '<p>My First Error</p>' ) );
	}
}
