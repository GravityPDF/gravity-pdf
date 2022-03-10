<?php

namespace GFPDF\Tests;

use Exception;
use GFPDF\Helper\Helper_Templates;
use GPDFAPI;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Templates Helper class
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * @since 4.1
 *
 * @group helper-templates
 */
class Test_Templates_Helper extends WP_UnitTestCase {

	/**
	 * Holds our Helper_Templates object
	 * used to ease access to our PDF templates
	 *
	 * @var Helper_Templates
	 *
	 * @since 4.1
	 */
	public $templates;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.1
	 */
	public function set_up() {
		global $gfpdf;

		/* run parent method */
		parent::set_up();

		/* Setup out loader class */
		$this->templates = new Helper_Templates( $gfpdf->log, $gfpdf->data, $gfpdf->gform );

		/* Cleanup the PDF working directory */
		$gfpdf->misc->rmdir( $gfpdf->data->template_location );
		$installer = GPDFAPI::get_mvc_class( 'Model_Install' );
		$installer->create_folder_structures();
	}

	/**
	 * Create our testing data
	 *
	 * @since 4.1
	 */
	private function create_form_and_entries() {
		global $gfpdf;

		$form  = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		$gfpdf->data->form_settings                = [];
		$gfpdf->data->form_settings[ $form['id'] ] = $form['gfpdf_form_settings'];

		return [
			'form'  => $form,
			'entry' => $entry,
		];
	}

	/**
	 * Check the get_template_path() returns correct value
	 *
	 * @since 4.1
	 */
	public function test_get_template_path() {
		$this->assertNotFalse( strpos( $this->templates->get_template_path(), '/wp-content/uploads/PDF_EXTENDED_TEMPLATES/' ) );
		$this->assertTrue( is_dir( $this->templates->get_template_path() ) );
	}

	/**
	 * Check the get_template_url() returns correct value
	 *
	 * @since 4.1
	 */
	public function test_get_template_url() {
		$this->assertNotFalse( strpos( $this->templates->get_template_url(), '/wp-content/uploads/PDF_EXTENDED_TEMPLATES/' ) );
		$this->assertNotFalse( filter_var( $this->templates->get_template_url(), FILTER_VALIDATE_URL ) );
	}

	/**
	 * Test the get_all_templates() functionality
	 * By extension the get_unfiltered_template_list() and parse_unique_templates() are also tested
	 *
	 * @since 4.1
	 */
	public function test_get_all_templates() {
		global $gfpdf;

		$templates = $this->templates->get_all_templates();

		/* Test the standard templates */
		$this->assertEquals( 4, count( $templates ) );

		/* Test for additional templates in PDF working directory */
		touch( $gfpdf->data->template_location . 'test.php' );
		touch( $gfpdf->data->template_location . 'test2.php' );

		$templates = $this->templates->get_all_templates();
		$this->assertEquals( 6, count( $templates ) );

		/* Test for override */
		$templates = $this->templates->get_all_templates();
		touch( $gfpdf->data->template_location . 'zadani.php' );

		$this->assertEquals( 6, count( $templates ) );

		/* Check that a configuration.php or configuration.archive.php file don't count */
		touch( $gfpdf->data->template_location . 'configuration.php' );
		touch( $gfpdf->data->template_location . 'configuration.archive.php' );

		$templates = $this->templates->get_all_templates();
		$this->assertEquals( 6, count( $templates ) );

		/* Test for multisite templates */
		if ( is_multisite() ) {
			touch( $gfpdf->data->multisite_template_location . 'test3.php' );
			$templates = $this->templates->get_all_templates();
			$this->assertEquals( 7, count( $templates ) );

			/* Check for override */
			touch( $gfpdf->data->multisite_template_location . 'zadani.php' );

			$templates = $this->templates->get_all_templates();
			$this->assertEquals( 7, count( $templates ) );
		}
	}

	/**
	 * Test the get_all_templates_by_group() functionality
	 *
	 * @since 4.1
	 */
	public function test_get_all_templates_by_group() {
		$templates = $this->templates->get_all_templates_by_group();

		$this->assertArrayHasKey( 'Core', $templates );
		$this->assertSame( 4, count( $templates['Core'] ) );
	}

	/**
	 * Check the is_template_compatible() method
	 *
	 * @since 4.1
	 */
	public function test_is_template_compatible() {
		$this->assertTrue( $this->templates->is_template_compatible( (float) PDF_EXTENDED_VERSION - 1 ) );
		$this->assertTrue( $this->templates->is_template_compatible( PDF_EXTENDED_VERSION ) );
		$this->assertFalse( $this->templates->is_template_compatible( (float) PDF_EXTENDED_VERSION + 1 ) );
	}

