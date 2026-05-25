<?php

namespace GFPDF\Model;

use GFPDF\Tests\Integration\AjaxTestCase;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;

/**
 * @group ajax
 */
class Test_Model_Settings_Ajax extends AjaxTestCase {

	public function test_ajax_process_license_deactivation() {
		$this->_setRole( 'administrator' );

		try {
			$this->_handleAjax( 'gfpdf_deactivate_license' );
		} catch ( WPAjaxDieStopException $e ) {
		}

		$this->assertEquals( '401', $e->getMessage() );

		$_POST['nonce'] = wp_create_nonce( 'gfpdf_deactivate_license' );

		try {
			$this->_handleAjax( 'gfpdf_deactivate_license' );
		} catch ( WPAjaxDieContinueException $e ) {
		}

		$this->assertStringContainsString( 'An unknown error occurred', json_decode( $this->_last_response )->error );
	}
}
