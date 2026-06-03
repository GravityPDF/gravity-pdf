<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use GFForms;
use GFFormsModel;
use PDF_Common;
use RGFormsModel;
use GFPDF\Tests\Integration\TestCase;
use WP_User;

/**
 * Test Common Gravity Forms Functions
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the Gravity Forms functionality we rely on in Gravity PDF
 *
 * @since 4.0
 * @group gravity-forms
 */
class Test_Gravity_Forms extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures(
			[ 'form-settings', 'gravityform-1' ],
			[ 'gravityform-1' ]
		);
	}
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
	public function set_up(): void {
		parent::set_up();

		$this->setup_form();
	}

	/**
	 * Pull in our form data
	 *
	 * @since 4.0
	 */
	private function setup_form() {
		$this->form_id = $this->form( 'form-settings' )['id'];
	}

	/**
	 * Test Gravity Form's GFFormsModel::get_form_meta( $form_id ) functionality
	 *
	 * @since 4.0
	 */
	public function test_get_form_meta() {
		/* Test non-existant form */
		$this->assertNull( GFFormsModel::get_form_meta( $this->form_id + 5000 ) );

		/* Test for existing form */
		$form = GFFormsModel::get_form_meta( $this->form_id );

		/* Test that the data was returned correctly */
		$this->assertSame( $this->form( 'form-settings' )['title'], $form['title'] );
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
		$form   = GFFormsModel::get_form_meta( $this->form_id );
		$backup = $form;

		/* make changes to the values */
		$form['notifications']       = 'My Notifications';
		$form['gfpdf_form_settings'] = 'My PDF settings';

		/* Update the form */
		GFFormsModel::update_form_meta( $this->form_id, $form );
		GFFormsModel::update_form_meta( $this->form_id, $form['notifications'], 'notifications' );

		/* check the update was successful */
		$form = GFFormsModel::get_form_meta( $this->form_id );

		$this->assertSame( 'My Notifications', $form['notifications'] );
		$this->assertSame( 'My PDF settings', $form['gfpdf_form_settings'] );

		/* Reset */
		GFFormsModel::update_form_meta( $this->form_id, $backup );
	}

	/**
	 * Test Gravity Form's rgpost() functionality
	 * Will return the value in the $_POST array, or empty string if not
	 *
	 * @since 4.0
	 */
	public function test_rgpost() {
		/* set up post data */
		$_POST = [
			'my_object' => 'Data here',
			'array'     => [
				'item1',
				"item2\'s",
				'item3',
			],
			'slashes'   => "How\'s it going?",
		];

		/* check string */
		$this->assertSame( 'Data here', rgpost( 'my_object' ) );

		/* check array and stripslashes deep */
		$array = rgpost( 'array' );
		$this->assertIsArray( $array );
		$this->assertSame( "item2's", $array[1] );

		/* check strip slashes */
		$this->assertSame( "How's it going?", rgpost( 'slashes' ) );

		/* check non-existant value */
		$this->assertSame( '', rgpost( 'empty' ) );
	}

	/**
	 * Test Gravity Form's rgget() functionality
	 * Will return the value in the $_GET array, or empty string if not
	 *
	 * @since 4.0
	 */
	public function test_rgget() {
		/* set up post data */
		$_GET = [
			'my_object' => 'Data here',
			'array'     => [
				'item1',
				"item2's",
				'item3',
			],
			'slashes'   => "How's it going?",
		];

		/* check string */
		$this->assertSame( 'Data here', rgget( 'my_object' ) );

		/* check array */
		$array = rgget( 'array' );
		$this->assertIsArray( $array );
		$this->assertSame( "item2's", $array[1] );

		/* check strip slashes */
		$this->assertSame( "How's it going?", rgget( 'slashes' ) );

		/* check non-existant value */
		$this->assertSame( '', rgget( 'empty' ) );
	}

	/**
	 * Test Gravity Form's rgempty() functionality which focuses on whether an array key exists
	 * If not array is passed, it will use the $_POST data
	 * If an array is passed as the first parameter it will check if the array is empty
	 *
	 * @since 4.0
	 */
	public function test_rgempty() {
		$array = [
			'item1' => 'Test',
		];

		/* test main array functionality */
		$this->assertFalse( rgempty( $array ) );
		$this->assertTrue( rgempty( [] ) );

		/* test if array item is empty */
		$this->assertTrue( rgempty( 'item2', $array ) );
		$this->assertFalse( rgempty( 'item1', $array ) );

		/* test if post item is empty */
		$_POST = [
			'my_object' => 'Data here',
		];

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
		$array = [
			'item1' => 'Test',
			'item2' => 'Test 2',
			'item3' => 'Test 3',
		];

		$this->assertSame( 'Test', rgar( $array, 'item1' ) );
		$this->assertSame( 'Test 2', rgar( $array, 'item2' ) );
		$this->assertSame( 'Test 3', rgar( $array, 'item3' ) );
		$this->assertSame( '', rgar( $array, 'item4' ) );
	}

	/**
	 * Test Gravity Form user privlages
	 * i.e $gfpdf->gform->has_capability("gravityforms_edit_settings")
	 *
	 * @since 4.0
	 */
	public function test_gf_privs() {
		global $gfpdf;

		/* create user using WP Unit Factory functions */
		$user_id = $this->factory->user->create();
		$this->assertIsInt( $user_id );

		/*
		 * Set up our users and test the privilages
		 */
		wp_set_current_user( $user_id );
		$this->assertFalse( $gfpdf->gform->has_capability( 'gravityforms_edit_settings' ) );

		/* Create second user we'll use to test out the privilage */
		$user_id = $this->factory->user->create();
		$this->assertIsInt( $user_id );

		/*
		 * Add the user capability
		 */
		$user = new WP_User( $user_id );
		$user->add_cap( 'gravityforms_edit_settings' );

		wp_set_current_user( $user_id );

		$this->assertTrue( $gfpdf->gform->has_capability( 'gravityforms_edit_settings' ) );

		/* Create third user we'll use to test out the privilage */
		$user_id = $this->factory->user->create();
		$this->assertIsInt( $user_id );

		/*
		 * Add the user capability
		 */
		$user = new WP_User( $user_id );
		$user->add_cap( 'gform_full_access' );

		wp_set_current_user( $user_id );

		$this->assertTrue( $gfpdf->gform->has_capability( 'gravityforms_edit_settings' ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Check that RGFormsModel::get_form_meta() method works as expected
	 *
	 * @since 3.6
	 */
	public function test_get_forms() {
		$form = RGFormsModel::get_form_meta( $this->form( 'gravityform-1' )['id'] );

		/*
		 * Check the basics
		 * Title is there, field number is correct
		 */
		$this->assertSame( $this->form( 'gravityform-1' )['title'], $form['title'] );
		$this->assertTrue( is_array( $form['fields'] ) );
		$this->assertCount( 7, $form['fields'] );
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
					$this->assertSame( 3, $field['id'] );
					break;

				case 'phone':
					$this->assertSame( 4, $field['id'] );
					$this->assertSame( 'standard', $field['phoneFormat'] );
					break;

				case 'select':
				case 'multiselect':
					$this->assertCount( 3, $field['choices'] );
					break;

				case 'textarea':
					$this->assertSame( 7, $field['id'] );
					break;
			}
		}

		/*
		 * Run through the notifications
		 */
		$this->assertCount( 2, $form['notifications'] );

		$form['notifications'] = array_values( $form['notifications'] );

		$this->assertSame( 'Admin Notification', $form['notifications'][0]['name'] );
		$this->assertSame( 'User Notification', $form['notifications'][1]['name'] );
	}

	/**
	 * Test that RGFormsModel::get_lead() functionality works correctly
	 *
	 * @since 3.6
	 */
	public function test_get_entry() {
		$entry = RGFormsModel::get_lead( $this->entry( 'gravityform-1', 0 )['id'] );

		$valid_entries = [
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
		];

		foreach ( $valid_entries as $v ) {
			$this->assertTrue( array_key_exists( $v, $entry ) );
		}

		$this->assertSame( 'My', $entry['1.3'] );
		$this->assertSame( 'Name', $entry['1.6'] );
		$this->assertSame( 'First Choice', $entry[5] );

		$entry = RGFormsModel::get_lead( $this->entry( 'gravityform-1', 1 )['id'] );

		$this->assertSame( 'First', $entry['1.3'] );
		$this->assertSame( 'Last', $entry['1.6'] );
		$this->assertSame( '12 Alister St', $entry['2.1'] );
		$this->assertSame( 'Ali', $entry['2.3'] );
		$this->assertSame( 'State', $entry['2.4'] );
		$this->assertSame( '2678', $entry['2.5'] );
		$this->assertSame( 'Barbados', $entry['2.6'] );
		$this->assertSame( 'my@test.com', $entry['3'] );
		$this->assertSame( '(345)445-4566', $entry['4'] );
		$this->assertSame( 'Second Choice', $entry['5'] );
		$this->assertSame( 'First Choice,Second Choice,Third Choice', $entry['6'] );

		$entry = RGFormsModel::get_lead( $this->entry( 'gravityform-1', 2 )['id'] );

		$this->assertSame( 'Jake', $entry['1.3'] );
		$this->assertSame( 'Jackson', $entry['1.6'] );
		$this->assertSame( '123 Fake St', $entry['2.1'] );
		$this->assertSame( 'Line 2', $entry['2.2'] );
		$this->assertSame( 'City', $entry['2.3'] );
		$this->assertSame( 'State', $entry['2.4'] );
		$this->assertSame( '2441', $entry['2.5'] );
		$this->assertSame( 'Albania', $entry['2.6'] );
		$this->assertSame( 'test@test.com', $entry['3'] );
		$this->assertSame( '(123)123-1234', $entry['4'] );
		$this->assertSame( 'Third Choice', $entry['5'] );
		$this->assertSame( 'Second Choice,Third Choice', $entry['6'] );
		$this->assertSame( 'This is paragraph test!', $entry['7'] );
	}

	/**
	 * Test GF replace variables function (merge tags)
	 * i.e GFCommon::replace_variables
	 *
	 * @param string $mergetag
	 * @param string $value
	 *
	 * @since        3.6
	 *
	 * @dataProvider provider_mergetag_test
	 */
	public function test_replace_variables( $mergetag, $value ) {
		// Per-class form titles are dedup-suffixed by GFAPI (e.g. "Simple Form Testing (1)").
		// Substitute the provider's expected title with the actual loaded form title.
		if ( $mergetag === '{form_title}' ) {
			$value = $this->form( 'gravityform-1' )['title'];
		}
		$this->assertSame( $value, PDF_Common::do_mergetags( $mergetag, $this->form( 'gravityform-1' )['id'], $this->entry( 'gravityform-1', 2 )['id'] ) );
	}

	/**
	 * Data provider for testing merge tags replace correctly
	 *
	 * @since 3.6
	 */
	public function provider_mergetag_test(): array {
		return [
			[ '{:1.3}', 'Jake' ],
			[ '{:1.6}', 'Jackson' ],
			[ '{:5}', 'Third Choice' ],
			[ '{:7}', 'This is paragraph test!' ],
			[ '{date_dmy}', gmdate( 'd/m/Y' ) ],
			[ '{date_mdy}', gmdate( 'm/d/Y' ) ],
			[ '{form_title}', 'Simple Form Testing' ],
		];
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
		$this->assertSame( $ip, GFFormsModel::get_ip() );
		unset( $_SERVER[ $var ] );
	}

	/**
	 * The data provider for the run_ip_test() function
	 *
	 * @since 3.6
	 */
	public function provider_ip_testing(): array {
		return [
			[ '5.120.2.1', 'REMOTE_ADDR' ],
			[ '6.10.3.9', 'REMOTE_ADDR' ],
			[ '7.60.126.3', 'REMOTE_ADDR' ],
			[ '240.24.12.44', 'REMOTE_ADDR' ],
			[ '10.17.54.234', 'REMOTE_ADDR' ],
			[ '127.0.0.1', 'REMOTE_ADDR' ],
		];
	}

	/**
	 * Test that \GFForms::$version will produce
	 * the expected result.
	 *
	 * @since 3.6
	 */
	public function test_gf_version() {
		$version = GFForms::$version;

		/* which the version number is a string before we try to match it */
		$this->assertTrue( is_string( $version ) );

		/*
		 * Do a final test to match the version number according to a set standard
		 * This will validate up to a four digit version x.x.x.x
		 */
		$this->assertMatchesRegularExpression( '/^(?:(\d+)\.)?(?:(\d+)\.)?(?:(\d+)\.)?(\*|\d+)/', $version );
	}
}
