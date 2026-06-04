<?php

declare( strict_types=1 );

namespace GFPDF\Model;
use Exception;
use GF_Field;
use GFAPI;
use GFCache;
use GFPDF\Controller\Controller_PDF;
use GFPDF\Helper\Fields\Field_Products;
use GFPDF\Helper\Helper_Field_Container;
use GFPDF\Helper\Helper_PDF;
use GFPDF\Helper\Helper_Url_Signer;
use GFPDF\Model\Model_PDF;
use GFPDF\Plugins\DeveloperToolkit\Loader\Helper;
use GFPDF\Statics\Cache;
use GFPDF\View\View_PDF;
use GPDFAPI;
use ReflectionMethod;
use WP_Error;
use GFPDF\Tests\Integration\TestCase;

/**
 * Test Gravity PDF Endpoint Functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the model / view / controller for the PDF Endpoint functionality
 *
 * @since 4.0
 * @group pdf
 */
class Test_PDF extends TestCase {

	/**
	 * Our Settings Controller
	 *
	 * @var Controller_PDF
	 *
	 * @since 4.0
	 */
	public $controller;

	/**
	 * Our Settings Model
	 *
	 * @var Model_PDF
	 *
	 * @since 4.0
	 */
	public $model;

	/**
	 * Our Settings View
	 *
	 * @var View_PDF
	 *
	 * @since 4.0
	 */
	public $view;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
		static::copy_test_fonts();
	}

	public static function tear_down_after_class(): void {
		static::remove_test_fonts();
		parent::tear_down_after_class();
	}

	public function set_up(): void {
		global $gfpdf, $wp_settings_errors;

		/* run parent method */
		parent::set_up();

		/*
		 * Clear state that other tests leak and that interferes with the signed URL middleware:
		 *  - $_GET[page/subview] flips is_gfpdf_page() true, which makes get_settings() consult the transient.
		 *  - The gfpdf_settings_user_data transient is cleared by the base TestCase::set_up().
		 *  - $wp_settings_errors carrying over derails update_settings()'s sanitize branching.
		 *  - Re-sync the in-memory cache with the DB. Older Test_Uninstaller runs wiped the gfpdf_settings
		 *    option but left $gfpdf->options->settings populated; Test_Uninstaller now restores in
		 *    tear_down_after_class(), but the defensive reload here keeps Test_PDF resilient to
		 *    future contamination sources.
		 */
		unset( $_GET['page'], $_GET['subview'] );
		$wp_settings_errors = [];
		$gfpdf->options->set_plugin_settings();

		/* Setup our test classes */
		$this->model = new Model_PDF( $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->notices, $gfpdf->templates, new Helper_Url_Signer() );
		$this->view  = new View_PDF( [], $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );

		$this->controller = new Controller_PDF( $this->model, $this->view, $gfpdf->gform, $gfpdf->log, $gfpdf->misc );
		$this->controller->init();
	}

	/**
	 * Check if all the correct actions are applied
	 *
	 * @since 4.0
	 */
	public function test_actions() {
		$this->assertSame( 1, has_action( 'parse_request', [ $this->controller, 'process_legacy_pdf_endpoint' ] ) );
		$this->assertSame( 1, has_action( 'parse_request', [ $this->controller, 'process_pdf_endpoint' ] ) );

		$this->assertSame(
			10,
			has_action(
				'gform_entries_first_column_actions',
				[
					$this->model,
					'view_pdf_entry_list',
				]
			)
		);
		$this->assertSame( 10, has_action( 'gform_after_submission', [ $this->model, 'maybe_save_pdf' ] ) );
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
		$this->assertSame( 15, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_signed_url_access' ] ) );
		$this->assertSame( 20, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_active' ] ) );
		$this->assertSame( 30, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_conditional' ] ) );
		$this->assertSame( 40, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_owner_restriction' ] ) );
		$this->assertSame( 50, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_logged_out_timeout' ] ) );
		$this->assertSame( 60, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_auth_logged_out_user' ] ) );
		$this->assertSame( 70, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_user_capability' ] ) );

		$this->assertSame( 9999, has_filter( 'gform_notification', [ $this->model, 'notifications' ] ) );

		$this->assertSame(
			10,
			has_filter(
				'mpdf_font_data',
				[
					$this->model,
					'register_custom_font_data_with_mPDF',
				]
			)
		);
		$this->assertSame( 20, has_filter( 'mpdf_font_data', [ $this->model, 'add_unregistered_fonts_to_mPDF' ] ) );

		$this->assertSame( 10, has_filter( 'gfpdf_pdf_html_output', [ $gfpdf->gform, 'process_tags' ] ) );
		$this->assertSame( 10, has_filter( 'gfpdf_pdf_html_output', 'do_shortcode' ) );

		$this->assertSame( 10, has_filter( 'gfpdf_template_args', [ $this->model, 'preprocess_template_arguments' ] ) );
		$this->assertSame(
			5,
			has_filter(
				'gfpdf_pdf_html_output',
				[
					$this->view,
					'autoprocess_core_template_options',
				]
			)
		);

		/* Backwards compatibility */
		$this->assertSame( 1, has_filter( 'gfpdfe_pre_load_template', [ 'PDFRender', 'prepare_ids' ] ) );
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
			$this->fail( 'Expected Exception on PDF creation failure was not thrown.' );
		} catch ( Exception $e ) {
			$this->assertSame( 'There was a problem creating the PDF', $e->getMessage() );

			return;
		}
	}

	/**
	 * Ensure our legacy PDF endpoint listener is working correctly
	 *
	 * @since 4.0
	 */
	public function test_process_legacy_pdf_endpoint() {
		$this->setExpectedIncorrectUsage( 'GFPDF\Controller\Controller_PDF::process_legacy_pdf_endpoint');
		$this->setExpectedIncorrectUsage( 'GFPDF\Model\Model_PDF::get_legacy_config');

		/* Force a failure */
		$this->assertNull( $this->controller->process_legacy_pdf_endpoint() );

		/* Test our endpoint is firing correctly */
		$_GET['gf_pdf']   = 1;
		$_GET['fid']      = -1;
		$_GET['lid']      = -1;
		$_GET['template'] = 'test';

		try {
			$results = $this->controller->process_legacy_pdf_endpoint();
			$this->fail( 'Expected Exception on legacy PDF creation failure was not thrown.' );
		} catch ( Exception $e ) {
			$this->assertSame( 'There was a problem creating the PDF', $e->getMessage() );

			return;
		}
	}

	/**
	 * Ensure the correct error message is shown to the user
	 *
	 * @since 4.0
	 */
	public function test_pdf_error() {

		/* pdf_error is private but we do want to verify the different errors are showing to the correct audience without having to go through the public API */
		$method = new ReflectionMethod(
			'\GFPDF\Controller\Controller_PDF',
			'pdf_error'
		);

		if ( version_compare( PHP_VERSION, '8.1', '<' )  ) {
			$method->setAccessible( true );
		}

		/* Ensure our public errors are shown */

		try {
			$error = new WP_Error( 'timeout_expired', 'Expired' );
			$method->invoke( $this->controller, $error );
			$this->fail( 'Expected Exception on timeout_expired error was not thrown.' );
		} catch ( Exception $e ) {
			$this->assertSame( 'Expired', $e->getMessage() );
		}

		try {
			$error = new WP_Error( 'access_denied', 'Denied' );
			$method->invoke( $this->controller, $error );
			$this->fail( 'Expected Exception on access_denied error was not thrown.' );
		} catch ( Exception $e ) {
			$this->assertSame( 'Denied', $e->getMessage() );
		}

		/* Ensure our private errors aren't shown to unauthorised users */
		try {
			$error = new WP_Error( 'other_problem', 'Other' );
			$method->invoke( $this->controller, $error );
			$this->fail( 'Expected Exception on private error for unauthorised user was not thrown.' );
		} catch ( Exception $e ) {
			$this->assertSame( 'There was a problem creating the PDF', $e->getMessage() );
		}

		/* Authorise the current user and check the message is displayed correctly */
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertIsInt( $user_id );
		wp_set_current_user( $user_id );

		try {
			$error = new WP_Error( 'other_problem', 'Other' );
			$method->invoke( $this->controller, $error );
			$this->fail( 'Expected Exception on private error for authorised user was not thrown.' );
		} catch ( Exception $e ) {
			$this->assertSame( 'Other', $e->getMessage() );
		}

		wp_set_current_user( 0 );

	}

	/**
	 * Test if our public access middleware works as expected
	 *
	 * @since 4.0
	 */
	public function test_middle_public_access() {

		/* Check if error correctly triggered */
		$settings = [
			'id'            => 0,
			'public_access' => 'No',
		];

		$this->model->middle_public_access( '', [ 'id' => 0 ], $settings );

		/* Run our Tests */
		$this->assertSame( 20, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_active' ] ) );
		$this->assertSame( 30, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_conditional' ] ) );
		$this->assertSame( 40, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_owner_restriction' ] ) );
		$this->assertSame( 50, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_logged_out_timeout' ] ) );
		$this->assertSame( 60, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_auth_logged_out_user' ] ) );
		$this->assertSame( 70, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_user_capability' ] ) );

		/* Check if setting passes */
		$settings['public_access'] = 'Yes';
		$this->model->middle_public_access( '', [ 'id' => 0 ], $settings );

		$this->assertSame( 20, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_active' ] ) );
		$this->assertSame( 30, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_conditional' ] ) );
		$this->assertFalse( has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_owner_restriction' ] ) );
		$this->assertFalse( has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_logged_out_timeout' ] ) );
		$this->assertFalse( has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_auth_logged_out_user' ] ) );
		$this->assertFalse( has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_user_capability' ] ) );

	}

	/**
	 * Test the URL signing middleware works as expected
	 *
	 * @since 5.1
	 */
	public function test_middle_signed_url_access() {
		/* Setup some test data */
		$results          = $this->form_and_entry();
		$entry            = $results['entry'];
		$entry['form_id'] = $results['form']['id'];
		$options          = GPDFAPI::get_options_class();
		$_SERVER['HTTP_HOST'] = str_replace( [ 'http://', 'http://' ], '', home_url() );

		/* Test it does nothing by default */
		$this->model->middle_signed_url_access( '', [ 'id' => 0 ], [ 'id' => '' ] );

		$this->assertSame( 20, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_active' ] ) );
		$this->assertSame( 30, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_conditional' ] ) );
		$this->assertSame( 40, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_owner_restriction' ] ) );
		$this->assertSame( 50, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_logged_out_timeout' ] ) );
		$this->assertSame( 60, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_auth_logged_out_user' ] ) );
		$this->assertSame( 70, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_user_capability' ] ) );

		/* Generate a signed URL and verify it validates */
		$url = do_shortcode( '[gravitypdf id="556690c67856b" entry="' . $entry['id'] . '" raw="1" signed="1"]' );
		$options->set_plugin_settings();
		$_GET['expires']        = '';
		$_GET['signature']      = '';
		$_SERVER['REQUEST_URI'] = str_replace( home_url(), '', $url );

		$this->model->middle_signed_url_access( '', [ 'id' => 0 ], [ 'id' => '' ] );

		$this->assertSame( 20, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_active' ] ) );
		$this->assertSame( 30, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_conditional' ] ) );
		$this->assertFalse( has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_owner_restriction' ] ) );
		$this->assertFalse( has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_logged_out_timeout' ] ) );
		$this->assertFalse( has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_auth_logged_out_user' ] ) );
		$this->assertFalse( has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_user_capability' ] ) );
	}

	public function test_multisite_signed_url_access() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped(
				'Not running multisite tests'
			);
		}

		switch_to_blog( self::factory()->blog->create() );

		$form_id  = 0;
		$entry_id = 0;

		try {
			gf_upgrade()->install();

			/* Setup some test data */
			$_SERVER['HTTP_HOST'] = str_replace( [ 'http://', 'http://' ], '', home_url() );
			$results          = $this->form_and_entry();

			$form_id          = $this->gf_factory()->form->create([], $results['form']);
			$entry            = $results['entry'];
			$entry['form_id'] = $form_id;
			$entry_id         = $this->gf_factory()->entry->create($entry);

			$options = GPDFAPI::get_options_class();
			$options->set_plugin_settings();

			$url = do_shortcode( '[gravitypdf id="556690c67856b" entry="' . $entry_id . '" raw="1" signed="1"]' );

			$_GET['expires']   = '';
			$_GET['signature'] = '';

			$protocol = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
			$domain   = $_SERVER['HTTP_HOST'];

			$_SERVER['REQUEST_URI'] = str_replace( $protocol . $domain, '', $url );

			$this->model->middle_signed_url_access( '', [ 'id' => 0 ], [ 'id' => '' ] );

			$this->assertSame( 20, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_active' ] ) );
			$this->assertSame( 30, has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_conditional' ] ) );
			$this->assertFalse( has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_owner_restriction' ] ) );
			$this->assertFalse( has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_logged_out_timeout' ] ) );
			$this->assertFalse( has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_auth_logged_out_user' ] ) );
			$this->assertFalse( has_filter( 'gfpdf_pdf_middleware', [ $this->model, 'middle_user_capability' ] ) );
		} finally {
			if ( $entry_id ) {
				GFAPI::delete_entry( $entry_id );
			}
			if ( $form_id ) {
				GFAPI::delete_form( $form_id );
			}
			restore_current_blog();
		}
	}

	/**
	 * Test if our active PDF middleware works correctly
	 *
	 * @since 4.0
	 */
	public function test_middle_active() {

		/* Check if error correctly triggered */
		$settings = [
			'id' => '',
			'active' => false,
		];
		$settings['active'] = false;
		$this->assertInstanceOf( \WP_Error::class, $this->model->middle_active( '', [ 'id' => 0 ], $settings ) );

		/* Check if setting passes */
		$settings['active'] = true;
		$this->assertTrue( $this->model->middle_active( true, [ 'id' => 0 ], $settings ) );
	}

	/**
	 * Test if our conditional logic middleware works correctly
	 *
	 * @since 4.0
	 */
	public function test_middle_conditional() {

		/* Setup some test data */
		$results          = $this->form_and_entry();
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

		$this->assertInstanceOf( \WP_Error::class, $this->model->middle_conditional( true, $entry, $settings ) );
	}

	/**
	 * Check if correct GF entry owner is determined
	 *
	 * @since 4.0
	 */
	public function test_is_current_pdf_owner() {
		/* set up a user to test its privilages */
		$user_id = $this->factory->user->create();
		$this->assertIsInt( $user_id );
		wp_set_current_user( $user_id );

		/* Set up a blank entry array */
		$entry = [
			'id' =>         0,
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
		$entry['ip']            = '197.64.12.40';
		$_SERVER['REMOTE_ADDR'] = $entry['ip'];

		$this->assertTrue( $this->model->is_current_pdf_owner( $entry ) );
		$this->assertTrue( $this->model->is_current_pdf_owner( $entry, 'logged_out' ) );
		$this->assertFalse( $this->model->is_current_pdf_owner( $entry, 'logged_in' ) );

		/* IP matches server */
		$entry['ip']            = '10.0.0.1';
		$_SERVER['SERVER_ADDR'] = $entry['ip'];
		$_SERVER['REMOTE_ADDR'] = '10.0.0.10';

		$this->assertFalse( $this->model->is_current_pdf_owner( $entry ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Check if our logged out restrictions are being applied correctly
	 *
	 * @since 4.0
	 */
	public function test_middle_owner_restriction() {
		$this->assertTrue( $this->model->middle_owner_restriction( true, [ 'id' => 0 ], [ 'id' => '', 'restrict_owner' => 'No' ] ) );
		$this->assertInstanceOf( \WP_Error::class, $this->model->middle_owner_restriction( new WP_Error( '' ), [ 'id' => 0 ], [  'id' => '', 'restrict_owner' => 'No' ] ) );

		/* test if we are redirecting */
		try {
			wp_set_current_user( 0 );
			$this->model->middle_owner_restriction( true, [ 'id' => 0 ], [  'id' => '', 'restrict_owner' => 'Yes' ] );
			$this->fail( 'Expected Exception on owner restriction redirect was not thrown.' );
		} catch ( Exception $e ) {
			$this->assertSame( 'Redirecting', $e->getMessage() );
		}

		/* Test if logged in users are ignored */
		$user_id = $this->factory->user->create();
		$this->assertIsInt( $user_id );
		wp_set_current_user( $user_id );
		$this->assertTrue( $this->model->middle_owner_restriction( true, [ 'id' => 0 ], [  'id' => '', 'restrict_owner' => 'Yes' ] ) );

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
			'id' => 0,
			'date_created' => gmdate( 'Y-m-d H:i:s', strtotime( '-32 minutes' ) ),
			'ip'           => '197.64.12.40',
		];

		$_SERVER['REMOTE_ADDR'] = $entry['ip'];

		/* Test we get a timeout error */
		$results = $this->model->middle_logged_out_timeout( true, $entry, [ 'id' => '', ] );
		$this->assertInstanceOf( \WP_Error::class, $results );
		$this->assertSame( 'timeout_expired', $results->get_error_code() );

		/* Test we get a auth redirect */
		$entry['created_by'] = 5;

		try {
			$this->model->middle_logged_out_timeout( true, $entry, [ 'id' => '', ] );
			$this->fail( 'Expected Exception on logged-out timeout redirect was not thrown.' );
		} catch ( Exception $e ) {
			$this->assertSame( 'Redirecting', $e->getMessage() );
		}

		/* Update timeout settings and check again */
		$gfpdf->options->update_option( 'logged_out_timeout', '33' );
		$this->assertTrue( $this->model->middle_logged_out_timeout( true, $entry, [ 'id' => '', ] ) );

		/* Check if the test should be skipped */
		$_SERVER['REMOTE_ADDR'] = '12.123.123.124';
		$this->assertTrue( $this->model->middle_logged_out_timeout( true, $entry, [ 'id' => '', ] ) );
		$this->assertInstanceOf( \WP_Error::class, $this->model->middle_logged_out_timeout( new WP_Error(), $entry, [ 'id' => '', ] ) );

		$user_id = $this->factory->user->create();
		$this->assertIsInt( $user_id );
		wp_set_current_user( $user_id );
		$this->assertTrue( $this->model->middle_logged_out_timeout( true, $entry, [ 'id' => '', ] ) );

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
			'id' => 0,
			'ip' => '197.64.12.40',
		];

		/* Check for WP Error */
		$this->assertInstanceOf( \WP_Error::class, $this->model->middle_auth_logged_out_user( true, $entry, [ 'id' => '', ] ) );

		/* Check for redirect */
		$entry['created_by'] = 5;

		try {
			$this->model->middle_auth_logged_out_user( true, $entry, [ 'id' => '', ] );
			$this->fail( 'Expected Exception on logged-out auth redirect was not thrown.' );
		} catch ( Exception $e ) {
			$this->assertSame( 'Redirecting', $e->getMessage() );
		}

		/* Test that the middleware is skipped */
		$_SERVER['REMOTE_ADDR'] = $entry['ip'];
		$this->assertTrue( $this->model->middle_auth_logged_out_user( true, $entry, [ 'id' => '', ] ) );

		unset( $_SERVER['REMOTE_ADDR'] );
		$user_id = $this->factory->user->create();
		$this->assertIsInt( $user_id );
		wp_set_current_user( $user_id );
		$this->assertTrue( $this->model->middle_auth_logged_out_user( true, $entry, [ 'id' => '', ] ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Check if our logged-in user has access to our PDF
	 *
	 * @since 4.0
	 */
	public function test_middle_user_capability() {
		/* Check for WP Error */
		$this->assertInstanceOf( \WP_Error::class, $this->model->middle_user_capability( new WP_Error(), [ 'id' => 0, ], [ 'id' => '', ] ) );

		/* create subscriber and test access */
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		/* get the results */
		$results = $this->model->middle_user_capability( true, [ 'id' => 0,  'created_by' => 0 ], [ 'id' => '', ] );

		$this->assertInstanceOf( \WP_Error::class, $results );
		$this->assertSame( 'access_denied', $results->get_error_code() );

		/* make subscriber owner of the entry and test access */
		$this->assertTrue( $this->model->middle_user_capability( true, [ 'id' => 0,  'created_by' => $user_id ], [ 'id' => '', ] ) );

		/* make subscriber owner, but turn on the owner restrict setting and test access */
		$results = $this->model->middle_user_capability( true, [ 'id' => 0,  'created_by' => $user_id ], [ 'id' => '', 'restrict_owner' => 'Yes' ] );

		$this->assertInstanceOf( \WP_Error::class, $results );
		$this->assertSame( 'access_denied', $results->get_error_code() );

		/* Elevate user to administrator */
		$user = wp_get_current_user();
		$user->remove_role( 'subscriber' );
		$user->add_role( 'administrator' );

		$this->assertTrue( $this->model->middle_user_capability( true, [ 'id' => 0, 'created_by' => 0 ], [ 'id' => '', 'restrict_owner' => 'Yes'  ] ) );

		/* Remove elevated user privileges and set the default capability 'gravityforms_view_entries' */
		$user->remove_role( 'administrator' );
		$user->add_role( 'subscriber' );

		/* Double check they have been removed */
		$results = $this->model->middle_user_capability( true, [ 'id' => 0, 'created_by' => 0 ], [ 'id' => '', ] );

		$this->assertInstanceOf( \WP_Error::class, $results );
		$this->assertSame( 'access_denied', $results->get_error_code() );

		/* Add default capability and test */
		$user->add_cap( 'gravityforms_view_entries' );
		$user->get_role_caps();
		$user->update_user_level_from_caps();
		$this->assertTrue( $this->model->middle_user_capability( true, [ 'id' => 0, 'created_by' => 0 ], [ 'id' => '', ] ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Check our PDF list is displaying correctly
	 *
	 * @since 4.0
	 */
	public function test_view_pdf_entry_list() {

		$user = $this::factory()->user->create_and_get();
		$user->add_role( 'administrator' );
		wp_set_current_user( $user->ID );

		$results = $this->form_and_entry();
		$form_id = $results['form']['id'];
		$entry   = $results['entry'];

		ob_start();
		$this->model->view_pdf_entry_list( $form_id, '', '', $entry );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'View PDFs</a>', $html );

		wp_set_current_user( 0 );
	}

	/**
	 * Check that an array of PDFs gets correctly returned in the right format
	 *
	 * @since 4.0
	 */
	public function test_get_pdf_display_list() {
		global $wp_rewrite;

		/* Setup some test data */
		$results = $this->form_and_entry();
		$entry   = $results['entry'];

		/*
		 * get_pdf_url() (the URL builder behind get_pdf_display_list) branches
		 * on $wp_rewrite->using_permalinks(), which only reads the structure
		 * string — the rewrite_rules option is irrelevant, so no flush needed.
		 */
		$wp_rewrite->set_permalink_structure( '' );

		$pdfs = $this->model->get_pdf_display_list( $entry );

		$lid = $entry['id'];
		$this->assertStringContainsString( 'test-', $pdfs[0]['name'] );
		$this->assertStringContainsString( "http://example.org/?gpdf=1&pid=556690c67856b&lid=$lid", $pdfs[0]['view'] );
		$this->assertStringContainsString( "http://example.org/?gpdf=1&pid=556690c67856b&lid=$lid&action=download", $pdfs[0]['download'] );

		$wp_rewrite->set_permalink_structure( '/%postname%/' );

		$pdfs = $this->model->get_pdf_display_list( $entry );

		$this->assertStringContainsString( 'http://example.org/pdf/556690c67856b/', $pdfs[0]['view'] );
		$this->assertStringContainsString( '/download/', $pdfs[0]['download'] );

		$wp_rewrite->set_permalink_structure( '' );
	}

	public function test_view_pdf_gravityflow_inbox() {
		global $wp_rewrite;

		$user = $this::factory()->user->create_and_get();
		$user->add_role( 'administrator' );
		wp_set_current_user( $user->ID );

		/* Setup some test data */
		$results = $this->form_and_entry();
		$form    = $results['form'];
		$entry   = $results['entry'];

		/* using_permalinks() only checks the structure string, no flush needed. */
		$wp_rewrite->set_permalink_structure( '' );

		ob_start();
		$this->model->view_pdf_gravityflow_inbox( $form, $entry, [], [] );
		$html = ob_get_clean();

		$lid = $entry['id'];
		$this->assertStringContainsString( "http://example.org/?gpdf=1&#038;pid=fawf90c678523b&#038;lid=$lid", $html );
		$this->assertStringContainsString( "http://example.org/?gpdf=1&#038;pid=fawf90c678523b&#038;lid=$lid&#038;action=download", $html );

		$wp_rewrite->set_permalink_structure( '/%postname%/' );

		ob_start();
		$this->model->view_pdf_gravityflow_inbox( $form, $entry, [], [] );
		$html = ob_get_clean();

		$id = $entry['id'];
		$this->assertStringContainsString( 'http://example.org/pdf/556690c67856b/' . $id . '/', $html );
		$this->assertStringContainsString( 'http://example.org/pdf/556690c67856b/' . $id . '/download/', $html );

		$wp_rewrite->set_permalink_structure( '' );

		wp_set_current_user( 0 );
	}

	/**
	 * Check that our PDF name gets processed correctly
	 * We'll unit test in more detail do_mergetags and strip_invalid_characters separately so just a quick run through here
	 *
	 * @since 4.0
	 */
	public function test_get_pdf_name() {

		/* Setup some test data */
		$results = $this->form_and_entry();
		$form    = $results['form'];
		$entry   = $results['entry'];

		/* Get our active PDFs */
		$pdfs = ( isset( $form['gfpdf_form_settings'] ) ) ? $this->model->get_active_pdfs( $form['gfpdf_form_settings'], $entry ) : [];

		/* Get a PDF configuration */
		$pdf = $pdfs['556690c67856b'];

		/* Check merge tags and being processed */
		$this->assertSame( 'test-' . $form['id'], $this->model->get_pdf_name( $pdf, $entry ) );

		/* Check invalid characters are stripped */
		$pdf['filename'] = 'my/file"name*willbe:great_{form_id}';
		$this->assertSame( 'my_file_name_willbe_great_' . $form['id'], $this->model->get_pdf_name( $pdf, $entry ) );

		/* Check our filters work correctly */

		add_filter(
			'gfpdf_pdf_filename',
			function() {
				return 'filter';
			}
		);

		$this->assertSame( 'filter', $this->model->get_pdf_name( $pdf, $entry ) );

		add_filter(
			'gfpdfe_pdf_filename',
			function() {
				return 'filter';
			}
		);

		$this->assertSame( 'filter', $this->model->get_pdf_name( $pdf, $entry ) );
	}

	/**
	 * Check that the returned PDF URL is correct
	 *
	 * @param $pid
	 * @param $id
	 * @param $download
	 * @param $print
	 * @param $expected
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_get_pdf_url
	 *
	 */
	public function test_get_pdf_url( $pid, $id, $download, $print, $expected ) {
		global $wp_rewrite;

		/* get_pdf_url() branches on using_permalinks(); flush_rewrite_rules() is not required. */
		$old_permalink_structure = get_option( 'permalink_structure' );
		$wp_rewrite->set_permalink_structure( '/%postname%/' );

		$this->assertSame( $expected, $this->model->get_pdf_url( $pid, $id, $download, $print ) );

		$wp_rewrite->set_permalink_structure( $old_permalink_structure );
	}

	/**
	 * The data provider for the test_get_pdf_url() function
	 *
	 * @since 4.0
	 */
	public function provider_get_pdf_url(): array {
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
	 * @param $pid
	 * @param $id
	 * @param $download
	 * @param $print
	 * @param $expected
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_get_pdf_url_no_perma
	 *
	 */
	public function test_get_pdf_url_no_perma( $pid, $id, $download, $print, $expected ) {
		global $wp_rewrite;

		/* Force plain permalinks — get_pdf_url branches on using_permalinks(), and a sibling
		 * test (or multisite's non-empty default) can leave permalink_structure pretty.
		 * flush_rewrite_rules() is not required: using_permalinks() only reads the
		 * structure string. */
		$old_permalink_structure = get_option( 'permalink_structure' );
		$wp_rewrite->set_permalink_structure( '' );

		$this->assertSame( $expected, $this->model->get_pdf_url( $pid, $id, $download, $print ) );

		$wp_rewrite->set_permalink_structure( $old_permalink_structure );
	}

	/**
	 * The data provider for the test_get_pdf_url() function
	 *
	 * @since 4.0
	 */
	public function provider_get_pdf_url_no_perma(): array {
		return [
			[ '240arkj92kda', '50', false, false, 'http://example.org/?gpdf=1&pid=240arkj92kda&lid=50' ],
			[ 'kjoai2', '25', false, false, 'http://example.org/?gpdf=1&pid=kjoai2&lid=25' ],
			[
				'AIfawjoi24012',
				'9992',
				false,
				false,
				'http://example.org/?gpdf=1&pid=AIfawjoi24012&lid=9992',
			],
			[ 'JJiawfafwwaa', '5020', false, false, 'http://example.org/?gpdf=1&pid=JJiawfafwwaa&lid=5020' ],
			[ 'fa2a20koawas', '2', false, false, 'http://example.org/?gpdf=1&pid=fa2a20koawas&lid=2' ],
			[
				'JJiawfafwwaa',
				'5020',
				true,
				false,
				'http://example.org/?gpdf=1&pid=JJiawfafwwaa&lid=5020&action=download',
			],
			[
				'fa2a20koawas',
				'2',
				false,
				true,
				'http://example.org/?gpdf=1&pid=fa2a20koawas&lid=2&print=1',
			],
			[
				'kjoai2',
				'25',
				true,
				true,
				'http://example.org/?gpdf=1&pid=kjoai2&lid=25&action=download&print=1',
			],
			[
				'AIfawjoi24012',
				'9992',
				true,
				true,
				'http://example.org/?gpdf=1&pid=AIfawjoi24012&lid=9992&action=download&print=1',
			],
		];
	}

	/**
	 * Check if we are determining active PDFs correctly
	 *
	 * @param bool $expected
	 * @param array $pdf
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_get_active_pdfs
	 */
	public function test_get_active_pdfs( $expected, $pdf ) {

		/* Setup some test data */
		$results = $this->form_and_entry();
		$entry   = $results['entry'];

		$result = ( $expected ) ? 1 : 0;
		$this->assertSame( $result, count( $this->model->get_active_pdfs( [ $pdf ], $entry ) ) );
	}

	/**
	 * Data provider for test_get_active_pdfs()
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function provider_get_active_pdfs(): array {
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
		$form_class = \GPDFAPI::get_form_class();

		/* Setup some test data */
		$results = $this->form_and_entry();
		$entry   = $results['entry'];
		$form    = $form_class->get_form( $results['form']['id'] );  /* get from the database so the date created is accurate */

		/* Create PDF file so it isn't recreated */
		$path = Cache::get_path( $form, $entry, $form['gfpdf_form_settings']['556690c67856b'] );
		$file   = "test-{$form['id']}.pdf";

		wp_mkdir_p( $path );
		touch( $path . $file );

		$notifications = $this->model->notifications( $form['notifications']['54bca349732b8'], $form, $entry );

		/* Check the results are successful */
		$this->assertSame( $path . $file, $notifications['attachments'][0] );

		/* Clean up */
		unlink( $notifications['attachments'][0] );

		/* Check we don't process an entry not stored in the database */
		$entry['id'] = null;

		$notifications = $this->model->notifications( $form['notifications']['54bca349732b8'], $form, $entry );

		$this->assertArrayNotHasKey( 'attachments', $notifications );
	}

	/**
	 * Check if our PDF exists on disk
	 *
	 * @since 4.0
	 */
	public function test_does_pdf_exist() {
		global $gfpdf;

		$pdf = new Helper_PDF(
			[
				'id'      => 1,
				'form_id' => $this->form( 'all-form-fields' )['id'],
			],
			[ 'id' => '556690c67856b' ],
			$gfpdf->gform,
			$gfpdf->data,
			$gfpdf->misc,
			$gfpdf->templates,
			$gfpdf->log
		);
		$pdf->set_path( '/tmp/' );
		$pdf->set_filename( 'unittest' );

		/* Check that PDF exists */
		touch( '/tmp/unittest.pdf' );
		$this->assertTrue( $this->model->does_pdf_exist( $pdf ) );

		/* Check that PDF does not exist */
		unlink( '/tmp/unittest.pdf' );
		$this->assertFalse( $this->model->does_pdf_exist( $pdf ) );
	}

	/**
	 * Ensure the PDF output setting is correct
	 *
	 * @since 4.0
	 */
	public function test_get_output_type() {
		global $gfpdf;

		$pdf = new Helper_PDF(
			[
				'id'      => 1,
				'form_id' => $this->form( 'all-form-fields' )['id'],
			],
			[ 'id' => '556690c67856b' ],
			$gfpdf->gform,
			$gfpdf->data,
			$gfpdf->misc,
			$gfpdf->templates,
			$gfpdf->log
		);

		$pdf->set_output_type( 'display' );
		$this->assertSame( 'DISPLAY', $pdf->get_output_type() );

		$pdf->set_output_type( 'download' );
		$this->assertSame( 'DOWNLOAD', $pdf->get_output_type() );

		$pdf->set_output_type( 'save' );
		$this->assertSame( 'SAVE', $pdf->get_output_type() );
	}

	/**
	 * Ensure the correct template path is returned
	 *
	 * @since 4.0
	 */
	public function test_get_template_path() {
		global $gfpdf;

		$pdf = new Helper_PDF(
			[
				'id'      => 1,
				'form_id' => $this->form( 'all-form-fields' )['id'],
			],
			[ 'id' => '556690c67856b', 'template' => 'zadani' ],
			$gfpdf->gform,
			$gfpdf->data,
			$gfpdf->misc,
			$gfpdf->templates,
			$gfpdf->log
		);

		/* Cleanup any previous tests */
		@unlink( $gfpdf->data->template_location . 'zadani.php' );

		/* Set our current PDF template */
		$pdf->set_template();

		/* Check our basic struction is correct */
		$this->assertSame( PDF_PLUGIN_DIR . 'src/templates/zadani.php', $pdf->get_template_path() );

		/* Copy the template to our PDF_EXTENDED_TEMPLATES directory and recheck the path */
		copy( PDF_PLUGIN_DIR . 'src/templates/zadani.php', $gfpdf->data->template_location . 'zadani.php' );

		/* Set our current PDF template */
		$pdf->set_template();

		/* Run our new test */
		$this->assertSame( $gfpdf->data->template_location . 'zadani.php', $pdf->get_template_path() );
		@unlink( $gfpdf->data->template_location . 'zadani.php' );

		/* Check the multisite option */
		if ( is_multisite() ) {
			/* Copy the template to our multisite PDF_EXTENDED_TEMPLATES directory and recheck the path */
			copy( PDF_PLUGIN_DIR . 'src/templates/zadani.php', $gfpdf->data->multisite_template_location . 'zadani.php' );

			/* Set our current PDF template */
			$pdf->set_template();

			/* Run our new test */
			$this->assertSame( $gfpdf->data->multisite_template_location . 'zadani.php', $pdf->get_template_path() );
			@unlink( $gfpdf->data->multisite_template_location . 'zadani.php' );
		}

		/* Check for errors */
		$pdf = new Helper_PDF(
			[
				'id'      => 1,
				'form_id' => $this->form( 'all-form-fields' )['id'],
			],
			[
				'id' => '556690c67856b',
				'template' => 'non-existant',
			],
			$gfpdf->gform,
			$gfpdf->data,
			$gfpdf->misc,
			$gfpdf->templates,
			$gfpdf->log
		);

		$caught = null;
		try {
			/* Set our current PDF template */
			$pdf->set_template();
		} catch ( Exception $e ) {
			$caught = $e;
		}
		$this->assertNotNull( $caught, 'Expected Exception on missing template was not thrown.' );
		$this->assertSame( 'Could not find the template: non-existant.php', $caught->getMessage() );

		/* Check for incorrect version requirements */
		$template = file_get_contents( PDF_PLUGIN_DIR . 'src/templates/zadani.php' );
		$template = str_replace( 'Required PDF Version: 4.0-alpha', 'Required PDF Version: 10', $template );
		file_put_contents( $gfpdf->data->template_location . 'zadani.php', $template );

		/* Flush the template-info transient cache so the overwritten file's new version is read. */
		delete_transient( $gfpdf->data->template_transient_cache );

		$pdf = new Helper_PDF(
			[
				'id'      => 1,
				'form_id' => $this->form( 'all-form-fields' )['id'],
			],
			[ 'id' => '556690c67856b', 'template' => 'zadani' ],
			$gfpdf->gform,
			$gfpdf->data,
			$gfpdf->misc,
			$gfpdf->templates,
			$gfpdf->log
		);

		try {
			$caught = null;
			try {
				$pdf->set_template();
			} catch ( Exception $e ) {
				$caught = $e;
			}
			$this->assertNotNull( $caught, 'Expected Exception on incompatible template version was not thrown.' );
			$this->assertSame( sprintf( 'The PDF Template %s requires Gravity PDF version %s. Upgrade to the latest version.', '<em>zadani</em>', '<em>10</em>' ), $caught->getMessage() );
		} finally {
			/* Ensure the version-10 override never survives a failing assertion. */
			@unlink( $gfpdf->data->template_location . 'zadani.php' );
			delete_transient( $gfpdf->data->template_transient_cache );
		}
	}

	/**
	 * Check our tmp directory is being cleaned up correctly
	 *
	 * @since 4.0
	 */
	public function test_cleanup_tmp_dir() {
		global $gfpdf;

		$tmp = $gfpdf->data->template_tmp_location;

		wp_mkdir_p( $gfpdf->data->template_location );
		wp_mkdir_p( $gfpdf->data->mpdf_tmp_location );

		/* Create our files to test */
		$files = [
			'test'      => time(),
			'test1'     => time() - ( 11.5 * 3600 ),
			'test2'     => time() - ( 12.01 * 3600 ),
			'test3'     => time() - ( 12.5 * 3600 ),
			'test4'     => time() - ( 25 * 3600 ),
			'test5'     => time() - ( 15 * 3600 ),
			'test6'     => time() - ( 5 * 3600 ),
			'.htaccess' => time() - ( 48 * 3600 ),
			'mpdf/test' => time() - ( 0.5 * 3600 ),
			'mpdf/test1' => time() - 3601,
			'mpdf/test2' => time() - 3600,
			'mpdf/test3' => time() - ( 25 * 3600 ),

		];

		foreach ( $files as $file => $modified ) {
			touch( $tmp . $file, (int) $modified );
		}

		/* Run our cleanup function and test the output */
		$this->model->cleanup_tmp_dir();

		$this->assertFileExists( $tmp . 'test' );
		$this->assertFileExists( $tmp . 'test1' );
		$this->assertFileDoesNotExist( $tmp . 'test2' );
		$this->assertFileDoesNotExist( $tmp . 'test3' );
		$this->assertFileDoesNotExist( $tmp . 'test4' );
		$this->assertFileDoesNotExist( $tmp . 'test5' );
		$this->assertFileExists( $tmp . 'test6' );
		$this->assertFileExists( $tmp . '.htaccess' );
		$this->assertFileExists( $tmp . 'mpdf/test' );
		$this->assertFileDoesNotExist( $tmp . 'mpdf/test1' );
		$this->assertFileExists( $tmp . 'mpdf/test2' );
		$this->assertFileDoesNotExist( $tmp . 'mpdf/test3' );

		/* Cleanup our files */
		foreach ( $files as $file => $modified ) {
			@unlink( $tmp . $file );
		}
	}

	/**
	 * Check that our PDF is cleaned up after the Gravity Forms entry save process
	 *
	 * @since 4.0
	 */
	public function test_cleanup_pdf() {
		$this->setExpectedIncorrectUsage('GFPDF\Model\Model_PDF::cleanup_pdf');

		$form_class = \GPDFAPI::get_form_class();

		/* Setup some test data */
		$results = $this->form_and_entry();
		$entry   = $results['entry'];
		$form    = $form_class->get_form( $results['form']['id'] );  /* get from the database so the date created is accurate */

		$path = Cache::get_path( $form, $entry, $form['gfpdf_form_settings']['556690c67856b'] );
		$file   = "test-{$form['id']}.pdf";

		wp_mkdir_p( $path );
		touch( $path . $file );

		$this->assertFileExists( $path . $file );

		$this->model->cleanup_pdf( $entry, $form );

		$this->assertFileDoesNotExist( $path . $file );
	}

	/**
	 * Test our custom fonts are registering correctly
	 *
	 * @since 4.0
	 */
	public function test_register_custom_font_data_with_mPDF() {
		global $gfpdf;

		/* Check our data is being returned correctly */
		$this->assertCount( 2, $this->model->register_custom_font_data_with_mPDF( [ '1', '2' ] ) );

		/* Add font data to test */
		$fonts = [
			[
				'id'          => 'arialc',
				'font_name'   => 'Arial',
				'regular'     => 'arial',
				'bold'        => 'arialB',
				'italics'     => 'arialI',
				'bolditalics' => 'arialBI',
			],

			[
				'id'          => 'courierc',
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
		$this->assertCount( 4, $results );

		$this->assertSame( 'arial', $results['arialc']['R'] );
		$this->assertSame( 'arialB', $results['arialc']['B'] );
		$this->assertSame( 'arialI', $results['arialc']['I'] );
		$this->assertSame( 'arialBI', $results['arialc']['BI'] );
	}

	/**
	 * Check that any unregistered fonts will be autoloaded into mPDF
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
		$results = $this->form_and_entry();
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

		$this->assertCount( 1, $results );
		$this->assertSame( 'value', $results['item'] );

		/* Replace the array key and verify the results */
		$results = $this->model->replace_key( $array, 'item', 'donkey' );

		$this->assertCount( 1, $results );
		$this->assertSame( 'value', $results['donkey'] );

	}

	/**
	 * Check the correct field class is being called
	 *
	 * @since 4.0
	 */
	public function test_get_field_class() {
		global $gfpdf;

		/* Setup some test data */
		$results   = $this->form_and_entry();
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
			24 => $namespace . 'Field_Quiz',
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
			$this->assertSame( $expected[ $field->id ], get_class( $this->model->get_field_class( $field, $form, $entry, $products ) ) );
		}

		/* Check config setting/getter */
		$class = $this->model->get_field_class( $field, $form, $entry, $products, [ 'settings' => true ] );
		$this->assertArrayHasKey( 'settings', $class->get_pdf_config() );

		/* Check our fallback class */
		$this->assertSame( $namespace . 'Field_Default', get_class( $this->model->get_field_class( new GF_Field(), $form, $entry, $products ) ) );

		/* Check config setting/getter */
		$class = $this->model->get_field_class( new GF_Field(), $form, $entry, $products, [ 'settings' => true ] );
		$this->assertArrayHasKey( 'settings', $class->get_pdf_config() );
	}

	/**
	 * Check our legacy configuration is being loaded correctly
	 *
	 * @since 4.0
	 */
	public function test_get_legacy_config() {
		$this->setExpectedIncorrectUsage('GFPDF\Model\Model_PDF::get_legacy_config');

		/* Setup some test data */
		$results = $this->form_and_entry();
		$form    = $results['form'];

		/* Test our aid legacy PDF selector is working */
		$config = [
			'fid'      => $form['id'],
			'aid'      => 3,
			'template' => 'Gravity Forms Style',
		];

		$pid = $this->model->get_legacy_config( $config );
		$this->assertSame( 'fawf90c678523b', $pid );

		/* Test our fallback works */
		unset( $config['aid'] );

		$pid = $this->model->get_legacy_config( $config );
		$this->assertSame( '555ad84787d7e', $pid );
	}

	/**
	 * Test that we can successfully get the template filename
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_get_template_filename
	 */
	public function test_get_template_filename( $expected, $template ) {
		$this->setExpectedIncorrectUsage('GFPDF\View\View_PDF::get_template_filename');
		$this->assertSame( $expected, $this->view->get_template_filename( $template ) );
	}

	/**
	 * Our data provider for getting View_PDF::get_template_filename()
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function provider_get_template_filename(): array {
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

		$results = $this->form_and_entry();
		$entry   = $results['entry'];

		$html = $this->view->process_html_structure( $entry, $this->model, [ 'meta' => [ 'echo' => false ] ] );

		$this->assertStringContainsString( '<td class="grandtotal_amount totals">', $html );
	}

	/**
	 * Check our main html structure generator works correctly
	 *
	 * @since 4.0
	 */
	public function test_generate_html_structure() {
		$results = $this->form_and_entry();
		$entry   = $results['entry'];

		ob_start();
		$this->view->generate_html_structure( $entry, $this->model, [] );
		$html = ob_get_clean();

		$this->assertStringContainsString( '<td class="grandtotal_amount totals">', $html );
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

		/* GFCache is a static, in-process store — clear our write so it can't leak to other tests. */
		GFCache::flush();
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

		$results  = $this->form_and_entry();
		$form     = $results['form'];
		$entry    = $results['entry'];
		$field    = $form['fields'][0];
		$products = new Field_Products( new GF_Field(), $entry, $gfpdf->gform, $gfpdf->misc );

		/* Check for standard output */
		GFCache::flush();
		ob_start();
		$this->view->process_field( $field, $entry, $form, [], $products, new Helper_Field_Container(), $this->model );
		$html = ob_get_clean();

		$this->assertStringContainsString( '<div class="value">My Single Line Response</div>', $html );

		/* Check for empty output */
		GFCache::flush();
		$entry[1] = '';

		ob_start();
		$this->view->process_field( $field, $entry, $form, [], $products, new Helper_Field_Container(), $this->model );
		$html = ob_get_clean();

		$this->assertEmpty( $html );

		/* Enable showing empty fields */
		$config['meta']['empty'] = true;

		ob_start();
		$this->view->process_field( $field, $entry, $form, $config, $products, new Helper_Field_Container(), $this->model );
		$html = ob_get_clean();

		$this->assertStringContainsString( '<div class="value">&nbsp;</div>', $html );
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

		$this->assertStringNotContainsString( '<h3 id="form_title">', $html );

		/* Ensure a positive reading */
		ob_start();
		$this->view->show_form_title( true, $form );
		$html = ob_get_clean();

		$this->assertStringContainsString( '<h3 id="form_title">', $html );
	}

	/**
	 * Test if we should be displaying the page name
	 *
	 * @since 4.0
	 */
	public function test_legacy_display_page_name() {
		$form = [
			'pagination' => [
				'pages' => [
					0 => 'My Test Page',
					1 => '',
					2 => 'Other Test Page',
				],
			],
			'fields'     => [
				new \GF_Field_Page( [ 'pageNumber' => 1, 'cssClass' => 'my-test-class' ] ),
				new \GF_Field_Page( [ 'pageNumber' => 2 ] ),
				new \GF_Field_Page( [ 'pageNumber' => 3, 'label' => 'Other Test Page' ] ),
			],
		];

		ob_start();
		$this->view->display_page_name( 0, $form, new Helper_Field_Container() );
		$html = ob_get_clean();

		$this->assertStringContainsString( '<h3 class="gfpdf-page gfpdf-field my-test-class', $html );
		$this->assertStringContainsString( 'My Test Page', $html );

		ob_start();
		$this->view->display_page_name( 1, $form, new Helper_Field_Container() );
		$html = ob_get_clean();

		$this->assertStringNotContainsString( '<h3 class="gfpdf-page', $html );
		$this->assertStringNotContainsString( 'My Test Page', $html );

		/* test new signature */
		ob_start();
		$this->view->display_page_name( 2, $form, new Helper_Field_Container(), $form['fields'][2] );
		$html = ob_get_clean();

		$this->assertStringContainsString( '<h3 class="gfpdf-page', $html );
		$this->assertStringContainsString( 'Other Test Page', $html );
	}

	public function test_page_break_field() {
		global $gfpdf;

		$form = [
			'id' => 1,
			'fields'     => [
				new \GF_Field_Page( [ 'pageNumber' => 1, 'cssClass' => 'my-test-class', 'content' => 'First Page' ] ),
				new \GF_Field_Page( [ 'pageNumber' => 2, 'content' => 'Second Page' ] ),
				new \GF_Field_Page( [ 'pageNumber' => 3, 'content' => 'Third Page' ] ),
			],
		];

		$config = [
			'meta' => [
				'empty' => true,
			]
		];

		$products = new Field_Products( new GF_Field(), [ 'form_id' => $this->form( 'all-form-fields' )['id'] ], $gfpdf->gform, $gfpdf->misc );

		ob_start();
		$this->view->process_field( $form['fields'][0], [ 'form_id' => $this->form( 'all-form-fields' )['id'] ], $form, $config, $products, new Helper_Field_Container(), $this->model );

		$html = ob_get_clean();

		$this->assertStringContainsString('First Page', $html );
		$this->assertStringContainsString('<h3 id="page-no-1"', $html );
		$this->assertStringContainsString('class="gfpdf-field gfpdf-page my-test-class"', $html );
		$this->assertStringContainsString('<div class="row-separator odd">', $html );

		ob_start();
		$this->view->process_field( $form['fields'][1], [ 'form_id' => $this->form( 'all-form-fields' )['id'] ], $form, $config, $products, new Helper_Field_Container(), $this->model );

		$html = ob_get_clean();

		$this->assertStringContainsString('Second Page', $html );

		/* Ensure it disables */
		$config['meta']['empty'] = false;

		ob_start();
		$this->view->process_field( $form['fields'][1], [ 'form_id' => $this->form( 'all-form-fields' )['id'] ], $form, $config, $products, new Helper_Field_Container(), $this->model );

		$html = ob_get_clean();

		$this->assertSame( '', $html );
	}

	/**
	 * Check that our backwards compatibility filters work as expected
	 *
	 * @since 4.0
	 */
	public function test_apply_backwards_compatibility_filters() {
		$entry            = $this->entry( 'all-form-fields' );
		$entry['form_id'] = $this->form( 'all-form-fields' )['id'];

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
		$results = $this->model->apply_backwards_compatibility_filters( $settings, $entry );

		foreach ( $results as $key => $value ) {
			$this->assertArrayHasKey( $key, $settings );
			$this->assertSame( $value, $settings[ $key ] );
		}

		/* Add filters to manipulate the data */
		add_filter(
			'gfpdfe_pdf_name',
			function( $item ) {
				return 'big-document.pdf';
			}
		);

		add_filter(
			'gfpdfe_template',
			function( $item ) {
				return 'default-template.php';
			}
		);

		add_filter(
			'gfpdf_orientation',
			function( $item ) {
				return 'landscape';
			}
		);

		add_filter(
			'gfpdf_security',
			function( $item ) {
				return false;
			}
		);

		add_filter(
			'gfpdf_privilages',
			function( $item ) {
				return [ 'print', 'print-highres' ];
			}
		);

		add_filter(
			'gfpdf_password',
			function( $item ) {
				return 'pass';
			}
		);

		add_filter(
			'gfpdf_master_password',
			function( $item ) {
				return '';
			}
		);

		add_filter(
			'gfpdf_rtl',
			function( $item ) {
				return true;
			}
		);

		$test = $this->model->apply_backwards_compatibility_filters( $settings, $entry );

		$this->assertSame( 'big-document', $test['filename'] );
		$this->assertSame( 'default-template', $test['template'] );
		$this->assertSame( 'landscape', $test['orientation'] );
		$this->assertSame( 'No', $test['security'] );
		$this->assertCount( 2, $test['privileges'] );
		$this->assertSame( 'pass', $test['password'] );
		$this->assertSame( '', $test['master_password'] );
		$this->assertSame( 'Yes', $test['rtl'] );
	}

	/**
	 * Check that our PDF settings get preprocessed correctly
	 *
	 * @since 4.0
	 */
	public function test_preprocess_template_arguments() {

		$data = $this->form_and_entry();

		/* Setup the testing data */
		$args = [
			'settings' => [
				'header'       => '<img src="test.png" class="my-class" />',
				'first_header' => '<span>Working</span> <img src="going.jpg" width="150" /> <span>Other Stuff</span>',
				'footer'       => '<strong>Footer</strong>',
				'first_footer' => '<img src="/this/is/my/path/image.gif" class="class1 class2" />',
				'other_value'  => 'testing',
			],
			'form'     => $data['form'],
			'entry'    => $data['entry'],
		];

		$results = $this->model->preprocess_template_arguments( $args );

		/* Test the results */
		$this->assertStringContainsString( '<img src=', $results['settings']['header'] );
		$this->assertStringContainsString( 'class="my-class header-footer-img"', $results['settings']['header'] );
		$this->assertStringContainsString( 'class="header-footer-img"', $results['settings']['first_header'] );
		$this->assertStringNotContainsString( 'width="150"', $results['settings']['first_header'] );
		$this->assertStringContainsString( '<img src=', $results['settings']['first_header'] );

		$this->assertStringNotContainsString( 'class="my-class header-footer-img"', $results['settings']['footer'] );
		$this->assertStringContainsString( 'class="class1 class2 header-footer-img"', $results['settings']['first_footer'] );
		$this->assertStringContainsString( '<img src=', $results['settings']['first_footer'] );

		$this->assertSame( 'testing', $results['settings']['other_value'] );

		/* Test non-related array */
		$results = $this->model->preprocess_template_arguments( [ 'other_array' ] );
		$this->assertSame( 'other_array', $results[0] );
	}

	/**
	 * Verify our core HTML output is accurate for the input settings we include
	 *
	 * @since 4.0
	 */
	public function test_core_template_options() {
		$data  = $this->form_and_entry();
		/* Setup the test data */
		$settings = [
			'font'             => 'Arial',
			'font_colour'      => '#CCC',
			'font_size'        => '12',

			'header'           => 'This is my header',
			'first_header'     => 'This is the first header',

			'footer'           => 'This is the footer',
			'first_footer'     => 'This is the first footer',

			'background_image' => '/path/image.png?{:16}',
			'background_color' => '#FF2222',
		];

		ob_start();
		$this->view->core_template_styles( [ 'settings' => $settings, 'form' => $data['form'] , 'entry' =>  $data['entry'] ] );
		$results = ob_get_clean();

		/* Test the results */
		$this->assertStringContainsString( 'font-family: Arial, sans-serif;', $results );
		$this->assertStringContainsString( 'font-size: 12pt;', $results );
		$this->assertStringContainsString( 'color: #CCC', $results );

		$this->assertStringContainsString( 'header: html_TemplateHeader', $results );
		$this->assertStringContainsString( 'footer: html_TemplateFooter', $results );
		$this->assertStringContainsString( 'header: html_TemplateFirstHeader', $results );
		$this->assertStringContainsString( 'footer: html_TemplateFirstFooter', $results );

		$this->assertStringContainsString( 'This is my header', $results );
		$this->assertStringContainsString( 'This is the first header', $results );

		$this->assertStringContainsString( 'This is the footer', $results );
		$this->assertStringContainsString( 'This is the first footer', $results );

		$this->assertStringContainsString( 'background-image: url(/path/image.png?https://gravitypdf.com) no-repeat 0 0;', $results );
		$this->assertStringContainsString( 'background-image-resize: 4;', $results );

		$this->assertStringContainsString( 'background-color: #FF2222;', $results );
	}

	/**
	 * Check that our backwards compatible Tier 2 add-on works as expected
	 *
	 * @since 4.0
	 */
	public function test_handle_legacy_tier_2_processing() {
		global $gfpdf;

		$settings  = [ 'id' => '556690c67856b', 'template' => 'zadani' ];
		$entry     = $this->entry( 'all-form-fields' );
		$form      = $gfpdf->gform->get_form( $entry['form_id'] );
		$model_pdf = GPDFAPI::get_mvc_class( 'Model_PDF' );

		$args = $gfpdf->templates->get_template_arguments(
			$form,
			$gfpdf->misc->get_fields_sorted_by_id( $form['id'] ),
			$entry,
			$model_pdf->get_form_data( $entry ),
			$settings,
			$gfpdf->templates->get_config_class( $settings['template'] ),
			$gfpdf->misc->get_legacy_ids( $entry['id'], $settings )
		);

		$pdf = new Helper_PDF(
			[
				'id'      => 1,
				'form_id' => $this->form( 'all-form-fields' )['id'],
			],
			$settings,
			$gfpdf->gform,
			$gfpdf->data,
			$gfpdf->misc,
			$gfpdf->templates,
			$gfpdf->log
		);
		$pdf->set_template();
		$pdf->set_output_type( 'save' );

		$this->assertFalse( $this->model->handle_legacy_tier_2_processing( $pdf, $entry, $settings, $args ) );

		/* Set a filter and ensure the test passes */
		add_filter(
			'gfpdfe_pre_load_template',
			function( $form_id ) {
				return true;
			}
		);

		$this->assertTrue( $this->model->handle_legacy_tier_2_processing( $pdf, $entry, $settings, $args ) );
	}

	/**
	 * @since 5.1.1
	 */
	public function test_kses() {
		$html = '<pagebreak orientation="landscape" />
		<table autosize="1"></table>
		<p style="page-break-inside: avoid"></p>
		<barcode code="04210000526" type="UPCE" />
		';

		do_action( 'gfpdf_pre_pdf_generation' );

		/* Check the PDF tags aren't stripped out during while generating a PDF */
		$html = wp_kses_post( $html );
		$this->assertMatchesRegularExpression( '/\<pagebreak orientation="landscape" \/\>/', $html );
		$this->assertMatchesRegularExpression( '/\<table autosize="1"\>\<\/table\>/', $html );
		$this->assertMatchesRegularExpression( '/\<p style="page-break-inside: avoid"\>\<\/p\>/', $html );
		$this->assertMatchesRegularExpression( '/\<barcode code="04210000526" type="UPCE" \/\>/', $html );

		do_action(
			'gfpdf_post_pdf_generation',
			[],
			[],
			[],
			new Helper_PDF(
				[
					'id'      => 1,
					'form_id' => $this->form( 'all-form-fields' )['id'],
				],
				[ 'id' => '556690c67856b' ],
				GPDFAPI::get_form_class(),
				GPDFAPI::get_data_class(),
				GPDFAPI::get_misc_class(),
				GPDFAPI::get_templates_class(),
				GPDFAPI::get_log_class()
			)
		);

		/* Verify they are stripped out at all other times */
		$html = wp_kses_post( $html );
		$this->assertDoesNotMatchRegularExpression( '/\<pagebreak orientation="landscape" \/\>/', $html );
		$this->assertDoesNotMatchRegularExpression( '/\<table autosize="1"\>\<\/table\>/', $html );
		$this->assertDoesNotMatchRegularExpression( '/\<p style="page-break-inside: avoid"\>\<\/p\>/', $html );
		$this->assertDoesNotMatchRegularExpression( '/\<barcode code="04210000526" type="UPCE" \/\>/', $html );

	}

	/**
	 * Test our PDF generator function works as expected
	 * This function prepares all the details for generating a PDF and is our authentication layer
	 *
	 * @since 4.0
	 *
	 * @group slow
	 */
	public function test_process_pdf() {

		/* Setup our form and entries */
		$results = $this->form_and_entry();
		$lid     = $results['entry']['id'];
		$pid     = '555ad84787d7e';

		/* Test for invalid entry error */
		$results = $this->model->process_pdf( $pid, 0 );
		$this->assertSame( 'not_found', $results->get_error_code() );

		/* Test for invalid PDF settings */
		$results = $this->model->process_pdf( '', $lid );
		$this->assertSame( 'invalid_pdf_id', $results->get_error_code() );

		/* Test our middleware works correctly */
		$results = $this->model->process_pdf( $pid, $lid );
		$this->assertSame( 'conditional_logic', $results->get_error_code() );

		/* Disable all middleware and check if PDF generation begins */
		remove_all_filters( 'gfpdf_pdf_middleware' );

		/* Verify the PDF generation begins and then fails as expected */
		$results = $this->model->process_pdf( $pid, $lid );
		if ( ! is_wp_error( $results ) ) {
			$this->fail( 'This test did not fail as expected' );
		}

		/*
		 * Prior to 6.12 $this->model->process_pdf() would call $this->view->generate_pdf()
		 * and any errors would be output via wp_die(), which could be caught as an exception
		 * in PHPUnit. Now that process_pdf() runs through $this->model->generate_and_save_pdf()
		 * any errors are returned back up the chain for $this->controller->process_pdf_endpoint() to handle.
		 * This is the reason this unit test was modified to explicitly check is_wp_error().
		 */
		$this->assertSame( 'pdf_generation_failure', $results->get_error_code() );
	}

	/**
	 * Check if the PDF is rendered and saved on disk correctly
	 *
	 * @since 4.0
	 *
	 * @group slow
	 */
	public function test_process_and_save_pdf() {
		global $gfpdf;

		/* Setup some test data */
		$results              = $this->form_and_entry();
		$entry                = $results['entry'];
		$form                 = $results['form'];
		$settings             = $form['gfpdf_form_settings']['555ad84787d7e'];
		$settings['template'] = 'zadani';

		/* Create our PDF object */
		$pdf_generator = new Helper_PDF( $entry, $settings, $gfpdf->gform, $gfpdf->data, $gfpdf->misc, $gfpdf->templates, $gfpdf->log );
		$pdf_generator->set_filename( 'Unit Testing' );

		/* Generate the PDF and verify it was successful */
		$this->assertTrue( $this->model->process_and_save_pdf( $pdf_generator ) );
		$this->assertFileExists( $pdf_generator->get_full_pdf_path() );
	}

	/**
	 * Check if the correct PDFs are saved on disk
	 *
	 * @since 4.0
	 *
	 * @group slow
	 */
	public function test_maybe_save_pdf() {
		global $gfpdf;

		$form_class = \GPDFAPI::get_form_class();

		/* Setup some test data */
		$results = $this->form_and_entry();
		$entry   = $results['entry'];
		$form    = $form_class->get_form( $results['form']['id'] );  /* get from the database so the date created is accurate */

		$path = Cache::get_path( $form, $entry, $form['gfpdf_form_settings']['556690c67856b'] );
		$file = "test-{$form['id']}.pdf";

		$this->model->maybe_save_pdf( $entry, $form );

		/* Check the results are successful */
		$this->assertFileExists( $path . $file );

		/* Clean up */
		unlink( $path . $file );

		/* Ensure function doesn't run when background processing enabled */
		$gfpdf->options->update_option( 'background_processing', 'Yes' );

		$this->model->maybe_save_pdf( $entry, $form );
		$this->assertFileDoesNotExist( $path . $file );
	}

	/**
	 * Check if we should be always saving the PDF based on the settings
	 *
	 * @since 4.0
	 */
	public function test_maybe_always_save_pdf() {

		$settings['save'] = 'Yes';
		$this->assertTrue( $this->model->maybe_always_save_pdf( $settings ) );

		$settings['save'] = 'No';
		$this->assertFalse( $this->model->maybe_always_save_pdf( $settings ) );

		add_filter( 'gfpdf_post_save_pdf', '__return_true' );
		$this->assertTrue( $this->model->maybe_always_save_pdf( $settings ) );
		remove_filter( 'gfpdf_post_save_pdf', '__return_true' );
	}

	/**
	 * Check if we should attach a PDF to the current notification
	 *
	 * @param bool $expectation
	 * @param array $notification
	 * @param array $settings
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_maybe_attach_to_notification
	 */
	public function test_maybe_attach_to_notification( $expectation, $notification, $settings ) {
		$this->assertSame( $expectation, $this->model->maybe_attach_to_notification( $notification, $settings ) );
	}

	/**
	 * Data provider for test_maybe_attach_to_notification()
	 *
	 * @return array
	 * @since 4.0
	 */
	public function provider_maybe_attach_to_notification(): array {

		$notification = [
			'aasffaa2FAa2',
			'sjfajwa124FAS',
			'91230jfa021AF',
			'0890afjIWFjas',
		];

		return [
			[ false, [ 'id' => '123afjafwij4' ], [ 'notification' => $notification ] ],
			[ true, [ 'id' => 'aasffaa2FAa2' ], [ 'notification' => $notification ] ],
			[ false, [ 'id' => 'koa290' ], [ 'notification' => $notification ] ],
			[ false, [ 'id' => 'AAFwa25940359' ], [ 'notification' => $notification ] ],
			[ true, [ 'id' => 'sjfajwa124FAS' ], [ 'notification' => $notification ] ],
			[ true, [ 'id' => '91230jfa021AF' ], [ 'notification' => $notification ] ],
			[ true, [ 'id' => '0890afjIWFjas' ], [ 'notification' => $notification ] ],
			[ false, [ 'id' => 'fawfja24a90fa' ], [ 'notification' => $notification ] ],
		];
	}

	/**
	 * Verify a PDF is generated and the appropriate PDF path is returned
	 *
	 * @since 4.0
	 *
	 * @group slow
	 */
	public function test_generate_and_save_pdf() {
		global $gfpdf;

		/* Setup our form and entries */
		$results = $this->form_and_entry();
		$entry   = $results['entry'];
		$fid     = $results['form']['id'];
		$pid     = '555ad84787d7e';

		/* Get our PDF */
		$settings             = $gfpdf->options->get_pdf( $fid, $pid );
		$settings['template'] = 'zadani';

		/* did_action() is cumulative and set_up's controller init doubles the listener; assert deltas. */
		$baseline = did_action( 'gfpdf_post_save_pdf' );

		/* Generate our PDF and verify it worked correctly */
		$filename = $this->model->generate_and_save_pdf( $entry, $settings );

		$this->assertFileExists( $filename );

		if ( is_file( $filename ) ) {
			unlink( $filename );
		}

		$after_success = did_action( 'gfpdf_post_save_pdf' );
		$this->assertGreaterThan( $baseline, $after_success, 'gfpdf_post_save_pdf should fire on successful generation' );

		$settings['template'] = 'doesntexist';

		/* Trigger an error */
		$error = $this->model->generate_and_save_pdf( $entry, $settings );

		$this->assertInstanceOf( \WP_Error::class, $error );
		$this->assertSame( $after_success, did_action( 'gfpdf_post_save_pdf' ), 'gfpdf_post_save_pdf should not fire on failed generation' );
	}
}
