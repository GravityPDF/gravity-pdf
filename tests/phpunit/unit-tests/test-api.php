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
 * @since 4.0
 * @group api
 */
class Test_API extends WP_UnitTestCase {

	/**
	 * Check the correct class is returned
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_classes
	 */
	public function test_get_class( $expected, $method ) {
		$this->assertEquals( $expected, get_class( GPDFAPI::$method() ) );
	}

	/**
	 * The data provider passing in our class getter methods and expected values
	 *
	 * @since 4.0
	 */
	public function provider_classes() {
		return [
			[ 'Monolog\Logger', 'get_log_class' ],
			[ 'GFPDF\Helper\Helper_Notices', 'get_notice_class' ],
			[ 'GFPDF\Helper\Helper_Data', 'get_data_class' ],
			[ 'GFPDF\Helper\Helper_Options_Fields', 'get_options_class' ],
			[ 'GFPDF\Helper\Helper_Misc', 'get_misc_class' ],
			[ 'GFPDF\Helper\Helper_Form', 'get_form_class' ],
			[ 'GFPDF\Helper\Helper_Templates', 'get_templates_class' ],
		];
	}

	/**
	 * Check we can get a form's PDF settings
	 *
	 * @since 4.0
	 */
	public function test_get_form_pdfs() {
		$this->assertTrue( is_wp_error( GPDFAPI::get_form_pdfs( null ) ) );
	}

	/**
	 * Check we are returning the correct class when called
	 *
	 * @since 4.0
	 */
	public function test_get_pdf_class() {
		/* Check View Class */
		$class = GPDFAPI::get_pdf_class();
		$this->assertEquals( 'GFPDF\View\View_PDF', get_class( $class ) );

		/* Check Model Class */
		$class = GPDFAPI::get_pdf_class( 'model' );
		$this->assertEquals( 'GFPDF\Model\Model_PDF', get_class( $class ) );
	}

	/**
	 * Verify our API returns the correct classes
	 *
	 * @since 4.0
	 */
	public function test_get_mvc_class() {
		$class = GPDFAPI::get_mvc_class( 'Model_Install' );

		$this->assertEquals( 'GFPDF\Model\Model_Install', get_class( $class ) );

		$this->assertFalse( GPDFAPI::get_mvc_class( 'Fake_Class' ) );
	}

	/**
	 * Check we can add a new PDF
	 *
	 * @since 4.0
	 */
	public function test_add_update_delete() {

		/* Check we can add a new PDF */
		$id = GPDFAPI::add_pdf( $GLOBALS['GFPDF_Test']->form['form-settings']['id'], [ 'working' => 'yes' ] );
		$this->assertNotFalse( $id );

		/* Check we can get the PDF details */
		$pdf = GPDFAPI::get_pdf( $GLOBALS['GFPDF_Test']->form['form-settings']['id'], $id );
		$this->assertEquals( 'yes', $pdf['working'] );

		/* Check we can update the PDF details correctly */
		GPDFAPI::update_pdf( $GLOBALS['GFPDF_Test']->form['form-settings']['id'], $id, [ 'working' => 'no' ] );
		$pdf = GPDFAPI::get_pdf( $GLOBALS['GFPDF_Test']->form['form-settings']['id'], $id );
		$this->assertEquals( 'no', $pdf['working'] );

		/* Check we can delete the PDF correctly */
		GPDFAPI::delete_pdf( $GLOBALS['GFPDF_Test']->form['form-settings']['id'], $id );
		$pdf = GPDFAPI::get_pdf( $GLOBALS['GFPDF_Test']->form['form-settings']['id'], $id );
		$this->assertTrue( is_wp_error( $pdf ) );
	}

	/**
	 * Check we can get the global Gravity PDF settings
	 *
	 * @since 4.0
	 */
	public function test_get_plugin_settings() {

		/* Add some settings */
		GPDFAPI::update_plugin_option( 'item1', 'yes' );
		GPDFAPI::update_plugin_option( 'item2', 'no' );

		/* Select the settings and verify the results */
		$settings = GPDFAPI::get_plugin_settings();

		$this->assertEquals( 'yes', $settings['item1'] );
		$this->assertEquals( 'no', $settings['item2'] );

		/* Add another option but cause an error */
		$this->assertTrue( is_wp_error( GPDFAPI::add_plugin_option( 'item1', 'yes' ) ) );
		$this->assertTrue( GPDFAPI::add_plugin_option( 'item3', 'maybe' ) );

		/* Check our getter works correctly */
		$this->assertEquals( 'maybe', GPDFAPI::get_plugin_option( 'item3' ) );

		/* Check our delete function works correctly */
		GPDFAPI::delete_plugin_option( 'item3' );
		$this->assertEquals( '', GPDFAPI::get_plugin_option( 'item3' ) );

		/* Cleanup */
		GPDFAPI::delete_plugin_option( 'item2' );
		GPDFAPI::delete_plugin_option( 'item1' );

		/* Verify cleanup */
		$this->assertSame( 0, sizeof( GPDFAPI::get_plugin_settings() ) );
	}

	/**
	 * Ensure we generate the product table correctly
	 *
	 * @since 4.0
	 */
	public function test_product_table() {

		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		$table = GPDFAPI::product_table( $entry, true );
		$this->assertNotFalse( strpos( $table, '<table class="entry-products' ) );

		ob_start();
		GPDFAPI::product_table( $entry );
		$table = ob_get_clean();
		$this->assertNotFalse( strpos( $table, '<table class="entry-products' ) );
	}

	/**
	 * Ensure we generate the likert table correctly
	 *
	 * @since 4.0
	 */
	public function test_likert_table() {
		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		$table = GPDFAPI::likert_table( $entry, 26, true );
		$this->assertNotFalse( strpos( $table, "class='gsurvey-likert-choice-label'" ) );

		ob_start();
		GPDFAPI::likert_table( $entry, 26 );
		$table = ob_get_clean();
		$this->assertNotFalse( strpos( $table, "class='gsurvey-likert-choice-label'" ) );
	}

	/**
	 * Test we can add our font correctly
	 *
	 * @since 4.1
	 */
	public function test_add_pdf_font() {

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
	 * @since 4.1
	 */
	public function test_delete_pdf_font() {

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
	 * @since 5.0
	 */
	public function test_get_form_data() {
		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		$results = GPDFAPI::get_form_data( $entry['id'] );

		$this->assertArrayHasKey( 'misc', $results );
		$this->assertArrayHasKey( 'field', $results );
		$this->assertArrayHasKey( 'list', $results );
		$this->assertArrayHasKey( 'signature_details_id', $results );
		$this->assertArrayHasKey( 'products', $results );
		$this->assertArrayHasKey( 'products_totals', $results );
		$this->assertArrayHasKey( 'poll', $results );
		$this->assertArrayHasKey( 'survey', $results );

		$this->assertEquals( 'My Single Line Response', $results['field'][1] );
	}
}
