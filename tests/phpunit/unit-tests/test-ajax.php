<?php

namespace GFPDF\Tests;

use GFAPI;
use GFForms;

use WP_Ajax_UnitTestCase;
use WPAjaxDieStopException;
use WPAjaxDieContinueException;


/**
 * Test Gravity AJAX Functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/*
	This file is part of Gravity PDF.

	Gravity PDF – Copyright (c) 2019, Blue Liquid Designs

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
 *
 * Tests all Ajax calls
 * For speed, non ajax calls of class-ajax.php are tested in test-ajax-others.php
 * Ajax tests are not marked risky when run in separate processes and wp_debug
 * disabled. But, this makes tests slow so non ajax calls are kept separate
 *
 * @group ajax
 * @runTestsInSeparateProcesses
 *
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
	public function setUp() {

		parent::setUp();

		$this->import_form();
	}

	/**
	 * Fix for WordPress 4.7 which seesm to close the MySQLi connection before
	 * the class is correctly setup
	 *
	 * @since 4.1
	 */
	public static function setUpBeforeClass() {
		global $wpdb;
		$wpdb->suppress_errors = false;
		$wpdb->show_errors     = true;
		$wpdb->db_connect();

		parent::setUpBeforeClass();
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
		$this->assertArrayHasKey( 'src', $response );
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
		$this->assertArrayHasKey( 'src', $response );
		$this->assertEquals( 'Active', $response['state'] );

		/* Test the function performed correctly */
		unset( $gfpdf->data->form_settings );
		$pdf = $gfpdf->options->get_pdf( $this->form_id, $this->pid );
		$this->assertTrue( $pdf['active'] );
	}

	/**
	 * Ensure we correctly authorise the end user
	 *
	 * @class Model_Actions
	 *
	 * @since 4.1
	 */
	public function test_multsite_v3_migration() {

		if ( ! is_multisite() ) {
			$this->markTestSkipped(
				'Not running multisite tests'
			);
		}

		/* Check for authentication failure */
		try {
			$this->_handleAjax( 'multisite_v3_migration' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/* become super admin */
		$this->_setRole( 'administrator' );
		grant_super_admin( get_current_user_id() );
		$_POST['blog_id'] = 1;

		/* Check for nonce failure */
		try {
			$this->_handleAjax( 'multisite_v3_migration' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/* Check for missing v3 configuration file failure */
		$_POST['nonce'] = wp_create_nonce( 'gfpdf_multisite_migration' );

		try {
			$this->_handleAjax( 'multisite_v3_migration' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		/* Get the response */
		$response = json_decode( $this->_last_response, true );

		$this->assertArrayHasKey( 'results', $response );
		$this->assertArrayHasKey( 'error', $response['results'] );
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
	 * Test we can save the font via AJAX
	 *
	 * @class Model_Form_Settings
	 *
	 * @since 4.0
	 */
	public function test_save_font() {

		/* set up our post data and role */
		$this->_setRole( 'administrator' );

		/* Check for nonce failure */
		try {
			$this->_handleAjax( 'gfpdf_font_save' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/* Set up a bad request by excluding required fields */
		$_POST['nonce'] = wp_create_nonce( 'gfpdf_font_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_font_save' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		$response = json_decode( $this->_last_response, true );
		unset( $this->_last_response );
		$this->assertEquals( 'Required fields have not been included.', $response['error'] );

		/* Test for invalid font name */
		$_POST['payload'] = [
			'font_name' => 'My AJAX_Font',
			'regular'   => 'myajaxfont.ttf',
		];

		try {
			$this->_handleAjax( 'gfpdf_font_save' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		$response = json_decode( $this->_last_response, true );
		unset( $this->_last_response );

		$this->assertEquals( 'Font name is not valid. Only alphanumeric characters and spaces are accepted.', $response['error'] );

		/* Test a valid installation */
		touch( ABSPATH . 'myajaxfont.ttf' );

		$_POST['payload'] = [
			'font_name' => 'My AJAX Font',
			'regular'   => ABSPATH . 'myajaxfont.ttf',
		];

		try {
			$this->_handleAjax( 'gfpdf_font_save' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertArrayHasKey( 'id', $response );

	}

	/**
	 * Test we can do the process of deleting a font via AJAX
	 * Because the database state never changes between requests we cannot
	 * correctly install a font and then do an AJAX request (two seperate processes).
	 * Instead, we'll just mimick a failed attempt and test the actual font removal function elsewhere
	 *
	 * @class Model_Form_Settings
	 *
	 * @since 4.0
	 */
	public function test_delete_font() {

		/* set up our post data and role */
		$this->_setRole( 'administrator' );

		/* Check for nonce failure */
		try {
			$this->_handleAjax( 'gfpdf_font_delete' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/* Set up a real delete request */
		$_POST['nonce'] = wp_create_nonce( 'gfpdf_font_nonce' );
		$_POST['id']    = '';

		try {
			$this->_handleAjax( 'gfpdf_font_delete' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		$response = json_decode( $this->_last_response, true );
		unset( $this->_last_response );

		$this->assertEquals( 'Could not delete Gravity PDF font correctly. Please try again.', $response['error'] );
	}

	/**
	 * Test our security has been implimented correctly
	 *
	 * @class Model_Settings
	 *
	 * @since 4.0
	 */
	public function test_check_tmp_pdf_security() {

		/**
		 * Check for authentication failure
		 */
		try {
			$this->_handleAjax( 'gfpdf_has_pdf_protection' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/* set up our role */
		$this->_setRole( 'administrator' );

		/**
		 * Check for nonce failure
		 */
		try {
			$this->_handleAjax( 'gfpdf_has_pdf_protection' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/*
		 * Do successful test
		 */
		$_POST['nonce'] = wp_create_nonce( 'gfpdf-direct-pdf-protection' );

		try {
			$this->_handleAjax( 'gfpdf_has_pdf_protection' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		$response = json_decode( $this->_last_response, true );
		unset( $this->_last_response );

		$this->assertTrue( $response );
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
	 * Check that we can succesfully delete a PDF template through this AJAX endpoint
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
		$this->assertFileNotExists( $file );
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
		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_save_core_font' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertFalse( json_decode( $this->_last_response ) );
		$this->_last_response = '';

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
