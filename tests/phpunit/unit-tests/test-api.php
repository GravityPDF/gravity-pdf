<?php

namespace GFPDF\Tests;

use GFPDF\Model\Model_Settings;
use GPDFAPI;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Helper Misc Functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
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
	 * @param string $expected
	 * @param string $method
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
			[ 'GFPDF_Vendor\Monolog\Logger', 'get_log_class' ],
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

		$pdfs = GPDFAPI::get_form_pdfs( $GLOBALS['GFPDF_Test']->form['all-form-fields']['id'] );
		$this->assertCount( 4, $pdfs );

		$this->assertArrayHasKey( 'id', $pdfs['555ad84787d7e'] );
		$this->assertArrayHasKey( 'filename', $pdfs['555ad84787d7e'] );
		$this->assertArrayHasKey( 'template', $pdfs['555ad84787d7e'] );
		$this->assertArrayHasKey( 'notification', $pdfs['555ad84787d7e'] );
		$this->assertArrayHasKey( 'conditionalLogic', $pdfs['555ad84787d7e'] );
	}

	/**
	 * Check we can get a form's PDF settings
	 *
	 * @since 6.0
	 */
	public function test_get_entry_pdfs() {
		$this->assertTrue( is_wp_error( GPDFAPI::get_entry_pdfs( null ) ) );

		$pdfs = GPDFAPI::get_entry_pdfs( $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0]['id'] );
		$this->assertCount( 2, $pdfs );

		$this->assertArrayHasKey( 'id', $pdfs['fawf90c678523b'] );
		$this->assertArrayHasKey( 'filename', $pdfs['fawf90c678523b'] );
		$this->assertArrayHasKey( 'template', $pdfs['fawf90c678523b'] );
		$this->assertArrayHasKey( 'notification', $pdfs['fawf90c678523b'] );
		$this->assertArrayHasKey( 'conditionalLogic', $pdfs['fawf90c678523b'] );
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
		$this->assertSame( 0, count( GPDFAPI::get_plugin_settings() ) );
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
		$this->assertEquals( 'font_validation_error', $results->get_error_code() );

		$results = GPDFAPI::add_pdf_font( [ 'font_name' => 'Apple%' ] );

		$this->assertTrue( is_wp_error( $results ) );
		$this->assertEquals( 'font_validation_error', $results->get_error_code() );

		/* Test we correctly install the font */
		$ttf_file = __DIR__ . '/fonts/Chewy.ttf';

		$font = [
			'font_name' => 'Test',
			'regular'   => $ttf_file,
		];

		$results = GPDFAPI::add_pdf_font( $font );

		$this->assertFalse( is_wp_error( $results ) );
		$this->assertTrue( $results );
		$this->assertFileExists( PDF_FONT_LOCATION . 'Chewy.ttf' );

		/* Clean up */
		GPDFAPI::delete_pdf_font( 'test' );
	}

	public function test_add_pdf_font_duplicate() {
		$ttf_file = __DIR__ . '/fonts/Chewy.ttf';

		$font = [
			'font_name' => 'Test',
			'regular'   => $ttf_file,
		];

		$this->assertTrue( GPDFAPI::add_pdf_font( $font ) );
		$this->assertTrue( GPDFAPI::add_pdf_font( $font ) );

		$this->assertCount( 1, GPDFAPI::get_pdf_fonts()['User-Defined Fonts'] ?? [] );
	}

	/**
	 * Test we can correctly delete the font
	 *
	 * @since 4.1
	 */
	public function test_delete_pdf_font() {

		/** @var Model_Settings $settings */
		$settings = GPDFAPI::get_mvc_class( 'Model_Settings' );

		/* Test font not installed */
		$results = GPDFAPI::delete_pdf_font( '' );

		$this->assertTrue( is_wp_error( $results ) );
		$this->assertEquals( 'invalid_font_id', $results->get_error_code() );

		/* Add a font and then see if we can remove it */
		$ttf_file = __DIR__ . '/fonts/Chewy.ttf';

		$font = [
			'font_name' => 'Test',
			'regular'   => $ttf_file,
		];

		$results = GPDFAPI::add_pdf_font( $font );
		$this->assertFalse( is_wp_error( $results ) );

		/* Now remove the newly added font and verify the results */
		$results = GPDFAPI::delete_pdf_font( 'test' );

		$this->assertTrue( $results );
		$this->assertFileDoesNotExist( PDF_FONT_LOCATION . 'Chewy.ttf' );
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
