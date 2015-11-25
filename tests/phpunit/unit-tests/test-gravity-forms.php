<?php

namespace GFPDF\Tests;

use GFFormsModel;
use GFAPI;
use GFForms;
use GFEntryDetail;
use GFFormDisplay;
use RGFormsModel;
use GFCommon;

use PDF_Common;

use WP_UnitTestCase;
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
 *
 * @since 4.0
 * @group gravity-forms
 */
class Test_Gravity_Forms extends WP_UnitTestCase {
	/**
	 * The Gravity Form ID assigned to the imported form
	 *
	 * @var integer
	 *
	 * @since 4.0
	 */
	public $form_id;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function setUp() {
		parent::setUp();

		$this->setup_form();
	}

	/**
	 * Pull in our form data
	 *
	 * @since 4.0
	 */
	private function setup_form() {
		$this->form_id = $GLOBALS['GFPDF_Test']->form['form-settings']['id'];
	}

	/**
	 * Test Gravity Form's GFFormsModel::get_form_meta( $form_id ) functionality
	 *
	 * @since 4.0
	 */
	public function test_get_form_meta() {
		/* Test non-existant form */
		$this->assertSame( null, GFFormsModel::get_form_meta( $this->form_id + 5000 ) );

		/* Test for existing form */
		$form = GFFormsModel::get_form_meta( $this->form_id );

		/* Test that the data was returned correctly */
		$this->assertEquals( 'My First Form', $form['title'] );
		$this->assertArrayHasKey( 'notifications', $form );
		$this->assertArrayHasKey( 'confirmations', $form );
		$this->assertArrayHasKey( 'gfpdf_form_settings', $form );

	}

	/**
	 * Test Gravity Form's GFFormsModel::update_form_meta( $form_id ) functionality
	 *
	 * @since 4.0
	 */
	public function test_update_form_meta() {
		/* Get the form */
		$form = GFFormsModel::get_form_meta( $this->form_id );

		/* make changes to the values */
		$form['notifications']       = 'My Notifications';
		$form['confirmations']       = 'My Confirmation';
		$form['gfpdf_form_settings'] = 'My PDF settings';

		/* Update the form */
		GFFormsModel::update_form_meta( $this->form_id, $form );
		GFFormsModel::update_form_meta( $this->form_id, $form['notifications'], 'notifications' );
		GFFormsModel::update_form_meta( $this->form_id, $form['confirmations'], 'confirmations' );

		/* check the update was successful */
		$form = GFFormsModel::get_form_meta( $this->form_id );

		$this->assertEquals( 'My Notifications', $form['notifications'] );
		$this->assertEquals( 'My Confirmation', $form['confirmations'] );
		$this->assertEquals( 'My PDF settings', $form['gfpdf_form_settings'] );
	}

	/**
	 * Test Gravity Form's rgpost() functionality
	 * Will return the value in the $_POST array, or empty string if not
	 *
	 * @since 4.0
	 */
	public function test_rgpost() {
		/* set up post data */
		$_POST = array(
			'my_object' => 'Data here',
			'array'     => array(
				'item1',
				"item2\'s",
				'item3',
			),
			'slashes'   => "How\'s it going?",
		);

		/* check string */
		$this->assertEquals( 'Data here', rgpost( 'my_object' ) );

		/* check array and stripslashes deep */
		$array = rgpost( 'array' );
		$this->assertTrue( is_array( $array ) );
		$this->assertEquals( "item2's", $array[1] );

		/* check strip slashes */
		$this->assertEquals( "How's it going?", rgpost( 'slashes' ) );

		/* check non-existant value */
		$this->assertEquals( '', rgpost( 'empty' ) );
	}

	/**
	 * Test Gravity Form's rgget() functionality
	 * Will return the value in the $_GET array, or empty string if not
	 *
	 * @since 4.0
	 */
	public function test_rgget() {
		/* set up post data */
		$_GET = array(
			'my_object' => 'Data here',
			'array'     => array(
				'item1',
				"item2's",
				'item3',
			),
			'slashes'   => "How's it going?",
		);

		/* check string */
		$this->assertEquals( 'Data here', rgget( 'my_object' ) );

		/* check array */
		$array = rgget( 'array' );
		$this->assertTrue( is_array( $array ) );
		$this->assertEquals( "item2's", $array[1] );

		/* check strip slashes */
		$this->assertEquals( "How's it going?", rgget( 'slashes' ) );

		/* check non-existant value */
		$this->assertEquals( '', rgget( 'empty' ) );
	}

