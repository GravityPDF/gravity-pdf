<?php

namespace GFPDF\Tests;
use WP_UnitTestCase;
use GFFormsModel;
use GFAPI;
use GFCommon;
use WP_User;

/**
 * Test Common Gravity Forms Functions
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
 * Test the Gravity Forms functionality we rely on in Gravity PDF
 * @since 4.0
 */
class Test_Gravity_Forms extends WP_UnitTestCase
{
    /**
     * The Gravity Form ID assigned to the imported form
     * @var Integer
     * @since 4.0
     */
    public $form_id;

    /**
     * The WP Unit Test Set up function
     * @since 4.0
     */
    public function setUp() {
        parent::setUp();
        $this->import_form();
    }

    /**
     * Load the JSON data and import it into Gravity Forms
     * @since 4.0
     */
    private function import_form() {
        $json = json_decode(file_get_contents( PDF_PLUGIN_DIR . 'tests/json/form-settings.json' ), true);
        $this->form_id = GFAPI::add_form($json);
    }

    /**
     * Test Gravity Form's GFFormsModel::get_form_meta( $form_id ) functionality
     * @since 4.0
     * @group gravityforms
     */
    public function test_get_form_meta() {
        /* Test non-existant form */
        $this->assertSame( null, GFFormsModel::get_form_meta( $this->form_id + 1 ));

        /* Test for existing form */
        $form = GFFormsModel::get_form_meta( $this->form_id );

        /* Test that the data was returned correctly */
        $this->assertEquals('My First Form', $form['title']);
        $this->assertArrayHasKey('notifications', $form);
        $this->assertArrayHasKey('confirmations', $form);
        $this->assertArrayHasKey('gfpdf_form_settings', $form);

    }

    /**
     * Test Gravity Form's GFFormsModel::update_form_meta( $form_id ) functionality
     * @since 4.0
     * @group gravityforms
     */
    public function test_update_form_meta() {
        /* Get the form */
        $form = GFFormsModel::get_form_meta( $this->form_id );

        /* make changes to the values */
        $form['notifications']       = 'My Notifications';
        $form['confirmations']       = 'My Confirmation';
        $form['gfpdf_form_settings'] = 'My PDF settings';

        /* Update the form */
        GFFormsModel::update_form_meta($this->form_id, $form);
        GFFormsModel::update_form_meta($this->form_id, $form['notifications'], 'notifications');
        GFFormsModel::update_form_meta($this->form_id, $form['confirmations'], 'confirmations');

        /* check the update was successful */
        $form = GFFormsModel::get_form_meta( $this->form_id );

        $this->assertEquals('My Notifications', $form['notifications']);
        $this->assertEquals('My Confirmation', $form['confirmations']);
        $this->assertEquals('My PDF settings', $form['gfpdf_form_settings']);
    }

    /**
     * Test Gravity Form's rgpost() functionality
     * Will return the value in the $_POST array, or empty string if not
     * @since 4.0
     * @group gravityforms
     */
    public function test_rgpost() {
        /* set up post data */
        $_POST = array(
            'my_object' => 'Data here',
            'array' => array(
                'item1', "item2\'s", 'item3',
            ),
            'slashes' => "How\'s it going?"
        );

        /* check string */
        $this->assertEquals('Data here', rgpost('my_object'));

        /* check array and stripslashes deep */
        $array = rgpost('array');
        $this->assertTrue(is_array($array));
        $this->assertEquals("item2's", $array[1]);

        /* check strip slashes */
        $this->assertEquals("How's it going?", rgpost('slashes'));

        /* check non-existant value */
        $this->assertEquals('', rgpost('empty'));
    }

    /**
     * Test Gravity Form's rgget() functionality
     * Will return the value in the $_GET array, or empty string if not
     * @since 4.0
     * @group gravityforms
     */
    public function test_rgget() {
        /* set up post data */
        $_GET = array(
            'my_object' => 'Data here',
            'array' => array(
                'item1', "item2's", 'item3',
            ),
            'slashes' => "How's it going?"
        );

        /* check string */
        $this->assertEquals('Data here', rgget('my_object'));

        /* check array */
        $array = rgget('array');
        $this->assertTrue(is_array($array));
        $this->assertEquals("item2's", $array[1]);

        /* check strip slashes */
        $this->assertEquals("How's it going?", rgget('slashes'));

        /* check non-existant value */
        $this->assertEquals('', rgget('empty'));
    }

    /**
     * Test Gravity Form's rgempty() functionality which focuses on whether an array key exists
     * If not array is passed, it will use the $_POST data
     * If an array is passed as the first parameter it will check if the array is empty
     * @since 4.0
     * @group gravityforms
     */
    public function test_rgempty() {
        $array = array(
            'item1' => 'Test',
        );

        /* test main array functionality */
        $this->assertFalse(rgempty($array));
        $this->assertTrue(rgempty(array()));

        /* test if array item is empty */
        $this->assertTrue(rgempty('item2', $array));
        $this->assertFalse(rgempty('item1', $array));

        /* test if post item is empty */
        $_POST = array(
            'my_object' => 'Data here',
        );

        $this->assertFalse(rgempty('my_object'));
        $this->assertTrue(rgempty('item1'));

    }

    /**
     * Test Gravity Form's rgblank() functionality
     * Checks if the string is empty and doesn't equal 0 - which equates to true when calling empty()
     * @since 4.0
     * @group gravityforms
     */
    public function test_rgblank() {
        $this->assertTrue(rgblank(''));
        $this->assertFalse(rgblank(0));
        $this->assertFalse(rgblank('My String'));
    }

    /**
     * Test Gravity Form's rgar() functionality
     * Will return the value in the passed $array, or empty string if not
     * @since 4.0
     * @group gravityforms
     */
    public function test_rgar() {
        $array = array(
            'item1' => 'Test',
            'item2' => 'Test 2',
            'item3' => 'Test 3',
        );

        $this->assertEquals('Test', rgar($array, 'item1'));
        $this->assertEquals('Test 2', rgar($array, 'item2'));
        $this->assertEquals('Test 3', rgar($array, 'item3'));
        $this->assertEquals('', rgar($array, 'item4'));
    }

    /**
     * Test Gravity Form user privlages
     * i.e GFCommon::current_user_can_any("gravityforms_edit_settings")
     * @since 4.0
     * @group gravityforms
     */
    public function test_gf_privs()
    {
        /* create user using WP Unit Factory functions */
        $user_id = $this->factory->user->create();
        $this->assertInternalType('integer', $user_id);

        /*
         * Set up our users and test the privilages
         */
        wp_set_current_user($user_id);
        $this->assertFalse(GFCommon::current_user_can_any('gravityforms_edit_settings'));

        /* Create second user we'll use to test out the privilage */
        $user_id = $this->factory->user->create();
        $this->assertInternalType('integer', $user_id);

        /*
         * Add the user capability
         */
        $user = new WP_User($user_id);
        $user->add_cap('gravityforms_edit_settings');

        wp_set_current_user($user_id);

        $this->assertTrue(GFCommon::current_user_can_any('gravityforms_edit_settings'));

        /* Create third user we'll use to test out the privilage */
        $user_id = $this->factory->user->create();
        $this->assertInternalType('integer', $user_id);

        /*
         * Add the user capability
         */
        $user = new WP_User($user_id);
        $user->add_cap('gform_full_access');

        wp_set_current_user($user_id);

        $this->assertTrue(GFCommon::current_user_can_any('gravityforms_edit_settings'));
    }
}