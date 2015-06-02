<?php

namespace GFPDF\Tests;
use GFPDF\Controller\Controller_Settings;
use GFPDF\Model\Model_Settings;
use GFPDF\View\View_Settings;
use WP_UnitTestCase;
use WP_Error;

/**
 * Test Gravity PDF Settings Functionality
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
 * Test the model / view / controller for the Settings Page
 * @since 4.0
 */
class Test_Settings extends WP_UnitTestCase
{

    /**
     * Our Settings Controller
     * @var Object
     * @since 4.0
     */
    public $controller;

    /**
     * Our Settings Model
     * @var Object
     * @since 4.0
     */
    public $model;

    /**
     * Our Settings View
     * @var Object
     * @since 4.0
     */
    public $view;

    /**
     * The WP Unit Test Set up function
     * @since 4.0
     */
    public function setUp() {

        /* run parent method */
        parent::setUp();

        /* Setup our test classes */
        $this->model = new Model_Settings();
        $this->view  = new View_Settings(array());

        $this->controller = new Controller_Settings($this->model, $this->view);
        $this->controller->init();
    }

    /**
     * Test the appropriate actions are set up
     * @since 4.0
     * @group settings
     */
    public function test_actions() {
        $this->assertEquals(10, has_action( 'current_screen', array( $this->model, 'add_meta_boxes')));
        $this->assertEquals(10, has_action( 'pdf-settings-general', array( $this->view, 'system_status')));
        $this->assertEquals(10, has_action( 'pdf-settings-tools', array( $this->view, 'system_status')));
        $this->assertEquals(20, has_action( 'pdf-settings-tools', array( $this->view, 'uninstaller')));
    }

    /**
     * Test the appropriate filters are set up
     * @since 4.0
     * @group settings
     */
    public function test_filters() {
        global $gfpdf;
        
        $this->assertEquals(10, has_filter( 'gform_tooltips', array( $this->view, 'add_tooltips')));
        $this->assertEquals(10, has_filter( 'gfpdf_capability_name', array( $this->model, 'style_capabilities')));
        $this->assertFalse(has_filter( 'gfpdf_registered_settings', array( $gfpdf->options, 'highlight_errors')));

        /* retest the gfpdf_register_settings filter is added when on the correct screen */
        set_current_screen( 'edit.php' );
        $_GET['page'] = 'gfpdf-settings';

        $this->controller->add_filters();

        $this->assertEquals(10, has_filter( 'gfpdf_registered_settings', array( $this->model, 'highlight_errors')));
    }

    /**
     * Test the appropriate filters are set up
     * @since 4.0
     * @group settings
     */
    public function test_setup_form_settings_errors() {
        $this->markTestIncomplete('This test has not been implimented yet');
    }

    /**
     * Test the appropriate filters are set up
     * @since 4.0
     * @group settings
     */
    public function test_highlight_errors() {
        $this->markTestIncomplete('This test has not been implimented yet');
    }

    /**
     * Test all required custom meta boxes are added
     * @since 4.0
     * @group settings
     */
    public function test_meta_boxes() {
        global $wp_meta_boxes;

        /* run our method to test */
        $this->model->add_meta_boxes();

        /* check our meta boxes have been added */
        $this->assertTrue(isset($wp_meta_boxes['pdf-help-and-support']));
        $this->assertTrue(isset($wp_meta_boxes['pdf-help-and-support']['row-1']));
        $this->assertTrue(isset($wp_meta_boxes['pdf-help-and-support']['row-2']));

        /* check they are not empty */
        $this->assertNotEquals(0, sizeof($wp_meta_boxes['pdf-help-and-support']['row-1']['default']));
        $this->assertNotEquals(0, sizeof($wp_meta_boxes['pdf-help-and-support']['row-2']['default']));
    }

    /**
     * Test the forum endpoint is returning the correct response
     * @since 4.0
     * @group settings
     */
    public function test_latest_forum_endpoint() {
        /* set a correct response */
        add_filter( 'pre_http_request', function($return, $r, $url) {
           $r['body'] = file_get_contents( PDF_PLUGIN_DIR . 'tests/json/latest-posts.json' );
           return $r;
        }, 10, 3);

        /* check for correct results */
        $response = $this->model->get_latest_forum_topics();
        $this->assertEquals(5, sizeof($response));
    }

    /**
     * Test the forum endpoint caching works as expected
     * @since 4.0
     * @group settings
     */
    public function test_endpoint_caching() {
        set_transient('gfpdf_latest_forum_topics', 'checking results', 86400);

        $this->assertEquals('checking results', $this->model->get_latest_forum_topics());

        delete_transient('gfpdf_latest_forum_topics');
    }

    /**
     * Test the forum endpoint returns the appropriate response when an error occurs
     * Note: had to split this out of the original endpoint as WP appeared to be caching the results (wasn't going to case the 'whys' so split it out in own method)
     * @since 4.0
     * @group settings
     */
    public function test_latest_forum_endpoint_error() {
        /* check for appropriate errors */
        add_filter( 'pre_http_request', function($return, $r, $url) {
           return new WP_Error('problem', 'Cannot load endpoint');
        }, 10, 3);

        /* check error thrown */
        $this->assertFalse($this->model->get_latest_forum_topics());
    }
}
