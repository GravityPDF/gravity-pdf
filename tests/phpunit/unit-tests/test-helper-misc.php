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
 * @group helper-misc
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
     * Ensure we correctly determine when we are on a Gravity PDF admin page
     * @since 4.0
     */
    public function test_is_gfpdf_page() {

        $this->assertFalse( $this->misc->is_gfpdf_page() );

        /* Set admin page */
        set_current_screen( 'dashboard-user' );
        $this->assertFalse( $this->misc->is_gfpdf_page() );

        /* Set up PDF page */
        $_GET['page'] = 'gfpdf-tools';
        $this->assertTrue( $this->misc->is_gfpdf_page() );

        unset( $_GET['page'] );

        $_GET['subview'] = 'PDF';
        $this->assertTrue( $this->misc->is_gfpdf_page() );
    }

    /**
     * Check if we are on the current settings tab
     * @since 4.0
     */
    public function test_is_gfpdf_settings_tab() {
        $this->assertFalse( $this->misc->is_gfpdf_settings_tab( 'general' ) );

        /* Set admin page */
        set_current_screen( 'dashboard-user' );
        $_GET['subview'] = 'PDF';

        $this->assertTrue( $this->misc->is_gfpdf_settings_tab( 'general' ) );

        /* Try a different tab */
        $this->assertFalse( $this->misc->is_gfpdf_settings_tab( 'tools' ) );

        $_GET['tab'] = 'tools';
        $this->assertTrue( $this->misc->is_gfpdf_settings_tab( 'tools' ) );
    }

    /**
     * Check we convert IDs into something human readable
     * @param  $expected
     * @param  $name
     * @since 4.0
     * @dataProvider provider_human_readable
     */
    public function test_human_readable( $expected, $name ) {
        $this->assertEquals( $expected, $this->misc->human_readable( $name ) );
    }

    /**
     * Data provider for human_readable test
     * @return array
     * @since  4.0
     */
    public function provider_human_readable() {
        return array(
            array('My Pretty Name', 'my_pretty-name'),
            array('Working Title', 'worKing-title'),
            array('Easy Listening', 'Easy Listening'),
            array('Double  Trouble  Listening', 'Double--Trouble__listening'),
            array('Out Of This World', 'OUT_OF_THIS_WORLD'),
        );
    }

    /**
     * Check if our HTML DOM manipulator correctly adds the class "header-footer-img" to <img /> tags
     * @param  $expected
     * @param  $html
     * @since 4.0
     * @dataProvider provider_test_fix_header_footer
     */
    public function test_fix_header_footer( $expected, $html ) {
        $this->assertEquals( $expected, $this->misc->fix_header_footer( $html ) );
    }

    /**
     * Dataprovider for our fix_header_footer method
     * @since 4.0
     */
    public function provider_test_fix_header_footer() {
        return array(
            array( '<img src="my-image.jpg" alt="My Image" class="header-footer-img"/>', '<img src="my-image.jpg" alt="My Image" />' ),
            array( '<img src="my-image.jpg" alt="My Image" class="header-footer-img"/>', '<img src="my-image.jpg" alt="My Image">' ),
            array( '<img src="my-image.jpg" alt="My Image" class="alternate header-footer-img"/>', '<img src="my-image.jpg" alt="My Image" class="alternate" />' ),
            array( '<span>Nothing</span>', '<span>Nothing</span>' ),
            array( '', '' ),
        );
    }

    /**
     * Check that we can push an associated array item onto the beginning of an existing array
     * @since 4.0
     */
    public function test_array_unshift_assoc() {
        $array = array(
            'item1' => 'Yes',
            'item2' => 'Maybe',
            'item3' => 'I do not know',
        );

        $test = $this->misc->array_unshift_assoc( $array, 'item0', 'No' );

        $this->assertEquals( 'No', reset( $test ) );
        $this->assertEquals( 'Yes', next( $test ) );
        $this->assertEquals( 'I do not know', end( $test ) );
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

    /**
     * Check our template image is correctly loaded
     * @since 4.0
     */
    public function test_add_template_image() {
        $settings = array(
            'template' => array(
                'value' => '',
                'desc' => '',
            ),
        );

        $results = $this->misc->add_template_image( $settings );

        /* Test for lack of an image */
        $this->assertFalse( strpos( $results['template']['desc'], '<img' ) );

        /* Test for image existance */
        $settings['template']['value'] = 'zadani';
        $results = $this->misc->add_template_image( $settings );

        $this->assertNotFalse( strpos( $results['template']['desc'], '<img' ) );

        /* Test skipping results */
        $results = $this->misc->add_template_image( array() );

        $this->assertEmpty( $results );
    }
}
