<?php

namespace GFPDF\Model;

use GFPDF\Tests\Integration\AjaxTestCase;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;

/**
 * @group ajax
 */
class Test_Model_Templates_Ajax extends AjaxTestCase {

	public function test_ajax_process_uploaded_template() {
		$this->_setRole( 'administrator' );

		try {
			$this->_handleAjax( 'gfpdf_upload_template' );
		} catch ( WPAjaxDieStopException $e ) {
		}

		$this->assertEquals( '401', $e->getMessage() );

		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_upload_template' );
		} catch ( WPAjaxDieStopException $e ) {
		}

		$this->assertEquals( '400', $e->getMessage() );
	}

	public function test_ajax_process_delete_template() {
		global $gfpdf;

		$this->_setRole( 'administrator' );

		try {
			$this->_handleAjax( 'gfpdf_delete_template' );
		} catch ( WPAjaxDieStopException $e ) {
		}

		$this->assertEquals( '401', $e->getMessage() );

		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_delete_template' );
		} catch ( WPAjaxDieStopException $e ) {
		}

		$this->assertEquals( '400', $e->getMessage() );

		$file = $gfpdf->data->template_location . 'zadani.php';
		touch( $file );

		$_POST['id'] = 'zadani';

		try {
			$this->_handleAjax( 'gfpdf_delete_template' );
		} catch ( WPAjaxDieContinueException $e ) {
		}

		$response = json_decode( $this->_last_response, true );
		unset( $this->_last_response );

		$this->assertTrue( $response );
		$this->assertFileDoesNotExist( $file );
	}

	public function test_ajax_process_build_template_options_html() {
		$this->_setRole( 'administrator' );

		try {
			$this->_handleAjax( 'gfpdf_get_template_options' );
		} catch ( WPAjaxDieStopException $e ) {
		}

		$this->assertEquals( '401', $e->getMessage() );

		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_get_template_options' );
		} catch ( WPAjaxDieContinueException $e ) {
		}

		$this->assertNotFalse( $this->_last_response, '<optgroup label="Core">' );
	}
}
