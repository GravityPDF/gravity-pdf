<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Misc;
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
 * Test the Helper_Misc class
 *
 * @since 4.0
 * @group helper-misc
 */
class Test_Helper_Misc extends WP_UnitTestCase {
	/**
	 * Our test class
	 *
	 * @var Helper_Misc
	 *
	 * @since 4.0
	 */
	public $misc;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function set_up() {
		global $gfpdf;

		/* run parent method */
		parent::set_up();

		/* Setup our test classes */
		$this->misc = new Helper_Misc( $gfpdf->log, $gfpdf->gform, $gfpdf->data );
	}

	/**
	 * Create our testing data
	 *
	 * @since 4.0
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
	 * Ensure we correctly determine when we are on a Gravity PDF admin page
	 *
	 * @since 4.0
	 */
	public function test_is_gfpdf_page() {

		$this->assertFalse( $this->misc->is_gfpdf_page() );

		/* Set admin page */
		set_current_screen( 'dashboard-user' );
		$this->assertFalse( $this->misc->is_gfpdf_page() );

		/* Set up PDF page */
		$_GET['page'] = 'gfpdf-tools';
		$this->assertTrue( $this->misc->is_gfpdf_page() );

		unset( $_GET['page'] );

		$_GET['subview'] = 'PDF';
		$this->assertTrue( $this->misc->is_gfpdf_page() );
	}

	/**
	 * Check if we are on the current settings tab
	 *
	 * @since 4.0
	 */
	public function test_is_gfpdf_settings_tab() {
		$this->assertFalse( $this->misc->is_gfpdf_settings_tab( 'general' ) );

		/* Set admin page */
		set_current_screen( 'dashboard-user' );
		$_GET['subview'] = 'PDF';

		$this->assertTrue( $this->misc->is_gfpdf_settings_tab( 'general' ) );

		/* Try a different tab */
		$this->assertFalse( $this->misc->is_gfpdf_settings_tab( 'tools' ) );

		$_GET['tab'] = 'tools';
		$this->assertTrue( $this->misc->is_gfpdf_settings_tab( 'tools' ) );
	}

	/**
	 * Check if our HTML DOM manipulator correctly adds the class "header-footer-img" to <img /> tags
	 *
	 * @param  $expected
	 * @param  $html
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_test_fix_header_footer
	 */
	public function test_fix_header_footer( $expected, $html ) {
		$test_html     = $this->misc->fix_header_footer( $html );
		$minified_html = $this->minify( $test_html );

		$this->assertEquals( $expected, $minified_html );
	}

	protected function minify($html) {
		$html = preg_replace(
			[ '/\n/', '/\t/', '/\>\s+\</' ],
			[ '', '', '><' ],
			$html
		);

		return $html;
	}

	/**
	 * Dataprovider for our fix_header_footer method
	 *
	 * @since 4.0
	 */
	public function provider_test_fix_header_footer() {
		return [
			[
				'<p><img src="my-image.jpg" alt="My Image" class="header-footer-img"/></p>',
				'<img src="my-image.jpg" alt="My Image" />',
			],
			[
				'<div id="header"><img src="my-image.jpg" alt="My Image" class="header-footer-img"/></div>',
				'<div id="header"><img src="my-image.jpg" alt="My Image" /></div>',
			],
			[
				'<p><span>Intro</span><img src="my-image.jpg" alt="My Image" class="header-footer-img"/><span>Outro</span></p>',
				'<span>Intro</span> <img src="my-image.jpg" alt="My Image" /> <span>Outro</span>',
			],
			[
				'<p><b>This is bold</b>. <i>This is italics</i><img src="image.jpg" class="header-footer-img"/></p>',
				'<b>This is bold</b>. <i>This is italics</i> <img src="image.jpg" />',
			],
			[
				'<p><img src="my-image.jpg" alt="My Image" class="header-footer-img"/></p>',
				'<img src="my-image.jpg" alt="My Image">',
			],
			[
				'<p><div class="alternate"><img src="my-image.jpg" alt="My Image" class="alternate header-footer-img"/></div></p>',
				'<img src="my-image.jpg" alt="My Image" class="alternate" />',
			],
			[
				'<p><span>Nothing</span></p>',
				'<span>Nothing</span>',
			],
			[
				'',
				'',
			],
			[
				'<p><a href="#"><img src="my-image.jpg" alt="My Image" class="header-footer-img"/></a></p>',
				'<a href="#"><img src="my-image.jpg" alt="My Image" /></a>',
			],
			[
				'<p><div class="alternate"><a href="#"><img src="my-image.jpg" alt="My Image" class="alternate header-footer-img"/></a></div></p>',
				'<a href="#"><img src="my-image.jpg" alt="My Image" class="alternate" /></a>',
			],
		];
	}

