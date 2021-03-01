<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Data;
use GFPDF\Model\Model_Custom_Fonts;
use GPDFAPI;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Controller_Custom_Fonts
 *
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   fonts
 */
class Test_Controller_Custom_Fonts extends WP_UnitTestCase {

	/**
	 * @var Controller_Custom_Fonts
	 */
	protected $controller;

	/**
	 * @var Model_Custom_Fonts
	 */
	protected $model;

	/**
	 * @var string
	 */
	protected $tmp_font_location;

	protected $test_fonts = [];

	protected $admin_user;

	protected $editor_user;

	public function setUp(): void {
		global $gfpdf;

		parent::setUp();

		$this->tmp_font_location = $gfpdf->data->template_font_location;
		wp_mkdir_p( $this->tmp_font_location );

		$class = $gfpdf->singleton->get_class( 'Controller_Custom_Fonts' );
		remove_action( 'rest_api_init', [ $class, 'register_endpoints' ] );

		/* Setup our test classes */
		$this->model      = new Model_Custom_Fonts( $gfpdf->options );
		$this->controller = new Controller_Custom_Fonts( $this->model, $gfpdf->log, $gfpdf->gform, $this->tmp_font_location, 'GFPDF\\Helper\\Fonts\\LocalFilesystem', 'GFPDF\\Helper\\Fonts\\LocalFile' );

		$this->controller->init();

		$this->admin_user = $this->factory->user->create(
			[
				'role'       => 'administrator',
				'user_login' => 'administrator',
			]
		);

		$this->editor_user = $this->factory->user->create(
			[
				'role'       => 'editor',
				'user_login' => 'editor',
			]
		);

		$fonts = [
			'DejaVuSans.ttf',
			'DejaVuSans-Bold.ttf',
			'DejaVuSansCondensed.ttf',
			'DejaVuSerifCondensed.ttf',
		];

		foreach ( $fonts as $font ) {
			$tmp_font = get_temp_dir() . $font;
			copy( __DIR__ . '/../fonts/' . $font, $tmp_font );
			$this->test_fonts[] = $tmp_font;
		}

		error_reporting( E_ALL & ~E_NOTICE );
	}

	public function tearDown(): void {
		global $gfpdf;

		$_FILES = [];

		$gfpdf->misc->cleanup_dir( $this->tmp_font_location );

		foreach ( $this->test_fonts as $font ) {
			if ( is_file( $font ) ) {
				unlink( $font );
			}
		}

		$gfpdf->options->update_option( 'custom_fonts', [] );

		parent::tearDown();

		error_reporting( E_ALL );
	}

	public function test_register_endpoints() {
		$rest   = rest_get_server();
		$routes = $rest->get_routes( Helper_Data::REST_API_BASENAME . 'v1' );

		$this->assertArrayHasKey( '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts', $routes );
		$this->assertArrayHasKey( '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts/(?P<id>[a-z0-9]+)', $routes );
	}

	public function test_get_all_items() {
		$this->assertCount( 0, $this->controller->get_all_items() );

		$this->model->add_font( [ 'id' => 'font1' ] );
		$this->model->add_font( [ 'id' => 'font2' ] );
		$this->model->add_font( [ 'id' => 'font3' ] );

		$this->assertCount( 3, $this->controller->get_all_items() );
	}

	public function test_add_item_success() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'POST', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts' );
		$request->set_param( 'label', 'Font' );
		$this->set_all_file_params( $request );

		$response = rest_get_server()->dispatch( $request );
		$font     = $response->get_data();

