<?php

namespace GFPDF\Tests;

use GFPDF\Controller\Controller_Form_Settings;
use GFPDF\Model\Model_Form_Settings;
use GFPDF\View\View_Form_Settings;

use GFAPI;
use GFForms;
use GFCommon;

use WP_UnitTestCase;

use Exception;

/**
 * Test Gravity PDF Form Settings Functionality
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
 * Test the model / view / controller for the Form Settings Page
 * @since 4.0
 * @group form-settings
 */
class Test_Form_Settings extends WP_UnitTestCase
{

	/**
	 * Our Forms Settings Controller
	 * @var Object
	 * @since 4.0
	 */
	public $controller;

	/**
	 * Our Form Settings Model
	 * @var Object
	 * @since 4.0
	 */
	public $model;

	/**
	 * Our Form Settings View
	 * @var Object
	 * @since 4.0
	 */
	public $view;

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
		global $gfpdf;

		parent::setUp();

		/* Remove temporary tables which causes problems with GF */
		remove_all_filters( 'query', 10 );
		GFForms::setup_database();

		$this->setup_form();

		/* Setup our test classes */
		$this->model = new Model_Form_Settings( $gfpdf->form, $gfpdf->log, $gfpdf->data, $gfpdf->options, $gfpdf->misc, $gfpdf->notices );
		$this->view  = new View_Form_Settings( array() );

