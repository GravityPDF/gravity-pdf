<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Migration;

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
 * Test the Helper_Migration class
 * @since 4.0
 * @group migration
 */
class Test_Migration extends WP_UnitTestCase
{
    /**
     * Our migration object
     * @var Object
     * @since 4.0
     */
    public $migration;

    /**
     * The WP Unit Test Set up function
     * @since 4.0
     */
    public function setUp() {
        global $gfpdf;

        /* run parent method */
        parent::setUp();

        /* Setup our test classes */
        $this->migration = new Helper_Migration( $gfpdf->form, $gfpdf->log, $gfpdf->data, $gfpdf->options, $gfpdf->misc, $gfpdf->notices );
    }

    /**
     * Ensure a start-to-finish migration works as expected
     * @since 4.0
     */
    public function test_begin_migration() {
        $this->markTestIncomplete( 'Write unit test' );
    }
}
