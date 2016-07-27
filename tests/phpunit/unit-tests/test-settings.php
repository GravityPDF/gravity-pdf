<?php

namespace GFPDF\Tests;

use GFPDF\Controller\Controller_Settings;
use GFPDF\Model\Model_Settings;
use GFPDF\View\View_Settings;

use GFForms;

use WP_UnitTestCase;
use WP_Error;

use Exception;

/**
 * Test Gravity PDF Settings Functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2016, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2016, Blue Liquid Designs

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
 * Test the model / view / controller for the Settings Page
 *
 * @since 4.0
 * @group settings
 */
class Test_Settings extends WP_UnitTestCase {

	/**
	 * Our Settings Controller
	 *
	 * @var \GFPDF\Controller\Controller_Settings
	 * @since 4.0
	 */
	public $controller;

	/**
	 * Our Settings Model
	 *
	 * @var \GFPDF\Model\Model_Settings
	 * @since 4.0
	 */
	public $model;

	/**
	 * Our Settings View
	 *
	 * @var \GFPDF\View\View_Settings
	 * @since 4.0
	 */
	public $view;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function setUp() {
		global $gfpdf;

		/* run parent method */
		parent::setUp();

		/* Setup our test classes */
		$this->model = new Model_Settings( $gfpdf->gform, $gfpdf->log, $gfpdf->notices, $gfpdf->options, $gfpdf->data, $gfpdf->misc );
		$this->view  = new View_Settings( array(), $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc );

		$this->controller = new Controller_Settings( $this->model, $this->view, $gfpdf->gform, $gfpdf->log, $gfpdf->notices, $gfpdf->data, $gfpdf->misc );
		$this->controller->init();
	}

	/**
	 * Test the appropriate actions are set up
	 *
	 * @since 4.0
	 */
	public function test_actions() {
		$this->assertEquals( 10, has_action( 'gfpdf_post_general_settings_page', array( $this->view, 'system_status' ) ) );
		$this->assertEquals( 10, has_action( 'gfpdf_post_tools_settings_page', array( $this->view, 'system_status' ) ) );
		$this->assertEquals( 10, has_action( 'admin_init', array( $this->controller, 'process_tool_tab_actions' ) ) );
		$this->assertFalse( has_action( 'gfpdf_post_tools_settings_page', array( $this->view, 'uninstaller' ) ) );

		$this->assertEquals( 10, has_action( 'wp_ajax_gfpdf_font_save', array( $this->model, 'save_font' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_gfpdf_font_delete', array( $this->model, 'delete_font' ) ) );

	}

	/**
	 * Test the appropriate filters are set up
	 *
	 * @since 4.0
	 */
	public function test_filters() {
		global $gfpdf;

		$this->assertEquals( 10, has_filter( 'gform_tooltips', array( $this->view, 'add_tooltips' ) ) );
		$this->assertEquals( 10, has_filter( 'gfpdf_capability_name', array( $this->model, 'style_capabilities' ) ) );
		$this->assertEquals( 10, has_filter( 'option_page_capability_gfpdf_settings', array(
			$this->controller,
			'edit_options_cap',
		) ) );
		$this->assertEquals( 10, has_filter( 'gravitypdf_settings_navigation', array(
			$this->controller,
			'disable_tools_on_view_cap',
		) ) );
		$this->assertEquals( 10, has_filter( 'upload_mimes', array( $this->controller, 'allow_font_uploads' ) ) );
		$this->assertEquals( 10, has_filter( 'gfpdf_settings_general', array( $gfpdf->misc, 'add_template_image' ) ) );

		$this->assertFalse( has_filter( 'gfpdf_registered_settings', array( $gfpdf->options, 'highlight_errors' ) ) );
		/* retest the gfpdf_register_settings filter is added when on the correct screen */
		set_current_screen( 'edit.php' );
		$_GET['page'] = 'gfpdf-settings';

		$this->controller->add_filters();
		$this->assertEquals( 10, has_filter( 'gfpdf_registered_fields', array( $this->model, 'highlight_errors' ) ) );
	}

	/**
	 * Check the appropriate settings pages are loading
	 *
	 * @since 4.0
	 */
	public function test_display_page() {

		/* Test for missing tab */
		ob_start();
		$_GET['tab'] = 'test';
		$this->controller->display_page();
		$html = ob_get_clean();

		$this->assertEquals( null, $html );

		/* test help tab */
		ob_start();
		$_GET['tab'] = 'help';
		$this->controller->display_page();
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, '<div id="search-knowledgebase">' ) );

		/* test help tab */
		$_GET['tab'] = 'tools';

		try {
			$this->controller->display_page();
		} catch ( Exception $e ) {
			/* Expected */
		}

		$this->assertEquals( 'You do not have permission to access this page', $e->getMessage() );

	}

	/**
	 * Check only users with the appropriate permissions can edit the Gravity PDF options
	 *
	 * @since 4.0
	 */
	public function test_edit_options_cap() {

		try {
			$this->controller->edit_options_cap();
		} catch ( Exception $e ) {
			/* Expected */
		}

		$this->assertEquals( 'Access Denied', $e->getMessage() );

		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );

		$this->assertEquals( 'read', $this->controller->edit_options_cap() );

		wp_set_current_user( 0 );
	}

