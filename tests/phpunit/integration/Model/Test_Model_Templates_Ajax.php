<?php

declare( strict_types=1 );

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
		$this->maybe_grant_super_admin();

		try {
			$this->_handleAjax( 'gfpdf_upload_template' );
			$this->fail( 'Expected WPAjaxDieStopException (401) was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '401', $e->getMessage() );
		}

		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_upload_template' );
			$this->fail( 'Expected WPAjaxDieStopException (400) was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '400', $e->getMessage() );
		}
	}

	public function test_ajax_process_delete_template() {
		global $gfpdf;

		$this->_setRole( 'administrator' );
		$this->maybe_grant_super_admin();

		try {
			$this->_handleAjax( 'gfpdf_delete_template' );
			$this->fail( 'Expected WPAjaxDieStopException (401) was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '401', $e->getMessage() );
		}

		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_delete_template' );
			$this->fail( 'Expected WPAjaxDieStopException (400) was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '400', $e->getMessage() );
		}

		$file = $gfpdf->data->template_location . 'zadani.php';
		touch( $file );

		$_POST['id'] = 'zadani';

		try {
			$this->_handleAjax( 'gfpdf_delete_template' );
			$this->fail( 'Expected WPAjaxDieContinueException was not thrown.' );
		} catch ( WPAjaxDieContinueException $e ) {
			$response = json_decode( $this->_last_response, true );
			unset( $this->_last_response );

			$this->assertTrue( $response );
			$this->assertFileDoesNotExist( $file );
		}
	}

	/**
	 * Grant the current user super admin on multisite so they can manage PDF templates
	 *
	 * @since 6.18.0
	 */
	private function maybe_grant_super_admin() {
		if ( is_multisite() ) {
			grant_super_admin( get_current_user_id() );
		}
	}

	/**
	 * Run a template AJAX endpoint and return the wp_die() message
	 *
	 * @param string $action
	 *
	 * @return string
	 *
	 * @since 6.18.0
	 */
	private function get_ajax_die_message( $action ) {
		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieStopException $e ) {
			return $e->getMessage();
		}

		return '';
	}

	/**
	 * @since 6.18.0
	 */
	public function test_ajax_template_endpoints_require_upload_plugins() {
		global $gfpdf;

		$user_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		get_userdata( $user_id )->add_cap( 'gravityforms_edit_settings' );
		wp_set_current_user( $user_id );

		$file = $gfpdf->data->template_location . 'zadani.php';
		touch( $file );

		$_POST['id'] = 'zadani';

		$this->assertSame( '403', $this->get_ajax_die_message( 'gfpdf_upload_template' ) );
		$this->assertSame( '403', $this->get_ajax_die_message( 'gfpdf_delete_template' ) );
		$this->assertFileExists( $file );

		unlink( $file );

		add_filter( 'gfpdf_current_user_can_manage_templates', '__return_true' );

		$this->assertSame( '400', $this->get_ajax_die_message( 'gfpdf_upload_template' ) );
	}

	public function test_ajax_process_build_template_options_html() {
		$this->_setRole( 'administrator' );

		try {
			$this->_handleAjax( 'gfpdf_get_template_options' );
			$this->fail( 'Expected WPAjaxDieStopException (401) was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '401', $e->getMessage() );
		}

		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_get_template_options' );
			$this->fail( 'Expected WPAjaxDieContinueException was not thrown.' );
		} catch ( WPAjaxDieContinueException $e ) {
			$this->assertStringContainsString( '<optgroup label="Core">', $this->_last_response );
		}
	}
}
