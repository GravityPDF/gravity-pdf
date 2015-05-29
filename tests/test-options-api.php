<?php

namespace GFPDF\Tests;
use GFPDF\Helper\Helper_Options;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Options API Class
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
 * Test the WordPress Options API Implimentation
 * @since 4.0
 */
class Test_Options_API extends WP_UnitTestCase
{

    /**
     * Our Gravity PDF Options API Object
     * @var Object
     * @since 4.0
     */
    public $options;

    /**
     * The WP Unit Test Set up function
     * @since 4.0
     */
    public function setUp() {
        /* run parent method */
        parent::setUp();

        /* setup our object */
        $this->options = new Helper_Options();

        /* load settings in database  */
        update_option( 'gfpdf_settings', json_decode(file_get_contents( PDF_PLUGIN_DIR . 'tests/json/options-settings.json' ), true));
    }

    /**
     * Check if settings getter function works correctly
     * @group options
     * @since 4.0
     */
    public function test_get_settings()  {
       
        /**
         * Check our default action works correctly
         */
        $settings = $this->options->get_settings();

        $this->assertEquals('custom', $settings['default_pdf_size']);
        $this->assertEquals('Awesomeness', $settings['default_template']);
        $this->assertEquals('dejavusans', $settings['default_font_type']);
        $this->assertEquals('No', $settings['default_rtl']);
        $this->assertEquals('View', $settings['default_action']);
        $this->assertEquals('No', $settings['limit_to_admin']);
        $this->assertEquals('20', $settings['logged_out_timeout']);

        $this->assertTrue(is_array($settings['default_custom_pdf_size']));
        $this->assertTrue(is_array($settings['admin_capabilities']));

        $this->assertEquals(30, $settings['default_custom_pdf_size'][0]);
        $this->assertEquals(50, $settings['default_custom_pdf_size'][1]);
        $this->assertEquals('millimeters', $settings['default_custom_pdf_size'][2]);

        $this->assertEquals('gravityforms_create_form', $settings['admin_capabilities'][0]);

        /**
         * Check our transient user data is loaded
         * Used in settings_sanitize() when there are errors the user has to fix
         */
        
    }
}