	/**
	 * Check if our HTML DOM manipulator correctly changes local URLs to Paths
	 *
	 * @since 4.0
	 */
	public function test_fix_header_footer_path() {

		$html = $this->misc->fix_header_footer( '<img src="' . PDF_PLUGIN_URL . 'src/assets/images/cap-paws-sitting.png" alt="My Image" />' );
		$this->assertFalse( strpos( PDF_PLUGIN_URL, $html ) );

		$html = $this->misc->fix_header_footer( '<img src="http://test.com/image.png" alt="My Image" />' );
		$minified_html = $this->minify( $html );
		$this->assertEquals( '<p><img src="http://test.com/image.png" alt="My Image" class="header-footer-img"/></p>', $minified_html );
	}

	/**
	 * Check that we can push an associated array item onto the beginning of an existing array
	 *
	 * @since 4.0
	 */
	public function test_array_unshift_assoc() {
		$array = [
			'item1' => 'Yes',
			'item2' => 'Maybe',
			'item3' => 'I do not know',
		];

		$test = $this->misc->array_unshift_assoc( $array, 'item0', 'No' );

		$this->assertEquals( 'No', reset( $test ) );
		$this->assertEquals( 'Yes', next( $test ) );
		$this->assertEquals( 'I do not know', end( $test ) );
	}

	/**
	 * Test we are correctly stripping an extension from the end of a string
	 *
	 * @param  $expected
	 * @param  $string
	 * @param  $type
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_remove_extension_from_string
	 */
	public function test_remove_extension_from_string( $expected, $string, $type ) {
		$this->assertEquals( $expected, $this->misc->remove_extension_from_string( $string, $type ) );
	}

	/**
	 * Data provider for our remove_extension_from_string method
	 *
	 * @return array
	 *
	 * @since  4.0
	 */
	public function provider_remove_extension_from_string() {
		return [
			[ 'mydocument', 'mydocument.pdf', '.pdf' ],
			[ 'mydocument', 'mydocument.jpg', '.Jpg' ],
			[ 'mydocument.pdf', 'mydocument.pdf', '.pda' ],
			[ 'Helper_Document', 'Helper_Document.php', '.php' ],
			[ 'カタ_Document', 'カタ_Document.php', '.php' ],
			[ 'カタ_Document', 'カタ_Document.excel', '.excel' ],
			[ 'Working', 'Working.excel', '.excel' ],
			[ 'Working_漢字', 'Working_漢字.pdf', '.pdf' ],
		];
	}

	/**
	 * Test we correctly convert our v3 config data into the appropriate value
	 *
	 * @param  $expected
	 * @param  $value
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_update_deprecated_config
	 */
	public function test_update_deprecated_config( $expected, $value ) {
		$this->assertEquals( $expected, $this->misc->update_deprecated_config( $value ) );
	}

	/**
	 * Data provider for testing update_deprecated_config()
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function provider_update_deprecated_config() {
		return [
			[ 'Yes', true ],
			[ 'No', false ],
			[ null, null ],
			[ 'Other', 'Other' ],
			[ [ 1, 2, 3 ], [ 1, 2, 3 ] ],
			[ 'true', 'true' ],
			[ 'false', 'false' ],
		];
	}

	/**
	 * Check our contrast checker returns the correct contrasting colours
	 *
	 * @param string $expected The results we expect
	 * @param string $hexcolor The colour to test
	 *
	 * @dataProvider provider_get_contrast
	 * @since        4.0
	 */
	public function test_get_contrast( $expected, $hexcolor ) {
		$this->assertEquals( $expected, $this->misc->get_contrast( $hexcolor ) );
	}

