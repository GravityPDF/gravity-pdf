<?php

namespace GFPDF\Tests;

use GFPDF\Controller\Controller_PDF;
use GFPDF\Model\Model_PDF;
use GFPDF\View\View_PDF;
use GFPDF\Helper\Helper_PDF;

use GFForms;

use GPDFAPI;
use WP_UnitTestCase;

use Exception;

/**
 * Any slow PDF-generation related tests should be included here. By default, this is excluded from the usual tests
 * Can be tested with: phpunit --group slow-pdf-processes
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2016, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2016, Blue Liquid Designs

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
 * @group slow-pdf-processes
 */
class Test_Slow_PDF_Processes extends WP_UnitTestCase {

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
		$this->model = new Model_PDF( $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->notices );
		$this->view  = new View_PDF( array(), $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc );

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

		$gfpdf->data->form_settings                = array();
		$gfpdf->data->form_settings[ $form['id'] ] = $form['gfpdf_form_settings'];

		return array(
			'form'  => $form,
			'entry' => $entry,
		);
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
			$results = $this->model->process_pdf( $pid, $lid );
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
		$pdf_generator = new Helper_PDF( $entry, $settings, $gfpdf->gform, $gfpdf->data );
		$pdf_generator->set_filename( 'Unit Testing' );

		/* Generate the PDF and verify it was successfull */
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
		$file    = $gfpdf->data->template_tmp_location . "{$form['id']}{$entry['id']}/test-{$form['id']}.pdf";

		$this->model->maybe_save_pdf( $entry, $form );

		/* Check the results are successful */
		$this->assertFileExists( $file );

		/* Clean up */
		unlink( $file );
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
		add_filter( 'mpdf_output_destination', function() {
			return 'O';
		} );

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
	}

	/**
	 * Check if we should attach a PDF to the current notification
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

		$notification = array(
			'aasffaa2FAa2',
			'sjfajwa124FAS',
			'91230jfa021AF',
			'0890afjIWFjas',
		);

		return array(
			array( false, array( 'id' => '123afjafwij4' ), array( 'notification' => $notification ) ),
			array( true, array( 'id' => 'aasffaa2FAa2' ), array( 'notification' => $notification ) ),
			array( false, array( 'id' => 'koa290' ), array( 'notification' => $notification ) ),
			array( false, array( 'id' => 'AAFwa25940359' ), array( 'notification' => $notification ) ),
			array( true, array( 'id' => 'sjfajwa124FAS' ), array( 'notification' => $notification ) ),
			array( true, array( 'id' => '91230jfa021AF' ), array( 'notification' => $notification ) ),
			array( true, array( 'id' => '0890afjIWFjas' ), array( 'notification' => $notification ) ),
			array( false, array( 'id' => 'fawfja24a90fa' ), array( 'notification' => $notification ) ),
		);
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

		/* Trigger an error */
		$error = $this->model->generate_and_save_pdf( array(), array( 'filename' => '' ) );

		$this->assertTrue( is_wp_error( $error ) );
	}

	/**
	 * Verify the appropriate variables are passed in and that a PDF is correctly generated
	 *
	 * Belongs to GPDFAPI class (found in api.php)
	 *
	 * @since 4.0
	 */
	public function test_create_pdf() {
		global $gfpdf;

		/* Setup our form and entries */
		$results = $this->create_form_and_entries();
		$entry   = $results['entry'];
		$fid     = $results['form']['id'];
		$pid     = '555ad84787d7e';

		/* Check for $entry error first */
		$pdf = GPDFAPI::create_pdf( '', '' );
		$this->assertEquals( 'invalid_entry', $pdf->get_error_code() );

		/* Check for $settings error */
		$pdf = GPDFAPI::create_pdf( $entry['id'], '' );
		$this->assertEquals( 'invalid_pdf_setting', $pdf->get_error_code() );

		/* Create the PDF and test it was correctly generated */
		add_filter( 'gfpdf_pdf_config', function( $settings ) {
			$settings['template'] = 'zadani';
			return $settings;
		} );

		$filename = GPDFAPI::create_pdf( $entry['id'], $pid );

		$this->assertTrue( is_file( $filename ) );

		unlink( $filename );

	}

	/**
	 * Verify our depreciated GFPDF_Core_Model::gfpdfe_save_pdf() method
	 * works as expected.
	 */
	public function test_depreciated_save_pdf() {
		global $gfpdf;

		$results = $this->create_form_and_entries();
		$entry   = $results['entry'];
		$form    = $results['form'];

		$filename = $gfpdf->data->template_tmp_location . "11/test-{$form['id']}.pdf";

		if ( is_file( $filename ) ) {
			unlink( $filename );
		}

		\GFPDF_Core_Model::gfpdfe_save_pdf( $entry, $form );
		$this->assertTrue( is_file( $filename ) );

		unlink( $filename );
	}
}
