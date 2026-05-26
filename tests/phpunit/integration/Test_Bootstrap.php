<?php

namespace GFPDF\Tests;

use GFPDF\Router;
use GFPDF\Tests\Integration\TestCase;

/**
 * Test Gravity PDF Bootstrap Class
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/**
 * Test the Bootstrap / Main Router
 *
 * @since 4.0
 * @group bootstrap
 */
class Test_Bootstrap extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'form-settings' ] );
	}
	/**
	 * Our Gravity PDF Router object
	 *
	 * @var Router
	 *
	 * @since 4.0
	 */
	public $loader;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function set_up() {
		/* run parent method */
		parent::set_up();

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
		$this->assertEquals( 10, has_action( 'init', [ $this->loader, 'register_assets' ] ) );
		$this->assertEquals( 20, has_action( 'admin_enqueue_scripts', [ $this->loader, 'load_admin_assets' ] ) );

		$this->assertEquals( 1, has_action( 'init', [ $this->loader, 'init_settings_api' ] ) );
		$this->assertEquals( 1, has_action( 'admin_init', [ $this->loader, 'setup_settings_fields' ] ) );
	}

	/**
	 * Test the global bootstrap filters are applied
	 *
	 * @since 4.0
	 */
	public function test_filters() {
		$this->assertEquals(
			10,
			has_filter(
				'gform_noconflict_scripts',
				[
					$this->loader,
					'auto_noconflict_scripts',
				]
			)
		);
		$this->assertEquals(
			10,
			has_filter(
				'gform_noconflict_styles',
				[
					$this->loader,
					'auto_noconflict_styles',
				]
			)
		);
	}

	/**
	 * Check the required helper classes are loaded into the Router
	 *
	 * @param string $expected
	 * @param string $property
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
		return [
			[ 'GFPDF\Helper\Helper_Form', 'gform' ],
			[ 'GFPDF\Helper\Helper_Data', 'data' ],
			[ 'GFPDF\Helper\Helper_Misc', 'misc' ],
			[ 'GFPDF\Helper\Helper_Notices', 'notices' ],
			[ 'GFPDF\Helper\Helper_Options_Fields', 'options' ],
		];
	}

	/**
	 * Test that any Gravity PDF scripts are automatically loading when GF is in no conflict mode
	 *
	 * @since 4.0
	 */
	public function test_auto_noconflict_gfpdf_js() {
		/* get test data */
		$queue = [
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
		];

		/* override queue */
		$wp_scripts        = wp_scripts();
		$saved             = $wp_scripts->queue;
		$wp_scripts->queue = $queue;

		/* get the results and test the expected output */
		$results = $this->loader->auto_noconflict_scripts( [] );

		/* run assertions */
		$this->assertCount( 3, $results );
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
		$queue = [
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
		];

		/* override queue */
		$wp_styles        = wp_styles();
		$saved            = $wp_styles->queue;
		$wp_styles->queue = $queue;

		/* get the results and test the expected output */
		$results = $this->loader->auto_noconflict_styles( [] );

		/* run assertions */
		$this->assertCount( 2, $results );
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

		$this->assertCount( 1, $logger );
		$this->assertEquals( 'GFPDF_Vendor\Monolog\Handler\NullHandler', get_class( $logger[0] ) );
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
		$form_id                          = $this->form( 'form-settings' )['id'];
		$pid                              = '555ad84787d7e';
		$GLOBALS['wp']->query_vars['pid'] = $pid;

		$gfpdf->data->form_settings                                   = [];
		$gfpdf->data->form_settings[ $form_id ]                       = $this->form( 'form-settings' )['gfpdf_form_settings'];
		$gfpdf->data->form_settings[ $form_id ][ $pid ]['html_field'] = 'Yes';

		$settings = $this->loader->get_default_config_data( $form_id );

		$this->assertFalse( $settings['empty_field'] );
		$this->assertTrue( $settings['html_field'] );
		$this->assertFalse( $settings['page_names'] );
		$this->assertFalse( $settings['section_content'] );
	}

	/**
	 * @since 4.2
	 */
	public function test_licensing_requirements() {
		global $gfpdf;

		$this->assertTrue( class_exists( '\GFPDF\Helper\Licensing\EDD_SL_Plugin_Updater' ) );
		$this->assertTrue( is_array( $gfpdf->data->addon ) );
		$this->assertNotEmpty( $gfpdf->data->store_url );
	}

	public function test_plugin_action_links_prepends_settings_link() {
		$links = $this->loader->plugin_action_links( [ 'deactivate' => '<a>Deactivate</a>' ] );

		$keys = array_keys( $links );
		$this->assertSame( 'settings', $keys[0] );
		$this->assertStringContainsString( 'View Gravity PDF Settings', $links['settings'] );
		$this->assertArrayHasKey( 'deactivate', $links );
	}

	public function test_plugin_row_meta_passes_through_for_unrelated_plugins() {
		$links = $this->loader->plugin_row_meta( [ 'view-details' => '<a>View</a>' ], 'other-plugin/other.php' );

		$this->assertSame( [ 'view-details' => '<a>View</a>' ], $links );
	}

	public function test_plugin_row_meta_adds_docs_links_for_gravity_pdf() {
		$links = $this->loader->plugin_row_meta( [ 'view-details' => '<a>View</a>' ], PDF_PLUGIN_BASENAME );

		$this->assertArrayHasKey( 'docs', $links );
		$this->assertArrayHasKey( 'support', $links );
		$this->assertArrayHasKey( 'extension-shop', $links );
		$this->assertArrayHasKey( 'template-shop', $links );
	}

	public function test_add_body_class_appends_gfpdf_page_class_on_admin_pdf_page() {
		set_current_screen( 'dashboard' );
		$_GET['page'] = 'gfpdf-tools';

		$classes = $this->loader->add_body_class( 'foo' );

		unset( $_GET['page'] );

		$this->assertSame( 'foo gfpdf-page', $classes );
	}

	public function test_add_body_class_passes_through_when_not_on_gfpdf_page() {
		$this->assertSame( 'foo', $this->loader->add_body_class( 'foo' ) );
	}

	public function test_tinymce_styles_appends_to_existing_content_style() {
		$result = $this->loader->tinymce_styles( [ 'content_style' => 'p { color: red; }' ] );

		$this->assertStringStartsWith( 'p { color: red; } ', $result['content_style'] );
		$this->assertStringContainsString( 'body#tinymce', $result['content_style'] );
	}

	public function test_tinymce_styles_sets_content_style_when_missing() {
		$result = $this->loader->tinymce_styles( [] );

		$this->assertStringContainsString( 'body#tinymce', $result['content_style'] );
	}

	public function test_register_assets_registers_gfpdf_styles_and_scripts() {
		$this->loader->register_assets();

		$this->assertTrue( wp_style_is( 'gfpdf_css_styles', 'registered' ) );
		$this->assertTrue( wp_script_is( 'gfpdf_js_settings', 'registered' ) );
		$this->assertTrue( wp_script_is( 'gfpdf_js_entrypoint', 'registered' ) );
		$this->assertTrue( wp_script_is( 'gfpdf_js_entries', 'registered' ) );
	}

	public function test_get_config_data_delegates_to_get_default_config_data() {
		$this->assertSame(
			$this->loader->get_default_config_data( 1 ),
			$this->loader->get_config_data( 1 )
		);
	}

	public function test_add_admin_messages_routes_errors_and_notices_into_notice_system() {
		add_settings_error( 'gfpdf-notices', 'err-code', 'Boom', 'error' );
		add_settings_error( 'gfpdf-notices', 'ok-code', 'All good', 'updated' );

		$this->loader->add_admin_messages();

		$this->assertTrue( $this->loader->notices->has_error() );
		$this->assertTrue( $this->loader->notices->has_notice() );

		global $wp_settings_errors;
		$wp_settings_errors = [];
	}
}
