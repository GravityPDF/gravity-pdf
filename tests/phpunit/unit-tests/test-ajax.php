<?php

namespace GFPDF\Tests;

use GFAPI;
use WP_Ajax_UnitTestCase;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;


/**
 * Test Gravity AJAX Functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/**
 *
 * Tests all Ajax calls
 * For speed, non ajax calls of class-ajax.php are tested in test-ajax-others.php
 * Ajax tests are not marked risky when run in separate processes and wp_debug
 * disabled. But, this makes tests slow so non ajax calls are kept separate
 *
 * @group ajax
 */
class Test_PDF_Ajax extends WP_Ajax_UnitTestCase {

	/**
	 * The Gravity Form ID assigned to the imported form
	 *
	 * @var integer
	 *
	 * @since 4.0
	 */
	public $form_id;

	/**
	 * The Gravity Form PDF Settings ID loaded into the $form
	 *
	 * @var integer
	 *
	 * @since 4.0
	 */
	public $pid = '555ad84787d7e';

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function set_up() {

		parent::set_up();

		$this->import_form();
	}

	/**
	 * Fix for WordPress 4.7 which seems to close the MySQLi connection before
	 * the class is correctly setup
	 *
	 * @since 4.1
	 */
	public static function set_upBeforeClass() {
		global $wpdb;
		$wpdb->suppress_errors = false;
		$wpdb->show_errors     = true;
		$wpdb->db_connect();

		parent::set_upBeforeClass();
	}

	/**
	 * Load the JSON data and import it into Gravity Forms
	 *
	 * @since 4.0
	 */
	private function import_form() {
		$json          = json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/json/form-settings.json' ) ), true );
		$this->form_id = GFAPI::add_form( $json );
	}

