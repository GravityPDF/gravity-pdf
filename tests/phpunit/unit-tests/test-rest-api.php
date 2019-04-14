<?php

namespace GFPDF\Tests;

use GPDFAPI;

use WP_UnitTestCase;

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
class Test_Rest_API extends WP_UnitTestCase {

	/**
	 * Test we can add our core font correctly
	 *
	 * @since 5.2
	 */
	public function test_add_core_font() {

		$settings = GPDFAPI::get_mvc_class( 'Api_Fonts_Core' );

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


}
