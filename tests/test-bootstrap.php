<?php

namespace GFPDF\Tests;
use GFPDF\Router;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Bootstrap Class
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
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
 * Test the Bootstrap / Main Router
 * @since 4.0
 */
class Test_Bootstrap extends WP_UnitTestCase
{
    /**
     * Our Gravity PDF Router object
     * @var Object 
     * @since 4.0
     */
    public $loader;  

    /**
     * The WP Unit Test Set up function 
     * @since 4.0
     */
    public function setUp() {
        /* run parent method */
        parent::setUp();       

        /* Setup out loader class */          
        $this->loader = new Router();
        $this->loader->init();        
    }     

    /**
     * Test the global bootstrap actions are applied 
     * @since 4.0
     * @return void
     */
    public function test_actions() {
        $this->assertEquals(10, has_action('init', array($this->loader, 'register_assets')));
        $this->assertEquals(15, has_action('init', array($this->loader, 'load_assets')));
        $this->assertEquals(10, has_action('admin_init', array($this->loader, 'setup_settings_fields')));        
    }   

    /**
     * Test the global bootstrap filters are applied 
     * @since 4.0
     * @return void
     */
    public function test_filters() {
        $this->assertEquals(10, has_filter('gform_noconflict_scripts', array($this->loader, 'auto_noconflict_gfpdf')));
        $this->assertEquals(10, has_filter('gform_noconflict_styles', array($this->loader, 'auto_noconflict_gfpdf')));       
    }   

    /**
     * Test that any Gravity PDF scripts are automatically loading when GF is in no conflict mode
     * @since 4.0
     * @return void
     */
    public function test_auto_noconflict_gfpdf_js() {
        /* get test data */
        $queue = array(
            'common',
            'gfpdf_css_chosen_style',
            'admin-bar',
            'gfpdf_test',
            'gfpdf_js_chosen',
            'gfpdf_j_admin',
            'gfpdf_jsapples',
            'gfpdf_css_styles',
            'gforms_locking',
            'gfpdf_js_settings',
            'gfwebapi_enc_base64',
        );

        /* override queue */
        $wp_scripts = wp_scripts();
        $saved = $wp_scripts->queue;
        $wp_scripts->queue = $queue;

        /* get the results and test the expected output */
        $results = $this->loader->auto_noconflict_gfpdf(array());

        /* run assertions */
        $this->assertEquals(3, sizeof($results));
        $this->assertContains('gfpdf_js_chosen', $results);
        $this->assertContains('gfpdf_js_settings', $results);
        $this->assertContains('gfpdf_jsapples', $results);

        /* reset the queue */
        $wp_scripts->queue = $saved;
    }

    /**
     * Test that any Gravity PDF styles are automatically loading when GF is in no conflict mode
     * @since 4.0
     * @return void
     */
    public function test_auto_noconflict_gfpdf_css() {
        /* get test data */
        $queue = array(
            'common',
            'gfpdf_css_chosen_style',
            'admin-bar',
            'gfpdf_test',
            'gfpdf_js_chosen',
            'gfpdf_j_admin',
            'gfpdf_jsapples',
            'gfpdf_css_styles',
            'gforms_locking',
            'gfpdf_js_settings',
            'gfwebapi_enc_base64',
        );

        /* override queue */
        $wp_styles = wp_styles();
        $saved = $wp_styles->queue;
        $wp_styles->queue = $queue;

        /* get the results and test the expected output */
        $results = $this->loader->auto_noconflict_gfpdf(array());

        /* run assertions */
        $this->assertEquals(2, sizeof($results));
        $this->assertContains('gfpdf_css_chosen_style', $results);
        $this->assertContains('gfpdf_css_styles', $results);

        /* reset the queue */
        $wp_styles->queue = $saved;        
    }      
}