	/**
	 * Check the tools tab gets hidden when the user permissions aren't high enough
	 *
	 * @since 4.0
	 */
	public function test_disable_tools_on_view_cap() {

		$nav = array(
			10  => 'General',
			100 => 'Tools',
		);

		/* Ensure tools tab isn't present when permissions aren't set */
		$results = $this->controller->disable_tools_on_view_cap( $nav );
		$this->assertTrue( ! isset( $results[100] ) );

		/* Setup appropriate permissions and recheck */
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );

		$results = $this->controller->disable_tools_on_view_cap( $nav );
		$this->assertTrue( isset( $results[100] ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Check this can't be executed if permissions are wrong
	 *
	 * @since 4.0
	 */
	public function test_process_tool_tab_actions() {

		/* Show we're on the tools tab */
		$_GET['page']    = 'gfpdf-';
		$_GET['subview'] = 'PDF';
		$_GET['tab']     = 'tools';

		$this->assertNull( $this->controller->process_tool_tab_actions() );
	}

	/**
	 * Verify our font mime types are added to the allowed upload list
	 *
	 * @since 4.0
	 */
	public function test_allow_font_uploads() {
		$results = $this->controller->allow_font_uploads();
		$this->assertEquals( 'application/octet-stream', $results['ttf|otf'] );
	}

	/**
	 * Test the form errors are generated and stored correctly
	 *
	 * @since 4.0
	 */
	public function test_setup_form_settings_errors() {
		global $wp_settings_errors;

		/* Set up test data */
		add_settings_error( 'notices', 'normal', __( 'Normal Notice', 'gravity-forms-pdf-extended' ) );
		add_settings_error( 'gfpdf-notices', 'select', __( 'PDF Settings could not be saved. Please enter all required information below.', 'gravity-forms-pdf-extended' ) );
		add_settings_error( 'gfpdf-notices', 'text', __( 'PDF Settings could not be saved. Please enter all required information below.', 'gravity-forms-pdf-extended' ) );
		add_settings_error( 'gfpdf-notices', 'hidden', __( 'PDF Settings could not be saved. Please enter all required information below.', 'gravity-forms-pdf-extended' ) );

		/* set up test transient (like in options.php) */
		set_transient( 'settings_errors', $wp_settings_errors, 30 );

		/* trigger function */
		$this->model->setup_form_settings_errors();

		/* test results */
		$this->assertSame( 4, sizeof( $this->model->form_settings_errors ) );
		$this->assertSame( 2, sizeof( get_transient( 'settings_errors' ) ) );
	}

	/**
	 * Verify errors are highlighted appropriately
	 *
	 * @since 4.0
	 */
	public function test_highlight_errors() {

		/* Setup an error to match */
		$this->model->form_settings_errors = array(
			array( 'type' => 'error', 'code' => 'rtl' ),
			array( 'type' => 'error', 'code' => 'name' ),
		);

		/* Setup settings fields */
		$settings = array(
			'general' => array(
				array( 'id' => 'item', 'class' => 'normal' ),
				array( 'id' => 'rtl', 'class' => 'hello' ),
				array( 'id' => 'item2', 'class' => '' ),
				array( 'id' => 'name' ),
			),
		);

		$results = $this->model->highlight_errors( $settings );

		$this->assertEquals( 'normal', $results['general'][0]['class'] );
		$this->assertEquals( 'hello gfield_error', $results['general'][1]['class'] );
		$this->assertEquals( '', $results['general'][2]['class'] );
		$this->assertEquals( 'gfield_error', $results['general'][3]['class'] );
	}

	/**
	 * Check our template installer functions correctly
	 *
	 * @since 4.0
	 */
	public function test_install_template() {
		global $gfpdf;

		$install_path = ( is_multisite() ) ? $gfpdf->data->multisite_template_location : $gfpdf->data->template_location;

		$this->assertFileNotExists( $install_path . 'zadani.php' );

		$this->model->install_templates();

		$this->assertFileExists( $install_path . 'zadani.php' );

		/* Cleanup */
		foreach ( glob( PDF_PLUGIN_DIR . 'src/templates/*' ) as $file ) {

			$file = $install_path . basename( $file );

			if ( is_dir( $file ) ) {
				$gfpdf->misc->rmdir( $file );
			} else {
				unlink( $file );
			}
		}
	}

	/**
	 * Check the font removal method works
	 *
	 * @since 4.0
	 */
	public function test_remove_font_file() {
		global $gfpdf;

		/* Create font array */
		$font = array(
			'regular' => 'MyFont.ttf',
			'bold'    => 'MyFont-Bold.ttf',
		);

		/* Create our tmp font files */
		array_walk( $font, function( $value ) use ( $gfpdf ) {
			touch( $gfpdf->data->template_font_location . $value );
		} );

		/* Verify they exist */
		$this->assertFileExists( $gfpdf->data->template_font_location . 'MyFont.ttf' );
		$this->assertFileExists( $gfpdf->data->template_font_location . 'MyFont-Bold.ttf' );

		/* Remove the fonts and verify they are both removed */
		$this->model->remove_font_file( $font );

		$this->assertFileNotExists( $gfpdf->data->template_font_location . 'MyFont.ttf' );
		$this->assertFileNotExists( $gfpdf->data->template_font_location . 'MyFont-Bold.ttf' );
	}

	/**
	 * Check if we have a valid font name
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_is_font_name_valid
	 */
	public function test_is_font_name_valid( $expected, $name ) {
		$this->assertSame( $expected, $this->model->is_font_name_valid( $name ) );
	}

	/**
	 * Dataprovider for test_is_font_name_valid
	 *
	 * @since 4.0
	 */
	public function provider_is_font_name_valid() {
		return array(
			array( true, 'My font name' ),
			array( false, 'My f@nt name' ),
			array( false, 'Calibri-pro' ),
			array( true, 'Calibri Pro' ),
			array( true, '123Roman' ),
			array( false, '123_Roman' ),
		);
	}

	/**
	 * Verify we have a unique font name
	 *
	 * @since 4.0
	 */
	public function test_is_font_name_unique() {
		global $gfpdf;

		/* Check the name is unique */
		$this->assertTrue( $this->model->is_font_name_unique( 'Calibri' ) );

		/* Insert that name into the database and recheck for uniqueness */
		$font = array(
			'font_name' => 'Calibri',
		);

		$results = $this->model->install_fonts( $font );

		$this->assertFalse( $this->model->is_font_name_unique( 'Calibri' ) );

		/* Ensure we skip over itself */
		$id = $results['id'];

		$this->assertTrue( $this->model->is_font_name_unique( 'Calibri', $id ) );
	}

	/**
	 * Test the install_fonts() method
	 *
	 * @since 4.0
	 */
	public function test_install_fonts() {
		global $gfpdf;

		$uploads = wp_upload_dir();

		/* Create font array */
		$font = array(
			'name'        => 'Custom Font',
			'regular'     => $uploads['url'] . '/MyFont.ttf',
			'bold'        => $uploads['url'] . '/MyFont-Bold.ttf',
			'italics'     => $uploads['url'] . '/MyFont-Italics.otf',
			'bolditalics' => $uploads['url'] . '/MyFont-BI.ttf',
		);

		/* Create our tmp font files */
		array_walk( $font, function( $value ) use ( $uploads ) {
			touch( $uploads['path'] . '/' . basename( $value ) );
		} );

		/* Install our fonts */
		$results = $this->model->install_fonts( $font );

		/* Verify the results */
		$this->assertFileExists( $gfpdf->data->template_font_location . 'MyFont.ttf' );
		$this->assertFileExists( $gfpdf->data->template_font_location . 'MyFont-Bold.ttf' );
		$this->assertFileExists( $gfpdf->data->template_font_location . 'MyFont-Italics.otf' );
		$this->assertFileExists( $gfpdf->data->template_font_location . 'MyFont-BI.ttf' );

		$default_fonts = $gfpdf->options->get_option( 'custom_fonts' );

		$this->assertEquals( 'Custom Font', $default_fonts[ $results['id'] ]['name'] );

		/* Cleanup the fonts */
		$this->model->remove_font_file( $font );

	}
}