		$this->assertIsArray( $font );
		$this->assertSame( 'Font', $font['font_name'] );
		$this->assertSame( 'font', $font['id'] );
		$this->assertSame( 255, $font['useOTL'] );
		$this->assertSame( 75, $font['useKashida'] );
		$this->assertStringEndsWith( 'DejaVuSans.ttf', $font['regular'] );
		$this->assertStringEndsWith( 'DejaVuSans-Bold.ttf', $font['bold'] );
		$this->assertStringEndsWith( 'DejaVuSansCondensed.ttf', $font['italics'] );
		$this->assertStringEndsWith( 'DejaVuSerifCondensed.ttf', $font['bolditalics'] );
	}

	public function test_add_item_permission_failed() {
		$request = new WP_REST_Request( 'POST', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts' );
		$request->set_param( 'label', 'Font' );
		$this->set_all_file_params( $request );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	public function test_add_item_basic_validation_failed() {
		wp_set_current_user( $this->admin_user );

		/* Test without a label */
		$request = new WP_REST_Request( 'POST', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );

		/* Test without the regular font */
		$request->set_param( 'label', 'Font' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );

		/* Test with an invalid label */
		$request->set_param( 'label', 'Font-Name' );
		$this->set_all_file_params( $request );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	public function test_add_item_font_validation_failed() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'POST', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts' );
		$request->set_param( 'label', 'Font' );

		/* JSON file masquerading as a ttf file */
		$test_file = __DIR__ . '/../json/all-form-fields.json';

		$_FILES = [
			'regular' => [
				'file'     => file_get_contents( $test_file ),
				'name'     => 'DejaVuSans.ttf',
				'size'     => filesize( $test_file ),
				'tmp_name' => $test_file,
				'error'    => UPLOAD_ERR_OK,
			],
		];

		$request->set_file_params( $_FILES );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );

		/* TTF file masquerading as a JSON file */
		$test_file = __DIR__ . '/../fonts/DejaVuSans.ttf';

		$_FILES = [
			'regular' => [
				'file'     => file_get_contents( $test_file ),
				'name'     => 'DejaVuSans.json',
				'size'     => filesize( $test_file ),
				'tmp_name' => $test_file,
				'error'    => UPLOAD_ERR_OK,
			],
		];

		$request->set_file_params( $_FILES );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	public function test_update_item_success() {

		GPDFAPI::add_pdf_font(
			[
				'font_name' => 'Lato',
				'regular'   => $this->test_fonts[0],
			]
		);

		wp_set_current_user( $this->admin_user );

		/* Replace with new Font files and label */
		$request = new WP_REST_Request( 'POST', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts/lato' );
		$request->set_param( 'label', 'Font' );
		$this->set_all_file_params( $request );

		$response = rest_get_server()->dispatch( $request );
		$font     = $response->get_data();

		$this->assertIsArray( $font );
		$this->assertSame( 'Font', $font['font_name'] );
		$this->assertSame( 'lato', $font['id'] );
		$this->assertSame( 255, $font['useOTL'] );
		$this->assertSame( 75, $font['useKashida'] );
		$this->assertRegExp( '/DejaVuSans([0-9]{5})\.ttf$$/', $font['regular'] );
		$this->assertStringEndsWith( 'DejaVuSans-Bold.ttf', $font['bold'] );
		$this->assertStringEndsWith( 'DejaVuSansCondensed.ttf', $font['italics'] );
		$this->assertStringEndsWith( 'DejaVuSerifCondensed.ttf', $font['bolditalics'] );

		/* Rename label */
		$_FILES = [];
		$request->set_file_params( $_FILES );
		$request->set_param( 'label', 'Lato2' );

		$response = rest_get_server()->dispatch( $request );
		$font     = $response->get_data();

		$this->assertSame( 'Lato2', $font['font_name'] );
		$this->assertSame( 'lato', $font['id'] );
		$this->assertSame( 255, $font['useOTL'] );
		$this->assertSame( 75, $font['useKashida'] );
		$this->assertRegExp( '/DejaVuSans([0-9]{5})\.ttf$$/', $font['regular'] );
		$this->assertStringEndsWith( 'DejaVuSans-Bold.ttf', $font['bold'] );
		$this->assertStringEndsWith( 'DejaVuSansCondensed.ttf', $font['italics'] );
		$this->assertStringEndsWith( 'DejaVuSerifCondensed.ttf', $font['bolditalics'] );

		/* Delete bold/italics fonts */
		$request->set_param( 'bold', '' );
		$request->set_param( 'italics', '' );

		$response = rest_get_server()->dispatch( $request );
		$font     = $response->get_data();

		$this->assertSame( 'Lato2', $font['font_name'] );
		$this->assertSame( 'lato', $font['id'] );
		$this->assertSame( 255, $font['useOTL'] );
		$this->assertSame( 75, $font['useKashida'] );
		$this->assertRegExp( '/DejaVuSans([0-9]{5})\.ttf$$/', $font['regular'] );
		$this->assertSame( '', $font['bold'] );
		$this->assertSame( '', $font['italics'] );
		$this->assertStringEndsWith( 'DejaVuSerifCondensed.ttf', $font['bolditalics'] );
	}

	public function test_update_item_permission_failed() {
		GPDFAPI::add_pdf_font(
			[
				'font_name' => 'Lato',
				'regular'   => __DIR__ . '/../fonts/DejaVuSans.ttf',
			]
		);

		$request = new WP_REST_Request( 'POST', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts/lato' );
		$request->set_param( 'label', 'Font' );
		$this->set_all_file_params( $request );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	public function test_update_item_basic_validation_failed() {
		GPDFAPI::add_pdf_font(
			[
				'font_name' => 'Lato',
				'regular'   => __DIR__ . '/../fonts/DejaVuSans.ttf',
			]
		);

		wp_set_current_user( $this->admin_user );

		/* Test with an invalid label */
		$request = new WP_REST_Request( 'POST', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts/lato' );
		$request->set_param( 'label', 'Font-Name' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	public function test_update_item_invalid_font_id() {
		wp_set_current_user( $this->admin_user );

		/* Test with an invalid label */
		$request = new WP_REST_Request( 'POST', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts/lato' );
		$request->set_param( 'label', 'Font Name' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	public function test_update_item_font_validation_failed() {
		GPDFAPI::add_pdf_font(
			[
				'font_name' => 'Lato',
				'regular'   => __DIR__ . '/../fonts/DejaVuSans.ttf',
			]
		);

		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'POST', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts/lato' );

		/* JSON file masquerading as a ttf file */
		$test_file = __DIR__ . '/../json/all-form-fields.json';

		$_FILES = [
			'regular' => [
				'file'     => file_get_contents( $test_file ),
				'name'     => 'DejaVuSans.ttf',
				'size'     => filesize( $test_file ),
				'tmp_name' => $test_file,
				'error'    => UPLOAD_ERR_OK,
			],
		];

		$request->set_file_params( $_FILES );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );

		/* TTF file masquerading as a JSON file */
		$test_file = __DIR__ . '/../fonts/DejaVuSans.ttf';

		$_FILES = [
			'regular' => [
				'file'     => file_get_contents( $test_file ),
				'name'     => 'DejaVuSans.json',
				'size'     => filesize( $test_file ),
				'tmp_name' => $test_file,
				'error'    => UPLOAD_ERR_OK,
			],
		];

		$request->set_file_params( $_FILES );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	public function test_delete_item_success() {
		wp_set_current_user( $this->admin_user );

		/* Create font */
		$request = new WP_REST_Request( 'POST', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts' );
		$request->set_param( 'label', 'Font' );
		$this->set_all_file_params( $request );

		$response = rest_get_server()->dispatch( $request );
		$font     = $response->get_data();

		/* Delete font */
		$request  = new WP_REST_Request( 'DELETE', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts/' . $font['id'] );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_delete_item_with_font_reference_gone_success() {
		wp_set_current_user( $this->admin_user );

		/* Create font */
		GPDFAPI::add_pdf_font(
			[
				'font_name' => 'Lato',
				'regular'   => $this->test_fonts[0],
			]
		);

		$options = GPDFAPI::get_options_class();
		$fonts   = $options->get_option( 'custom_fonts' );
		unlink( $fonts['lato']['regular'] );

		/* Delete font */
		$request  = new WP_REST_Request( 'DELETE', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts/lato' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_delete_item_invalid_font_id() {
		wp_set_current_user( $this->admin_user );

		/* Test with an invalid label */
		$request = new WP_REST_Request( 'DELETE', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts/lato' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	protected function set_all_file_params( WP_REST_Request $request ) {
		$_FILES = [
			'regular'     => [
				'file'     => file_get_contents( $this->test_fonts[0] ),
				'name'     => 'DejaVuSans.ttf',
				'size'     => filesize( $this->test_fonts[0] ),
				'tmp_name' => $this->test_fonts[0],
				'error'    => UPLOAD_ERR_OK,
			],

			'bold'        => [
				'file'     => file_get_contents( $this->test_fonts[1] ),
				'name'     => 'DejaVuSans-Bold.ttf',
				'size'     => filesize( $this->test_fonts[1] ),
				'tmp_name' => $this->test_fonts[1],
				'error'    => UPLOAD_ERR_OK,
			],

			'italics'     => [
				'file'     => file_get_contents( $this->test_fonts[2] ),
				'name'     => 'DejaVuSansCondensed.ttf',
				'size'     => filesize( $this->test_fonts[2] ),
				'tmp_name' => $this->test_fonts[2],
				'error'    => UPLOAD_ERR_OK,
			],

			'bolditalics' => [
				'file'     => file_get_contents( $this->test_fonts[3] ),
				'name'     => 'DejaVuSerifCondensed.ttf',
				'size'     => filesize( $this->test_fonts[3] ),
				'tmp_name' => $this->test_fonts[3],
				'error'    => UPLOAD_ERR_OK,
			],
		];

		$request->set_file_params( $_FILES );
	}

	public function test_get_absolute_font_path() {
		$this->assertEmpty( $this->controller->get_absolute_font_path( '' ) );

		$this->assertSame( $this->tmp_font_location . 'font.ttf', $this->controller->get_absolute_font_path( 'font.ttf' ) );
	}
}
