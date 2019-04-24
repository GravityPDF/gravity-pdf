<?php

namespace GFPDF\Tests;

use GPDFAPI;

// use WP_UnitTestCase;
use WP_Ajax_UnitTestCase;

use GFAPI;
use GFForms;

use WPAjaxDieStopException;
use WPAjaxDieContinueException;


/**
 * Test Gravity PDF Hlper Misc Functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/*
	This file is part of Gravity PDF.

	Gravity PDF – Copyright (c) 2019, Blue Liquid Designs

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
 * Test the GPDFAPI class
 *
 * @since 5.2
 * @group rest-api
 */
class Test_Rest_API extends WP_Ajax_UnitTestCase {

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 5.2
	 */
	public function setUp() {

		parent::setUp();
		
	}

	/**
	 * Sample Test case
	 *
	 * @since 5.2
	 */
	public function test_sample() {
		$this->assertTrue( true );
	}		


	/**
	 * Test we can correctly save core font
	 *
	 * @since 5.2
	 */
	public function test_rest_api_save_core_font() {
		/* set up our post data and role */
		$this->_setRole( 'administrator' );

		/* Setup a bad request */
		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_save_core_font' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		/* Check for nonce failure */
		// try {
		// 	$this->_handleAjax( 'gfpdf_save_core_font' );
		// } catch ( WPAjaxDieStopException $e ) {
		// 	/* do nothing (error expected) */
		// 	$e->getMessage();
		// }

		// $this->assertEquals( '401', $e->getMessage() );

		// /* Setup a bad request */
		// $_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		// try {
		// 	$this->_handleAjax( 'gfpdf_save_core_font' );
		// } catch ( WPAjaxDieContinueException $e ) {
		// 	/* do nothing (error expected) */
		// }

		// $this->assertFalse( json_decode( $this->_last_response ) );
		// $this->_last_response = '';

		// $api_response = function() {
		// 	return [
		// 		'response' => [ 'code' => 200 ],
		// 		'body'     => '',
		// 	];
		// };

		// add_filter( 'pre_http_request', $api_response );

		// try {
		// 	$this->_handleAjax( 'gfpdf_save_core_font' );
		// } catch ( WPAjaxDieContinueException $e ) {
		// 	/* do nothing (error expected) */
		// }

		// remove_filter( 'pre_http_request', $api_response );

		// $this->assertTrue( json_decode( $this->_last_response ) );
	}

	/**
	 * Test we can correctly download and save font
	 *
	 * @since 5.2
	 */
	public function test_rest_api_download_and_save_font() {
		/* set up our post data and role */
		$this->_setRole( 'administrator' );


		/* Setup a bad request */
		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_save_core_font' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		
		// $request = $this->getMockBuilder( 'ArrayAccess' )->setMockClassName( 'WP_REST_Request' )->getMock();

		/* Check for nonce failure */
		// try {
		// 	$this->_handleAjax( 'gfpdf_save_core_font' );
		// } catch ( WPAjaxDieStopException $e ) {
		// 	/* do nothing (error expected) */
		// 	$e->getMessage();
		// }

		// $this->assertEquals( '401', $e->getMessage() );

		// /* Setup a bad request */
		// $_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		// try {
		// 	$this->_handleAjax( 'gfpdf_save_core_font' );
		// } catch ( WPAjaxDieContinueException $e ) {
		// 	/* do nothing (error expected) */
		// }

		// $this->assertFalse( json_decode( $this->_last_response ) );
		// $this->_last_response = '';

		// $api_response = function() {
		// 	return [
		// 		'response' => [ 'code' => 200 ],
		// 		'body'     => '',
		// 	];
		// };

		// add_filter( 'pre_http_request', $api_response );

		// try {
		// 	$this->_handleAjax( 'gfpdf_save_core_font' );
		// } catch ( WPAjaxDieContinueException $e ) {
		// 	/* do nothing (error expected) */
		// }

		// remove_filter( 'pre_http_request', $api_response );

		// $this->assertTrue( json_decode( $this->_last_response ) );
	}


