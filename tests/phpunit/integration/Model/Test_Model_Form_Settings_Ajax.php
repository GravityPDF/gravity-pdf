<?php

namespace GFPDF\Model;

use GFAPI;
use GFPDF\Tests\Integration\AjaxTestCase;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;

/**
 * @group ajax
 */
class Test_Model_Form_Settings_Ajax extends AjaxTestCase {

	public $form_id;
	public $pid = '555ad84787d7e';

	public function set_up() {
		parent::set_up();

		$json          = json_decode( trim( file_get_contents( PDF_PLUGIN_DIR . '/tools/phpunit/data/forms/form-settings.json' ) ), true );
		$this->form_id = GFAPI::add_form( $json );
	}

	public function test_change_state_pdf_setting() {
		global $gfpdf;

		$this->_setRole( 'administrator' );
		$_POST['fid'] = 0;
		$_POST['pid'] = $this->pid;

		try {
			$this->_handleAjax( 'gfpdf_change_state' );
		} catch ( WPAjaxDieStopException $e ) {
		}

		$this->assertEquals( '401', $e->getMessage() );

		$_POST['nonce'] = wp_create_nonce( "gfpdf_state_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_change_state' );
		} catch ( WPAjaxDieStopException $e ) {
		}

		$this->assertEquals( '500', $e->getMessage() );

		$_POST['fid']   = $this->form_id;
		$_POST['nonce'] = wp_create_nonce( "gfpdf_state_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_change_state' );
		} catch ( WPAjaxDieContinueException $e ) {
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertArrayHasKey( 'state', $response );
		$this->assertEquals( 'Inactive', $response['state'] );

		unset( $gfpdf->data->form_settings );
		$pdf = $gfpdf->options->get_pdf( $this->form_id, $this->pid );
		$this->assertFalse( $pdf['active'] );

		$this->_last_response = '';

		try {
			$this->_handleAjax( 'gfpdf_change_state' );
		} catch ( WPAjaxDieContinueException $e ) {
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertArrayHasKey( 'state', $response );
		$this->assertEquals( 'Active', $response['state'] );

		unset( $gfpdf->data->form_settings );
		$pdf = $gfpdf->options->get_pdf( $this->form_id, $this->pid );
		$this->assertTrue( $pdf['active'] );
	}

	public function test_render_template_fields() {
		try {
			$this->_handleAjax( 'gfpdf_get_template_fields' );
		} catch ( WPAjaxDieStopException $e ) {
		}

		$this->assertEquals( '401', $e->getMessage() );

		$this->_setRole( 'administrator' );

		try {
			$this->_handleAjax( 'gfpdf_get_template_fields' );
		} catch ( WPAjaxDieStopException $e ) {
		}

		$this->assertEquals( '401', $e->getMessage() );

		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_get_template_fields' );
		} catch ( WPAjaxDieContinueException $e ) {
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertArrayHasKey( 'fields', $response );
		$this->assertArrayHasKey( 'editors', $response );
		$this->assertArrayHasKey( 'editor_init', $response );
		$this->assertArrayHasKey( 'template_type', $response );
	}

	public function test_duplicate_gf_pdf_settings() {
		global $gfpdf;

		$this->_setRole( 'administrator' );
		$_POST['fid'] = 0;
		$_POST['pid'] = $this->pid;

		try {
			$this->_handleAjax( 'gfpdf_list_duplicate' );
		} catch ( WPAjaxDieStopException $e ) {
		}

		$this->assertEquals( '401', $e->getMessage() );

		$_POST['nonce'] = wp_create_nonce( "gfpdf_duplicate_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_list_duplicate' );
		} catch ( WPAjaxDieStopException $e ) {
		}

		$this->assertEquals( '500', $e->getMessage() );

		$_POST['fid']   = $this->form_id;
		$_POST['nonce'] = wp_create_nonce( "gfpdf_duplicate_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_list_duplicate' );
		} catch ( WPAjaxDieContinueException $e ) {
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertArrayHasKey( 'msg', $response );
		$this->assertArrayHasKey( 'pid', $response );
		$this->assertArrayHasKey( 'name', $response );
		$this->assertArrayHasKey( 'dup_nonce', $response );
		$this->assertArrayHasKey( 'del_nonce', $response );
		$this->assertArrayHasKey( 'state_nonce', $response );

		unset( $gfpdf->data->form_settings );
		$pdf1 = $gfpdf->options->get_pdf( $this->form_id, $this->pid );
		$pdf2 = $gfpdf->options->get_pdf( $this->form_id, $response['pid'] );

		$this->assertEquals( $pdf1['name'] . ' (copy)', $pdf2['name'] );
		$this->assertEquals( $pdf1['template'], $pdf2['template'] );
		$this->assertEquals( $pdf1['filename'], $pdf2['filename'] );

		$this->_last_response = '';
	}

	public function test_delete_gf_pdf_setting() {
		global $gfpdf;

		$pdf = $gfpdf->options->get_pdf( $this->form_id, $this->pid );
		$this->assertEquals( 'My First PDF Template', $pdf['name'] );

		$this->_setRole( 'administrator' );
		$_POST['fid'] = 0;
		$_POST['pid'] = $this->pid;

		try {
			$this->_handleAjax( 'gfpdf_list_delete' );
		} catch ( WPAjaxDieStopException $e ) {
		}

		$this->assertEquals( '401', $e->getMessage() );

		$_POST['nonce'] = wp_create_nonce( "gfpdf_delete_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_list_delete' );
		} catch ( WPAjaxDieStopException $e ) {
		}

		$this->assertEquals( '500', $e->getMessage() );

		$_POST['fid']   = $this->form_id;
		$_POST['nonce'] = wp_create_nonce( "gfpdf_delete_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_list_delete' );
		} catch ( WPAjaxDieContinueException $e ) {
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertArrayHasKey( 'msg', $response );

		unset( $gfpdf->data->form_settings );
		$pdf = $gfpdf->options->get_pdf( $this->form_id, $this->pid );
		$this->assertTrue( is_wp_error( $pdf ) );
	}
}
