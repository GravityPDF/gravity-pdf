<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Integration;

use GFPDF\Model\Model_Settings;
use GPDFAPI;

/**
 * Test Gravity PDF Helper Misc Functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the GPDFAPI class
 *
 * @since 4.0
 * @group api
 */
class Test_API extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures(
			[ 'all-form-fields', 'form-settings' ],
			[ 'all-form-fields' ]
		);
		static::copy_test_fonts();
	}

	public static function tear_down_after_class(): void {
		static::remove_test_fonts();
		parent::tear_down_after_class();
	}

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
		$this->assertSame( $expected, get_class( GPDFAPI::$method() ) );
	}

	/**
	 * The data provider passing in our class getter methods and expected values
	 *
	 * @since 4.0
	 */
	public function provider_classes(): array {
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
		$this->assertInstanceOf( \WP_Error::class, GPDFAPI::get_form_pdfs( null ) );

		$pdfs = GPDFAPI::get_form_pdfs( $this->form( 'all-form-fields' )['id'] );
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
		$this->assertInstanceOf( \WP_Error::class, GPDFAPI::get_entry_pdfs( null ) );

		$pdfs = GPDFAPI::get_entry_pdfs( $this->entry( 'all-form-fields' )['id'] );
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
		$this->assertInstanceOf( 'GFPDF\View\View_PDF', $class );

		/* Check Model Class */
		$class = GPDFAPI::get_pdf_class( 'model' );
		$this->assertInstanceOf( 'GFPDF\Model\Model_PDF', $class );
	}

	/**
	 * Verify our API returns the correct classes
	 *
	 * @since 4.0
	 */
	public function test_get_mvc_class() {
		$class = GPDFAPI::get_mvc_class( 'Model_Install' );

		$this->assertInstanceOf( 'GFPDF\Model\Model_Install', $class );

		$this->assertFalse( GPDFAPI::get_mvc_class( 'Fake_Class' ) );
	}

	/**
	 * Check we can add a new PDF
	 *
	 * @since 4.0
	 */
	public function test_add_update_delete() {

		/* Check we can add a new PDF */
		$id = GPDFAPI::add_pdf( $this->form( 'form-settings' )['id'], [ 'working' => 'yes' ] );
		$this->assertNotFalse( $id );

		/* Check we can get the PDF details */
		$pdf = GPDFAPI::get_pdf( $this->form( 'form-settings' )['id'], $id );
		$this->assertSame( 'yes', $pdf['working'] );

		/* Check we can update the PDF details correctly */
		GPDFAPI::update_pdf( $this->form( 'form-settings' )['id'], $id, [ 'working' => 'no' ] );
		$pdf = GPDFAPI::get_pdf( $this->form( 'form-settings' )['id'], $id );
		$this->assertSame( 'no', $pdf['working'] );

		/* Check we can delete the PDF correctly */
		GPDFAPI::delete_pdf( $this->form( 'form-settings' )['id'], $id );
		$pdf = GPDFAPI::get_pdf( $this->form( 'form-settings' )['id'], $id );
		$this->assertInstanceOf( \WP_Error::class, $pdf );
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

		$this->assertSame( 'yes', $settings['item1'] );
		$this->assertSame( 'no', $settings['item2'] );

		/* Add another option but cause an error */
		$this->assertInstanceOf( \WP_Error::class, GPDFAPI::add_plugin_option( 'item1', 'yes' ) );
		$this->assertTrue( GPDFAPI::add_plugin_option( 'item3', 'maybe' ) );

		/* Check our getter works correctly */
		$this->assertSame( 'maybe', GPDFAPI::get_plugin_option( 'item3' ) );

		/* Check our delete function works correctly */
		GPDFAPI::delete_plugin_option( 'item3' );
		$this->assertSame( '', GPDFAPI::get_plugin_option( 'item3' ) );

		/* Cleanup */
		GPDFAPI::delete_plugin_option( 'item2' );
		GPDFAPI::delete_plugin_option( 'item1' );

		/* Verify cleanup */
		$this->assertCount( 0, GPDFAPI::get_plugin_settings() );
	}

	/**
	 * Ensure we generate the product table correctly
	 *
	 * @since 4.0
	 */
	public function test_product_table() {

		$entry = $this->entry( 'all-form-fields' );

		$table = GPDFAPI::product_table( $entry, true );
		$this->assertStringContainsString( '<table class="entry-products', $table );

		ob_start();
		GPDFAPI::product_table( $entry );
		$table = ob_get_clean();
		$this->assertStringContainsString( '<table class="entry-products', $table );
	}

	/**
	 * The inner likert markup is rendered by GFSurvey's GF_Field_Likert when that addon is
	 * available; when it isn't, the field falls back to base GF_Field and the inner value is
	 * a raw choice id - so this test asserts only the Gravity PDF wrapper that survives both.
	 *
	 * @since 4.0
	 */
	public function test_likert_table() {
		$entry = $this->entry( 'all-form-fields' );

		$html = GPDFAPI::likert_table( $entry, 26, true );
		$this->assertStringContainsString( '<div id="field-26"', $html );
		$this->assertStringContainsString( 'gfpdf-field gfpdf-likert gfpdf-survey', $html );
		$this->assertStringContainsString( '<strong>Likert Survey Field</strong>', $html );

		ob_start();
		$echo_return = GPDFAPI::likert_table( $entry, 26 );
		$html        = ob_get_clean();
		$this->assertNull( $echo_return );
		$this->assertStringContainsString( '<div id="field-26"', $html );

		$this->assertNull( GPDFAPI::likert_table( $entry, 99999, true ) );
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

		$this->assertInstanceOf( \WP_Error::class, $results );
		$this->assertSame( 'font_validation_error', $results->get_error_code() );

		$results = GPDFAPI::add_pdf_font( [ 'font_name' => 'Apple%' ] );

		$this->assertInstanceOf( \WP_Error::class, $results );
		$this->assertSame( 'font_validation_error', $results->get_error_code() );

		/* Test we correctly install the font */
		$ttf_file = PDF_PLUGIN_DIR . '/tools/phpunit/data/fonts/Chewy.ttf';

		$font = [
			'font_name' => 'Test',
			'regular'   => $ttf_file,
		];

		$results = GPDFAPI::add_pdf_font( $font );

		$this->assertNotInstanceOf( \WP_Error::class, $results );
		$this->assertTrue( $results );
		$this->assertFileExists( PDF_FONT_LOCATION . 'Chewy.ttf' );

		/* Clean up */
		GPDFAPI::delete_pdf_font( 'test' );
	}

	public function test_add_pdf_font_duplicate() {
		$ttf_file = PDF_PLUGIN_DIR . '/tools/phpunit/data/fonts/Chewy.ttf';

		$font = [
			'font_name' => 'Test',
			'regular'   => $ttf_file,
		];

		$this->assertTrue( GPDFAPI::add_pdf_font( $font ) );
		$this->assertTrue( GPDFAPI::add_pdf_font( $font ) );

		$this->assertCount( 1, GPDFAPI::get_pdf_fonts()['User-Defined Fonts'] ?? [] );

		/* Filesystem writes outlive the test transaction; remove the copied TTF so it doesn't collide with later tests' add_pdf_font calls. */
		GPDFAPI::delete_pdf_font( 'test' );
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

		$this->assertInstanceOf( \WP_Error::class, $results );
		$this->assertSame( 'invalid_font_id', $results->get_error_code() );

		/* Add a font and then see if we can remove it */
		$ttf_file = PDF_PLUGIN_DIR . '/tools/phpunit/data/fonts/Chewy.ttf';

		$font = [
			'font_name' => 'Test',
			'regular'   => $ttf_file,
		];

		$results = GPDFAPI::add_pdf_font( $font );
		$this->assertNotInstanceOf( \WP_Error::class, $results );

		/* Now remove the newly added font and verify the results */
		$results = GPDFAPI::delete_pdf_font( 'test' );

		$this->assertTrue( $results );
		$this->assertFileDoesNotExist( PDF_FONT_LOCATION . 'Chewy.ttf' );
	}

	/**
	 * Verify the appropriate variables are passed in and that a PDF is correctly generated
	 *
	 * @since 4.0
	 *
	 * @group slow
	 */
	public function test_create_pdf() {
		/* Setup our form and entries */
		$results = $this->form_and_entry();
		$entry   = $results['entry'];
		$pid     = '555ad84787d7e';

		/* Check for $entry error first */
		$pdf = GPDFAPI::create_pdf( '', '' );
		$this->assertSame( 'invalid_entry', $pdf->get_error_code() );

		/* Check for $settings error */
		$pdf = GPDFAPI::create_pdf( $entry['id'], '' );
		$this->assertSame( 'invalid_pdf_setting', $pdf->get_error_code() );

		/* Create the PDF and test it was correctly generated */
		add_filter(
			'gfpdf_pdf_config',
			function( $settings ) {
				$settings['template'] = 'zadani';

				return $settings;
			}
		);

		$filename = GPDFAPI::create_pdf( $entry['id'], $pid );

		$this->assertFileExists( $filename );

		unlink( $filename );

	}

	/**
	 * @since 5.0
	 */
	public function test_get_form_data() {
		$entry = $this->entry( 'all-form-fields' );

		$results = GPDFAPI::get_form_data( $entry['id'] );

		$this->assertArrayHasKey( 'misc', $results );
		$this->assertArrayHasKey( 'field', $results );
		$this->assertArrayHasKey( 'list', $results );
		$this->assertArrayHasKey( 'signature_details_id', $results );
		$this->assertArrayHasKey( 'products', $results );
		$this->assertArrayHasKey( 'products_totals', $results );
		$this->assertArrayHasKey( 'poll', $results );
		$this->assertArrayHasKey( 'survey', $results );

		$this->assertSame( 'My Single Line Response', $results['field'][1] );
	}

	/**
	 * Check we can retrieve the PDF filename, with and without the extension, and that the
	 * WP_Error paths are returned for invalid entry IDs and invalid PDF IDs.
	 *
	 * @since 7.0
	 */
	public function test_get_pdf_filename() {
		$entry_id = $this->entry( 'all-form-fields' )['id'];
		$pdf_id   = '555ad84787d7e';

		/* The extension is excluded by default */
		$this->assertSame( 'test', GPDFAPI::get_pdf_filename( $entry_id, $pdf_id ) );

		/* Explicitly excluding the extension matches the default */
		$this->assertSame( 'test', GPDFAPI::get_pdf_filename( $entry_id, $pdf_id, false ) );

		/* The extension is included when requested */
		$this->assertSame( 'test.pdf', GPDFAPI::get_pdf_filename( $entry_id, $pdf_id, true ) );

		/* An invalid entry ID returns a WP_Error */
		$invalid_entry = GPDFAPI::get_pdf_filename( '', $pdf_id );
		$this->assertInstanceOf( \WP_Error::class, $invalid_entry );
		$this->assertSame( 'invalid_entry', $invalid_entry->get_error_code() );

		/* A valid entry with an invalid PDF ID returns a WP_Error */
		$invalid_pdf = GPDFAPI::get_pdf_filename( $entry_id, 'does-not-exist' );
		$this->assertInstanceOf( \WP_Error::class, $invalid_pdf );
		$this->assertSame( 'invalid_pdf_setting', $invalid_pdf->get_error_code() );
	}
}