	/**
	 * Test Gravity Form's rgempty() functionality which focuses on whether an array key exists
	 * If not array is passed, it will use the $_POST data
	 * If an array is passed as the first parameter it will check if the array is empty
	 *
	 * @since 4.0
	 */
	public function test_rgempty() {
		$array = array(
			'item1' => 'Test',
		);

		/* test main array functionality */
		$this->assertFalse( rgempty( $array ) );
		$this->assertTrue( rgempty( array() ) );

		/* test if array item is empty */
		$this->assertTrue( rgempty( 'item2', $array ) );
		$this->assertFalse( rgempty( 'item1', $array ) );

		/* test if post item is empty */
		$_POST = array(
			'my_object' => 'Data here',
		);

		$this->assertFalse( rgempty( 'my_object' ) );
		$this->assertTrue( rgempty( 'item1' ) );

	}

	/**
	 * Test Gravity Form's rgblank() functionality
	 * Checks if the string is empty and doesn't equal 0 - which equates to true when calling empty()
	 *
	 * @since 4.0
	 */
	public function test_rgblank() {
		$this->assertTrue( rgblank( '' ) );
		$this->assertFalse( rgblank( 0 ) );
		$this->assertFalse( rgblank( 'My String' ) );
	}

	/**
	 * Test Gravity Form's rgar() functionality
	 * Will return the value in the passed $array, or empty string if not
	 *
	 * @since 4.0
	 */
	public function test_rgar() {
		$array = array(
			'item1' => 'Test',
			'item2' => 'Test 2',
			'item3' => 'Test 3',
		);

		$this->assertEquals( 'Test', rgar( $array, 'item1' ) );
		$this->assertEquals( 'Test 2', rgar( $array, 'item2' ) );
		$this->assertEquals( 'Test 3', rgar( $array, 'item3' ) );
		$this->assertEquals( '', rgar( $array, 'item4' ) );
	}

