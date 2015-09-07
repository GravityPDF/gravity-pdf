<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Options_Fields;

use GFAPI;
use GFForms;

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
 * @group options
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
		global $gfpdf;

		/* run parent method */
		parent::setUp();

		/* Remove temporary tables which causes problems with GF */
		remove_all_filters( 'query', 10 );
		GFForms::setup_database();

		/* setup our object */
		$this->options = new Helper_Options_Fields( $gfpdf->log, $gfpdf->form, $gfpdf->data, $gfpdf->misc, $gfpdf->notices );

		/* load settings in database  */
		update_option( 'gfpdf_settings', json_decode( file_get_contents( dirname( __FILE__ ) . '/json/options-settings.json' ), true ) );

		/* Load a form / form PDF settings into database */
		$this->form_id = $GLOBALS['GFPDF_Test']->form['form-settings']['id'];

		/* Set up our global settings */
		$this->options->set_plugin_settings();
	}

	/**
	 * Check if settings getter function works correctly
	 *
	 * @since 4.0
	 */
	public function test_get_settings() {

		/**
		 * Check our default action works correctly
		 */
		$settings = $this->options->get_settings();

		$this->assertEquals( 'custom', $settings['default_pdf_size'] );
		$this->assertEquals( 'Awesomeness', $settings['default_template'] );
		$this->assertEquals( 'dejavusans', $settings['default_font_type'] );
		$this->assertEquals( 'No', $settings['default_rtl'] );
		$this->assertEquals( 'View', $settings['default_action'] );
		$this->assertEquals( 'No', $settings['limit_to_admin'] );
		$this->assertEquals( '20', $settings['logged_out_timeout'] );

		$this->assertTrue( is_array( $settings['default_custom_pdf_size'] ) );
		$this->assertTrue( is_array( $settings['admin_capabilities'] ) );

		$this->assertEquals( 30, $settings['default_custom_pdf_size'][0] );
		$this->assertEquals( 50, $settings['default_custom_pdf_size'][1] );
		$this->assertEquals( 'millimeters', $settings['default_custom_pdf_size'][2] );

		$this->assertEquals( 'gravityforms_create_form', $settings['admin_capabilities'][0] );

		/**
		 * Check our transient user data is loaded
		 * Used in settings_sanitize() when there are errors the user has to fix
		 */
		set_transient( 'gfpdf_settings_user_data', 'testing', 30 );

		$this->assertEquals( 'testing', $this->options->get_settings() );
	}

	/**
	 * Check if Gravity Forms PDF settings getter function works correctly
	 *
	 * @since 4.0
	 */
	public function test_get_form_settings() {
		/* test false values */
		$this->assertEmpty( $this->options->get_form_settings() );

		$_GET['id'] = $this->form_id + 50; /* a form ID that won't exist */

		/* check empty array is returned */
		$this->assertEmpty( $this->options->get_form_settings() );

		/* Set up and return real values */
		$form = GFAPI::get_form( $this->form_id );

		reset( $form['gfpdf_form_settings'] );
		$pid = key( $form['gfpdf_form_settings'] );

		/* set up our $_GET variables */
		$_GET['id'] = $this->form_id;
		$_GET['pid'] = $pid;

		/* get legitimate results */
		$results = $this->options->get_form_settings();

		/* check they contain values */
		$this->assertNotEmpty( $results );

		/* check for specific values */
		$this->assertEquals( 'My First PDF Template', $results['name'] );
		$this->assertEquals( 'Gravity Forms Style', $results['template'] );
		$this->assertTrue( in_array( 'Admin Notification', $results['notification'] ) );
	}

	/**
	 * Check that any settings passed in through get_registered_settings() gets registered correctly
	 *
	 * @since 4.0
	 */
	public function test_register_settings() {
		global $wp_settings_fields, $new_whitelist_options;

		$fields = array(
			'general' => array(
				'my_test_item' => array(
					'id'   => 'my_test_item',
					'name' => 'Test Item',
					'type' => 'text',
				),
			),
		);

		/* call our function */
		$this->options->register_settings( $fields );

		/* Test setting was added correctly */
		$this->assertTrue( isset($wp_settings_fields['gfpdf_settings_general']['gfpdf_settings_general']['gfpdf_settings[my_test_item]']) );
		$this->assertEquals( 'Test Item', $wp_settings_fields['gfpdf_settings_general']['gfpdf_settings_general']['gfpdf_settings[my_test_item]']['title'] );

		/* Test our registered settings were added */
		$this->assertTrue( isset($new_whitelist_options['gfpdf_settings']) );

		/* clean up filter */
		remove_all_filters( 'gfpdf_registered_fields' );
	}

	/**
	 * Check that we can successfully update a registered field item
	 * @since 4.0
	 */
	public function test_update_registered_field() {
		global $wp_settings_fields;

		$this->options->register_settings( $this->options->get_registered_fields() );

		$group     = 'gfpdf_settings_form_settings';
		$setting   = 'gfpdf_settings[notification]';
		$option_id = 'options';
		
		/* Run false test */
		$this->assertSame( 0, sizeof( $wp_settings_fields[ $group ][ $group ][ $setting ]['args'][ $option_id ] ) );

		/* Run valid test */
		$this->options->update_registered_field( 'form_settings', 'notification', 'options', 'working' );

		$this->assertEquals( 'working', $wp_settings_fields[ $group ][ $group ][ $setting ]['args'][ $option_id ] );
	}

	/**
	 * Check the options list is returned correctly
	 *
	 * @since 4.0
	 */
	public function test_get_registered_settings() {
		$items = $this->options->get_registered_fields();

		/* Check the array */
		$this->assertTrue( isset($items['general']) );
		$this->assertTrue( isset($items['general_security']) );
		$this->assertTrue( isset($items['extensions']) );
		$this->assertTrue( isset($items['licenses']) );
		$this->assertTrue( isset($items['tools']) );
		$this->assertTrue( isset($items['form_settings']) );
		$this->assertTrue( isset($items['form_settings_appearance']) );
		$this->assertTrue( isset($items['form_settings_advanced']) );

		/* Check filters work correctly */
		add_filter( 'gfpdf_settings_general', function ( $array ) {
			return 'General Settings';
		});

		add_filter( 'gfpdf_settings_general_security', function ( $array ) {
			return 'General Security Settings';
		});

		add_filter( 'gfpdf_settings_extensions', function ( $array ) {
			return 'Extension Settings';
		});

		add_filter( 'gfpdf_settings_licenses', function ( $array ) {
			return 'License Settings';
		});

		add_filter( 'gfpdf_settings_tools', function ( $array ) {
			return 'Tools Settings';
		});

		add_filter( 'gfpdf_form_settings', function ( $array ) {
			return 'PDF Form Settings';
		});

		add_filter( 'gfpdf_form_settings_appearance', function ( $array ) {
			return 'PDF Form Settings Appearance';
		});

		add_filter( 'gfpdf_form_settings_advanced', function ( $array ) {
			return 'PDF Form Settings Advanced';
		});

		/* reset items */
		$items = $this->options->get_registered_fields();

		$this->assertEquals( 'General Settings', $items['general'] );
		$this->assertEquals( 'General Security Settings', $items['general_security'] );
		$this->assertEquals( 'Extension Settings', $items['extensions'] );
		$this->assertEquals( 'License Settings', $items['licenses'] );
		$this->assertEquals( 'Tools Settings', $items['tools'] );
		$this->assertEquals( 'PDF Form Settings', $items['form_settings'] );
		$this->assertEquals( 'PDF Form Settings Appearance', $items['form_settings_appearance'] );
		$this->assertEquals( 'PDF Form Settings Advanced', $items['form_settings_advanced'] );

		/* Cleanup */
		remove_all_filters( 'gfpdf_settings_general' );
		remove_all_filters( 'gfpdf_settings_general_security' );
		remove_all_filters( 'gfpdf_settings_extensions' );
		remove_all_filters( 'gfpdf_settings_licenses' );
		remove_all_filters( 'gfpdf_settings_tools' );
		remove_all_filters( 'gfpdf_form_settings' );
		remove_all_filters( 'gfpdf_form_settings_appearance' );
		remove_all_filters( 'gfpdf_form_settings_advanced' );
	}


	/**
	 * Test we can get a single global PDF option
	 *
	 * @since 4.0
	 */
	public function test_get_option() {
		/* test for real values */
		$this->assertEquals( 'custom', $this->options->get_option( 'default_pdf_size' ) );
		$this->assertEquals( 'Awesomeness', $this->options->get_option( 'default_template' ) );
		$this->assertEquals( 'No', $this->options->get_option( 'limit_to_admin' ) );
		$this->assertTrue( is_array( $this->options->get_option( 'admin_capabilities' ) ) );

		/* test for non-existant option */
		$this->assertFalse( $this->options->get_option( 'non-existant' ) );

		/* test default when non-existant option */
		$this->assertTrue( $this->options->get_option( 'non-existant', true ) );

		/* check filters */
		add_filter( 'gfpdf_get_option', function ( $value ) {
			return 'New Value';
		});

		$this->assertEquals( 'New Value', $this->options->get_option( 'default_pdf_size' ) );

		/* clean up */
		remove_all_filters( 'gfpdf_get_option' );

		add_filter( 'gfpdf_get_option_default_rtl', function ( $value ) {
			return 'RTL';
		});

		$this->assertEquals( 'RTL', $this->options->get_option( 'default_rtl' ) );

		/* cleanup */
		remove_all_filters( 'gfpdf_get_option_default_rtl' );
	}

	/**
	 * Test we can update a single global PDF option
	 *
	 * @since 4.0
	 */
	public function test_update_option() {
		/* test failures */
		$this->assertFalse( $this->options->update_option() );
		$this->assertFalse( $this->options->update_option( '' ) );

		/* test update functionality */
		$this->assertTrue( $this->options->update_option( 'default_pdf_size', 'new pdf size' ) );
		$this->assertEquals( 'new pdf size', $this->options->get_option( 'default_pdf_size' ) );

		/* Check filters */
		add_filter( 'gfpdf_update_option', function ( $value ) {
			return 'filtered option';
		});

		$this->assertTrue( $this->options->update_option( 'default_pdf_size', 'new pdf size' ) );
		$this->assertEquals( 'filtered option', $this->options->get_option( 'default_pdf_size' ) );

		remove_all_filters( 'gfpdf_update_option' );

		add_filter( 'gfpdf_update_option_limit_to_admin', function ( $value ) {
			return 'filtered admin option';
		});

		$this->assertTrue( $this->options->update_option( 'default_pdf_size', 'new pdf size' ) );
		$this->assertEquals( 'new pdf size', $this->options->get_option( 'default_pdf_size' ) );

		$this->assertTrue( $this->options->update_option( 'limit_to_admin', 'admin' ) );
		$this->assertEquals( 'filtered admin option', $this->options->get_option( 'limit_to_admin' ) );

		remove_all_filters( 'gfpdf_update_option_limit_to_admin' );
	}

	/**
	 * Test we can delete a single global PDF option
	 *
	 * @since 4.0
	 */
	public function test_delete_option() {
		/* test failure */
		$this->assertFalse( $this->options->delete_option() );
		$this->assertFalse( $this->options->delete_option( '' ) );

		/* test delete functionality */
		$this->assertEquals( 'custom', $this->options->get_option( 'default_pdf_size' ) );
		$this->assertTrue( $this->options->delete_option( 'default_pdf_size' ) );
		$this->assertFalse( $this->options->get_option( 'default_pdf_size' ) );
	}

	/**
	 * Test the returned capabilities list
	 *
	 * @since 4.0
	 */
	public function test_get_capabilities() {
		$capabilities = $this->options->get_capabilities();

		$this->assertTrue( isset($capabilities['Gravity Forms Capabilities']) );
		$this->assertTrue( isset($capabilities['Active WordPress Capabilities']) );

		$this->assertNotSame( 0, sizeof( $capabilities['Gravity Forms Capabilities'] ) );
		$this->assertNotSame( 0, sizeof( $capabilities['Active WordPress Capabilities'] ) );
	}

	/**
	 * Test the returned paper size list
	 *
	 * @since 4.0
	 */
	public function test_get_paper_size() {
		$paper_size = $this->options->get_paper_size();

		$this->assertTrue( isset($paper_size['Common Sizes']) );
		$this->assertTrue( isset($paper_size['"A" Sizes']) );
		$this->assertTrue( isset($paper_size['"B" Sizes']) );
		$this->assertTrue( isset($paper_size['"C" Sizes']) );
		$this->assertTrue( isset($paper_size['"RA" and "SRA" Sizes']) );
	}

	/**
	 * Test the get templates functionality
	 *
	 * @since 4.0
	 */
	public function test_get_templates() {
		
		$templates = $this->options->get_templates();

		$this->assertArrayHasKey( 'Core', $templates );
		$this->assertNotSame( 0, sizeof( $templates['Core'] ) );
	}

	/**
	 * Test our PDF template headers are all registered
	 *
	 * @since 4.0
	 */
	public function test_get_template_header_details() {
		$header = $this->options->get_template_header_details();

		$this->assertArrayHasKey( 'template', $header );
		$this->assertArrayHasKey( 'version', $header );
		$this->assertArrayHasKey( 'description', $header );
		$this->assertArrayHasKey( 'author', $header );
		$this->assertArrayHasKey( 'group', $header );
		$this->assertArrayHasKey( 'required_pdf_version', $header );
	}

	/**
	 * Check we can correctly read the template headers
	 *
	 * @since 4.0
	 */
	public function test_get_template_headers() {
		
		$path = PDF_PLUGIN_DIR . 'initialisation/templates/core-simple.php';
		$header = $this->options->get_template_headers( $path );

		$this->assertEquals( 'Simple Structure', $header['template'] );
		$this->assertEquals( '1.0', $header['version'] );
		$this->assertEquals( 'The default template for Gravity PDF 4.x+', $header['description'] );
		$this->assertEquals( 'Gravity PDF', $header['author'] );
		$this->assertEquals( 'Core', $header['group'] );
		$this->assertEquals( '4.0', $header['required_pdf_version'] );
	}

	/**
	 * Check we can get the core PDF templates
	 *
	 * @since 4.0
	 */
	public function test_get_plugin_pdf_templates() {
		$this->assertNotSame( 0, sizeof( $this->options->get_plugin_pdf_templates() ) );
	}


	/**
	 * Test the installed fonts getter functionality
	 *
	 * @since 4.0
	 */
	public function test_get_installed_fonts() {
		 
		$fonts = $this->options->get_installed_fonts();

		$this->assertArrayHasKey( 'Unicode', $fonts );
		$this->assertArrayHasKey( 'Indic', $fonts );
		$this->assertArrayHasKey( 'Arabic', $fonts );
		$this->assertArrayHasKey( 'Other', $fonts );

		$this->assertTrue( isset( $fonts['Unicode']['dejavusans'] ) );
	}

	/**
	 * Add a custom font to our array
	 *
	 * @since 4.0
	 */
	public function test_add_custom_fonts() {

		$fonts = array(
			array( 'font_name' => 'Helvetica' ),
			array( 'font_name' => 'Calibri Bold' ),
		);

		$this->options->update_option( 'custom_fonts', $fonts );

		$existing_fonts = array(
			'Unicode' => array(
				'dejavusans' => 'Dejavu Sans',
				'courier'    => 'Courier',
			)
		);

		$get_fonts = $this->options->add_custom_fonts( $existing_fonts );

		$this->assertTrue( isset( $get_fonts['Unicode'] ) );
		$this->assertTrue( isset( $get_fonts['User-Defined Fonts'] ) );

		$this->assertSame( 2, sizeof( $get_fonts['Unicode'] ) );
		$this->assertSame( 2, sizeof( $get_fonts['User-Defined Fonts'] ) );
	}

	/**
	 * Test the custom font getter
	 *
	 * @since 4.0
	 */
	public function test_get_custom_fonts() {
		
		$fonts = array(
			array( 'font_name' => 'Helvetica' ),
			array( 'font_name' => 'Calibri Bold' ),
		);

		$this->options->update_option( 'custom_fonts', $fonts );

		$get_fonts = $this->options->get_custom_fonts();

		$this->assertEquals( 'helvetica', $get_fonts[0]['shortname'] );
		$this->assertEquals( 'calibribold', $get_fonts[1]['shortname'] );
	}

	/**
	 * Test the font display name getter
	 *
	 * @since 4.0
	 */
	public function test_get_font_display_name() {
		$this->assertEquals( 'Dejavu Sans', $this->options->get_font_display_name( 'dejavusans' ) );
	}

	/**
	 * Test the privilages getter
	 *
	 * @since 4.0
	 */
	public function test_get_privilages() {
		$this->assertTrue( is_array( $this->options->get_privilages() ) );
	}

	/**
	 * Test our semi-cached PDF counter will increment correctly
	 *
	 * @since 4.0
	 */
	public function test_increment_pdf_count() {

		while( 100 >= $this->options->get_option( 'pdf_count' ) ) {
			$this->options->increment_pdf_count();
		}

		$this->assertGreaterThan( 100, $this->options->get_option( 'pdf_count' ) );
	}

	public function test_settings_sanitize() {
		
		/* Test failed referer / option name */
		$this->assertEquals( 'test', $this->options->settings_sanitize( 'test' ) );

		$_POST['_wp_http_referer'] = '?tab=general';
		$_POST['option_name']      = 'option_page';

		$input = array(
			'default_pdf_size' => 'A5',
			'default_font_size' => '15',
			'other_type' => 'wont validate',
		);

		/* Test our current settings */
		$initial_settings = $this->options->get_settings();

		$this->assertEquals( 'custom', $initial_settings['default_pdf_size'] );
		$this->assertEmpty( $initial_settings['default_font_size'] );
		$this->assertEmpty( $initial_settings['other_type'] );

		/* Run our settings santize function and check the results are accurate */
		$this->options->settings_sanitize( $input );

		$updated_settings = $this->options->get_settings();

		$this->assertEquals( 'A5', $updated_settings['default_pdf_size'] );
		$this->assertEquals( '15', $updated_settings['default_font_size'] );
		$this->assertEmpty( $updated_settings['other_type'] );


	}

	/**
	 * Test the trim sanitisation function
	 *
	 * @since 4.0
	 * @dataProvider dataprovider_sanitize_trim
	 */
	public function test_sanitize_trim_field( $expected, $input ) {
		$this->assertEquals( $expected, $this->options->sanitize_trim_field( $input ) );
	}

	/**
	 * Test data provider for our trim functionality (test_sanitize_trim_field)
	 * @return array The data to test
	 * @since  4.0
	 */
	public function dataprovider_sanitize_trim() {
		return array(
			array( 'My First PDF', '    My First PDF ' ),
			array( 'My First   PDF', 'My First   PDF   ' ),
			array( '123_Advanced_{My Funny\\\'s PDF Name:213}', '              123_Advanced_{My Funny\\\'s PDF Name:213}' ),
			array( '驚いた彼は道を走っていった', '   驚いた彼は道を走っていった  ' ),
			array( 'élève forêt', 'élève forêt                ' ),
			array( 'English', 'English' ),
			array( 'मानक हिन्दी', '            मानक हिन्दी ' ),
		);
	}

	/**
	 * Test the number sanitisation function
	 *
	 * @since 4.0
	 * @dataProvider dataprovider_sanitize_number
	 */
	public function test_sanitize_number_field( $expected, $input ) {
		$this->assertSame( $expected, $this->options->sanitize_number_field( $input ) );
	}

	/**
	 * Test data provider for our number functionality (test_sanitize_number_field)
	 * @return array The data to test
	 * @since  4.0
	 */
	public function dataprovider_sanitize_number() {
		return array(
			array( 122, '122.34343The' ),
			array( 0, 'The122.34343' ),
			array( 20, '20' ),
			array( 2000, '2000' ),
			array( 20, '20.50' ),
			array( 50, '50,20' ),
		);
	}

	/**
	 * Test our global sanitisation function
	 *
	 * @since 4.0
	 * @dataProvider provider_sanitize_all_fields
	 */
	public function test_sanitize_all_fields( $type, $value, $expected ) {
		$this->assertEquals( $expected, $this->options->sanitize_all_fields( $value, '', '', array( 'type' => $type ) ) );
	}

	/**
	 * Test our sanitize_all_fields functions correctly
	 * @return Array
	 * @since 4.0
	 */
	public function provider_sanitize_all_fields() {
		return array(
			array( 'rich_editor', '<strong>Test</strong> <script>console.log("test");</script>', '<strong>Test</strong> console.log("test");' ),
			array( 'textarea', '<em>Test</em> <script>console.log("test");</script>', '<em>Test</em> console.log("test");' ),
			array( 'text', '<b><em>Test</em></b>', 'Test' ),
			array( 'checkbox', array( '<b>Item 1</b>', '<em>Item 2</em>' ), array( 'Item 1', 'Item 2' ) ),
		);
	}

	/**
	 * Test our required sanitized field errors trigger
	 *
	 * @since 4.0
	 * @dataProvider provider_sanitize_required_field
	 */
	public function test_sanitize_required_field( $type, $value, $expected ) {
		global $wp_settings_errors;

		/* Reset the WP errors */
		$wp_settings_errors = array();

		/* Setup data needed for our test */
		$input = array( 'default_pdf_size' => 'custom' );

		$settings = array(
			'required' => true,
			'type'     => $type,
		);

		/* Execute test */
		$this->options->sanitize_required_field( $value, $type, $input, $settings );

		/* Check the results */
		$this->assertEquals( $expected, sizeof( $wp_settings_errors ) );
	}

	/**
	 * Test our sanitize_required_field functions correctly
	 * @return Array
	 * @since 4.0
	 */
	public function provider_sanitize_required_field() {
		return array(
			array( 'select', array(), true ),
			array( 'multicheck', array(), true ),
			array( 'paper_size', array(), true ),
			array( 'text', '', true ),

			array( 'select', array( 'item' ), false ),
			array( 'multicheck', array( 'item' ), false ),
			array( 'paper_size', array( '10', '20', 'cm' ), false ),
			array( 'text', 'Working', false ),
		);
	}

	/**
	 * Test we can correctly get the field details
	 * @since  4.0
	 * @dataProvider provider_get_form_value
	 * @todo checkbox, multicheck and conditionalLogic
	 */
	public function test_get_form_value( $input, $expected ) {
		$_GET['id']  = $this->form_id;
		$_GET['pid'] = '555ad84787d7e';
		$this->options->update_option( 'default_font_size', 13 );

		$this->assertEquals( $expected, $this->options->get_form_value( $input ) );
	}


	public function provider_get_form_value() {
		return array(

			/* Test Settings Radio */
			array( array(
				'id' => 'default_action',
				'type' => 'radio',
				'options' => array(
					'View' => 'View',
					'Download'  => 'Download',
				),
			), 'View' ),

			/* Test Form Settings Radio */
			array( array(
				'id' => 'rtl',
				'type' => 'radio',
				'options' => array(
					'Yes' => 'Yes',
					'No'  => 'No',
				),
			), 'No' ),

			/* Test Fallback Radio */
			array( array(
				'id' => 'no_field',
				'type' => 'radio',
				'options' => array(
					'Yes' => 'Yes',
					'No'  => 'No',
				),
				'std' => 'Yes',
			), 'Yes' ),


			/* Test Blank Radio */
			array( array(
				'id' => 'no_field',
				'type' => 'radio',
				'options' => array(
					'Yes' => 'Yes',
					'No'  => 'No',
				),
			), '' ),


			/* Test Settings Select */
			array( array(
				'id' => 'admin_capabilities',
				'type' => 'select',
				'options' => array(
					'Gravity Forms Capabilities' => array(
						'gform_view_settings'
					),

					'Active WordPress Capabilities' => array(
						'read'
					),
				),
			), array( 'gravityforms_create_form' ) ),

			/* Test Form Settings Select */
			array( array(
				'id' => 'template',
				'type' => 'select',
				'options' => array(

				),
			), 'Gravity Forms Style' ),

			/* Test Fallback Select */
			array( array(
				'id' => 'no_field',
				'type' => 'select',
				'options' => array(
					'Yes' => 'Yes',
					'No'  => 'No',
				),
				'std' => 'Yes',
			), 'Yes' ),


			/* Test Blank Select */
			array( array(
				'id' => 'no_field',
				'type' => 'select',
				'options' => array(
					'Yes' => 'Yes',
					'No'  => 'No',
				),
			), '' ),



			/* Test Settings Text */
			array( array(
				'id' => 'default_font_size',
				'type' => 'number',
			), '13' ),

			/* Test Form Settings Text */
			array( array(
				'id' => 'name',
				'type' => 'text',
			), 'My First PDF Template' ),

			/* Test Fallback Text */
			array( array(
				'id' => 'no_field',
				'type' => 'text',
				'std' => 'Working',
			), 'Working' ),


			/* Test Blank Text */
			array( array(
				'id' => 'no_field',
				'type' => 'text',
			), '' ),
		);
	}
}
