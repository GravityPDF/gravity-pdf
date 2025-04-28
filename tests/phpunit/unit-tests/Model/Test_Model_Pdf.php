<?php

namespace GFPDF\Model;

use GF_UnitTest_Factory;
use GFPDF\Controller\Controller_PDF;
use GFPDF\Helper\Helper_Url_Signer;
use GFPDF\View\View_PDF;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Model_PDF
 *
 * @package   GFPDF\Model
 *
 * @group     model
 * @group     pdfs
 */
class Test_Model_PDF extends WP_UnitTestCase {

	/**
	 * @var Controller_PDF
	 */
	public $controller;

	/**
	 * @var Model_PDF
	 */
	public $model;

	/**
	 * @var View_PDF
	 */
	public $view;

	public function set_up() {
		global $gfpdf;

		parent::set_up();

		/* Setup our test classes */
		$this->model = new Model_PDF( $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->notices, $gfpdf->templates, new Helper_Url_Signer() );

		$this->view = new View_PDF( [], $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );

		$this->controller = new Controller_PDF( $this->model, $this->view, $gfpdf->gform, $gfpdf->log, $gfpdf->misc );
	}

	public function test_middleware() {
		/* setup data */
		$factory = new GF_UnitTest_Factory();
		$form    = $factory->form->create_and_get();
		$entry   = $factory->entry->create_and_get( [ 'form_id' => $form['id'], 'ip' => '10.0.0.1', ] );
		$factory->pdf->set_form_id( $form['id'] );
		$pdf = $factory->pdf->create_and_get( [ 'public_access' => 'No' ] );

		$this->controller->init();

		/* Check unauthorized access */
		$this->assertInstanceOf( 'WP_Error', apply_filters( 'gfpdf_pdf_middleware', false, $entry, $pdf ) );

		/* Check public access */
		$this->controller->init();

		$pdf['public_access'] = 'Yes';
		$this->assertNotInstanceOf( 'WP_Error', apply_filters( 'gfpdf_pdf_middleware', false, $entry, $pdf ) );

		/* Check Signing Request */
		$this->controller->init();

		$pdf['public_access'] = 'No';

		// fail
		$_SERVER['REQUEST_URI'] = '/';
		$this->assertInstanceOf( 'WP_Error', apply_filters( 'gfpdf_pdf_middleware', false, $entry, $pdf ) );

		// pass
		$url                    = do_shortcode( sprintf( '[gravitypdf id="%s" entry="%d" raw="1" signed="1"]', $pdf['id'], $entry['id'] ) );
		$_SERVER['HTTP_HOST']   = str_replace( [ 'http://', 'http://' ], '', home_url() );
		$_GET['expires']        = '';
		$_GET['signature']      = '';
		$_SERVER['REQUEST_URI'] = str_replace( home_url(), '', $url );

		$this->assertNotInstanceOf( 'WP_Error', apply_filters( 'gfpdf_pdf_middleware', false, $entry, $pdf ) );

		$_SERVER['REQUEST_URI'] = '';
		unset( $_GET['expires'], $_GET['signature'] );

		/* logged out IP-matching */
		$this->controller->init();

		// fail
		$_SERVER['REMOTE_ADDR'] = '10.0.0.2';
		$this->assertInstanceOf( 'WP_Error', apply_filters( 'gfpdf_pdf_middleware', false, $entry, $pdf ) );

		// pass
		$_SERVER['REMOTE_ADDR'] = '10.0.0.1';
		$this->assertNotInstanceOf( 'WP_Error', apply_filters( 'gfpdf_pdf_middleware', false, $entry, $pdf ) );

		/* Logged out IP-timeout */
		$default_timeout = 60 * 20;
		$entry           = $factory->entry->create_and_get( [ 'form_id' => $form['id'], 'date_created' => time() - $default_timeout ] );

		$this->assertInstanceOf( 'WP_Error', apply_filters( 'gfpdf_pdf_middleware', false, $entry, $pdf ) );

		/* Logged-in owner */
		$subscriber1 = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		$subscriber2 = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		$entry       = $factory->entry->create_and_get( [ 'form_id' => $form['id'], 'created_by' => $subscriber1 ] );

		// fail
		wp_set_current_user( $subscriber2 );
		$this->assertInstanceOf( 'WP_Error', apply_filters( 'gfpdf_pdf_middleware', false, $entry, $pdf ) );

		// pass
		wp_set_current_user( $subscriber1 );
		$this->assertNotInstanceOf( 'WP_Error', apply_filters( 'gfpdf_pdf_middleware', false, $entry, $pdf ) );

		/* Restrict Owner */
		$pdf['restrict_owner'] = 'Yes';
		$this->assertInstanceOf( 'WP_Error', apply_filters( 'gfpdf_pdf_middleware', false, $entry, $pdf ) );

		/* Give entry owner `gravityforms_view_entries` capability */
		global $current_user;
		$user = get_user_by( 'id', $subscriber1 );
		$user->add_cap( 'gravityforms_view_entries' );
		$current_user = $user;

		$this->assertNotInstanceOf( 'WP_Error', apply_filters( 'gfpdf_pdf_middleware', false, $entry, $pdf ) );

		/* Disable PDF */
		$pdf['active'] = false;
		$this->assertInstanceOf( 'WP_Error', apply_filters( 'gfpdf_pdf_middleware', false, $entry, $pdf ) );

		/* Fail Conditional Logic */
		$pdf['active'] = true;

		// pass
		$pdf['conditionalLogic'] = [
			'actionType' => 'show',
			'logicType'  => 'all',
			'rules'      => [
				[
					'fieldId'  => '1',
					'operator' => 'is',
					'value'    => '',
				],
			],
		];

		$this->assertNotInstanceOf( 'WP_Error', apply_filters( 'gfpdf_pdf_middleware', false, $entry, $pdf ) );

		// fail
		$pdf['conditionalLogic'] = [
			'actionType' => 'show',
			'logicType'  => 'all',
			'rules'      => [
				[
					'fieldId'  => '1',
					'operator' => 'isnot',
					'value'    => '',
				],
			],
		];

		$this->assertInstanceOf( 'WP_Error', apply_filters( 'gfpdf_pdf_middleware', false, $entry, $pdf ) );

	}


}
