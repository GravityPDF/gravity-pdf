<?php

namespace GFPDF\Tests;

use GFPDF\Controller\Controller_Shortcodes;
use GFPDF\Model\Model_Shortcodes;
use GFPDF\View\View_Shortcodes;

use WP_UnitTestCase;

/**
 * Test Gravity PDF Shortcode functionality
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
 * Test the model / view / controller for the Shortcode MVC
 * @since 4.0
 * @group shortcode
 */
class Test_Shortcode extends WP_UnitTestCase
{
    /**
     * Our Controller
     * @var Object
     * @since 4.0
     */
    public $controller;

    /**
     * Our Model
     * @var Object
     * @since 4.0
     */
    public $model;

    /**
     * Our View
     * @var Object
     * @since 4.0
     */
    public $view;

    /**
     * The WP Unit Test Set up function
     * @since 4.0
     */
    public function setUp() {
        global $gfpdf;

        /* run parent method */
        parent::setUp();

        /* Setup our test classes */
        $this->model = new Model_Shortcodes( $gfpdf->form, $gfpdf->log );
        $this->view  = new View_Shortcodes( array() );

        $this->controller = new Controller_Shortcodes( $this->model, $this->view, $gfpdf->log );
        $this->controller->init();
    }

    /**
     * Test the appropriate filters are set up
     * @since 4.0
     */
    public function test_filters() {
        $this->assertEquals( 10, has_filter( 'gform_confirmation', array( $this->model, 'gravitypdf_confirmation' ) ) );
        $this->assertEquals( 10, has_filter( 'gform_admin_pre_render', array( $this->model, 'gravitypdf_redirect_confirmation' ) ) );
    }

    /**
     * Test the appropriate shortcodes are set up
     * @since 4.0
     */
    public function test_shortcodes() {
        global $shortcode_tags;

        /* Check shortcode not set up */
        $this->assertTrue( isset( $shortcode_tags['gravitypdf']) );
    }

    /**
     * Test the gravitypdf shortcodes render as expected
     * @since 4.0
     */
    public function test_gravitypdf() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Test we're correctly handling the Gravity Forms text confirmation method and including the entry ID
     * @since 4.0
     */
    public function test_gravitypdf_confirmation() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Test we can correctly update shortcode attributes easily
     * @since 4.0
     */
    public function test_add_shortcode_attr() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Verify we're replacing a shortcode with the correct URL for the redirect confirmation
     * @since 4.0
     */
    public function test_gravitypdf_redirect_confirmation() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Verify we can return a parsed version of the shortcode information
     * @since 4.0
     */
    public function test_get_shortcode_information() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check our no entry ID view displays correctly
     * @since 4.0
     */
    public function test_no_entry_id() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check our invalid PDF view displays correctly
     * @since 4.0
     */
    public function test_invalid_pdf_config() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check our display GF shortcode view displays correctly
     * @since 4.0
     */
    public function test_display_gravitypdf_shortcode() {
        $this->markTestIncomplete( 'Write unit test' );
    }
}
