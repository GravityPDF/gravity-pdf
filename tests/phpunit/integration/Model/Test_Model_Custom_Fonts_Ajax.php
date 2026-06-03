<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Integration\AjaxTestCase;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;

/**
 * @group ajax
 */
class Test_Model_Custom_Fonts_Ajax extends AjaxTestCase {

	public function test_ajax_save_core_font() {
		$this->_setRole( 'administrator' );

		try {
			$this->_handleAjax( 'gfpdf_save_core_font' );
			$this->fail( 'Expected WPAjaxDieStopException (401) was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '401', $e->getMessage() );
		}

		$_POST['nonce']     = wp_create_nonce( 'gfpdf_ajax_nonce' );
		$_POST['font_name'] = 'nothing';

		try {
			$this->_handleAjax( 'gfpdf_save_core_font' );
			$this->fail( 'Expected WPAjaxDieContinueException was not thrown.' );
		} catch ( WPAjaxDieContinueException $e ) {
			$this->assertFalse( json_decode( $this->_last_response ) );
		}

		$this->_last_response = '';

		$_POST['font_name'] = 'Aegean.otf';

		$api_response = function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => '',
			];
		};

		add_filter( 'pre_http_request', $api_response );

		try {
			$this->_handleAjax( 'gfpdf_save_core_font' );
			$this->fail( 'Expected WPAjaxDieContinueException was not thrown.' );
		} catch ( WPAjaxDieContinueException $e ) {
			$this->assertTrue( json_decode( $this->_last_response ) );
		}

		remove_filter( 'pre_http_request', $api_response );
	}
}
