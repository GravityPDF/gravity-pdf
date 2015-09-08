<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Notices;

use WP_UnitTestCase;

/**
 * Test Gravity PDF Actions functionality
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
 * Test the Helper_Notices class
 * @since 4.0
 * @group notices
 */
class Test_Notices extends WP_UnitTestCase
{
    /**
     * Our notice object
     * @var Object
     * @since 4.0
     */
    public $notices;

    /**
     * The WP Unit Test Set up function
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
     * @since 4.0
     */
    public function test_actions() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Test we correctly return the notice type based on the current location of the user
     * @since 4.0
     */
    public function test_get_notice_type() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check we can correctly add a notice
     * @since 4.0
     */
    public function test_add_notice() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check we can correctly add an error
     * @since 4.0
     */
    public function test_add_error() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Can we correctly determine if there are any scheduled notices
     * @since 4.0
     */
    public function test_has_notice() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Ensure we display / process errors and notices correctly
     * @since 4.0
     */
    public function test_process() {
        $this->markTestIncomplete( 'Write unit test' );
    }
}