	/**
	 * Check the maybe_add_template_compatibility_notice() returns correctly
	 *
	 * @since 4.1
	 */
	public function test_maybe_add_template_compatibility_notice() {
		$this->assertEquals( 'Template', $this->templates->maybe_add_template_compatibility_notice( 'Template', PDF_EXTENDED_VERSION ) );

		$version = (float) PDF_EXTENDED_VERSION + 1;
		$this->assertEquals( 'Template (Requires Gravity PDF v' . $version . ')', $this->templates->maybe_add_template_compatibility_notice( 'Template', $version ) );
	}

	/**
	 * Check the get_all_template_info() returns the correct information
	 *
	 * Also checks get_template_info_by_path() and a side check for get_all_templates()
	 */
	public function test_get_all_template_info() {

		foreach ( $this->templates->get_all_template_info() as $template ) {
			$this->assertArrayHasKey( 'template', $template );
			$this->assertArrayHasKey( 'version', $template );
			$this->assertArrayHasKey( 'description', $template );
			$this->assertArrayHasKey( 'author', $template );
			$this->assertArrayHasKey( 'author uri', $template );
			$this->assertArrayHasKey( 'group', $template );
			$this->assertArrayHasKey( 'required_pdf_version', $template );
			$this->assertArrayHasKey( 'tags', $template );
			$this->assertArrayHasKey( 'id', $template );
			$this->assertArrayHasKey( 'path', $template );
			$this->assertArrayHasKey( 'screenshot', $template );
		}
	}

	/**
	 * Check we get the correct path to the PDF
	 *
	 * @since 4.1
	 */
	public function test_get_template_path_by_id() {
		global $gfpdf;

		/* Test the core template files */
		$path = $this->templates->get_template_path_by_id( 'zadani' );
		$this->assertEquals( PDF_PLUGIN_DIR . 'src/templates/zadani.php', $path );

		/* Test our PDF working directory override */
		$test_path = $gfpdf->data->template_location . 'zadani.php';
		touch( $test_path );

		$path = $this->templates->get_template_path_by_id( 'zadani' );
		$this->assertEquals( $test_path, $path );

		unlink( $test_path );

		if ( is_multisite() ) {
			$test_path = $gfpdf->data->multisite_template_location . 'zadani.php';
			touch( $test_path );

			$path = $this->templates->get_template_path_by_id( 'zadani' );
			$this->assertEquals( $test_path, $path );

			unlink( $test_path );
		}

		/* Check exception */
		try {
			$this->templates->get_template_path_by_id( 'doesnt-exist' );
		} catch ( Exception $e ) {

		}

		$this->assertEquals( 'Could not find the template: doesnt-exist.php', $e->getMessage() );
	}

	/**
	 * Check we get the expected results from get_template_info_by_id()
	 *
	 * @since 4.1
	 */
	public function test_get_template_info_by_id() {
		$info = $this->templates->get_template_info_by_id( 'zadani' );

		$this->assertEquals( 'zadani', $info['id'] );
		$this->assertEquals( 'Zadani', $info['template'] );
		$this->assertEquals( 'Core', $info['group'] );

		$info = $this->templates->get_template_info_by_id( 'rubix' );

		$this->assertEquals( 'rubix', $info['id'] );
	}

	/**
	 * Check we get all the correct files
	 *
	 * Tests get_template_path_by_id(), get_config_path_by_id() and get_template_image()
	 *
	 * @since 4.1
	 */
	public function test_get_template_files_by_id() {
		global $gfpdf;

		/* Setup our test files */
		$test_template        = $gfpdf->data->template_location . 'test.php';
		$test_template_config = $gfpdf->data->template_location . 'config/test.php';
		$test_template_image  = $gfpdf->data->template_location . 'images/test.png';

		mkdir( $gfpdf->data->template_location . 'config' );
		mkdir( $gfpdf->data->template_location . 'images' );
		touch( $test_template );
		touch( $test_template_config );
		touch( $test_template_image );

		/* Run our first test */
		try {
			$files = $this->templates->get_template_files_by_id( 'test' );
		} catch ( Exception $e ) {
			//do nothing
		}

		$this->assertNotFalse( strpos( $files[0], 'PDF_EXTENDED_TEMPLATES/test.php' ) );
		$this->assertNotFalse( strpos( $files[1], 'PDF_EXTENDED_TEMPLATES/config/test.php' ) );
		$this->assertNotFalse( strpos( $files[2], 'PDF_EXTENDED_TEMPLATES/images/test.png' ) );

		/* Cleanup config and image directories */
		$gfpdf->misc->rmdir( $gfpdf->data->template_location . 'config' );
		$gfpdf->misc->rmdir( $gfpdf->data->template_location . 'images' );

		/* Test we get the correct number of files returned after removing config and image files */
		try {
			$files = $this->templates->get_template_files_by_id( 'test' );
		} catch ( Exception $e ) {
			//do nothing
		}

		$this->assertEquals( 1, count( $files ) );

		/* Test for an exception */
		unlink( $test_template );

		try {
			$files = $this->templates->get_template_files_by_id( 'test' );
		} catch ( Exception $e ) {
			//do nothing
		}

		$this->assertEquals( 'Could not find PDF template file', $e->getMessage() );
	}

