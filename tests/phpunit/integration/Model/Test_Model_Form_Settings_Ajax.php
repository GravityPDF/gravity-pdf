<?php

declare( strict_types=1 );

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

	public function set_up(): void {
		parent::set_up();

		/*
		 * Tests in this class mutate the form's PDF settings (state/duplicate/delete),
		 * so each test gets a fresh form. tear_down() deletes it so the GFAPI
		 * non-transactional tables don't accumulate orphan rows.
		 */
		$json          = json_decode( trim( file_get_contents( PDF_PLUGIN_DIR . '/tools/phpunit/data/forms/form-settings.json' ) ), true );
		$this->form_id = $this->gf_factory()->form->create([], $json);
	}

	public function tear_down(): void {
		if ( $this->form_id ) {
			GFAPI::delete_form( $this->form_id );
			$this->form_id = null;
		}

		parent::tear_down();
	}

	public function test_change_state_pdf_setting() {
		global $gfpdf;

		$this->_setRole( 'administrator' );
		$_POST['fid'] = 0;
		$_POST['pid'] = $this->pid;

		try {
			$this->_handleAjax( 'gfpdf_change_state' );
			$this->fail( 'Expected WPAjaxDieStopException (401) was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '401', $e->getMessage() );
		}

		$_POST['nonce'] = wp_create_nonce( "gfpdf_state_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_change_state' );
			$this->fail( 'Expected WPAjaxDieStopException (500) was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '500', $e->getMessage() );
		}

		$_POST['fid']   = $this->form_id;
		$_POST['nonce'] = wp_create_nonce( "gfpdf_state_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_change_state' );
			$this->fail( 'Expected WPAjaxDieContinueException was not thrown.' );
		} catch ( WPAjaxDieContinueException $e ) {
			$response = json_decode( $this->_last_response, true );

			$this->assertArrayHasKey( 'state', $response );
			$this->assertSame( 'Inactive', $response['state'] );
		}

		unset( $gfpdf->data->form_settings );
		$pdf = $gfpdf->options->get_pdf( $this->form_id, $this->pid );
		$this->assertFalse( $pdf['active'] );

		$this->_last_response = '';

		try {
			$this->_handleAjax( 'gfpdf_change_state' );
			$this->fail( 'Expected WPAjaxDieContinueException was not thrown.' );
		} catch ( WPAjaxDieContinueException $e ) {
			$response = json_decode( $this->_last_response, true );

			$this->assertArrayHasKey( 'state', $response );
			$this->assertSame( 'Active', $response['state'] );
		}

		unset( $gfpdf->data->form_settings );
		$pdf = $gfpdf->options->get_pdf( $this->form_id, $this->pid );
		$this->assertTrue( $pdf['active'] );
	}

	public function test_render_template_fields() {
		try {
			$this->_handleAjax( 'gfpdf_get_template_fields' );
			$this->fail( 'Expected WPAjaxDieStopException (401) was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '401', $e->getMessage() );
		}

		$this->_setRole( 'administrator' );

		try {
			$this->_handleAjax( 'gfpdf_get_template_fields' );
			$this->fail( 'Expected WPAjaxDieStopException (401) was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '401', $e->getMessage() );
		}

		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_get_template_fields' );
			$this->fail( 'Expected WPAjaxDieContinueException was not thrown.' );
		} catch ( WPAjaxDieContinueException $e ) {
			$response = json_decode( $this->_last_response, true );

			$this->assertArrayHasKey( 'fields', $response );
			$this->assertArrayHasKey( 'editors', $response );
			$this->assertArrayHasKey( 'editor_init', $response );
			$this->assertArrayHasKey( 'template_type', $response );
		}
	}

	public function test_duplicate_gf_pdf_settings() {
		global $gfpdf;

		$this->_setRole( 'administrator' );
		$_POST['fid'] = 0;
		$_POST['pid'] = $this->pid;

		try {
			$this->_handleAjax( 'gfpdf_list_duplicate' );
			$this->fail( 'Expected WPAjaxDieStopException (401) was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '401', $e->getMessage() );
		}

		$_POST['nonce'] = wp_create_nonce( "gfpdf_duplicate_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_list_duplicate' );
			$this->fail( 'Expected WPAjaxDieStopException (500) was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '500', $e->getMessage() );
		}

		$_POST['fid']   = $this->form_id;
		$_POST['nonce'] = wp_create_nonce( "gfpdf_duplicate_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		$response = null;

		try {
			$this->_handleAjax( 'gfpdf_list_duplicate' );
			$this->fail( 'Expected WPAjaxDieContinueException was not thrown.' );
		} catch ( WPAjaxDieContinueException $e ) {
			$response = json_decode( $this->_last_response, true );

			$this->assertArrayHasKey( 'msg', $response );
			$this->assertArrayHasKey( 'pid', $response );
			$this->assertArrayHasKey( 'name', $response );
			$this->assertArrayHasKey( 'dup_nonce', $response );
			$this->assertArrayHasKey( 'del_nonce', $response );
			$this->assertArrayHasKey( 'state_nonce', $response );
		}

		unset( $gfpdf->data->form_settings );
		$pdf1 = $gfpdf->options->get_pdf( $this->form_id, $this->pid );
		$pdf2 = $gfpdf->options->get_pdf( $this->form_id, $response['pid'] );

		$this->assertSame( $pdf1['name'] . ' (copy)', $pdf2['name'] );
		$this->assertSame( $pdf1['template'], $pdf2['template'] );
		$this->assertSame( $pdf1['filename'], $pdf2['filename'] );

		$this->_last_response = '';
	}

	public function test_delete_gf_pdf_setting() {
		global $gfpdf;

		$pdf = $gfpdf->options->get_pdf( $this->form_id, $this->pid );
		$this->assertSame( 'My First PDF Template', $pdf['name'] );

		$this->_setRole( 'administrator' );
		$_POST['fid'] = 0;
		$_POST['pid'] = $this->pid;

		try {
			$this->_handleAjax( 'gfpdf_list_delete' );
			$this->fail( 'Expected WPAjaxDieStopException (401) was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '401', $e->getMessage() );
		}

		$_POST['nonce'] = wp_create_nonce( "gfpdf_delete_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_list_delete' );
			$this->fail( 'Expected WPAjaxDieStopException (500) was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '500', $e->getMessage() );
		}

		$_POST['fid']   = $this->form_id;
		$_POST['nonce'] = wp_create_nonce( "gfpdf_delete_nonce_{$_POST['fid']}_{$_POST['pid']}" );

		try {
			$this->_handleAjax( 'gfpdf_list_delete' );
			$this->fail( 'Expected WPAjaxDieContinueException was not thrown.' );
		} catch ( WPAjaxDieContinueException $e ) {
			$response = json_decode( $this->_last_response, true );

			$this->assertArrayHasKey( 'msg', $response );
		}

		unset( $gfpdf->data->form_settings );
		$pdf = $gfpdf->options->get_pdf( $this->form_id, $this->pid );
		$this->assertInstanceOf( \WP_Error::class, $pdf );
	}
}
