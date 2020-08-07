<?php

namespace GFPDF\Model;

use GFPDF\Controller\Controller_PDF;
use GFPDF\Helper\Helper_Url_Signer;
use GFPDF\View\View_PDF;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class TestModelPDFSuccess
 *
 * @package GFPDF\Model
 *
 * @group   pdf
 */
class TestModelPDFMetaBox extends WP_UnitTestCase {

	/**
	 * @var Controller_PDF
	 */
	protected $controller;

	/**
	 * @var Model_PDF
	 */
	protected $model;

	/**
	 * @var View_PDF
	 */
	protected $view;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 6.0
	 */
	public function setUp() {
		global $gfpdf;

		/* run parent method */
		parent::setUp();

		/* Setup our test classes */
		$this->model      = new Model_PDF( $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->notices, $gfpdf->templates, new Helper_Url_Signer() );
		$this->view       = new View_PDF( [], $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );
		$this->controller = new Controller_PDF( $this->model, $this->view, $gfpdf->gform, $gfpdf->log, $gfpdf->misc );
		$this->controller->init();
	}

	/**
	 * Create our testing data
	 *
	 * @since 4.0
	 */
	protected function create_form_and_entries() {
		global $gfpdf;

		$form  = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		$gfpdf->data->form_settings[ $form['id'] ] = $form['gfpdf_form_settings'];

		return [ $form, $entry ];
	}

	/**
	 * Check our PDF detail list is displaying correctly
	 *
	 * @since 4.0
	 */
	public function test_view_pdf_entry_detail_success() {

		/* Setup some test data */
		[ $form, $entry['entry'] ] = $this->create_form_and_entries();

		ob_start();
		$this->model->view_pdf_entry_detail( $entry );
		$html = ob_get_clean();

		$this->assertStringContainsString( '<div class="gfpdf_detailed_pdf_cta">', $html );
	}

	/**
	 * Check our PDF detail list is displaying correctly when there is no entry passed
	 *
	 * @since 4.0
	 */
	public function test_view_pdf_entry_detail_fail() {

		ob_start();
		$this->model->view_pdf_entry_detail( [ 'entry' => [ 'form_id' => 0 ] ] );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'No PDFs available for this entry.', $html );
	}

	/**
	 * Check if Metabox registration is working properly
	 *
	 * @since 6.0
	 */
	public function test_register_pdf_meta_box_success() {

		/* Setup some test data */
		[ $form, $entry ] = $this->create_form_and_entries();

		$this->assertArrayHasKey( 'gfpdf-entry-details-list', $this->model->register_pdf_meta_box( [], $entry, $form ) );

		/*  Get all PDFs ID */
		$pdf_ids = array_keys( $form['gfpdf_form_settings'] );

		/* Test if metabox is  registered when there is atleast 2 active PDFs */
		$active_pdfs = array_rand( $pdf_ids, 2 );

		foreach ( $active_pdfs as $id ) {
			$form['gfpdf_form_settings'][ $pdf_ids[ $id ] ]['active'] = false;
		}

		$this->assertArrayHasKey( 'gfpdf-entry-details-list', $this->model->register_pdf_meta_box( [], $entry, $form ) );
	}

	/**
	 * Check if Metabox registration is working properly when there is no active PDFs
	 *
	 * @since 6.0
	 */
	public function test_register_pdf_meta_box_fail() {

		/* Setup some test data */
		[ $form, $entry ] = $this->create_form_and_entries();

		/*  Set all pdfs to inactive */
		$pdf_ids = array_keys( $form['gfpdf_form_settings'] );

		/* Test if metabox is not registered when there is no active PDFs */
		foreach ( $pdf_ids as $id ) {
			$form['gfpdf_form_settings'][ $id ]['active'] = false;
		}

		$this->assertEmpty( $this->model->register_pdf_meta_box( [], $entry, $form ) );
	}
}