		$this->controller = new Controller_Form_Settings( $this->model, $this->view, $gfpdf->data, $gfpdf->options, $gfpdf->misc );
		$this->controller->init();
	}

	/**
	 * Setup our form data and our cached form settings
	 * @since 4.0
	 */
	private function setup_form() {
		global $gfpdf;

		$this->form_id = $GLOBALS['GFPDF_Test']->form['form-settings']['id'];
		$gfpdf->data->form_settings[ $this->form_id ] = $GLOBALS['GFPDF_Test']->form['form-settings']['gfpdf_form_settings'];

	}

	/**
	 * Test the appropriate actions are set up
	 * @since 4.0
	 */
	public function test_actions() {
		global $gfpdf;

		/* standard actions */
		$this->assertEquals( 5, has_action( 'admin_init', array( $this->controller, 'maybe_save_pdf_settings' ) ) );

		$this->assertEquals( 10, has_action( 'gform_form_settings_menu', array( $this->model, 'add_form_settings_menu' ) ) );
		$this->assertEquals( 10, has_action( 'gform_form_settings_page_' . $gfpdf->data->slug, array( $this->controller, 'display_page' ) ) );

		/* ajax endpoints */
		$this->assertEquals( 10, has_action( 'wp_ajax_gfpdf_list_delete', array( $this->model, 'delete_gf_pdf_setting' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_gfpdf_list_duplicate', array( $this->model, 'duplicate_gf_pdf_setting' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_gfpdf_change_state', array( $this->model, 'change_state_pdf_setting' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_gfpdf_get_template_fields', array( $this->model, 'render_template_fields' ) ) );

	}

	/**
	 * Test the appropriate filters are set up
	 * @since 4.0
	 */
	public function test_filters() {
		global $gfpdf;

		/* general filters */
		$this->assertEquals( 10, has_filter( 'gfpdf_form_settings', array( $gfpdf->misc, 'add_template_image' ) ) );
		$this->assertEquals( 10, has_filter( 'gfpdf_form_settings_custom_appearance', array( $this->model, 'register_custom_appearance_settings' ) ) );

		/* validation filters */
		$this->assertEquals( 10, has_filter( 'gfpdf_form_settings', array( $this->model, 'validation_error' ) ) );
		$this->assertEquals( 10, has_filter( 'gfpdf_form_settings_appearance', array( $this->model, 'validation_error' ) ) );

		/* sanitation functions */
		$this->assertEquals( 10, has_filter( 'gfpdf_form_settings_sanitize', array( $gfpdf->options, 'sanitize_all_fields' ) ) );
		$this->assertEquals( 15, has_filter( 'gfpdf_form_settings_sanitize_text',  array( $this->model, 'parse_filename_extension' ) ) );
		$this->assertEquals( 15, has_filter( 'gfpdf_form_settings_sanitize_text',  array( $gfpdf->options, 'sanitize_trim_field' ) ) );
		$this->assertEquals( 10, has_filter( 'gfpdf_form_settings_sanitize_hidden',  array( $this->model, 'decode_json' ) ) );

		/* Tiny MCE Settings for our AJAX loading TinyMCE editors */
		$this->assertEquals( 10, has_filter( 'tiny_mce_before_init', array( $this->controller, 'store_tinymce_settings' ) ) );
	}

	/**
	 * Test the Controller_Form_Settings maybe_save_pdf_settings() method
	 * @since 4.0
	 */
	public function test_maybe_save_pdf_settings() {

		/* Don't run the submission process */
		$this->assertSame( null, $this->controller->maybe_save_pdf_settings() );

		/* Test running the submission process */
		$_GET['id'] = 1;
		$_GET['pid'] = '223421afjiaf2';
		$_POST['gfpdf_save_pdf'] = true;

		try {
			$this->controller->maybe_save_pdf_settings();
		} catch ( Exception $e ) {
			/* Expected. Do Nothing */
		}

		$this->assertEquals( 'You do not have permission to access this page', $e->getMessage() );
	}

	/**
	 * Test the process_list_view() method correctly renders the view
	 * or throws an error when the user doesn't have the correct capabilities
	 * @since 4.0
	 */
	public function test_process_list_view() {

		require_once( GFCommon::get_base_path() . '/form_settings.php' );

		$form_id = $this->form_id;

		/* Test capability security */
		try {
			$this->model->process_list_view( $form_id );
		} catch ( Exception $e ) {
			/* Expected. Do Nothing */
		}

		$this->assertEquals( 'You do not have permission to access this page', $e->getMessage() );

		/* Authorise the current user and check correct output */
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );

		ob_start();
		$this->model->process_list_view( $form_id );
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, '<form id="gfpdf_list_form" method="post">' ) );

		wp_set_current_user(0);
	}

	/**
	 * Test the show_edit_view() method correctly renders the view
	 * or throws an error when the user doesn't have the correct capabilities
	 * @since 4.0
	 */
	public function test_show_edit_view() {

		require_once( GFCommon::get_base_path() . '/form_settings.php' );

		$form_id = $this->form_id;
		$pid     = '555ad84787d7e';

		/* Test capability security */
		try {
			$this->model->show_edit_view( $form_id, $pid );
		} catch ( Exception $e ) {
			/* Expected. Do Nothing */
		}

		$this->assertEquals( 'You do not have permission to access this page', $e->getMessage() );

		/* Authorise the current user and check correct output */
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );

		ob_start();
		$this->model->show_edit_view( $form_id, $pid );
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, '<form method="post" id="gfpdf_pdf_form">' ) );

		wp_set_current_user(0);
	}

	/**
	 * Check our validation method correctly functions
	 * @since 4.0
	 */
	public function test_validation_error() {
		global $gfpdf;

		/* remove validation filter on settings */
		remove_all_filters( 'gfpdf_form_settings' );

		/* get our fields */
		$all_fields = $gfpdf->options->get_registered_fields();
		$fields     = $all_fields['form_settings'];

		/* check there are no issues if not meant to be validated */
		$this->assertSame( $fields, $this->model->validation_error( $fields ) );

		/* check error is triggered when nonce fails */
		$_POST['gfpdf_save_pdf'] = true;
		$this->assertFalse( $this->model->validation_error( $fields ) );

		/* fake the nonce */
		$_POST['gfpdf_save_pdf'] = wp_create_nonce( 'gfpdf_save_pdf' );

		/* get validated fields */
		$validatedFields = $this->model->validation_error( $fields );

		/* check error is applied when no value is present in the $_POST['gfpdf_settings'] key */
		$this->assertNotFalse( strstr( $validatedFields['name']['class'], 'gfield_error' ) );
		$this->assertNotFalse( strstr( $validatedFields['filename']['class'], 'gfield_error' ) );

		/* now ensure no error is applied when the POST data does exist */
		$_POST['gfpdf_settings']['filename'] = 'My PDF';

		/* get validated fields */
		$validatedFields = $this->model->validation_error( $fields );

		/* check appropriate response */
		$this->assertNotFalse( strstr( $validatedFields['name']['class'], 'gfield_error' ) );
		$this->assertFalse( strstr( $validatedFields['filename']['class'], 'gfield_error' ) );
	}

	/**
	 * Check our process submission permissions, sanitization and save / update functionality works correctly
	 * @since 4.0
	 */
	public function test_process_submission() {
		global $gfpdf;

		$form_id = $this->form_id;
		$pid     = '555ad84787d7e';

		/* Test capability security */
		try {
			$this->model->process_submission( $form_id, $pid );
		} catch ( Exception $e ) {
			/* Expected. Do Nothing */
		}

		$this->assertEquals( 'You do not have permission to access this page', $e->getMessage() );

		/* Authorise the current user and check correct output */
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );

		/* Fail the nonce */
		$this->assertFalse( $this->model->process_submission( $form_id, $pid ) );

		/* Setup valid nonce */
		$_POST['gfpdf_save_pdf'] = wp_create_nonce( 'gfpdf_save_pdf' );

		/* Create semi-valid post data */
		$_POST['gfpdf_settings']['name'] = 'My New Name';

		/* Fail sanitization */
		$this->assertFalse( $this->model->process_submission( $form_id, $pid ) );

		$pdf = $gfpdf->options->get_pdf( $form_id, $pid );
		$this->assertEquals( 'sanitizing', $pdf['status'] );

		/* Pass sanitizing */
		$_POST['gfpdf_settings']['filename'] = 'My Filename';

		$this->assertTrue( $this->model->process_submission( $form_id, $pid ) );

		wp_set_current_user(0);
	}

	/**
	 * Test our sanitize filters are correctly firing for each section type
	 * @since 4.0
	 */
	public function test_settings_sanitize() {
		/* remove validation filter on settings */
		remove_all_filters( 'gfpdf_form_settings' );

		/* get faux input data */
		$input = json_decode( file_get_contents( dirname( __FILE__ ) . '/json/form-settings-sample-input.json' ), true );

		/**
		 * Set up global filters we can check
		 */
		add_filter( 'gfpdf_settings_form_settings_sanitize', function ( $input ) {
			return 'form_settings sanitized';
		});

		/* pass input data to our sanitization function */
		$this->assertEquals( 'form_settings sanitized', $this->model->settings_sanitize( $input ) );
		remove_all_filters( 'gfpdf_settings_form_settings_sanitize' );

		add_filter( 'gfpdf_settings_form_settings_appearance_sanitize', function ( $input ) {
			return 'form_settings_appearance sanitized';
		});

		/* pass input data to our sanitization function */
		$this->assertEquals( 'form_settings_appearance sanitized', $this->model->settings_sanitize( $input ) );
		remove_all_filters( 'gfpdf_settings_form_settings_appearance_sanitize' );

		add_filter( 'gfpdf_settings_form_settings_advanced_sanitize', function ( $input ) {
			return 'form_settings_advanced sanitized';
		});

		/* pass input data to our sanitization function */
		$this->assertEquals( 'form_settings_advanced sanitized', $this->model->settings_sanitize( $input ) );
		remove_all_filters( 'gfpdf_settings_form_settings_advanced_sanitize' );

		/**
		 * Get global input filter
		 */
		add_filter( 'gfpdf_form_settings_sanitize', function ( $input, $key ) {
			return 'global input value';
		}, 15, 2);

		$values = $this->model->settings_sanitize( $input );

		/* loop through array and check results */
		foreach ( $values as $v ) {
			$this->assertEquals( 'global input value', $v );
		}

		remove_all_filters( 'gfpdf_form_settings_sanitize' );

		/**
		 * Get specific input filters
		 */
		$types = array( 'text', 'select', 'conditional_logic', 'hidden', 'paper_size', 'radio', 'number' );

		/* set up filters to test */
		foreach ( $types as $type ) {
			add_filter( 'gfpdf_form_settings_sanitize_' . $type, function ( $value, $key ) use ( $type ) {
				return $type;
			}, 10, 2);
		}

		/* get new values */
		$values = $this->model->settings_sanitize( $input );

		/* loop through array and check results */
		foreach ( $input as $id => $field ) {
			if ( in_array( $field['type'], $types ) ) {
				$this->assertEquals( $field['type'], $values[$id] );
			}
		}

	}

	/**
	 * Check that .pdf is correctly removed from all filenames
	 *
	 * @since 4.0
	 *
	 * @dataProvider provider_strip_filename
	 */
	public function test_strip_filename_extension( $expected, $string ) {
		$this->assertSame( $expected, $this->model->parse_filename_extension( $string, 'filename' ) );
	}

	/**
	 * A data provider for our strip filename test
	 * @return Array Our test data
	 * @since 4.0
	 */
	public function provider_strip_filename() {
		return array(
			array( 'My First PDF', 'My First PDF.pdf' ),
			array( 'My First PDF', 'My First PDF.PDf' ),
			array( '123_Advanced_{My Funny\\\'s PDF Name:213}', '123_Advanced_{My Funny\\\'s PDF Name:213}.pdf' ),
			array( '驚いた彼は道を走っていった', '驚いた彼は道を走っていった.pdf' ),
			array( 'élève forêt', 'élève forêt.pdf' ),
			array( 'English.txt', 'English.txt.pdf' ),
			array( 'Document.pdf', 'Document.pdf.pdf' ),
			array( 'मानक हिन्दी', 'मानक हिन्दी.pdf' ),
		);
	}

	/**
	 * Check if we are registering our custom template appearance settings
	 * @since 4.0
	 */
	public function test_register_custom_appearance_settings() {
		global $gfpdf;

		$form_id = $_GET['id'] = $this->form_id;
		$pid     = $_GET['pid'] = '555ad84787d7e';

		/* Setup a valid template */
		$pdf = $gfpdf->options->get_pdf( $form_id, $pid );
		$pdf['template'] = 'zadani';
		$gfpdf->options->update_pdf( $form_id, $pid, $pdf );

		$results = $this->model->register_custom_appearance_settings( array() );

		$this->assertSame( 13, sizeof( $results ) );
	}

	/**
	 * Check if we are registering our custom template appearance settings correctly
	 * @since 4.0
	 */
	public function test_setup_custom_appearance_settings() {

		$class = $this->model->get_template_configuration( 'zadani' );
		$settings = $this->model->setup_custom_appearance_settings( $class, array() );

		$this->assertEquals( 13, sizeof( $settings ) );
		$this->assertArrayHasKey( 'border_colour', $settings );
	}

	/**
	 * Check if we are registering our core custom template appearance settings correctly
	 * @since 4.0
	 */
	public function test_setup_core_custom_appearance_settings() {

		$class = $this->model->get_template_configuration( 'zadani' );
		$settings = $this->model->setup_core_custom_appearance_settings( array(), $class, $class->configuration() );

		$this->assertEquals( 12, sizeof( $settings ) );

		$core_fields = array( 'show_form_title', 'show_page_names', 'show_html', 'show_section_content', 'show_hidden', 'show_empty', 'header', 'first_header', 'footer', 'first_footer', 'background_color', 'background_image' );

		foreach ( $core_fields as $key ) {
			$this->assertTrue( isset( $settings[ $key ] ) );
		}
	}

	/**
	 * Check if we are registering our core custom template appearance settings correctly
	 * @since 4.0
	 */
	public function test_get_template_configuration() {

		/* Test failure first */
		$this->assertFalse( $this->model->get_template_configuration( 'test' ) );

		/* Test default template */
		$this->assertEquals( 'GFPDF\Templates\Config\zadani', get_class( $this->model->get_template_configuration( 'zadani' ) ) );

		/* Test legacy templates */
		$this->assertEquals( 'GFPDF\Templates\Config\legacy', get_class( $this->model->get_template_configuration( 'default-template' ) ) );
	}

	/**
	 * Check we are decoding the json data successfully
	 * @since 4.0
	 */
	public function test_decode_json() {

		$json = '{"conditionalLogic":["Item 1","Item 2"]}';

		/* Test decode result */
		$data = $this->model->decode_json( $json, 'conditionalLogic' );

		$this->assertArrayHasKey( 'conditionalLogic', $data );
		$this->assertSame( 2, sizeof( $data['conditionalLogic'] ) );

		/* Test pass result */
		$this->assertEquals( $json, $this->model->decode_json( $json, 'other' ) );
	}

	/**
	 * Check we can successfully update the notification field data
	 * @since 4.0
	 */
	public function test_register_notifications() {
		global $wp_settings_fields, $gfpdf;

		$gfpdf->options->register_settings( $gfpdf->options->get_registered_fields() );

		$group     = 'gfpdf_settings_form_settings';
		$setting   = 'gfpdf_settings[notification]';
		$option_id = 'options';

		/* Run false test */
		$this->assertSame( 0, sizeof( $wp_settings_fields[ $group ][ $group ][ $setting ]['args'][ $option_id ] ) );

		/* Setup notification data */
		$notifications = array(
			array( 'id' => 'id1', 'name' => 'Notification  1' ),
			array( 'id' => 'id2', 'name' => 'Notification  2' ),
			array( 'id' => 'id3', 'name' => 'Notification  3' ),
		);

		/* Run valid test */
		$this->model->register_notifications( $notifications );

		$this->assertSame( 3, sizeof( $wp_settings_fields[ $group ][ $group ][ $setting ]['args'][ $option_id ] ) );

	}
}