	/**
	 * Check if we are registering our core custom template appearance settings correctly
	 *
	 * Tests get_config_class(), load_template_config_file() and get_config_class_name()
	 *
	 * @since 4.1
	 */
	public function test_get_template_configuration() {

		/* Test failure first */
		$this->assertEquals( 'stdClass', get_class( $this->templates->get_config_class( 'test' ) ) );

		/* Test default template */
		$this->assertEquals( 'GFPDF\Templates\Config\Zadani', get_class( $this->templates->get_config_class( 'zadani' ) ) );

		/* Test legacy templates */
		$this->assertEquals( 'GFPDF\Templates\Config\Legacy', get_class( $this->templates->get_config_class( 'default-template' ) ) );
	}

	/**
	 * Test our PDF template headers are all registered
	 *
	 * @since 4.1
	 */
	public function test_get_template_header_details() {
		$header = $this->templates->get_template_header_details();

		$this->assertArrayHasKey( 'template', $header );
		$this->assertArrayHasKey( 'version', $header );
		$this->assertArrayHasKey( 'description', $header );
		$this->assertArrayHasKey( 'author', $header );
		$this->assertArrayHasKey( 'group', $header );
		$this->assertArrayHasKey( 'required_pdf_version', $header );
	}

	/**
	 * Check we can correctly read the template headers
	 *
	 * @since 4.1
	 */
	public function test_get_template_headers() {
		global $gfpdf;

		$header = $this->templates->get_template_info_by_path( PDF_PLUGIN_DIR . 'src/templates/zadani.php' );

		$this->assertEquals( 'Zadani', $header['template'] );
		$this->assertArrayHasKey( 'version', $header );
		$this->assertArrayHasKey( 'description', $header );
		$this->assertArrayHasKey( 'author', $header );
		$this->assertArrayHasKey( 'author uri', $header );
		$this->assertEquals( 'Core', $header['group'] );
		$this->assertEquals( '4.0-alpha', $header['required_pdf_version'] );
		$this->assertArrayHasKey( 'tags', $header );
		$this->assertArrayHasKey( 'screenshot', $header );

		$this->assertCount( 1, get_transient( $gfpdf->data->template_transient_cache ) );

		$this->templates->flush_template_transient_cache();

		$this->assertFalse( get_transient( $gfpdf->data->template_transient_cache ) );
		$gfpdf->options->update_option( 'debug_mode', 'Yes' );
		$this->templates->get_template_info_by_path( PDF_PLUGIN_DIR . 'src/templates/zadani.php' );
		$this->assertFalse( get_transient( $gfpdf->data->template_transient_cache ) );
	}

	/**
	 * Check we can get the core PDF templates
	 *
	 * @since 4.1
	 */
	public function test_get_plugin_pdf_templates() {
		$core_templates = $this->templates->get_core_pdf_templates();
		$this->assertNotSame( 0, count( $core_templates ) );
		$this->assertNotSame( false, strpos( $core_templates[0], 'blank-slate.php' ) );
	}

	/**
	 * Check we convert IDs into something human readable
	 *
	 * Tests human_readable_template_name()
	 *
	 * @param  $expected
	 * @param  $name
	 *
	 * @since        4.1
	 *
	 * @dataProvider provider_human_readable
	 */
	public function test_human_readable( $expected, $name ) {
		$this->assertEquals( $expected, $this->templates->human_readable_template_name( $name ) );
	}

