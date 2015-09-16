<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Misc;

use WP_UnitTestCase;

/**
 * Test Gravity PDF Hlper Misc Functionality
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
 * Test the Helper_Misc class
 * @since 4.0
 * @group misc
 */
class Test_Helper_Misc extends WP_UnitTestCase
{
    /**
     * Our test class
     * @var Object
     * @since 4.0
     */
    public $misc;

    /**
     * The WP Unit Test Set up function
     * @since 4.0
     */
    public function setUp() {
        global $gfpdf;

        /* run parent method */
        parent::setUp();

        /* Setup our test classes */
        $this->misc = new Helper_Misc( $gfpdf->log, $gfpdf->form, $gfpdf->data );
    }


    /**
     * Test we are correctly stripping an extension from the end of a string
     * @param  $expected
     * @param  $string
     * @param  $type
     * @since 4.0
     * @dataProvider provider_remove_extension_from_string
     */
    public function test_remove_extension_from_string( $expected, $string, $type ) {
        $this->assertEquals( $expected, $this->misc->remove_extension_from_string( $string, $type ) );
    }

    /**
     * Data provider for our remove_extension_from_string method
     * @return Array
     * @since  4.0
     */
    public function provider_remove_extension_from_string() {
        return array(
            array( 'mydocument', 'mydocument.pdf', '.pdf'),
            array( 'mydocument', 'mydocument.jpg', '.Jpg'),
            array( 'mydocument.pdf', 'mydocument.pdf', '.pda'),
            array( 'Helper_Document', 'Helper_Document.php', '.php'),
            array( 'カタ_Document', 'カタ_Document.php', '.php'),
            array( 'カタ_Document', 'カタ_Document.excel', '.excel'),
            array( 'Working', 'Working.excel', '.excel'),
            array( 'Working_漢字', 'Working_漢字.pdf', '.pdf'),
        );
    }

    /**
     * Test we correctly convert our v3 config data into the appropriate value
     * @param  $expected
     * @param  $value
     * @since 4.0
     * @dataProvider provider_update_depreciated_config
     */
    public function test_update_depreciated_config( $expected, $value ) {
        $this->assertEquals( $expected, $this->misc->update_depreciated_config( $value ) );
    }

    /**
     * Data provider for testing update_depreciated_config()
     * @return array
     * @since 4.0
     */
    public function provider_update_depreciated_config() {
        return array(
            array('Yes', true),
            array('No', false),
            array(null, null),
            array('Other', 'Other'),
            array(array(1, 2, 3), array(1, 2, 3)),
            array('true', 'true'),
            array('false', 'false'),
        );
    }
}