	/**
	 * Data provider for testing get_contrast() method
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function provider_get_contrast() {
		return [
			[ '#FFF', '#000000' ],
			[ '#FFF', '#000' ],
			[ '#FFF', '#222' ],
			[ '#FFF', '#068a2b' ],
			[ '#FFF', '#a70404' ],
			[ '#000', '#fff' ],
			[ '#000', '#FFFFFF' ],
			[ '#000', '#999' ],
			[ '#000', '#EEE' ],
			[ '#000', '#CCC' ],
		];
	}

	/**
	 * Check our contrast checker returns the correct contrasting colours
	 *
	 * @param string  $expected The results we expect
	 * @param string  $hexcolor The colour to test
	 * @param integer $diff     Whether to go lighter or darker
	 *
	 * @dataProvider provider_change_brightness
	 * @since        4.0
	 */
	public function test_change_brightness( $expected, $hexcolor, $diff ) {
		$this->assertEquals( $expected, $this->misc->change_brightness( $hexcolor, $diff ) );
	}

	/**
	 * Data provider for testing provider_change_brightness() method
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function provider_change_brightness() {
		return [
			[ '#0a0a0a', '#000000', 10 ],
			[ '#0a0a0a', '#000', 10 ],
			[ '#181818', '#222', -10 ],
			[ '#2c2c2c', '#222', 10 ],
			[ '#fefefe', '#CCC', 50 ],
			[ '#9a9a9a', '#CCC', -50 ],
			[ '#ffffff', '#FFFFFF', 25 ],
			[ '#e6e6e6', '#FFF', -25 ],
		];
	}

	/**
	 * Test the basics of the evaluate_conditional_logic() method
	 * when used with show/hide logic
	 *
	 * @since 4.0
	 */
	public function test_evaluate_conditional_logic() {
		$data = $this->create_form_and_entries();

		$logic['actionType'] = 'show';

		$this->assertTrue( $this->misc->evaluate_conditional_logic( $logic, $data['entry'] ) );

		$logic['actionType'] = 'hide';

		$this->assertFalse( $this->misc->evaluate_conditional_logic( $logic, $data['entry'] ) );
	}

	/**
	 * Ensure we correctly return an appropriate class name based on the file path given
	 *
	 * @param string $expected The expected value
	 * @param string $file     The test path
	 *
	 * @dataProvider provider_get_config_class_name
	 *
	 * @since        4.0
	 */
	public function test_get_config_class_name( $expected, $file ) {
		global $gfpdf;

		$this->assertEquals( $expected, $gfpdf->templates->get_config_class_name( $file ) );
	}

	/**
	 * Data provider for our get_config_class_name() test
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function provider_get_config_class_name() {
		return [
			[ 'Manage_Document', '/path/to/templates/manage-document.php' ],
			[ 'Manage_Document', '/path/to/templates/manage_document.php' ],
			[ 'Manage_Document', '/path/to/templates/manage document.php' ],
			[ 'Superawesome_Working_Directory', '/my/path/superawesome-working-directory.php' ],
			[ 'Template', 'template.php' ],
		];
	}

	/**
	 * Check we correctly parse the hex code and spit out the correct background and border values
	 *
	 * @param string $expected
	 * @param string $hex
	 *
	 * @dataProvider provider_get_background_and_border_contrast
	 *
	 * @since        4.0
	 */
	public function test_get_background_and_border_contrast( $expected, $hex ) {
		$contrast = $this->misc->get_background_and_border_contrast( $hex );

		$this->assertEquals( $expected[0], $contrast['background'] );
		$this->assertEquals( $expected[1], $contrast['border'] );
	}

