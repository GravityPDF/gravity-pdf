<?php

namespace GFPDF\Tests;

use GFPDF\Router;

use WP_UnitTestCase;

/**
 * Test Gravity PDF Bootstrap Class
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2016, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
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
 * Test the Bootstrap / Main Router
 *
 * @since 4.0
 * @group bootstrap
 */
class Test_Bootstrap extends WP_UnitTestCase {
	/**
	 * Our Gravity PDF Router object
	 *
	 * @var \GFPDF\Router
	 *
	 * @since 4.0
	 */
	public $loader;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function setUp() {
		/* run parent method */
		parent::setUp();

		/* Setup out loader class */
		$this->loader = new Router();
		$this->loader->init();
	}

	/**
	 * Test the global bootstrap actions are applied
	 *
	 * @since 4.0
	 */
	public function test_actions() {
		$this->assertEquals( 10, has_action( 'init', array( $this->loader, 'register_assets' ) ) );
		$this->assertEquals( 15, has_action( 'init', array( $this->loader, 'load_assets' ) ) );

		$this->assertEquals( 1, has_action( 'init', array( $this->loader, 'init_settings_api' ) ) );
		$this->assertEquals( 1, has_action( 'admin_init', array( $this->loader, 'setup_settings_fields' ) ) );
	}

	/**
	 * Test the global bootstrap filters are applied
	 *
	 * @since 4.0
	 */
	public function test_filters() {
		$this->assertEquals( 10, has_filter( 'gform_noconflict_scripts', array(
			$this->loader,
			'auto_noconflict_scripts',
		) ) );
		$this->assertEquals( 10, has_filter( 'gform_noconflict_styles', array(
			$this->loader,
			'auto_noconflict_styles',
		) ) );

		$this->assertEquals( 10, has_filter( 'gform_logging_supported', array( $this->loader, 'add_gf_logger' ) ) );
	}

	/**
	 * Check the required helper classes are loaded into the Router
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_dependant_helper_classes
	 */
	public function test_dependant_helper_classes( $expected, $property ) {
		$this->assertEquals( $expected, get_class( $this->loader->$property ) );
	}

	/**
	 * Returns the test data for our test_dependant_helper_classes
	 * Test the $log property in another test
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function provider_dependant_helper_classes() {
		return array(
			array( 'GFPDF\Helper\Helper_Form', 'gform' ),
			array( 'GFPDF\Helper\Helper_Data', 'data' ),
			array( 'GFPDF\Helper\Helper_Misc', 'misc' ),
			array( 'GFPDF\Helper\Helper_Notices', 'notices' ),
			array( 'GFPDF\Helper\Helper_Options_Fields', 'options' ),
		);
	}

	/**
	 * Test that any Gravity PDF scripts are automatically loading when GF is in no conflict mode
	 *
	 * @since 4.0
	 */
	public function test_auto_noconflict_gfpdf_js() {
		/* get test data */
		$queue = array(
			'common',
			'gfpdf_css_chosen_style',
			'admin-bar',
			'gfpdf_test',
			'gfpdf_js_chosen',
			'gfpdf_j_admin',
			'gfpdf_jsapples',
			'gfpdf_css_styles',
			'gforms_locking',
			'gfpdf_js_settings',
			'gfwebapi_enc_base64',
		);

		/* override queue */
		$wp_scripts        = wp_scripts();
		$saved             = $wp_scripts->queue;
		$wp_scripts->queue = $queue;

		/* get the results and test the expected output */
		$results = $this->loader->auto_noconflict_scripts( array() );

		/* run assertions */
		$this->assertEquals( 3, sizeof( $results ) );
		$this->assertContains( 'gfpdf_js_chosen', $results );
		$this->assertContains( 'gfpdf_js_settings', $results );
		$this->assertContains( 'gfpdf_jsapples', $results );

		/* reset the queue */
		$wp_scripts->queue = $saved;
	}

	/**
	 * Test that any Gravity PDF styles are automatically loading when GF is in no conflict mode
	 *
	 * @since 4.0
	 */
	public function test_auto_noconflict_gfpdf_css() {
		/* get test data */
		$queue = array(
			'common',
			'gfpdf_css_chosen_style',
			'admin-bar',
			'gfpdf_test',
			'gfpdf_js_chosen',
			'gfpdf_j_admin',
			'gfpdf_jsapples',
			'gfpdf_css_styles',
			'gforms_locking',
			'gfpdf_js_settings',
			'gfwebapi_enc_base64',
		);

		/* override queue */
		$wp_styles        = wp_styles();
		$saved            = $wp_styles->queue;
		$wp_styles->queue = $queue;

		/* get the results and test the expected output */
		$results = $this->loader->auto_noconflict_styles( array() );

		/* run assertions */
		$this->assertEquals( 2, sizeof( $results ) );
		$this->assertContains( 'gfpdf_css_chosen_style', $results );
		$this->assertContains( 'gfpdf_css_styles', $results );

		/* reset the queue */
		$wp_styles->queue = $saved;
	}

	/**
	 * Check the logger is setting up correctly
	 *
	 * @since 4.0
	 */
	public function test_setup_logger() {

		$logger = $this->loader->log->getHandlers();

		$this->assertSame( 1, sizeof( $logger ) );
		$this->assertEquals( 'Monolog\Handler\NullHandler', get_class( $logger[0] ) );
	}

	/**
	 * Check we're registering Gravity PDF with the Gravity Forms logger
	 *
	 * @since 4.0
	 */
	public function test_add_gf_logger() {
		$this->assertArrayHasKey( 'gravity-pdf', $this->loader->add_gf_logger( array() ) );
	}

	/**
	 * Test backwards compatibility function for our v3 default PDF templates
	 *
	 * @since 4.0
	 */
	public function test_get_default_config_data() {
		global $gfpdf;

		/* Test a failure first */
		$settings = $this->loader->get_default_config_data( 1 );

		$this->assertFalse( $settings['empty_field'] );
		$this->assertFalse( $settings['html_field'] );
		$this->assertFalse( $settings['page_names'] );
		$this->assertFalse( $settings['section_content'] );

		/* Test pass */
		$form_id                                 = $GLOBALS['GFPDF_Test']->form['form-settings']['id'];
		$pid = $GLOBALS['wp']->query_vars['pid'] = '555ad84787d7e';

		$gfpdf->data->form_settings                                   = array();
		$gfpdf->data->form_settings[ $form_id ]                       = $GLOBALS['GFPDF_Test']->form['form-settings']['gfpdf_form_settings'];
		$gfpdf->data->form_settings[ $form_id ][ $pid ]['html_field'] = 'Yes';

		$settings = $this->loader->get_default_config_data( $form_id );

		$this->assertFalse( $settings['empty_field'] );
		$this->assertTrue( $settings['html_field'] );
		$this->assertFalse( $settings['page_names'] );
		$this->assertFalse( $settings['section_content'] );

	}
}