	/**
	 * Test Gravity Form user privlages
	 * i.e $gfpdf->form->has_capability("gravityforms_edit_settings")
	 *
	 * @since 4.0
	 */
	public function test_gf_privs() {
		global $gfpdf;

		/* create user using WP Unit Factory functions */
		$user_id = $this->factory->user->create();
		$this->assertInternalType( 'integer', $user_id );

		/*
         * Set up our users and test the privilages
         */
		wp_set_current_user( $user_id );
		$this->assertFalse( $gfpdf->form->has_capability( 'gravityforms_edit_settings' ) );

		/* Create second user we'll use to test out the privilage */
		$user_id = $this->factory->user->create();
		$this->assertInternalType( 'integer', $user_id );

		/*
         * Add the user capability
         */
		$user = new WP_User( $user_id );
		$user->add_cap( 'gravityforms_edit_settings' );

		wp_set_current_user( $user_id );

		$this->assertTrue( $gfpdf->form->has_capability( 'gravityforms_edit_settings' ) );

		/* Create third user we'll use to test out the privilage */
		$user_id = $this->factory->user->create();
		$this->assertInternalType( 'integer', $user_id );

		/*
         * Add the user capability
         */
		$user = new WP_User( $user_id );
		$user->add_cap( 'gform_full_access' );

		wp_set_current_user( $user_id );

		$this->assertTrue( $gfpdf->form->has_capability( 'gravityforms_edit_settings' ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Check the core classes exist
	 *
	 * @since 3.6
	 */
	public function test_core_classes() {
		$this->assertTrue( true, class_exists( 'GFCommon' ) );
		$this->assertTrue( true, class_exists( 'GFFormsModel' ) );
		$this->assertTrue( true, class_exists( 'GFEntryDetail' ) );
		$this->assertTrue( true, class_exists( 'GFFormDisplay' ) );
	}

	/**
	 * Check that RGFormsModel::get_form_meta() method works as expected
	 *
	 * @since 3.6
	 */
	public function test_get_forms() {
		$form = RGFormsModel::get_form_meta( $GLOBALS['GFPDF_Test']->form['gravityform-1']['id'] );

		/*
         * Check the basics
         * Title is there, field number is correct
         */
		$this->assertEquals( 'Simple Form Testing', $form['title'] );
		$this->assertEquals( true, is_array( $form['fields'] ) );
		$this->assertEquals( 7, sizeof( $form['fields'] ) );
		$this->assertEquals( 1, $form['is_active'] );

		/*
         * Run through each field type and ensure the correct data is present
         */
		foreach ( $form['fields'] as $field ) {
			switch ( $field['type'] ) {
				case 'name':
					$this->assertEquals( $field['inputs'][0]['id'], $field['id'] . '.3' );
					$this->assertEquals( $field['inputs'][1]['id'], $field['id'] . '.6' );
					break;

				case 'address':
					$this->assertEquals( $field['inputs'][0]['id'], $field['id'] . '.1' );
					$this->assertEquals( $field['inputs'][1]['id'], $field['id'] . '.2' );
					$this->assertEquals( $field['inputs'][2]['id'], $field['id'] . '.3' );
					$this->assertEquals( $field['inputs'][3]['id'], $field['id'] . '.4' );
					$this->assertEquals( $field['inputs'][4]['id'], $field['id'] . '.5' );
					$this->assertEquals( $field['inputs'][5]['id'], $field['id'] . '.6' );
					break;

				case 'email':
					$this->assertEquals( 3, $field['id'] );
					break;

				case 'phone':
					$this->assertEquals( 4, $field['id'] );
					$this->assertEquals( 'standard', $field['phoneFormat'] );
					break;

				case 'select':
				case 'multiselect':
					$this->assertEquals( 3, sizeof( $field['choices'] ) );
					break;

				case 'textarea':
					$this->assertEquals( 7, $field['id'] );
					break;
			}
		}

		/*
         * Run through the notifications
         */
		$this->assertEquals( 2, sizeof( $form['notifications'] ) );

		$form['notifications'] = array_values( $form['notifications'] );

		$this->assertEquals( 'Admin Notification', $form['notifications'][0]['name'] );
		$this->assertEquals( 'User Notification', $form['notifications'][1]['name'] );
	}

	/**
	 * Test that RGFormsModel::get_lead() functionality works correctly
	 *
	 * @since 3.6
	 */
	public function test_get_entry() {
		$entry = RGFormsModel::get_lead( $GLOBALS['GFPDF_Test']->entries['gravityform-1'][0]['id'] );

		$valid_entries = array(
			'id',
			'form_id',
			'date_created',
			'is_starred',
			'is_read',
			'ip',
			'source_url',
			'post_id',
			'currency',
			'payment_status',
			'payment_date',
			'transaction_id',
			'payment_amount',
			'payment_method',
			'is_fulfilled',
			'created_by',
			'transaction_type',
			'user_agent',
			'status',
		);

		foreach ( $valid_entries as $v ) {
			$this->assertEquals( array_key_exists( $v, $entry ), true );
		}

		$this->assertEquals( 'My', $entry['1.3'] );
		$this->assertEquals( 'Name', $entry['1.6'] );
		$this->assertEquals( 'First Choice', $entry[5] );

		$entry = RGFormsModel::get_lead( $GLOBALS['GFPDF_Test']->entries['gravityform-1'][1]['id'] );

		$this->assertEquals( 'First', $entry['1.3'] );
		$this->assertEquals( 'Last', $entry['1.6'] );
		$this->assertEquals( '12 Alister St', $entry['2.1'] );
		$this->assertEquals( 'Ali', $entry['2.3'] );
		$this->assertEquals( 'State', $entry['2.4'] );
		$this->assertEquals( '2678', $entry['2.5'] );
		$this->assertEquals( 'Barbados', $entry['2.6'] );
		$this->assertEquals( 'my@test.com', $entry['3'] );
		$this->assertEquals( '(345)445-4566', $entry['4'] );
		$this->assertEquals( 'Second Choice', $entry['5'] );
		$this->assertEquals( 'First Choice,Second Choice,Third Choice', $entry['6'] );

		$entry = RGFormsModel::get_lead( $GLOBALS['GFPDF_Test']->entries['gravityform-1'][2]['id'] );

		$this->assertEquals( 'Jake', $entry['1.3'] );
		$this->assertEquals( 'Jackson', $entry['1.6'] );
		$this->assertEquals( '123 Fake St', $entry['2.1'] );
		$this->assertEquals( 'Line 2', $entry['2.2'] );
		$this->assertEquals( 'City', $entry['2.3'] );
		$this->assertEquals( 'State', $entry['2.4'] );
		$this->assertEquals( '2441', $entry['2.5'] );
		$this->assertEquals( 'Albania', $entry['2.6'] );
		$this->assertEquals( 'test@test.com', $entry['3'] );
		$this->assertEquals( '(123)123-1234', $entry['4'] );
		$this->assertEquals( 'Third Choice', $entry['5'] );
		$this->assertEquals( 'Second Choice,Third Choice', $entry['6'] );
		$this->assertEquals( 'This is paragraph test!', $entry['7'] );
	}

	/**
	 * Test GF replace variables function (merge tags)
	 * i.e GFCommon::replace_variables
	 *
	 * @since        3.6
	 *
	 * @dataProvider provider_mergetag_test
	 */
	public function test_replace_variables( $mergetag, $value ) {
		$this->assertEquals( $value, PDF_Common::do_mergetags( $mergetag, $GLOBALS['GFPDF_Test']->form['gravityform-1']['id'], $GLOBALS['GFPDF_Test']->entries['gravityform-1'][2]['id'] ) );
	}

	/**
	 * Data provider for testing merge tags replace correctly
	 *
	 * @since 3.6
	 */
	public function provider_mergetag_test() {
		return array(
			array( '{:1.3}', 'Jake' ),
			array( '{:1.6}', 'Jackson' ),
			array( '{:5}', 'Third Choice' ),
			array( '{:7}', 'This is paragraph test!' ),
			array( '{date_dmy}', date( 'd/m/Y' ) ),
			array( '{date_mdy}', date( 'm/d/Y' ) ),
			array( '{form_title}', 'Simple Form Testing' ),
		);
	}

	/**
	 * Test that the correct IP is returned by the function
	 *
	 * @param String $ip  The test IP address
	 * @param String $var The $_SERVER array key
	 *
	 * @dataProvider provider_ip_testing
	 *
	 * @since        3.6
	 */
	public function run_ip_test( $ip, $var ) {
		$_SERVER[ $var ] = $ip;
		$this->assertEquals( $ip, GFFormsModel::get_ip() );
		unset( $_SERVER[ $var ] );
	}

	/**
	 * The data provider for the run_ip_test() function
	 *
	 * @since 3.6
	 */
	public function provider_ip_testing() {
		return array(
			array( '5.120.2.1', 'HTTP_CLIENT_IP' ),
			array( '6.10.3.9', 'HTTP_X_FORWARDED_FOR' ),
			array( '7.60.126.3', 'REMOTE_ADDR' ),
			array( '240.24.12.44,5.120.2.1', 'HTTP_CLIENT_IP' ),
			array( '10.17.54.234,6.10.3.9', 'HTTP_X_FORWARDED_FOR' ),
			array( '7.60.126.3,65.4.69.129', 'REMOTE_ADDR' ),
		);
	}

	/**
	 * Test that GFCommon::$version will produce
	 * the expected result.
	 *
	 * @since 3.6
	 */
	public function test_gf_version() {
		$version = GFCommon::$version;

		/* which the version number is a string before we try to match it */
		$this->assertEquals( true, is_string( $version ) );

		/*
         * Do a final test to match the version number according to a set standard
         * This will validate up to a four digit version x.x.x.x
         */
		$this->assertRegExp( '/^(?:(\d+)\.)?(?:(\d+)\.)?(?:(\d+)\.)?(\*|\d+)$/', $version );
	}
}