	/**
	 * Test we can save font correctly
	 *
	 * @since 5.2
	 */
	public function test_rest_api_save_font() {

		$settings = GPDFAPI::get_mvc_class( 'Model_Settings' );

		/* Check we get invalid font error */
		$results = GPDFAPI::add_pdf_font( '' );

		$this->assertTrue( is_wp_error( $results ) );
		$this->assertEquals( 'invalid_font_name', $results->get_error_code() );

		$results = GPDFAPI::add_pdf_font( [ 'font_name' => 'Apple%' ] );

		$this->assertTrue( is_wp_error( $results ) );
		$this->assertEquals( 'invalid_font_name', $results->get_error_code() );

		/* Test we correctly install the font */
		$ttf_file = PDF_TEMPLATE_LOCATION . 'test.ttf';
		touch( $ttf_file );

		$font = [
			'font_name' => 'Test',
			'regular'   => $ttf_file,
		];

		$results = GPDFAPI::add_pdf_font( $font );

		$this->assertFalse( is_wp_error( $results ) );
		$this->assertTrue( $results );
		$this->assertFileExists( PDF_FONT_LOCATION . 'test.ttf' );
		$this->assertNotNull( $settings->get_font_id_by_name( 'Test' ) );

		/* Test we get an error for not having a unique font name */
		$results = GPDFAPI::add_pdf_font( $font );
		$this->assertTrue( is_wp_error( $results ) );
		$this->assertEquals( 'font_name_not_unique', $results->get_error_code() );

		/* Clean up */
		unlink( $ttf_file );
		GPDFAPI::delete_pdf_font( 'Test' );
	}

	/**
	 * Test we can correctly delete the font
	 *
	 * @since 5.2
	 */
	public function test_rest_api_delete_font() {

		$settings = GPDFAPI::get_mvc_class( 'Model_Settings' );

		/* Test font not installed */
		$results = GPDFAPI::delete_pdf_font( '' );

		$this->assertTrue( is_wp_error( $results ) );
		$this->assertEquals( 'font_not_installed', $results->get_error_code() );

		/* Add a font and then see if we can remove it */
		$ttf_file = PDF_TEMPLATE_LOCATION . 'test.ttf';
		touch( $ttf_file );

		$font = [
			'font_name' => 'Test',
			'regular'   => $ttf_file,
		];

		$results = GPDFAPI::add_pdf_font( $font );
		$this->assertFalse( is_wp_error( $results ) );

		/* Now remove the newly added font and verify the results */
		$results = GPDFAPI::delete_pdf_font( 'Test' );

		$this->assertTrue( $results );
		$this->assertFileNotExists( PDF_FONT_LOCATION . 'test.ttf' );
		$this->assertNull( $settings->get_font_id_by_name( 'Test' ) );

		/* Clean up */
		unlink( $ttf_file );
	}



	/**
	 * Test we can deactivate license
	 *
	 * @since 5.2
	 */
	public function test_rest_api_process_license_deactivation() {
		/* set up our post data and role */
		$this->_setRole( 'administrator' );

		/* Check for nonce failure */
		try {
			$this->_handleAjax( 'gfpdf_deactivate_license' );
		} catch ( WPAjaxDieStopException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( '401', $e->getMessage() );

		/* Setup a bad request */
		$_POST['nonce'] = wp_create_nonce( 'gfpdf_deactivate_license' );

		try {
			$this->_handleAjax( 'gfpdf_deactivate_license' );
		} catch ( WPAjaxDieContinueException $e ) {
			/* do nothing (error expected) */
		}

		$this->assertEquals( 'An error occurred during deactivation, please try again', json_decode( $this->_last_response )->error );
	}


	/**
	 * @param bool  $expected
	 * @param array $api
	 * @param int   $status
	 *
	 * @since        5.2
	 * @dataProvider provider_deactivate_license_key
	 */
	public function test_rest_api_deactivate_license_key( $expected, $api, $status ) {
		global $gfpdf;

		$this->add_addon_1();

		$api_response = function() use ( $api, $status ) {
			return [
				'response' => [ 'code' => $status ],
				'body'     => json_encode( $api ),
			];
		};

		add_filter( 'pre_http_request', $api_response );

		$results = $this->model->deactivate_license_key( $gfpdf->data->addon['my-custom-plugin'], '' );
		$this->assertSame( $expected, $results );

		remove_filter( 'pre_http_request', $api_response );
		$gfpdf->data->addon = [];
	}


	/**
	 * @return array
	 *
	 * @since 5.2
	 */
	public function provider_deactivate_license_key() {
		return [
			[ true, [ 'license' => 'deactivated' ], 200 ],
			[ false, [ 'license' => '' ], 200 ],
			[ false, [ 'license' => 'deactivated' ], 500 ],
		];
	}


}
