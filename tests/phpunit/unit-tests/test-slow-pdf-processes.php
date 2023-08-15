<?php

namespace GFPDF\Tests;

use Exception;
use GFPDF\Controller\Controller_PDF;
use GFPDF\Helper\Helper_PDF;
use GFPDF\Helper\Helper_Url_Signer;
use GFPDF\Model\Model_PDF;
use GFPDF\View\View_PDF;
use GFPDF_Core_Model;
use GPDFAPI;
use WP_UnitTestCase;

/**
 * Any slow PDF-generation related tests should be included here. By default, this is excluded from the usual tests
 * Can be tested with: phpunit --group slow-pdf-processes
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the model / view / controller for the PDF Endpoint functionality
 *
 * @since 4.0
 * @group slow-pdf-processes
 */
class Test_Slow_PDF_Processes extends WP_UnitTestCase {

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
	public function set_up() {
		global $gfpdf;

		/* run parent method */
		parent::set_up();

		/* Setup our test classes */
		$this->model = new Model_PDF( $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->notices, $gfpdf->templates, new Helper_Url_Signer() );
		$this->view  = new View_PDF( [], $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );

		$this->controller = new Controller_PDF( $this->model, $this->view, $gfpdf->gform, $gfpdf->log, $gfpdf->misc );

		$fonts = glob( dirname( __FILE__ ) . '/fonts/' . '*.[tT][tT][fF]' );
		$fonts = ( is_array( $fonts ) ) ? $fonts : [];

		foreach ( $fonts as $font ) {
			$font_name = basename( $font );
			/* phpcs:disable */
			@copy( $font, $gfpdf->data->template_font_location . $font_name );
			/* phpcs:enable */
		}

		error_reporting( E_ALL & ~E_NOTICE );
	}

	/**
	 * @since 5.0
	 */
	public function tear_down() {
		global $gfpdf;

		$fonts = glob( $gfpdf->data->template_font_location . '*.[tT][tT][fF]' );
		$fonts = ( is_array( $fonts ) ) ? $fonts : [];

		foreach ( $fonts as $font ) {
			@unlink( $font );
		}

		parent::tear_down();

		error_reporting( E_ALL );
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

		$gfpdf->data->form_settings                = [];
		$gfpdf->data->form_settings[ $form['id'] ] = $form['gfpdf_form_settings'];

		return [
			'form'  => $form,
			'entry' => $entry,
		];
	}

	/**
	 * Test our PDF generator function works as expected
	 * This function prepares all the details for generating a PDF and is our authentication layer
	 *
	 * Belongs to Model_PDF.php
	 *
	 * @since 4.0
	 */
	public function test_process_pdf() {

		/* Setup our form and entries */
		$results = $this->create_form_and_entries();
		$lid     = $results['entry']['id'];
		$pid     = '555ad84787d7e';

		/* Test for invalid entry error */
		$results = $this->model->process_pdf( $pid, 0 );
		$this->assertEquals( 'not_found', $results->get_error_code() );

		/* Test for invalid PDF settings */
		$results = $this->model->process_pdf( '', $lid );
		$this->assertEquals( 'invalid_pdf_id', $results->get_error_code() );

		/* Test our middleware works correctly */
		$results = $this->model->process_pdf( $pid, $lid );
		$this->assertEquals( 'conditional_logic', $results->get_error_code() );

		/* Disable all middleware and check if PDF generation begins */
		remove_all_filters( 'gfpdf_pdf_middleware' );

		try {
			$this->model->process_pdf( $pid, $lid );
		} catch ( Exception $e ) {
			$this->assertEquals( 'There was a problem generating your PDF', $e->getMessage() );

			return;
		}

		$this->fail( 'This test did not fail as expected' );
	}

