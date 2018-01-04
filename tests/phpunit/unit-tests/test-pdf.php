<?php

namespace GFPDF\Tests;

use GFPDF\Controller\Controller_PDF;
use GFPDF\Model\Model_PDF;
use GFPDF\View\View_PDF;
use GFPDF\Helper\Helper_PDF;
use GFPDF\Helper\Fields\Field_Products;
use GFPDF\Helper\Helper_Field_Container;

use GFAPI;
use GFFormsModel;
use GF_Field;
use GFForms;
use GFCache;

use WP_UnitTestCase;
use WP_Error;
use WP_Rewrite;

use Exception;
use ReflectionMethod;

/**
 * Test Gravity PDF Endpoint Functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

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
 * Test the model / view / controller for the PDF Endpoint functionality
 *
 * @since 4.0
 * @group pdf
 */
class Test_PDF extends WP_UnitTestCase {

	/**
	 * Our Settings Controller
	 *
	 * @var \GFPDF\Controller\Controller_PDF
	 *
	 * @since 4.0
	 */
	public $controller;

	/**
	 * Our Settings Model
	 *
	 * @var \GFPDF\Model\Model_PDF
	 *
	 * @since 4.0
	 */
	public $model;

	/**
	 * Our Settings View
	 *
	 * @var \GFPDF\View\View_PDF
	 *
	 * @since 4.0
	 */
	public $view;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function setUp() {
		global $gfpdf;

		/* run parent method */
		parent::setUp();

		/* Setup our test classes */
		$this->model = new Model_PDF( $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->notices, $gfpdf->templates );
		$this->view  = new View_PDF( [], $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );

		$this->controller = new Controller_PDF( $this->model, $this->view, $gfpdf->gform, $gfpdf->log, $gfpdf->misc );
		$this->controller->init();
	}

	/**
	 * Create our testing data
	 *
	 * @since 4.0
	 */
	private function create_form_and_entries() {
		global $gfpdf;

		$form  = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		$gfpdf->data->form_settings[ $form['id'] ] = $form['gfpdf_form_settings'];

		return [
			'form'  => $form,
			'entry' => $entry,
		];
	}

	/**
	 * Check if all the correct actions are applied
	 *
	 * @since 4.0
	 */
	public function test_actions() {
		$this->assertSame( 10, has_action( 'parse_request', [ $this->controller, 'process_legacy_pdf_endpoint' ] ) );
		$this->assertSame( 10, has_action( 'parse_request', [ $this->controller, 'process_pdf_endpoint' ] ) );

		$this->assertSame( 10, has_action( 'gform_entries_first_column_actions', [
			$this->model,
			'view_pdf_entry_list',
		] ) );
		$this->assertSame( 10, has_action( 'gform_entry_info', [ $this->model, 'view_pdf_entry_detail' ] ) );
		$this->assertSame( 10, has_action( 'gform_after_submission', [ $this->model, 'maybe_save_pdf' ] ) );
		$this->assertSame( 9999, has_action( 'gform_after_submission', [ $this->model, 'cleanup_pdf' ] ) );
		$this->assertSame( 9999, has_action( 'gform_after_update_entry', [
			$this->model,
			'cleanup_pdf_after_submission',
		] ) );
		$this->assertSame( 10, has_action( 'gfpdf_cleanup_tmp_dir', [ $this->model, 'cleanup_tmp_dir' ] ) );
	}