	/**
	 * Test our Gravity Forms PDF Settings configuration state change
	 *
	 * @class Model_Form_Settings
	 *
	 * @since 4.0
	 */
	public function test_change_state_pdf_setting() {
		global $gfpdf;

		/* set up our post data and role */
		$this->_setRole( 'administrator' );
		$_POST['fid'] = 0;
		$_POST['pid'] = $this->pid;

		/**
		 * Check for nonce failure
		 */
		try {
			$this->_handleAjax( 'gfpdf_change_state' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/**
		 * Check for update failure (invalid form ID)
		 */
		$_POST['nonce'] = wp_create_nonce( "gfpdf_state_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_change_state' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '500', $e->getMessage() );

		/**
		 * Set up a real response
		 */
		$_POST['fid']   = $this->form_id;
		$_POST['nonce'] = wp_create_nonce( "gfpdf_state_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_change_state' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		/* Get the response */
		$response = json_decode( $this->_last_response, true );

		/* Test the response is accurate */
		$this->assertArrayHasKey( 'state', $response );
		$this->assertEquals( 'Inactive', $response['state'] );

		/* Test the function performed correctly */
		unset( $gfpdf->data->form_settings );
		$pdf = $gfpdf->options->get_pdf( $this->form_id, $this->pid );
		$this->assertFalse( $pdf['active'] );

		/* reset the last response */
		$this->_last_response = '';

		/**
		 * Reverse the process
		 */
		try {
			$this->_handleAjax( 'gfpdf_change_state' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		/* Get the response */
		$response = json_decode( $this->_last_response, true );

		/* Test the response is accurate */
		$this->assertArrayHasKey( 'state', $response );
		$this->assertEquals( 'Active', $response['state'] );

		/* Test the function performed correctly */
		unset( $gfpdf->data->form_settings );
		$pdf = $gfpdf->options->get_pdf( $this->form_id, $this->pid );
		$this->assertTrue( $pdf['active'] );
	}

	/**
	 * Ensure we correctly authorise the end user
	 *
	 * @class Model_Form_Settings
	 *
	 * @since 4.1
	 */
	public function test_render_template_fields() {

		/* Check for authentication failure */
		try {
			$this->_handleAjax( 'gfpdf_get_template_fields' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/* become admin */
		$this->_setRole( 'administrator' );

		/* Check for nonce failure */
		try {
			$this->_handleAjax( 'gfpdf_get_template_fields' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/* Check for missing v3 configuration file failure */
		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_get_template_fields' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		/* Get the response */
		$response = json_decode( $this->_last_response, true );

		$this->assertArrayHasKey( 'fields', $response );
		$this->assertArrayHasKey( 'editors', $response );
		$this->assertArrayHasKey( 'editor_init', $response );
		$this->assertArrayHasKey( 'template_type', $response );
	}

	/**
	 * Test our Gravity Forms PDF Settings configuration duplication functionality
	 *
	 * @class Model_Form_Settings
	 *
	 * @since 4.0
	 */
	public function test_duplicate_gf_pdf_settings() {
		global $gfpdf;

		/* set up our post data and role */
		$this->_setRole( 'administrator' );
		$_POST['fid'] = 0;
		$_POST['pid'] = $this->pid;

		/**
		 * Check for nonce failure
		 */
		try {
			$this->_handleAjax( 'gfpdf_list_duplicate' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/**
		 * Check for update failure (invalid form ID)
		 */
		$_POST['nonce'] = wp_create_nonce( "gfpdf_duplicate_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_list_duplicate' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '500', $e->getMessage() );

		/**
		 * Set up a real response
		 */
		$_POST['fid']   = $this->form_id;
		$_POST['nonce'] = wp_create_nonce( "gfpdf_duplicate_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_list_duplicate' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		/* Get the response */
		$response = json_decode( $this->_last_response, true );

		/* Test the response is accurate */
		$this->assertArrayHasKey( 'msg', $response );
		$this->assertArrayHasKey( 'pid', $response );
		$this->assertArrayHasKey( 'name', $response );
		$this->assertArrayHasKey( 'dup_nonce', $response );
		$this->assertArrayHasKey( 'del_nonce', $response );
		$this->assertArrayHasKey( 'state_nonce', $response );

		/* Test the function performed correctly */
		unset( $gfpdf->data->form_settings );
		$pdf1 = $gfpdf->options->get_pdf( $this->form_id, $this->pid );
		$pdf2 = $gfpdf->options->get_pdf( $this->form_id, $response['pid'] );

		$this->assertEquals( $pdf1['name'] . ' (copy)', $pdf2['name'] );
		$this->assertEquals( $pdf1['template'], $pdf2['template'] );
		$this->assertEquals( $pdf1['filename'], $pdf2['filename'] );

		/* reset the last response */
		$this->_last_response = '';
	}

	/**
	 * Test our Gravity Forms PDF Settings configuration duplication functionality
	 *
	 * @class Model_Form_Settings
	 *
	 * @since 4.0
	 */
	public function test_delete_gf_pdf_setting() {
		global $gfpdf;

		/* test configuration exists already */
		$pdf = $gfpdf->options->get_pdf( $this->form_id, $this->pid );
		$this->assertEquals( 'My First PDF Template', $pdf['name'] );

		/* set up our post data and role */
		$this->_setRole( 'administrator' );
		$_POST['fid'] = 0;
		$_POST['pid'] = $this->pid;

		/**
		 * Check for nonce failure
		 */
		try {
			$this->_handleAjax( 'gfpdf_list_delete' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/**
		 * Check for update failure (invalid form ID)
		 */
		$_POST['nonce'] = wp_create_nonce( "gfpdf_delete_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_list_delete' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '500', $e->getMessage() );

		/**
		 * Set up a real response
		 */
		$_POST['fid']   = $this->form_id;
		$_POST['nonce'] = wp_create_nonce( "gfpdf_delete_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_list_delete' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		/* Get the response */
		$response = json_decode( $this->_last_response, true );

		/* Test the response is accurate */
		$this->assertArrayHasKey( 'msg', $response );

		/* Test the function performed correctly */
		unset( $gfpdf->data->form_settings );
		$pdf = $gfpdf->options->get_pdf( $this->form_id, $this->pid );
		$this->assertTrue( is_wp_error( $pdf ) );
	}

	/**
	 * Testing Model_Templates.php wp_ajax_gfpdf_upload_template
	 *
	 * Because this AJAX endpoint is suppose to have a zip file POSTed,
	 * and because we cannot mock \Upload\File directly (see test-templates.php for specific tests)
	 * we're just testing this endpoint requires authentication AND throws an error when
	 * no file is posted.
	 *
	 * @since 4.1
	 */
	public function test_ajax_process_uploaded_template() {

		/* set up our post data and role */
		$this->_setRole( 'administrator' );

		/* Check for nonce failure */
		try {
			$this->_handleAjax( 'gfpdf_upload_template' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/* Set up a bad request by excluding required fields */
		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_upload_template' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '400', $e->getMessage() );
	}

	/**
	 * Check that we can successfully delete a PDF template through this AJAX endpoint
	 *
	 * @since 4.1
	 */
	public function test_ajax_process_delete_template() {
		global $gfpdf;

		/* set up our post data and role */
		$this->_setRole( 'administrator' );

		/* Check for nonce failure */
		try {
			$this->_handleAjax( 'gfpdf_delete_template' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/* Set up a bad request by excluding required fields */
		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_delete_template' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '400', $e->getMessage() );

		/* Create a test template and actually delete it */
		$file = $gfpdf->data->template_location . 'zadani.php';
		touch( $file );

		$_POST['id'] = 'zadani';

		try {
			$this->_handleAjax( 'gfpdf_delete_template' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		$response = json_decode( $this->_last_response, true );
		unset( $this->_last_response );

		$this->assertTrue( $response );
		$this->assertFileDoesNotExist( $file );
	}

	/**
	 *
	 * @since 4.1
	 */
	public function test_ajax_process_build_template_options_html() {
		/* set up our post data and role */
		$this->_setRole( 'administrator' );

		/* Check for nonce failure */
		try {
			$this->_handleAjax( 'gfpdf_get_template_options' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/* Set up a bad request by excluding required fields */
		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_get_template_options' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertNotFalse( $this->_last_response, '<optgroup label="Core">' );
	}

	public function test_ajax_process_license_deactivation() {
		/* set up our post data and role */
		$this->_setRole( 'administrator' );

		/* Check for nonce failure */
		try {
			$this->_handleAjax( 'gfpdf_deactivate_license' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/* Setup a bad request */
		$_POST['nonce'] = wp_create_nonce( 'gfpdf_deactivate_license' );

		try {
			$this->_handleAjax( 'gfpdf_deactivate_license' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( 'An error occurred during deactivation, please try again', json_decode( $this->_last_response )->error );
	}

	public function test_ajax_save_core_font() {
		/* set up our post data and role */
		$this->_setRole( 'administrator' );

		/* Check for nonce failure */
		try {
			$this->_handleAjax( 'gfpdf_save_core_font' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/* Setup a bad request */
		$_POST['nonce']     = wp_create_nonce( 'gfpdf_ajax_nonce' );
		$_POST['font_name'] = 'nothing';

		try {
			$this->_handleAjax( 'gfpdf_save_core_font' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertFalse( json_decode( $this->_last_response ) );
		$this->_last_response = '';

		/* Test that a core font API download request gets made */
		$_POST['font_name'] = 'Aegean.otf';

		$api_response = function() {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => '',
			];
		};

		add_filter( 'pre_http_request', $api_response );

		try {
			$this->_handleAjax( 'gfpdf_save_core_font' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		remove_filter( 'pre_http_request', $api_response );

		$this->assertTrue( json_decode( $this->_last_response ) );
	}
}
