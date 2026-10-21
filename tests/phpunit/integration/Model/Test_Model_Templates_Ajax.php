<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Integration\AjaxTestCase;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger as MonoLogger;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;

/**
 * @group ajax
 */
class Test_Model_Templates_Ajax extends AjaxTestCase {

	public function tear_down(): void {
		global $gfpdf;

		$file = $gfpdf->data->template_location . 'zadani.php';
		if ( is_file( $file ) ) {
			unlink( $file );
		}

		parent::tear_down();
	}

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

		$file = $this->create_deletable_template();

		$this->assertSame( 'true', $this->get_ajax_response( 'gfpdf_delete_template' ) );
		$this->assertFileDoesNotExist( $file );
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
	 * Run a template AJAX endpoint and return its response
	 *
	 * @param string $action
	 * @param bool   $with_nonce
	 *
	 * @return string
	 *
	 * @since 6.18.0
	 */
	private function get_ajax_response( $action, $with_nonce = true ) {
		if ( $with_nonce ) {
			$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );
		}

		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieStopException $e ) {
			return $e->getMessage();
		} catch ( WPAjaxDieContinueException $e ) {
			$response = $this->_last_response;
			unset( $this->_last_response );

			return $response;
		}

		return '';
	}

	/**
	 * Create a template the delete endpoint can target
	 *
	 * @return string The template path
	 *
	 * @since 6.18.0
	 */
	private function create_deletable_template() {
		global $gfpdf;

		$file = $gfpdf->data->template_location . 'zadani.php';
		touch( $file );

		$_POST['id'] = 'zadani';

		return $file;
	}

	/**
	 * Check both template endpoints refuse the request and leave the template alone
	 *
	 * @param string $code
	 * @param bool   $with_nonce
	 *
	 * @since 6.18.0
	 */
	private function assert_template_endpoints_deny( $code, $with_nonce = true ) {
		$file = $this->create_deletable_template();

		$this->assertSame( $code, $this->get_ajax_response( 'gfpdf_upload_template', $with_nonce ) );
		$this->assertSame( $code, $this->get_ajax_response( 'gfpdf_delete_template', $with_nonce ) );
		$this->assertFileExists( $file );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_ajax_template_endpoints_require_upload_plugins() {
		$user_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		get_userdata( $user_id )->add_cap( 'gravityforms_edit_settings' );
		wp_set_current_user( $user_id );

		$this->assert_template_endpoints_deny( '403' );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_ajax_template_endpoints_log_only_the_refused_user_id() {
		global $gfpdf;

		$user_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );

		$handler = new TestHandler();
		$gfpdf->log->pushHandler( $handler );
		$code = $this->get_ajax_response( 'gfpdf_upload_template' );
		$gfpdf->log->popHandler();

		$this->assertSame( '403', $code );

		$this->assertTrue(
			$handler->hasRecordThatPasses(
				function ( $record ) use ( $user_id ) {
					return $record['context'] === [
						'type'              => 'Process Uploaded Template Zip Package',
						'user'              => $user_id,
						'capability_needed' => 'upload_plugins',
					];
				},
				MonoLogger::CRITICAL
			)
		);
	}

	/**
	 * @since 6.18.0
	 */
	public function test_ajax_template_endpoints_filter_can_deny_administrator() {
		$this->_setRole( 'administrator' );
		$this->maybe_grant_super_admin();
		add_filter( 'gfpdf_current_user_can_manage_templates', '__return_false' );

		$this->assert_template_endpoints_deny( '403' );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_ajax_template_endpoints_follow_the_capability_filter() {
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'editor' ] ) );
		add_filter( 'gfpdf_current_user_can_manage_templates', '__return_true' );

		$file = $this->create_deletable_template();

		$this->assertSame( '400', $this->get_ajax_response( 'gfpdf_upload_template' ) );
		$this->assertSame( 'true', $this->get_ajax_response( 'gfpdf_delete_template' ) );
		$this->assertFileDoesNotExist( $file );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_ajax_template_endpoints_check_nonce_when_filter_grants_access() {
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'editor' ] ) );
		add_filter( 'gfpdf_current_user_can_manage_templates', '__return_true' );

		$this->assert_template_endpoints_deny( '401', false );
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