	/**
	 * Check if the PDF is rendered and saved on disk correctly
	 *
	 * Belongs to Helper_PDF.php
	 *
	 * @since 4.0
	 */
	public function test_process_and_save_pdf() {
		global $gfpdf;

		/* Setup some test data */
		$results              = $this->create_form_and_entries();
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
	 * Belongs to Model_PDF.php
	 *
	 * @since 4.0
	 */
	public function test_maybe_save_pdf() {
		global $gfpdf;

		/* Setup some test data */
		$results = $this->create_form_and_entries();
		$entry   = $results['entry'];
		$form    = $results['form'];
		$file    = $gfpdf->data->template_tmp_location . "{$form['id']}{$entry['id']}556690c67856b/test-{$form['id']}.pdf";

		$this->model->maybe_save_pdf( $entry, $form );

		/* Check the results are successful */
		$this->assertFileExists( $file );

		/* Clean up */
		unlink( $file );

		/* Ensure function doesn't run when background processing enabled */
		$gfpdf->options->update_option( 'background_processing', 'Yes' );

		$this->model->maybe_save_pdf( $entry, $form );
		$this->assertFileDoesNotExist( $file );
	}

	/**
	 * Test that we can successfully generate a PDF based on an entry and settings
	 *
	 * Belongs to View_PDF.php
	 *
	 * @since 4.0
	 */
	public function test_generate_pdf() {
		global $gfpdf;

		/* Setup our form and entries */
		$results = $this->create_form_and_entries();
		$entry   = $results['entry'];
		$fid     = $results['form']['id'];
		$pid     = '555ad84787d7e';

		/* Get our PDF */
		$pdf = $gfpdf->options->get_pdf( $fid, $pid );

		/* Fix our template */
		$pdf['template'] = 'zadani';

		/* Add filters to force the PDF to throw and error */
		add_filter(
			'mpdf_output_destination',
			function() {
				return 'O';
			}
		);

		try {
			$this->view->generate_pdf( $entry, $pdf );
		} catch ( Exception $e ) {
			/* Expected */
		}

		$this->assertEquals( 'There was a problem generating your PDF', $e->getMessage() );
	}

	/**
	 * Check if we should be always saving the PDF based on the settings
	 *
	 * Belongs to Model_PDF.php
	 *
	 * @since 4.0
	 */
	public function test_maybe_always_save_pdf() {

		$settings['save'] = 'Yes';
		$this->assertSame( true, $this->model->maybe_always_save_pdf( $settings ) );

		$settings['save'] = 'No';
		$this->assertSame( false, $this->model->maybe_always_save_pdf( $settings ) );

		add_filter( 'gfpdf_post_save_pdf', '__return_true' );
		$this->assertSame( true, $this->model->maybe_always_save_pdf( $settings ) );
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
	public function provider_maybe_attach_to_notification() {

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
	 * Belongs to Model_PDF.php
	 *
	 * @since 4.0
	 */
	public function test_generate_and_save_pdf() {
		global $gfpdf;

		/* Setup our form and entries */
		$results = $this->create_form_and_entries();
		$entry   = $results['entry'];
		$fid     = $results['form']['id'];
		$pid     = '555ad84787d7e';

		/* Get our PDF */
		$settings             = $gfpdf->options->get_pdf( $fid, $pid );
		$settings['template'] = 'zadani';

		/* Generate our PDF and verify it worked correctly */
		$filename = $this->model->generate_and_save_pdf( $entry, $settings );

		$this->assertTrue( is_file( $filename ) );

		if ( is_file( $filename ) ) {
			unlink( $filename );
		}

		$this->assertSame( 1, did_action( 'gfpdf_post_save_pdf' ) );

		$settings['template'] = 'doesntexist';

		/* Trigger an error */
		$error = $this->model->generate_and_save_pdf( $entry, $settings );

		$this->assertTrue( is_wp_error( $error ) );
		$this->assertSame( 1, did_action( 'gfpdf_post_save_pdf' ) );
	}

	/**
	 * Verify the appropriate variables are passed in and that a PDF is correctly generated
	 *
	 * Belongs to GPDFAPI class (found in api.php)
	 *
	 * @since 4.0
	 */
	public function test_create_pdf() {
		/* Setup our form and entries */
		$results = $this->create_form_and_entries();
		$entry   = $results['entry'];
		$pid     = '555ad84787d7e';

		/* Check for $entry error first */
		$pdf = GPDFAPI::create_pdf( '', '' );
		$this->assertEquals( 'invalid_entry', $pdf->get_error_code() );

		/* Check for $settings error */
		$pdf = GPDFAPI::create_pdf( $entry['id'], '' );
		$this->assertEquals( 'invalid_pdf_setting', $pdf->get_error_code() );

		/* Create the PDF and test it was correctly generated */
		add_filter(
			'gfpdf_pdf_config',
			function( $settings ) {
				$settings['template'] = 'zadani';

				return $settings;
			}
		);

		$filename = GPDFAPI::create_pdf( $entry['id'], $pid );

		$this->assertTrue( is_file( $filename ) );

		unlink( $filename );

	}

	/**
	 * Verify our deprecated GFPDF_Core_Model::gfpdfe_save_pdf() method
	 * works as expected.
	 */
	public function test_deprecated_save_pdf() {
		global $gfpdf;

		$results = $this->create_form_and_entries();
		$entry   = $results['entry'];
		$form    = $results['form'];

		$filename = $gfpdf->data->template_tmp_location . "11556690c67856b/test-{$form['id']}.pdf";

		if ( is_file( $filename ) ) {
			unlink( $filename );
		}

		GFPDF_Core_Model::gfpdfe_save_pdf( $entry, $form );
		$this->assertTrue( is_file( $filename ) );

		unlink( $filename );
	}
}