	/**
	 * Our test data for the get_background_and_border_contrast() method
	 *
	 * @return array
	 */
	public function provider_get_background_and_border_contrast() {
		return [
			[ [ '#ebebeb', '#c3c3c3' ], '#FFFFFF' ],
			[ [ '#ebebeb', '#c3c3c3' ], '#FFF' ],
			[ [ '#141414', '#3c3c3c' ], '#000000' ],
			[ [ '#141414', '#3c3c3c' ], '#000' ],
			[ [ '#e82828', '#ff5050' ], '#d41414' ],
			[ [ '#295399', '#517bc1' ], '#153f85' ],
			[ [ '#5cbb50', '#349328' ], '#70cf64' ],
			[ [ '#dfdfdf', '#b7b7b7' ], '#f3f3f3' ],
		];
	}

	/**
	 * Check we are correctly getting our form fields by ID
	 *
	 * @since 4.0
	 */
	public function test_get_fields_sorted_by_id() {

		/* Check for non-existent form */
		$this->assertSame( 0, count( $this->misc->get_fields_sorted_by_id( 0 ) ) );

		/* Check for real form and verify the results */
		$form = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

		$fields = $this->misc->get_fields_sorted_by_id( $form['id'] );

		$this->assertEquals( 56, count( $fields ) );
		$this->assertEquals( 'Section Break', $fields[10]->label );
	}

	/**
	 * Check if our backwards compatible settings conversion works correctly
	 *
	 * @since 4.0
	 */
	public function test_backwards_compat_conversion() {
		$settings = [
			'irrelevant' => 'Yes',
		];

		/* Check all the defaults work as expected */
		$compat = $this->misc->backwards_compat_conversion( $settings, [], [] );

		$this->assertCount( 8, $compat );
		$this->assertArrayNotHasKey( 'irrelevant', $compat );
		$this->assertFalse( $compat['premium'] );
		$this->assertFalse( $compat['rtl'] );
		$this->assertFalse( $compat['security'] );
		$this->assertFalse( $compat['pdfa1b'] );
		$this->assertFalse( $compat['pdfx1a'] );
		$this->assertEquals( '', $compat['pdf_password'] );
		$this->assertEquals( '', $compat['pdf_privileges'] );
		$this->assertEquals( 96, $compat['dpi'] );

		/* Check all the settings get correctly converted */
		$settings = [
			'advanced_template' => 'Yes',
			'rtl'               => 'Yes',
			'image_dpi'         => 300,
			'security'          => 'Yes',
			'password'          => 'password',
			'privileges'        => 'privileges',
			'format'            => 'PDFX1A',
		];

		$compat = $this->misc->backwards_compat_conversion( $settings, [], [] );

		$this->assertTrue( $compat['premium'] );
		$this->assertTrue( $compat['rtl'] );
		$this->assertTrue( $compat['security'] );
		$this->assertFalse( $compat['pdfa1b'] );
		$this->assertTrue( $compat['pdfx1a'] );
		$this->assertEquals( 'password', $compat['pdf_password'] );
		$this->assertEquals( 'privileges', $compat['pdf_privileges'] );
		$this->assertEquals( 300, $compat['dpi'] );
	}

	/**
	 * Check if our backwards compatible output functions work correctly
	 *
	 * @since 4.0
	 */
	public function test_backwards_compat_output() {
		$this->assertEquals( 'save', $this->misc->backwards_compat_output() );
		$this->assertEquals( 'view', $this->misc->backwards_compat_output( 'display' ) );
		$this->assertEquals( 'download', $this->misc->backwards_compat_output( 'download' ) );
	}

	/**
	 * Check our recursive in_array() method works as expected
	 *
	 * @param boolean $expected
	 * @param boolean $strict
	 * @param mixed   $needle
	 * @param array   $haystack
	 *
	 * @dataProvider provider_in_array
	 *
	 * @since        4.0
	 */
	public function test_in_array( $expected, $strict, $needle, $haystack ) {
		$this->assertSame( $expected, $this->misc->in_array( $needle, $haystack, $strict ) );
	}

