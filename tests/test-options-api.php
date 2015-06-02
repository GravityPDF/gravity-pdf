<?php

namespace GFPDF\Tests;
use GFPDF\Helper\Helper_Options;
use WP_UnitTestCase;
use GFAPI;

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
     * The Gravity Form ID we are working with
     * @var Integer
     * @since  4.0
     */
    public $form_id;

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

        /* Load a form / form PDF settings into database */
        $form = json_decode(file_get_contents( PDF_PLUGIN_DIR . 'tests/json/form-settings.json' ), true);
        $form_id = GFAPI::add_form($form);

        if(!is_wp_error($form_id)) {
            $this->form_id = $form_id;
        }
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
        set_transient( 'gfpdf_settings_user_data', 'testing', 30);

        $this->assertEquals('testing', $this->options->get_settings());
    }

    /**
     * Check if Gravity Forms PDF settings getter function works correctly
     * @group options
     * @since 4.0
     */
    public function test_get_form_settings() {
        /* test false values */
        $this->assertEmpty($this->options->get_form_settings());

        $_GET['id'] = $this->form_id + 50; /* a form ID that won't exist */

        /* check empty array is returned */
        $this->assertEmpty($this->options->get_form_settings());

        /* Set up and return real values */
        $form = GFAPI::get_form($this->form_id);

        reset($form['gfpdf_form_settings']);
        $pid = key($form['gfpdf_form_settings']);
        
        /* set up our $_GET variables */
        $_GET['id'] = $this->form_id;
        $_GET['pid'] = $pid;

        /* get legitimate results */
        $results = $this->options->get_form_settings();

        /* check they contain values */
        $this->assertNotEmpty($results);

        /* check for specific values */
        $this->assertEquals('My First PDF Template', $results['name']);
        $this->assertEquals('Gravity Forms Style', $results['template']);
        $this->assertTrue(in_array('Admin Notification', $results['notification']));
    }

    /**
     * Check that any settings passed in through get_registered_settings() gets registered correctly
     * @group options
     * @since 4.0
     */
    public function test_register_settings() {
        global $wp_settings_fields, $new_whitelist_options;

        add_filter( 'gfpdf_registered_settings', function($settings) {
            return array(
                'general' => array(
                    'my_test_item' => array(
                        'id'   => 'my_test_item',
                        'name' => 'Test Item',
                        'type' => 'text',
                    ),
                )
            );
        });

        /* call our function */
        $this->options->register_settings();

        /* Test setting was added correctly */
        $this->assertTrue(isset($wp_settings_fields['gfpdf_settings_general']['gfpdf_settings_general']['gfpdf_settings[my_test_item]']));
        $this->assertEquals('Test Item', $wp_settings_fields['gfpdf_settings_general']['gfpdf_settings_general']['gfpdf_settings[my_test_item]']['title']);

        /* Test our registered settings were added */
        $this->assertTrue(isset($new_whitelist_options['gfpdf_settings']));

        /* clean up filter */
        remove_all_filters( 'gfpdf_registered_settings' );
    }

    /**
     * Check the options list is returned correctly
     * @group options
     * @since 4.0
     */
    public function test_get_registered_settings() {
        $items = $this->options->get_registered_settings();

        /* Check the array */
        $this->assertTrue(isset($items['general']));
        $this->assertTrue(isset($items['general_security']));
        $this->assertTrue(isset($items['extensions']));
        $this->assertTrue(isset($items['licenses']));
        $this->assertTrue(isset($items['tools']));
        $this->assertTrue(isset($items['form_settings']));
        $this->assertTrue(isset($items['form_settings_appearance']));
        $this->assertTrue(isset($items['form_settings_advanced']));

        /* Check filters work correctly */
        add_filter( 'gfpdf_settings_general', function($array) {
            return 'General Settings';
        });

        add_filter( 'gfpdf_settings_general_security', function($array) {
            return 'General Security Settings';
        });

        add_filter( 'gfpdf_settings_extensions', function($array) {
            return 'Extension Settings';
        });

        add_filter( 'gfpdf_settings_licenses', function($array) {
            return 'License Settings';
        });

        add_filter( 'gfpdf_settings_tools', function($array) {
            return 'Tools Settings';
        });

        add_filter( 'gfpdf_form_settings', function($array) {
            return 'PDF Form Settings';
        });

        add_filter( 'gfpdf_form_settings_appearance', function($array) {
            return 'PDF Form Settings Appearance';
        });

        add_filter( 'gfpdf_form_settings_advanced', function($array) {
            return 'PDF Form Settings Advanced';
        });

        /* reset items */
        $items = $this->options->get_registered_settings();

        $this->assertEquals('General Settings', $items['general']);
        $this->assertEquals('General Security Settings', $items['general_security']);
        $this->assertEquals('Extension Settings', $items['extensions']);
        $this->assertEquals('License Settings', $items['licenses']);
        $this->assertEquals('Tools Settings', $items['tools']);
        $this->assertEquals('PDF Form Settings', $items['form_settings']);
        $this->assertEquals('PDF Form Settings Appearance', $items['form_settings_appearance']);
        $this->assertEquals('PDF Form Settings Advanced', $items['form_settings_advanced']);

        /* Cleanup */
        remove_all_filters('gfpdf_settings_general');
        remove_all_filters('gfpdf_settings_general_security');
        remove_all_filters('gfpdf_settings_extensions');
        remove_all_filters('gfpdf_settings_licenses');
        remove_all_filters('gfpdf_settings_tools');
        remove_all_filters('gfpdf_form_settings');
        remove_all_filters('gfpdf_form_settings_appearance');
        remove_all_filters('gfpdf_form_settings_advanced');
    }


    /**
     * Test we can get a single global PDF option
     * @group options
     * @since 4.0
     */
    public function test_get_option() {

    }

    /**
     * Test we can update a single global PDF option
     * @group options
     * @since 4.0
     */
    public function test_update_option() {

    }

    /**
     * Test we can delete a single global PDF option
     * @group options
     * @since 4.0
     */
    public function test_delete_option() {

    }
}