	/**
	 * Check if all the correct filters are applied
	 *
	 * @since 4.0
	 */
	public function test_filters() {
		global $gfpdf;

		$this->assertSame( 10, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_public_access' ] ) );
		$this->assertSame( 20, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_active' ] ) );
		$this->assertSame( 30, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_conditional' ] ) );
		$this->assertSame( 40, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_owner_restriction' ] ) );
		$this->assertSame( 50, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_logged_out_timeout' ] ) );
		$this->assertSame( 60, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_auth_logged_out_user' ] ) );
		$this->assertSame( 70, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_user_capability' ] ) );

		$this->assertSame( 9999, has_filter( 'gform_notification', [ $this->model, 'notifications' ], 9999 ) );

		$this->assertSame( 10, has_filter( 'mpdf_tmp_path', [ $this->model, 'mpdf_tmp_path' ] ) );
		$this->assertSame( 10, has_filter( 'mpdf_fontdata_path', [ $this->model, 'mpdf_tmp_font_path' ] ) );
		$this->assertSame( 10, has_filter( 'mpdf_current_font_path', [ $this->model, 'set_current_pdf_font' ] ) );
		$this->assertSame( 10, has_filter( 'mpdf_font_data', [
			$this->model,
			'register_custom_font_data_with_mPDF',
		] ) );
		$this->assertSame( 20, has_filter( 'mpdf_font_data', [ $this->model, 'add_unregistered_fonts_to_mPDF' ] ) );

		$this->assertSame( 10, has_filter( 'gfpdf_pdf_html_output', [ $gfpdf->gform, 'process_tags' ] ) );
		$this->assertSame( 10, has_filter( 'gfpdf_pdf_html_output', 'do_shortcode' ) );

		$this->assertSame( 10, has_filter( 'gfpdf_template_args', [ $this->model, 'preprocess_template_arguments' ] ) );
		$this->assertSame( 5, has_filter( 'gfpdf_pdf_html_output', [
			$this->view,
			'autoprocess_core_template_options',
		] ) );

		/* Backwards compatiblity */
		$this->assertSame( 1, has_filter( 'gfpdfe_pre_load_template', [ 'PDFRender', 'prepare_ids' ] ) );
		$this->assertSame( 10, has_filter( 'gform_before_resend_notifications', [
			$this->model,
			'resend_notification_pdf_cleanup',
		] ) );
	}

	/**
	 * Ensure we're cleaning up the tmp directory and set intervals
	 *
	 * @since 4.0
	 */
	public function test_scheduled_tmp_cleanup() {
		$this->assertNotFalse( wp_next_scheduled( 'gfpdf_cleanup_tmp_dir' ) );
	}

	/**
	 * Ensure our PDF endpoint listener is working correctly
	 *
	 * @since 4.0
	 */
	public function test_process_pdf_endpoint() {

		/* Force a failure */
		$this->assertNull( $this->controller->process_pdf_endpoint() );

		/* Test our endpoint is firing correctly */
		$GLOBALS['wp']->query_vars['gpdf'] = 1;
		$GLOBALS['wp']->query_vars['pid']  = 1;
		$GLOBALS['wp']->query_vars['lid']  = 500;

		try {
			$this->controller->process_pdf_endpoint();
		} catch ( Exception $e ) {
			$this->assertEquals( 'There was a problem generating your PDF', $e->getMessage() );

			return;
		}

		$this->fail( 'This test did not fail as expected' );
	}

	/**
	 * Ensure our legacy PDF endpoint listener is working correctly
	 *
	 * @since 4.0
	 */
	public function test_process_legacy_pdf_endpoint() {

		/* Force a failure */
		$this->assertNull( $this->controller->process_legacy_pdf_endpoint() );

		/* Test our endpoint is firing correctly */
		$_GET['gf_pdf']   = 1;
		$_GET['fid']      = -1;
		$_GET['lid']      = -1;
		$_GET['template'] = 'test';

		try {
			$results = $this->controller->process_legacy_pdf_endpoint();
		} catch ( Exception $e ) {
			$this->assertEquals( 'There was a problem generating your PDF', $e->getMessage() );

			return;
		}

		$this->fail( 'This test did not fail as expected' );
	}

	/**
	 * Ensure the correct error message is shown to the user
	 *
	 * @since 4.0
	 */
	public function test_pdf_error() {

		/* pdf_error is private but we do want to verify the different errors are showing to the correct audience without having to go through the public API */
		$method = new ReflectionMethod(
			'\GFPDF\Controller\Controller_PDF', 'pdf_error'
		);

		$method->setAccessible( true );

		/* Ensure our public errors are shown */

		try {
			$error = new WP_Error( 'timeout_expired', 'Expired' );
			$method->invoke( $this->controller, $error );
		} catch ( Exception $e ) {
			/* Do nothing here */
		}

		$this->assertEquals( 'Expired', $e->getMessage() );

		try {
			$error = new WP_Error( 'access_denied', 'Denied' );
			$method->invoke( $this->controller, $error );
		} catch ( Exception $e ) {
			/* Do nothing here */
		}

		$this->assertEquals( 'Denied', $e->getMessage() );

		/* Ensure our private errors aren't shown to unauthorised users */
		try {
			$error = new WP_Error( 'other_problem', 'Other' );
			$method->invoke( $this->controller, $error );
		} catch ( Exception $e ) {
			/* Do nothing here */
		}

		$this->assertEquals( 'There was a problem generating your PDF', $e->getMessage() );

		/* Authorise the current user and check the message is displayed correctly */
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );

		try {
			$error = new WP_Error( 'other_problem', 'Other' );
			$method->invoke( $this->controller, $error );
		} catch ( Exception $e ) {
			/* Do nothing here */
		}

		$this->assertEquals( 'Other', $e->getMessage() );

		wp_set_current_user( 0 );

	}

	/**
	 * Test if our public access middleware works as expected
	 *
	 * @since 4.0
	 */
	public function test_middle_public_access() {

		/* Check if error correctly triggered */
		$settings['public_access'] = 'No';
		$this->model->middle_public_access( '', '', $settings );

		/* Run our Tests */
		$this->assertSame( 20, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_active' ] ) );
		$this->assertSame( 30, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_conditional' ] ) );
		$this->assertSame( 40, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_owner_restriction' ] ) );
		$this->assertSame( 50, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_logged_out_timeout' ] ) );
		$this->assertSame( 60, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_auth_logged_out_user' ] ) );
		$this->assertSame( 70, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_user_capability' ] ) );

		/* Check if setting passes */
		$settings['public_access'] = 'Yes';
		$this->model->middle_public_access( '', '', $settings );

		$this->assertSame( 20, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_active' ] ) );
		$this->assertSame( 30, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_conditional' ] ) );
		$this->assertFalse( has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_owner_restriction' ] ) );
		$this->assertFalse( has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_logged_out_timeout' ] ) );
		$this->assertFalse( has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_auth_logged_out_user' ] ) );
		$this->assertFalse( has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_user_capability' ] ) );

	}

	/**
	 * Test if our active PDF middleware works correctly
	 *
	 * @since 4.0
	 */
	public function test_middle_active() {

		/* Check if error correctly triggered */
		$settings['active'] = false;
		$this->assertTrue( is_wp_error( $this->model->middle_active( '', '', $settings ) ) );

		/* Check if setting passes */
		$settings['active'] = true;
		$this->assertTrue( $this->model->middle_active( true, '', $settings ) );
	}

	/**
	 * Test if our conditional logic middleware works correctly
	 *
	 * @since 4.0
	 */
	public function test_middle_conditional() {

		/* Setup some test data */
		$results          = $this->create_form_and_entries();
		$entry            = $results['entry'];
		$entry['form_id'] = $results['form']['id'];

		/* Create a passing condition */
		$settings['conditionalLogic'] = [
			'actionType' => 'show',
			'logicType'  => 'all',
			'rules'      => [
				[
					'fieldId'  => '1',
					'operator' => 'is',
					'value'    => 'My Single Line Response',
				],
			],
		];

		$this->assertTrue( $this->model->middle_conditional( true, $entry, $settings ) );

		/* Create a failing condition */
		$settings['conditionalLogic']['rules'][0]['value'] = 'test';

		$this->assertTrue( is_wp_error( $this->model->middle_conditional( true, $entry, $settings ) ) );
	}

	/**
	 * Check if correct GF entry owner is determined
	 *
	 * @since 4.0
	 */
	public function test_is_current_pdf_owner() {
		/* set up a user to test its privilages */
		$user_id = $this->factory->user->create();
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );

		/* Set up a blank entry array */
		$entry = [
			'created_by' => '',
			'ip'         => '',
		];

		$this->assertFalse( $this->model->is_current_pdf_owner( $entry ) );

		/* assign our user ID */
		$entry['created_by'] = $user_id;

		$this->assertTrue( $this->model->is_current_pdf_owner( $entry ) );
		$this->assertTrue( $this->model->is_current_pdf_owner( $entry, 'logged_in' ) );
		$this->assertFalse( $this->model->is_current_pdf_owner( $entry, 'logged_out' ) );

		/* logout and retest */
		wp_set_current_user( 0 );
		$this->assertFalse( $this->model->is_current_pdf_owner( $entry ) );
		$this->assertFalse( $this->model->is_current_pdf_owner( $entry, 'logged_in' ) );

		/* Set the IPs */
		$entry['ip']               = '197.64.12.40';
		$_SERVER['REMOTE_ADDR'] = $entry['ip'];

		$this->assertTrue( $this->model->is_current_pdf_owner( $entry ) );
		$this->assertTrue( $this->model->is_current_pdf_owner( $entry, 'logged_out' ) );
		$this->assertFalse( $this->model->is_current_pdf_owner( $entry, 'logged_in' ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Check if our logged out restrictions are being applied correctly
	 *
	 * @since 4.0
	 */
	public function test_middle_owner_restriction() {
		global $gfpdf;

		$this->assertTrue( $this->model->middle_owner_restriction( true, '', [ 'restrict_owner' => 'No' ] ) );
		$this->assertTrue( is_wp_error( $this->model->middle_owner_restriction( new WP_Error( '' ), '', [ 'restrict_owner' => 'No' ] ) ) );

		/* test if we are redirecting */
		try {
			wp_set_current_user( 0 );
			$this->model->middle_owner_restriction( true, '', [ 'restrict_owner' => 'Yes' ] );
		} catch ( Exception $e ) {
			$this->assertEquals( 'Redirecting', $e->getMessage() );
		}

		/* Test if logged in users are ignored */
		$user_id = $this->factory->user->create();
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );
		$this->assertTrue( $this->model->middle_owner_restriction( true, '', [ 'restrict_owner' => 'Yes' ] ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Check if our logged out timeout restrictions are being applied correctly
	 *
	 * @since 4.0
	 */
	public function test_middle_logged_out_timeout() {
		global $gfpdf;

		/* Set up our testing data */
		$entry = [
			'date_created' => date( 'Y-m-d H:i:s', strtotime( '-32 minutes' ) ),
			'ip'           => '197.64.12.40',
		];

		$_SERVER['REMOTE_ADDR'] = $entry['ip'];

		/* Test we get a timeout error */
		$results = $this->model->middle_logged_out_timeout( true, $entry, '' );
		$this->assertTrue( is_wp_error( $results ) );
		$this->assertEquals( 'timeout_expired', $results->get_error_code() );

		/* Test we get a auth redirect */
		$entry['created_by'] = 5;

		try {
			$this->model->middle_logged_out_timeout( true, $entry, '' );
		} catch ( Exception $e ) {
			$this->assertEquals( 'Redirecting', $e->getMessage() );
		}

		/* Update timeout settings and check again */
		$gfpdf->options->update_option( 'logged_out_timeout', '33' );
		$this->assertTrue( $this->model->middle_logged_out_timeout( true, $entry, '' ) );

		/* Check if the test should be skipped */
		$_SERVER['REMOTE_ADDR'] = '12.123.123.124';
		$this->assertTrue( $this->model->middle_logged_out_timeout( true, $entry, '' ) );
		$this->assertTrue( is_wp_error( $this->model->middle_logged_out_timeout( new WP_Error(), $entry, '' ) ) );

		$user_id = $this->factory->user->create();
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );
		$this->assertTrue( $this->model->middle_logged_out_timeout( true, $entry, '' ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Check if our logged out user has access to our PDF
	 *
	 * @since 4.0
	 */
	public function test_middle_auth_logged_out_user() {

		/* Set up our testing data */
		$entry = [
			'ip' => '197.64.12.40',
		];

		/* Check for WP Error */
		$this->assertTrue( is_wp_error( $this->model->middle_auth_logged_out_user( true, $entry, '' ) ) );

		/* Check for redirect */
		$entry['created_by'] = 5;

		try {
			$this->model->middle_auth_logged_out_user( true, $entry, '' );
		} catch ( Exception $e ) {
			$this->assertEquals( 'Redirecting', $e->getMessage() );
		}

		/* Test that the middleware is skipped */
		$_SERVER['REMOTE_ADDR'] = $entry['ip'];
		$this->assertTrue( $this->model->middle_auth_logged_out_user( true, $entry, '' ) );

		unset( $_SERVER['REMOTE_ADDR'] );
		$user_id = $this->factory->user->create();
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );
		$this->assertTrue( $this->model->middle_auth_logged_out_user( true, $entry, '' ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Check if our logged in user has access to our PDF
	 *
	 * @since 4.0
	 */
	public function test_middle_user_capability() {
		global $current_user;

		/* Check for WP Error */
		$this->assertTrue( is_wp_error( $this->model->middle_user_capability( new WP_Error(), '', '' ) ) );

		/* create subscriber and test access */
		$user_id = $this->factory->user->create();
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );

		/* get the results */
		$results = $this->model->middle_user_capability( true, [ 'created_by' => 0 ], '' );

		$this->assertTrue( is_wp_error( $results ) );
		$this->assertEquals( 'access_denied', $results->get_error_code() );

		/* Elevate user to administrator */
		$user = wp_get_current_user();
		$user->remove_role( 'subscriber' );
		$user->add_role( 'administrator' );

		$this->assertTrue( $this->model->middle_user_capability( true, [ 'created_by' => 0 ], '' ) );

		/* Remove elevated user privilages and set the default capability 'gravityforms_view_entries' */
		$user->remove_role( 'administrator' );
		$user->add_role( 'subscriber' );

		/* Double check they have been removed */
		$results = $this->model->middle_user_capability( true, [ 'created_by' => 0 ], '' );

		$this->assertTrue( is_wp_error( $results ) );
		$this->assertEquals( 'access_denied', $results->get_error_code() );

		/* Add default capability and test */
		$user->add_cap( 'gravityforms_view_entries' );
		$user->get_role_caps();
		$user->update_user_level_from_caps();
		$this->assertTrue( $this->model->middle_user_capability( true, [ 'created_by' => 0 ], '' ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Check our PDF list is displaying correctly
	 *
	 * @since 4.0
	 */
	public function test_view_pdf_entry_list() {

		$results = $this->create_form_and_entries();
		$form_id = $results['form']['id'];
		$entry   = $results['entry'];

		ob_start();
		$this->model->view_pdf_entry_list( $form_id, '', '', $entry );
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, 'View PDFs</a>' ) );
	}

	/**
	 * Check our PDF detail list is displaying correctly
	 *
	 * @since 4.0
	 */
	public function test_view_pdf_entry_detail() {

		$results = $this->create_form_and_entries();
		$form_id = $results['form']['id'];
		$entry   = $results['entry'];

		ob_start();
		$this->model->view_pdf_entry_detail( $form_id, $entry );
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, '<strong>PDFs</strong>' ) );
	}

	/**
	 * Check that an array of PDFs gets correctly returned in the right format
	 *
	 * @since 4.0
	 */
	public function test_get_pdf_display_list() {
		global $wp_rewrite, $gfpdf;

		/* Setup some test data */
		$results = $this->create_form_and_entries();
		$form    = $results['form'];
		$entry   = $results['entry'];

		$pdfs = $this->model->get_pdf_display_list( $entry );

		$this->assertArrayHasKey( 'name', $pdfs[0] );
		$this->assertArrayHasKey( 'view', $pdfs[0] );
		$this->assertArrayHasKey( 'download', $pdfs[0] );

		$this->assertNotFalse( strpos( $pdfs[0]['name'], 'test-' ) );
		$this->assertNotFalse( strpos( $pdfs[0]['view'], 'http://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1' ) );
		$this->assertNotFalse( strpos( $pdfs[0]['download'], 'http://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1&#038;action=download' ) );

		/* Process fancy permalinks */
		$old_permalink_structure = get_option( 'permalink_structure' );
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules();

		$pdfs = $this->model->get_pdf_display_list( $entry );

		$this->assertNotFalse( strpos( $pdfs[0]['view'], 'http://example.org/pdf/556690c67856b/' ) );
		$this->assertNotFalse( strpos( $pdfs[0]['download'], '/download/' ) );

		$wp_rewrite->set_permalink_structure( $old_permalink_structure );
		flush_rewrite_rules();
	}

	/**
	 * Check that our PDF name gets processed correctly
	 * We'll unit test in more detail do_mergetags and strip_invalid_characters separetly so just a quick run through here
	 *
	 * @since 4.0
	 */
	public function test_get_pdf_name() {

		/* Setup some test data */
		$results = $this->create_form_and_entries();
		$form    = $results['form'];
		$entry   = $results['entry'];

		/* Get our active PDFs */
		$pdfs = ( isset( $form['gfpdf_form_settings'] ) ) ? $this->model->get_active_pdfs( $form['gfpdf_form_settings'], $entry ) : [];

		/* Get a PDF configuration */
		$pdf = $pdfs['556690c67856b'];

		/* Check merge tags and being processed */
		$this->assertEquals( 'test-' . $form['id'], $this->model->get_pdf_name( $pdf, $entry ) );

		/* Check invalid characters are stripped */
		$pdf['filename'] = 'my/file"name*willbe:great_{form_id}';
		$this->assertEquals( 'my_file_name_willbe_great_' . $form['id'], $this->model->get_pdf_name( $pdf, $entry ) );

		/* Check our filters work correctly */

		add_filter( 'gfpdf_pdf_filename', function () {
			return 'filter';
		} );

		$this->assertEquals( 'filter', $this->model->get_pdf_name( $pdf, $entry ) );

		add_filter( 'gfpdfe_pdf_filename', function () {
			return 'filter';
		} );

		$this->assertEquals( 'filter', $this->model->get_pdf_name( $pdf, $entry ) );
	}

	/**
	 * Check that the returned PDF URL is correct
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_get_pdf_url
	 *
	 * @param $pid
	 * @param $id
	 * @param $download
	 * @param $print
	 * @param $expected
	 */
	public function test_get_pdf_url( $pid, $id, $download, $print, $expected ) {
		global $wp_rewrite;

		/* Process fancy permalinks */
		$old_permalink_structure = get_option( 'permalink_structure' );
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules();

		$this->assertEquals( $expected, $this->model->get_pdf_url( $pid, $id, $download, $print ) );

		$wp_rewrite->set_permalink_structure( $old_permalink_structure );
		flush_rewrite_rules();
	}

	/**
	 * The data provider for the test_get_pdf_url() function
	 *
	 * @since 4.0
	 */
	public function provider_get_pdf_url() {
		return [
			[ '240arkj92kda', '50', false, false, 'http://example.org/pdf/240arkj92kda/50/' ],
			[ 'kjoai2', '25', false, false, 'http://example.org/pdf/kjoai2/25/' ],
			[ 'AIfawjoi24012', '9992', false, false, 'http://example.org/pdf/AIfawjoi24012/9992/' ],
			[ 'JJiawfafwwaa', '5020', false, false, 'http://example.org/pdf/JJiawfafwwaa/5020/' ],
			[ 'fa2a20koawas', '2', false, false, 'http://example.org/pdf/fa2a20koawas/2/' ],
			[ 'JJiawfafwwaa', '5020', true, false, 'http://example.org/pdf/JJiawfafwwaa/5020/download/' ],
			[ 'fa2a20koawas', '2', false, true, 'http://example.org/pdf/fa2a20koawas/2/?print=1' ],
			[ 'kjoai2', '25', true, true, 'http://example.org/pdf/kjoai2/25/download/?print=1' ],
			[ 'AIfawjoi24012', '9992', true, true, 'http://example.org/pdf/AIfawjoi24012/9992/download/?print=1' ],
		];
	}

	/**
	 * Check that the returned PDF URL is correct
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_get_pdf_url_no_perma
	 *
	 * @param $pid
	 * @param $id
	 * @param $download
	 * @param $print
	 * @param $expected
	 */
	public function test_get_pdf_url_no_perma( $pid, $id, $download, $print, $expected ) {
		$this->assertEquals( $expected, $this->model->get_pdf_url( $pid, $id, $download, $print ) );
	}

	/**
	 * The data provider for the test_get_pdf_url() function
	 *
	 * @since 4.0
	 */
	public function provider_get_pdf_url_no_perma() {
		return [
			[ '240arkj92kda', '50', false, false, 'http://example.org/?gpdf=1&#038;pid=240arkj92kda&#038;lid=50' ],
			[ 'kjoai2', '25', false, false, 'http://example.org/?gpdf=1&#038;pid=kjoai2&#038;lid=25' ],
			[
				'AIfawjoi24012',
				'9992',
				false,
				false,
				'http://example.org/?gpdf=1&#038;pid=AIfawjoi24012&#038;lid=9992',
			],
			[ 'JJiawfafwwaa', '5020', false, false, 'http://example.org/?gpdf=1&#038;pid=JJiawfafwwaa&#038;lid=5020' ],
			[ 'fa2a20koawas', '2', false, false, 'http://example.org/?gpdf=1&#038;pid=fa2a20koawas&#038;lid=2' ],
			[
				'JJiawfafwwaa',
				'5020',
				true,
				false,
				'http://example.org/?gpdf=1&#038;pid=JJiawfafwwaa&#038;lid=5020&#038;action=download',
			],
			[
				'fa2a20koawas',
				'2',
				false,
				true,
				'http://example.org/?gpdf=1&#038;pid=fa2a20koawas&#038;lid=2&#038;print=1',
			],
			[
				'kjoai2',
				'25',
				true,
				true,
				'http://example.org/?gpdf=1&#038;pid=kjoai2&#038;lid=25&#038;action=download&#038;print=1',
			],
			[
				'AIfawjoi24012',
				'9992',
				true,
				true,
				'http://example.org/?gpdf=1&#038;pid=AIfawjoi24012&#038;lid=9992&#038;action=download&#038;print=1',
			],
		];
	}

	/**
	 * Check if we are determining active PDFs correctly
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_get_active_pdfs
	 */
	public function test_get_active_pdfs( $expected, $pdf ) {

		/* Setup some test data */
		$results = $this->create_form_and_entries();
		$entry   = $results['entry'];

		$result = ( $expected ) ? 1 : 0;
		$this->assertSame( $result, sizeof( $this->model->get_active_pdfs( [ $pdf ], $entry ) ) );
	}

	/**
	 * Data provider for test_get_active_pdfs()
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function provider_get_active_pdfs() {
		return [
			[
				true,
				[
					'id'     => 1,
					'active' => true,
				],
			],

			[
				false,
				[
					'id'     => 2,
					'active' => false,
				],
			],

			[
				false,
				[
					'id'               => 3,
					'active'           => true,
					'conditionalLogic' => [
						'actionType' => 'show',
						'logicType'  => 'all',
						'rules'      => [
							[
								'fieldId'  => '1',
								'operator' => 'is',
								'value'    => 'Test',
							],
						],
					],
				],
			],

			[
				true,
				[
					'id'               => 4,
					'active'           => true,
					'conditionalLogic' => [
						'actionType' => 'show',
						'logicType'  => 'all',
						'rules'      => [
							[
								'fieldId'  => '1',
								'operator' => 'is',
								'value'    => 'My Single Line Response',
							],
						],
					],
				],
			],
		];
	}

	/**
	 * Check if the correct PDFs are attached to Gravity Forms notifications
	 *
	 * @since 4.0
	 */
	public function test_notifications() {

		/* Setup some test data */
		$results = $this->create_form_and_entries();
		$entry   = $results['entry'];
		$form    = $results['form'];

		/* Create PDF file so it isn't recreated */
		$path = PDF_TEMPLATE_LOCATION . "tmp/{$form['id']}{$entry['id']}/";

		wp_mkdir_p( $path );
		touch( $path . "test-{$form['id']}.pdf" );

		$notifications = $this->model->notifications( $form['notifications']['54bca349732b8'], $form, $entry );

		/* Check the results are successful */
		$this->assertNotFalse( strpos( $notifications['attachments'][0], "PDF_EXTENDED_TEMPLATES/tmp/{$form['id']}{$entry['id']}/test-{$form['id']}.pdf" ) );

		/* Clean up */
		unlink( $notifications['attachments'][0] );

		/* Check we don't process an entry not stored in the database */
		$entry['id'] = null;

		$notifications = $this->model->notifications( $form['notifications']['54bca349732b8'], $form, $entry );

		$this->assertSame( 0, sizeof( $notifications['attachments'] ) );
	}

	/**
	 * Check if our PDF exists on disk
	 *
	 * @since 4.0
	 */
	public function test_does_pdf_exist() {
		global $gfpdf;

		$pdf = new Helper_PDF( [ 'id'      => 1,
		                         'form_id' => 1,
		], '', $gfpdf->gform, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );
		$pdf->set_path( ABSPATH );
		$pdf->set_filename( 'unittest' );

		/* Check that PDF exists */
		touch( ABSPATH . 'unittest.pdf' );
		$this->assertTrue( $this->model->does_pdf_exist( $pdf ) );

		/* Check that PDF does not exist */
		unlink( ABSPATH . 'unittest.pdf' );
		$this->assertFalse( $this->model->does_pdf_exist( $pdf ) );
	}

	/**
	 * Ensure the PDF output setting is correct
	 *
	 * @since 4.0
	 */
	public function test_get_output_type() {
		global $gfpdf;

		$pdf = new Helper_PDF( [ 'id'      => 1,
		                         'form_id' => 1,
		], '', $gfpdf->gform, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );

		$pdf->set_output_type( 'display' );
		$this->assertEquals( 'DISPLAY', $pdf->get_output_type() );

		$pdf->set_output_type( 'download' );
		$this->assertEquals( 'DOWNLOAD', $pdf->get_output_type() );

		$pdf->set_output_type( 'save' );
		$this->assertEquals( 'SAVE', $pdf->get_output_type() );
	}

	/**
	 * Ensure the correct template path is returned
	 *
	 * @since 4.0
	 */
	public function test_get_template_path() {
		global $gfpdf;

		$pdf = new Helper_PDF( [ 'id'      => 1,
		                         'form_id' => 1,
		], [ 'template' => 'zadani' ], $gfpdf->gform, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );

		/* Cleanup any previous tests */
		@unlink( $gfpdf->data->template_location . 'zadani.php' );

		/* Set our current PDF template */
		$pdf->set_template();

		/* Check our basic struction is correct */
		$this->assertEquals( PDF_PLUGIN_DIR . 'src/templates/zadani.php', $pdf->get_template_path() );

		/* Copy the template to our PDF_EXTENDED_TEMPLATES directory and recheck the path */
		copy( PDF_PLUGIN_DIR . 'src/templates/zadani.php', $gfpdf->data->template_location . 'zadani.php' );

		/* Set our current PDF template */
		$pdf->set_template();

		/* Run our new test */
		$this->assertEquals( $gfpdf->data->template_location . 'zadani.php', $pdf->get_template_path() );
		@unlink( $gfpdf->data->template_location . 'zadani.php' );

		/* Check the multisite option */
		if ( is_multisite() ) {
			/* Copy the template to our multisite PDF_EXTENDED_TEMPLATES directory and recheck the path */
			copy( PDF_PLUGIN_DIR . 'src/templates/zadani.php', $gfpdf->data->multisite_template_location . 'zadani.php' );

			/* Set our current PDF template */
			$pdf->set_template();

			/* Run our new test */
			$this->assertEquals( $gfpdf->data->multisite_template_location . 'zadani.php', $pdf->get_template_path() );
			@unlink( $gfpdf->data->multisite_template_location . 'zadani.php' );
		}

		/* Check for errors */
		$pdf = new Helper_PDF( [
			'id'      => 1,
			'form_id' => 1,
		], [
			'template' => 'non-existant',
		],
			$gfpdf->gform,
			$gfpdf->data,
			$gfpdf->misc,
			$gfpdf->templates
		);

		try {
			/* Set our current PDF template */
			$pdf->set_template();
		} catch ( Exception $e ) {
			$this->assertEquals( 'Could not find the template: non-existant.php', $e->getMessage() );
		}

		/* Check for incorrect version requirements */
		$template = file_get_contents( PDF_PLUGIN_DIR . 'src/templates/zadani.php' );
		$template = str_replace( 'Required PDF Version: 4.0-alpha', 'Required PDF Version: 10', $template );
		file_put_contents( $gfpdf->data->template_location . 'zadani.php', $template );

		$pdf = new Helper_PDF( [ 'id'      => 1,
		                         'form_id' => 1,
		], [ 'template' => 'zadani' ], $gfpdf->gform, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );

		try {
			$pdf->set_template();
		} catch ( Exception $e ) {
			$this->assertEquals( sprintf( 'The PDF Template %s requires Gravity PDF version %s. Upgrade to the latest version.', '<em>zadani</em>', '<em>10</em>' ), $e->getMessage() );
		}

		@unlink( $gfpdf->data->template_location . 'zadani.php' );
	}

	/**
	 * Check our tmp directory is being cleaned up correctly
	 *
	 * @since 4.0
	 */
	public function test_cleanup_tmp_dir() {
		global $gfpdf;

		$tmp = $gfpdf->data->template_tmp_location;

		/* Create our files to test */
		$files = [
			'test'      => time(),
			'test1'     => time() - ( 23 * 3600 ),
			'test3'     => time() - ( 24.5 * 3600 ),
			'test4'     => time() - ( 25 * 3600 ),
			'test5'     => time() - ( 15 * 3600 ),
			'test6'     => time() - ( 5 * 3600 ),
			'.htaccess' => time() - ( 48 * 3600 ),
		];

		foreach ( $files as $file => $modified ) {
			touch( $tmp . $file, $modified );
		}

		/* Run our cleanup function and test the out put */
		$this->model->cleanup_tmp_dir();

		$this->assertTrue( is_file( $tmp . 'test' ) );
		$this->assertTrue( is_file( $tmp . 'test1' ) );
		$this->assertFalse( is_file( $tmp . 'test3' ) );
		$this->assertFalse( is_file( $tmp . 'test4' ) );
		$this->assertTrue( is_file( $tmp . 'test5' ) );
		$this->assertTrue( is_file( $tmp . 'test6' ) );
		$this->assertTrue( is_file( $tmp . '.htaccess' ) );

		/* Cleanup our files */
		foreach ( $files as $file => $modified ) {
			@unlink( $tmp . $file, $modified );
		}
	}

	/**
	 * Check that our PDF is cleaned up after the Gravity Forms entry save process
	 *
	 * @since 4.0
	 */
	public function test_cleanup_pdf() {
		global $gfpdf;

		/* Setup some test data */
		$results = $this->create_form_and_entries();
		$entry   = $results['entry'];
		$form    = $results['form'];
		$file    = $gfpdf->data->template_tmp_location . "{$form['id']}{$entry['id']}/test-{$form['id']}.pdf";

		wp_mkdir_p( dirname( $file ) );
		touch( $file );

		$this->assertFileExists( $file );

		$this->model->cleanup_pdf( $entry, $form );

		$this->assertFileNotExists( $file );
	}

	/**
	 * Test our mPDF font override is working correctly
	 *
	 * @since 4.0
	 */
	public function test_set_current_pdf_font() {
		global $gfpdf;

		/* Check our alternate font location is bypassed */
		@unlink( $gfpdf->data->template_font_location . 'font' );
		$this->assertEquals( ABSPATH . 'font', $this->model->set_current_pdf_font( ABSPATH . 'font', 'font' ) );

		/* Create the file and ensure it isn't bypassed */
		touch( $gfpdf->data->template_font_location . 'font' );
		$this->assertEquals( $gfpdf->data->template_font_location . 'font', $this->model->set_current_pdf_font( ABSPATH . 'font', 'font' ) );
	}

	/**
	 * Test our custom fonts are registering correctly
	 *
	 * @since 4.0
	 */
	public function test_register_custom_font_data_with_mPDF() {
		global $gfpdf;

		/* Check our data is being returned correctly */
		$this->assertSame( 2, sizeof( $this->model->register_custom_font_data_with_mPDF( [ '1', '2' ] ) ) );

		/* Add font data to test */
		$fonts = [
			[
				'font_name'   => 'Arial',
				'regular'     => 'arial',
				'bold'        => 'arialB',
				'italics'     => 'arialI',
				'bolditalics' => 'arialBI',
			],

			[
				'font_name'   => 'Courier',
				'regular'     => 'courier',
				'bold'        => '',
				'italics'     => '',
				'bolditalics' => '',
			],
		];

		$gfpdf->options->update_option( 'custom_fonts', $fonts );

		/* Check the results are accurate */
		$results = $this->model->register_custom_font_data_with_mPDF( [ '1', '2' ] );
		$this->assertSame( 4, sizeof( $results ) );

		$this->assertArrayHasKey( 'R', $results['arial'] );
		$this->assertArrayHasKey( 'B', $results['arial'] );
		$this->assertArrayHasKey( 'I', $results['arial'] );
		$this->assertArrayHasKey( 'BI', $results['arial'] );

		$this->assertEquals( 'arial', $results['arial']['R'] );
		$this->assertEquals( 'arialB', $results['arial']['B'] );
		$this->assertEquals( 'arialI', $results['arial']['I'] );
		$this->assertEquals( 'arialBI', $results['arial']['BI'] );
	}

	/**
	 * Check that any unregistred fonts will be autoloaded into mPDF
	 *
	 * @since 4.0
	 */
	public function test_add_unregistered_fonts_to_mPDF() {
		global $gfpdf;

		touch( $gfpdf->data->template_font_location . 'calibri.ttf' );
		touch( $gfpdf->data->template_font_location . 'aladin.otf' );

		$fonts = $this->model->add_unregistered_fonts_to_mPDF( [] );

		$this->assertArrayHasKey( 'calibri', $fonts );
		$this->assertArrayNotHasKey( 'aladin', $fonts );
	}

	/**
	 * Test that our field exists
	 *
	 * @since 4.0
	 */
	public function test_check_field_exists() {

		/* Setup some test data */
		$results = $this->create_form_and_entries();
		$form    = $results['form'];

		$this->assertTrue( $this->model->check_field_exists( 'text', $form ) );
		$this->assertFalse( $this->model->check_field_exists( 'house', $form ) );
	}

	/**
	 * Check we are replacing the array key correctly
	 *
	 * @since 4.0
	 */
	public function test_replace_key() {

		$array = [
			'item' => 'value',
		];

		/* Check the array remains untouched when the key and replacement key are the same */
		$results = $this->model->replace_key( $array, 'item', 'item' );

		$this->assertSame( 1, sizeof( $results ) );
		$this->assertEquals( 'value', $results['item'] );

		/* Replace the array key and verify the results */
		$results = $this->model->replace_key( $array, 'item', 'donkey' );

		$this->assertSame( 1, sizeof( $results ) );
		$this->assertEquals( 'value', $results['donkey'] );

	}

	/**
	 * Check the correct field class is being called
	 *
	 * @since 4.0
	 */
	public function test_get_field_class() {
		global $gfpdf;

		/* Setup some test data */
		$results   = $this->create_form_and_entries();
		$form      = $results['form'];
		$entry     = $results['entry'];
		$products  = new Field_Products( new GF_Field(), $entry, $gfpdf->gform, $gfpdf->misc );
		$namespace = 'GFPDF\Helper\Fields\\';

		$expected = [
			1  => $namespace . 'Field_Text',
			2  => $namespace . 'Field_Textarea',
			3  => $namespace . 'Field_Select',
			4  => $namespace . 'Field_Multiselect',
			5  => $namespace . 'Field_Number',
			6  => $namespace . 'Field_Checkbox',
			7  => $namespace . 'Field_Radio',
			8  => $namespace . 'Field_Hidden',
			9  => $namespace . 'Field_Html',
			10 => $namespace . 'Field_Section',
			11 => $namespace . 'Field_Name',
			12 => $namespace . 'Field_Date',
			13 => $namespace . 'Field_Time',
			14 => $namespace . 'Field_Phone',
			15 => $namespace . 'Field_Address',
			16 => $namespace . 'Field_Website',
			17 => $namespace . 'Field_Email',
			18 => $namespace . 'Field_Fileupload',
			19 => $namespace . 'Field_Fileupload',
			20 => $namespace . 'Field_List',
			21 => $namespace . 'Field_List',
			22 => $namespace . 'Field_Poll',
			23 => $namespace . 'Field_Poll',
			41 => $namespace . 'Field_Poll',
			24 => $namespace . 'Field_Quiz',
			42 => $namespace . 'Field_Quiz',
			43 => $namespace . 'Field_Quiz',
			25 => $namespace . 'Field_Signature',
			26 => $namespace . 'Field_Survey',
			27 => $namespace . 'Field_Survey',
			44 => $namespace . 'Field_Survey',
			45 => $namespace . 'Field_Survey',
			46 => $namespace . 'Field_Survey',
			47 => $namespace . 'Field_Survey',
			48 => $namespace . 'Field_Survey',
			49 => $namespace . 'Field_Survey',
			50 => $namespace . 'Field_Survey',
			28 => $namespace . 'Field_Post_Title',
			29 => $namespace . 'Field_Post_Excerpt',
			30 => $namespace . 'Field_Post_Tags',
			31 => $namespace . 'Field_Post_Category',
			32 => $namespace . 'Field_Post_Image',
			33 => $namespace . 'Field_Post_Custom_Field',
			34 => $namespace . 'Field_Product',
			35 => $namespace . 'Field_Product',
			51 => $namespace . 'Field_Product',
			52 => $namespace . 'Field_Product',
			53 => $namespace . 'Field_Product',
			54 => $namespace . 'Field_Product',
			36 => $namespace . 'Field_Quantity',
			37 => $namespace . 'Field_Option',
			38 => $namespace . 'Field_Option',
			39 => $namespace . 'Field_Shipping',
			40 => $namespace . 'Field_Total',
			41 => $namespace . 'Field_Poll',
			42 => $namespace . 'Field_Quiz',
			78 => $namespace . 'Field_Post_Custom_Field',
			81 => $namespace . 'Field_Post_Custom_Field',
		];

		foreach ( $form['fields'] as $field ) {
			$this->assertEquals( $expected[ $field->id ], get_class( $this->model->get_field_class( $field, $form, $entry, $products ) ) );
		}

		/* Check our fallback class */
		$this->assertEquals( $namespace . 'Field_Default', get_class( $this->model->get_field_class( new GF_Field(), $form, $entry, $products ) ) );

	}

	/**
	 * Check our legacy configuration is being loaded correctly
	 *
	 * @since 4.0
	 */
	public function test_get_legacy_config() {

		/* Setup some test data */
		$results = $this->create_form_and_entries();
		$form    = $results['form'];

		/* Test our aid legacy PDF selector is working */
		$config = [
			'fid'      => $form['id'],
			'aid'      => 3,
			'template' => 'Gravity Forms Style',
		];

		$pid = $this->model->get_legacy_config( $config );
		$this->assertEquals( 'fawf90c678523b', $pid );

		/* Test our fallback works */
		unset( $config['aid'] );

		$pid = $this->model->get_legacy_config( $config );
		$this->assertEquals( '555ad84787d7e', $pid );
	}

	/**
	 * Test that we can successfully get the template filename
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_get_template_filename
	 */
	public function test_get_template_filename( $expected, $template ) {
		$this->assertEquals( $expected, $this->view->get_template_filename( $template ) );
	}

	/**
	 * Our data provider for getting View_PDF::get_template_filename()
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function provider_get_template_filename() {
		return [
			[ 'my-pdf-document.php', 'my-pdf-document' ],
			[ 'hello-world.ph.php', 'hello-world.ph' ],
			[ 'gravitypdf.php', 'gravitypdf.php' ],
			[ 'assimilate.p.php', 'assimilate.p' ],
			[ 'groundhog..php', 'groundhog.' ],
		];
	}

	/**
	 * Check that we're correctly process a valid HTML structure
	 *
	 * @since 4.0
	 */
	public function test_process_html_structure() {

		$results = $this->create_form_and_entries();
		$entry   = $results['entry'];

		$html = $this->view->process_html_structure( $entry, $this->model, [ 'meta' => [ 'echo' => false ] ] );

		$this->assertNotFalse( strpos( $html, '<td class="grandtotal_amount totals">' ) );
	}

	/**
	 * Check our main html structure generator works correctly
	 *
	 * @since 4.0
	 */
	public function test_generate_html_structure() {
		$results = $this->create_form_and_entries();
		$entry   = $results['entry'];

		ob_start();
		$this->view->generate_html_structure( $entry, $this->model, [] );
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, '<td class="grandtotal_amount totals">' ) );
	}

	/**
	 * @since 4.2
	 */
	public function test_field_middle_exclude() {
		$field           = new GF_Field();
		$field->cssClass = 'exclude';
		$config          = [ 'meta' => [ 'exclude' => false ] ];

		$results = $this->model->field_middle_exclude( false, $field, [], [], $config );
		$this->assertFalse( $results );

		$results = $this->model->field_middle_exclude( false, $field, [], [], [] );
		$this->assertTrue( $results );
	}

	/**
	 * @since 4.2
	 */
	public function test_field_middle_conditional_fields() {
		$field     = new GF_Field();
		$field->id = 2;
		$config    = [ 'meta' => [ 'conditional' => false ] ];

		$results = $this->model->field_middle_conditional_fields( false, $field, [], [], $config );
		$this->assertFalse( $results );

		GFCache::set( 'GFFormsModel::is_field_hidden_1_2', true );
		$results = $this->model->field_middle_conditional_fields( false, $field, [], [ 'id' => 1 ], [] );
		$this->assertTrue( $results );
	}

	/**
	 * @since 4.2
	 */
	public function test_field_middle_product_fields() {
		$field       = new GF_Field();
		$field->id   = 2;
		$field->type = 'product';
		$config      = [ 'meta' => [ 'individual_products' => true ] ];

		$results = $this->model->field_middle_product_fields( false, $field, [], [], $config );
		$this->assertFalse( $results );

		$results = $this->model->field_middle_product_fields( false, $field, [], [ 'id' => 1 ], [] );
		$this->assertTrue( $results );
	}

	/**
	 * @since 4.2
	 */
	public function test_field_middle_html_fields() {
		$field       = new GF_Field();
		$field->id   = 2;
		$field->type = 'html';
		$config      = [ 'meta' => [ 'html_field' => true ] ];

		$results = $this->model->field_middle_html_fields( false, $field, [], [], $config );
		$this->assertFalse( $results );

		$results = $this->model->field_middle_html_fields( false, $field, [], [ 'id' => 1 ], [] );
		$this->assertTrue( $results );
	}

	/**
	 * @since 4.2
	 */
	public function test_field_middle_blacklist() {
		$field       = new GF_Field();
		$field->type = 'html';

		$results = $this->model->field_middle_blacklist( false, $field, [], [], [], null, [] );
		$this->assertFalse( $results );

		$results = $this->model->field_middle_blacklist( false, $field, [], [], [], null, [ 'html' ] );
		$this->assertTrue( $results );
	}

	/**
	 * Test a single field and check if the results are valid
	 *
	 * @since 4.0
	 */
	public function test_process_field() {

		global $gfpdf;

		$results  = $this->create_form_and_entries();
		$form     = $results['form'];
		$entry    = $results['entry'];
		$field    = $form['fields'][0];
		$products = new Field_Products( new GF_Field(), $entry, $gfpdf->gform, $gfpdf->misc );

		/* Check for standard output */
		GFCache::flush();
		ob_start();
		$this->view->process_field( $field, $entry, $form, [], $products, new Helper_Field_Container(), $this->model );
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, '<div class="value">My Single Line Response</div>' ) );

		/* Check for empty output */
		GFCache::flush();
		$entry[1] = '';

		ob_start();
		$this->view->process_field( $field, $entry, $form, [], $products, new Helper_Field_Container(), $this->model );
		$html = ob_get_clean();

		$this->assertTrue( empty( $html ) );

		/* Enable showing empty fields */
		$config['meta']['empty'] = true;

		ob_start();
		$this->view->process_field( $field, $entry, $form, $config, $products, new Helper_Field_Container(), $this->model );
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, '<div class="value">&nbsp;</div>' ) );
	}

	/**
	 * Test if the form title should be displayed
	 *
	 * @since 4.0
	 */
	public function test_show_form_title() {

		$form['title'] = 'Form Title';

		/* Ensure a false reading */
		ob_start();
		$this->view->show_form_title( false, $form );
		$html = ob_get_clean();

		$this->assertFalse( strpos( $html, '<h3 id="form_title">' ) );

		/* Ensure a positive reading */
		ob_start();
		$this->view->show_form_title( true, $form );
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, '<h3 id="form_title">' ) );
	}

	/**
	 * Test if we should be displaying the page name
	 *
	 * @since 4.0
	 */
	public function test_display_page_name() {
		$form = [
			'pagination' => [
				'pages' => [
					1 => 'My Test Page',
				],
			],
		];

		ob_start();
		$this->view->display_page_name( 1, $form, new Helper_Field_Container() );
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, '<h3 class="gfpdf-page' ) );

		ob_start();
		$this->view->display_page_name( 2, $form, new Helper_Field_Container() );
		$html = ob_get_clean();

		$this->assertFalse( strpos( $html, '<h3 class="gfpdf-page' ) );
	}

	/**
	 * Check that our backwards compatibility filters work as expected
	 *
	 * @since 4.0
	 */
	public function test_apply_backwards_compatibility_filters() {
		$entry            = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];
		$entry['form_id'] = $GLOBALS['GFPDF_Test']->form['all-form-fields']['id'];

		$settings = [
			'filename'        => 'My PDF Document',
			'template'        => 'zadani',
			'orientation'     => 'portrait',
			'security'        => 'Yes',
			'privileges'      => [ 'print' ],
			'password'        => 'fjai2i0ra0if',
			'master_password' => 'A@490fkfkff',
			'rtl'             => 'No',
		];

		/* Test everything passes back the same */
		$this->assertSame( 0, sizeof( array_diff( $settings, $this->model->apply_backwards_compatibility_filters( $settings, $entry ) ) ) );

		/* Add filters to manipulate the data */
		add_filter( 'gfpdfe_pdf_name', function ( $item ) {
			return 'big-document.pdf';
		} );

		add_filter( 'gfpdfe_template', function ( $item ) {
			return 'default-template.php';
		} );

		add_filter( 'gfpdf_orientation', function ( $item ) {
			return 'landscape';
		} );

		add_filter( 'gfpdf_security', function ( $item ) {
			return false;
		} );

		add_filter( 'gfpdf_privilages', function ( $item ) {
			return [ 'print', 'print-highres' ];
		} );

		add_filter( 'gfpdf_password', function ( $item ) {
			return 'pass';
		} );

		add_filter( 'gfpdf_master_password', function ( $item ) {
			return '';
		} );

		add_filter( 'gfpdf_rtl', function ( $item ) {
			return true;
		} );

		$test = $this->model->apply_backwards_compatibility_filters( $settings, $entry );

		$this->assertEquals( 'big-document', $test['filename'] );
		$this->assertEquals( 'default-template', $test['template'] );
		$this->assertEquals( 'landscape', $test['orientation'] );
		$this->assertEquals( 'No', $test['security'] );
		$this->assertEquals( 2, sizeof( $test['privileges'] ) );
		$this->assertEquals( 'pass', $test['password'] );
		$this->assertEquals( '', $test['master_password'] );
		$this->assertEquals( 'Yes', $test['rtl'] );
	}

	/**
	 * Check that our PDF settings get preprocessed correctly
	 *
	 * @since 4.0
	 */
	public function test_preprocess_template_arguments() {

		/* Setup the testing data */
		$args = [
			'settings' => [
				'header'       => '<img src="test.png" class="my-class" />',
				'first_header' => '<span>Working</span> <img src="going.jpg" width="150" /> <span>Other Stuff</span>',
				'footer'       => '<strong>Footer</strong>',
				'first_footer' => '<img src="/this/is/my/path/image.gif" class="class1 class2" />',
				'other_value'  => 'testing',
			],
		];

		$results = $this->model->preprocess_template_arguments( $args );

		/* Test the results */
		$this->assertNotFalse( strpos( $results['settings']['header'], '<img src=' ) );
		$this->assertNotFalse( strpos( $results['settings']['header'], 'class="my-class header-footer-img"' ) );
		$this->assertNotFalse( strpos( $results['settings']['first_header'], 'class="header-footer-img"' ) );
		$this->assertFalse( strpos( $results['settings']['first_header'], 'width="150"' ) );
		$this->assertNotFalse( strpos( $results['settings']['first_header'], '<img src=' ) );

		$this->assertFalse( strpos( $results['settings']['footer'], 'class="my-class header-footer-img"' ) );
		$this->assertNotFalse( strpos( $results['settings']['first_footer'], 'class="class1 class2 header-footer-img"' ) );
		$this->assertNotFalse( strpos( $results['settings']['first_footer'], '<img src=' ) );

		$this->assertEquals( 'testing', $results['settings']['other_value'] );

		/* Test non-related array */
		$args = [ 'other_array' ];

		$results = $this->model->preprocess_template_arguments( $args );

		$this->assertEquals( 'other_array', $args[0] );
	}

	/**
	 * Verify our core HTML output is accurate for the input settings we include
	 *
	 * @since 4.0
	 */
	public function test_core_template_options() {

		/* Setup the test data */
		$settings = [
			'font'        => 'Arial',
			'font_colour' => '#CCC',
			'font_size'   => '12',

			'header'       => 'This is my header',
			'first_header' => 'This is the first header',

			'footer'       => 'This is the footer',
			'first_footer' => 'This is the first footer',

			'background_image' => '/path/image.png',
			'background_color' => 'red',
		];

		ob_start();
		$this->view->core_template_styles( [ 'settings' => $settings ] );
		$results = ob_get_clean();

		/* Test the results */
		$this->assertNotFalse( strpos( $results, 'font-family: Arial, sans-serif;' ) );
		$this->assertNotFalse( strpos( $results, 'font-size: 12pt;' ) );
		$this->assertNotFalse( strpos( $results, 'color: #CCC' ) );

		$this->assertNotFalse( strpos( $results, 'header: html_TemplateHeader' ) );
		$this->assertNotFalse( strpos( $results, 'footer: html_TemplateFooter' ) );
		$this->assertNotFalse( strpos( $results, 'header: html_TemplateFirstHeader' ) );
		$this->assertNotFalse( strpos( $results, 'footer: html_TemplateFirstFooter' ) );

		$this->assertNotFalse( strpos( $results, 'This is my header' ) );
		$this->assertNotFalse( strpos( $results, 'This is the first header' ) );

		$this->assertNotFalse( strpos( $results, 'This is the footer' ) );
		$this->assertNotFalse( strpos( $results, 'This is the first footer' ) );

		$this->assertNotFalse( strpos( $results, 'background-image: url(/path/image.png) no-repeat 0 0;' ) );
		$this->assertNotFalse( strpos( $results, 'background-image-resize: 4;' ) );

		$this->assertNotFalse( strpos( $results, 'background-color: red;' ) );
	}

	/**
	 * Check that our backwards compatible Tier 2 add-on works as expected
	 *
	 * @since 4.0
	 */
	public function test_handle_legacy_tier_2_processing() {
		global $gfpdf;

		$settings = [ 'template' => 'zadani' ];
		$entry     = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];
		$form      = $gfpdf->gform->get_form( $entry['form_id'] );
		$model_pdf = \GPDFAPI::get_mvc_class( 'Model_PDF' );

		$args = $gfpdf->templates->get_template_arguments(
			$form,
			$gfpdf->misc->get_fields_sorted_by_id( $form['id'] ),
			$entry,
			$model_pdf->get_form_data( $entry ),
			$settings,
			$gfpdf->templates->get_config_class( $settings['template'] ),
			$gfpdf->misc->get_legacy_ids( $entry['id'], $settings )
		);

		$pdf = new Helper_PDF( [ 'id'      => 1,
		                         'form_id' => 1,
		], $settings, $gfpdf->gform, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );
		$pdf->set_template();
		$pdf->set_output_type( 'save' );

		$this->assertFalse( $this->model->handle_legacy_tier_2_processing( $pdf, $entry, $settings, $args ) );

		/* Set a filter and ensure the test passes */
		add_filter( 'gfpdfe_pre_load_template', function ( $form_id ) {
			return true;
		} );

		$this->assertTrue( $this->model->handle_legacy_tier_2_processing( $pdf, $entry, $settings, $args ) );
	}
}