	public function provider_in_array() {
		return [

			/* basic multi-dimensional search */
			[
				true,
				true,
				'find me',
				[
					'item 1',
					'item 2',
					'item 3' => [ 'test', 'find me' ],
					'item 4',
				],
			],

			/* type check (strict) */
			[
				false,
				true,
				20,
				[
					'item 1',
					'item 2' => [ 'stuff', 'here', [ '20' ] ],
					'item 3',
				],
			],

			/* type check (not strict) */
			[
				true,
				false,
				20,
				[
					'item 1',
					'item 2' => [ 'stuff', 'here', [ '20' ] ],
					'item 3',
				],
			],

			/* deep multi-dimensional array */
			[
				true,
				true,
				'Find Me',
				[
					'item 1' => [ 'hi', 'how', 'are', [ 'you' => [ 'going' ] ] ],
					'item 2' => [ 'stuff', 'here', [ 'Find Me' ] ],
					'item 3',
				],
			],

			/* deep multi-dimensional array */
			[
				true,
				true,
				'Find Me',
				[
					'item 1' => [ 'hi', 'how', 'are', [ 'you' => [ 'going' => [ 'Find Me' ] ] ] ],
					'item 2' => [ 'stuff', 'here', [ 'wow' ] ],
					'item 3',
				],
			],

			/* ensure case sensitive match */
			[
				false,
				true,
				'find me',
				[
					'item 1',
					'item 2' => [ 'stuff', 'here', [ 'Find Me' ] ],
					'item 3',
				],
			],
		];
	}

	/**
	 * Test that the everything inside a directory gets removed
	 *
	 * @since 4.0
	 */
	public function test_cleanup_dir() {

		/* Create our test data */
		$path = '/tmp/test/';
		wp_mkdir_p( $path );
		touch( $path . 'test' );

		/* Ensure it created correctly */
		$this->assertFileExists( $path . 'test' );

		/* Run our test */
		$this->misc->cleanup_dir( $path );

		/* Check the file was deleted but the directory still exists */
		$this->assertFileDoesNotExist( $path . 'test' );
		$this->assertDirectoryExists( $path );

		rmdir( $path );
	}

	public function test_rmdir() {
		/* Create test data */
		$path = '/tmp/test/';
		wp_mkdir_p( $path );
		touch( $path . 'test' );

		/* Ensure it created correctly */
		$this->assertFileExists( $path . 'test' );

		/* Run our test but don't delete the top-level folder */
		$this->misc->rmdir( $path, false );

		$this->assertFileDoesNotExist( $path . 'test' );
		$this->assertDirectoryExists( $path );

		/* Setup and run out test again, but delete the top-level directory as well */
		touch( $path . 'test' );

		/* Ensure it created correctly */
		$this->assertFileExists( $path . 'test' );

		/* Run our test and delete the top-level folder */
		$this->misc->rmdir( $path );

		$this->assertFileDoesNotExist( $path . 'test' );
		$this->assertDirectoryDoesNotExist( $path );
	}

	/**
	 * @since 6.12
	 */
	public function test_flatten_array() {
		/* Check a single dimensional array */
		$test_array = [
			'one' => 'first',
			'two' => 'second',
		];

		$this->assertSame( [ 'one', 'two' ], $this->misc->flatten_array( $test_array ) );
		$this->assertSame( [ 'first', 'second' ], $this->misc->flatten_array( $test_array, 'values' ) );

		/* Check a multi dimensional array */
		$test_array = [
			'top-one' => [
				'one' => 'first',
			],
			'top-two' => [
				'two' => 'second',
			],
		];

		$this->assertSame( [ 'one', 'two' ], $this->misc->flatten_array( $test_array ) );
		$this->assertSame( [ 'first', 'second' ], $this->misc->flatten_array( $test_array, 'values' ) );

		/* Check a multi-multi dimensional array */
		$test_array = [
			[
				'top-one' => [
					'one' => 'first',
				],
				'top-two' => [
					'two' => 'second',
				],
			],
		];

		$this->assertSame( [ 'top-one', 'top-two' ], $this->misc->flatten_array( $test_array ) );
	}
}