	/**
	 * Data provider for human_readable test
	 *
	 * @return array
	 *
	 * @since  4.1
	 */
	public function provider_human_readable() {
		return [
			[ 'My Pretty Name', 'my_pretty-name' ],
			[ 'Working Title', 'worKing-title' ],
			[ 'Easy Listening', 'Easy Listening' ],
			[ 'Double  Trouble  Listening', 'Double--Trouble__listening' ],
			[ 'Out Of This World', 'OUT_OF_THIS_WORLD' ],
		];
	}

	/**
	 * Test we get the correct image, allowing for override in our PDF working directory
	 *
	 * Tests get_template_image()
	 *
	 * @since 4.1
	 */
	public function test_get_template_image() {
		global $gfpdf;

		$test_path = PDF_PLUGIN_URL . 'src/templates/images/zadani.png';
		$this->assertEquals( $test_path, $this->templates->get_template_image( 'zadani' ) );

		/* Test image override */
		mkdir( $gfpdf->data->template_location . 'images' );
		touch( $gfpdf->data->template_location . 'images/zadani.png' );

		$test_path = $gfpdf->data->template_location_url . 'images/zadani.png';
		$this->assertEquals( $test_path, $this->templates->get_template_image( 'zadani' ) );

		$gfpdf->misc->rmdir( $gfpdf->data->template_location . 'images' );

		if ( is_multisite() ) {
			mkdir( $gfpdf->data->multisite_template_location . 'images' );
			touch( $gfpdf->data->multisite_template_location . 'images/zadani.png' );

			$test_path = $gfpdf->data->multisite_template_location_url . 'images/zadani.png';
			$this->assertEquals( $test_path, $this->templates->get_template_image( 'zadani' ) );

			$gfpdf->misc->rmdir( $gfpdf->data->multisite_template_location . 'images' );
		}
	}

	/**
	 * Check that the appropriate array keys are returned when getting the template arguments
	 *
	 * @since 4.1
	 */
	public function test_get_template_args() {

		/* Get test entry and Gravity Forms settings */
		$results   = $this->create_form_and_entries();
		$model_pdf = GPDFAPI::get_mvc_class( 'Model_PDF' );
		$misc      = GPDFAPI::get_misc_class();
		$gform     = GPDFAPI::get_form_class();

		$entry = $results['entry'];
		$form  = $gform->get_form( $entry['form_id'] );
		$pdf   = GPDFAPI::get_pdf( $entry['form_id'], '556690c67856b' );

		/* Pass details on to our test method */
		$data = $this->templates->get_template_arguments(
			$form,
			$misc->get_fields_sorted_by_id( $form['id'] ),
			$entry,
			$model_pdf->get_form_data( $entry ),
			$pdf,
			$this->templates->get_config_class( $pdf['template'] ),
			$misc->get_legacy_ids( $entry['id'], $pdf )
		);

		/* Check all our keys exist */
		$this->assertArrayHasKey( 'form_id', $data );
		$this->assertArrayHasKey( 'lead_ids', $data );
		$this->assertArrayHasKey( 'lead_id', $data );
		$this->assertArrayHasKey( 'form', $data );
		$this->assertArrayHasKey( 'entry', $data );
		$this->assertArrayHasKey( 'lead', $data );
		$this->assertArrayHasKey( 'form_data', $data );
		$this->assertArrayHasKey( 'settings', $data );
		$this->assertArrayHasKey( 'fields', $data );
		$this->assertArrayHasKey( 'config', $data );
		$this->assertArrayHasKey( 'gfpdf', $data );

		/* Sniff that our keys have the correct details */
		$this->assertEquals( $entry['form_id'], $data['form_id'] );
		$this->assertSame( $entry['id'], $data['lead_id'] );
		$this->assertSame( [ $entry['id'] ], $data['lead_ids'] );
		$this->assertIsArray( $data['form'] );
		$this->assertIsArray( $data['entry'] );
		$this->assertIsArray( $data['lead'] );
		$this->assertIsArray( $data['form_data'] );
		$this->assertSame( $data['entry'], $data['lead'] );
		$this->assertSame( $pdf, $data['settings'] );
		$this->assertInstanceOf( 'GFPDF\Router', $data['gfpdf'] );
		$this->assertIsArray( $data['fields'] );
		$this->assertInstanceOf( 'GF_Field_Checkbox', $data['fields'][47] );
		$this->assertInstanceOf( 'GFPDF\Templates\Config\Zadani', $data['config'] );

		/* Check our config class has the settings populated */
		$this->assertSame( $data['settings'], $data['config']->get_settings() );
	}
}
