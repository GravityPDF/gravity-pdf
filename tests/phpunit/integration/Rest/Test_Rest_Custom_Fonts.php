<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Exceptions\GravityPdfIdException;
use GFPDF\Fonts\Font_Repository;
use GFPDF\Helper\Helper_Data;
use GFPDF\Model\Model_Custom_Fonts;
use GPDFAPI;
use WP_REST_Request;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Rest_Custom_Fonts
 *
 * @package GFPDF\Rest
 *
 * @group   api
 * @group   fonts
 */
class Test_Rest_Custom_Fonts extends TestCase {

	/**
	 * @var Rest_Custom_Fonts
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

	/** Shared across the class; LocalFilesystem copies these into the plugin font dir, never mutating the source. */
	protected static $test_fonts = [];

	protected $admin_user;

	protected $editor_user;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		$fonts = [
			'DejaVuSans.ttf',
			'DejaVuSans-Bold.ttf',
			'DejaVuSansCondensed.ttf',
			'DejaVuSerifCondensed.ttf',
		];

		foreach ( $fonts as $font ) {
			$tmp_font = get_temp_dir() . $font;
			copy( PDF_PLUGIN_DIR . '/tools/phpunit/data/fonts/' . $font, $tmp_font );
			self::$test_fonts[] = $tmp_font;
		}
	}

	public static function tear_down_after_class(): void {
		foreach ( self::$test_fonts as $font ) {
			if ( is_file( $font ) ) {
				unlink( $font );
			}
		}
		self::$test_fonts = [];

		parent::tear_down_after_class();
	}

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$this->tmp_font_location = $gfpdf->data->template_font_location;
		wp_mkdir_p( $this->tmp_font_location );

		$class = $gfpdf->singleton->get_class( 'Rest_Custom_Fonts' );
		remove_action( 'rest_api_init', [ $class, 'register_routes' ] );

		/* Setup our test classes */
		$this->model      = new Model_Custom_Fonts( $gfpdf->get_font_repository() );
		$this->controller = new Rest_Custom_Fonts( $this->model, $gfpdf->log, $gfpdf->gform, $gfpdf->get_font_registry(), $gfpdf->get_catalog_repository(), $gfpdf->get_install_requests(), $this->tmp_font_location, 'GFPDF\\Fonts\\LocalFilesystem', 'GFPDF\\Fonts\\LocalFile' );

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

		error_reporting( E_ALL & ~E_NOTICE );
	}

	public function tear_down(): void {
		global $gfpdf;

		$_FILES = [];

		$gfpdf->misc->cleanup_dir( $this->tmp_font_location );

		$gfpdf->options->update_option( 'custom_fonts', [] );

		parent::tear_down();

		error_reporting( E_ALL );
	}

	public function test_register_endpoints() {
		$rest   = rest_get_server();
		$routes = $rest->get_routes( Helper_Data::REST_API_BASENAME . 'v1' );

		$this->assertArrayHasKey( '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts', $routes );
		$this->assertArrayHasKey( '/' . Helper_Data::REST_API_BASENAME . 'v1' . Rest_Custom_Fonts::id_route(), $routes );
	}

	public function test_the_id_route_accepts_an_underscored_imported_key() {
		$this->assertSame( 1, preg_match( '@^' . Rest_Custom_Fonts::id_route() . '$@', '/fonts/open_sans' ) );
	}

	/**
	 * WordPress anchors the whole pattern, so this is the match a dispatched request would make
	 */
	public function test_the_id_route_never_swallows_a_literal_font_route() {
		foreach ( Font_Repository::RESERVED_ROUTE_KEYS as $word ) {
			$this->assertSame( 0, preg_match( '@^' . Rest_Custom_Fonts::id_route() . '$@', '/fonts/' . $word ), $word );
		}
	}

	/**
	 * A key equal to a route word would be unaddressable, so the upload path must never mint one
	 */
	public function test_a_font_named_after_a_literal_route_is_given_another_key() {
		foreach ( Font_Repository::RESERVED_ROUTE_KEYS as $word ) {
			$this->assertNotSame( $word, $this->model->get_unique_id( $word ), $word );
		}
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

		/* The row shape `GET /fonts/` lists, so the store can merge the upload without a second read (§4.7) */
		$this->assertIsArray( $font );
		$this->assertSame( 'Font', $font['label'] );
		$this->assertSame( 'font', $font['id'] );
		$this->assertSame( 'custom', $font['source'] );
		$this->assertSame( 'DejaVuSans.ttf', $font['files']['R']['path'] );
		$this->assertSame( 'DejaVuSans-Bold.ttf', $font['files']['B']['path'] );
		$this->assertSame( 'DejaVuSansCondensed.ttf', $font['files']['I']['path'] );
		$this->assertSame( 'DejaVuSerifCondensed.ttf', $font['files']['BI']['path'] );

		$row = GPDFAPI::get_font_repository()->get( 'font' );

		$this->assertSame( 255, $row['use_otl'] );
		$this->assertSame( 75, $row['use_kashida'] );
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
		$test_file = PDF_PLUGIN_DIR . '/tools/phpunit/data/forms/all-form-fields.json';

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
		$test_file = PDF_PLUGIN_DIR . '/tools/phpunit/data/fonts/DejaVuSans.ttf';

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
				'regular'   => self::$test_fonts[0],
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
		$this->assertSame( 'Font', $font['label'] );
		$this->assertSame( 'lato', $font['id'] );
		$this->assertMatchesRegularExpression( '/DejaVuSans([0-9]{5})\.ttf$/', $font['files']['R']['path'] );
		$this->assertSame( 'DejaVuSans-Bold.ttf', $font['files']['B']['path'] );
		$this->assertSame( 'DejaVuSansCondensed.ttf', $font['files']['I']['path'] );
		$this->assertSame( 'DejaVuSerifCondensed.ttf', $font['files']['BI']['path'] );

		$row = GPDFAPI::get_font_repository()->get( 'lato' );

		$this->assertSame( 255, $row['use_otl'] );
		$this->assertSame( 75, $row['use_kashida'] );

		/* Rename label */
		$_FILES = [];
		$request->set_file_params( $_FILES );
		$request->set_param( 'label', 'Lato2' );

		$response = rest_get_server()->dispatch( $request );
		$font     = $response->get_data();

		$this->assertSame( 'Lato2', $font['label'] );
		$this->assertSame( 'lato', $font['id'] );
		$this->assertMatchesRegularExpression( '/DejaVuSans([0-9]{5})\.ttf$/', $font['files']['R']['path'] );
		$this->assertSame( 'DejaVuSans-Bold.ttf', $font['files']['B']['path'] );
		$this->assertSame( 'DejaVuSansCondensed.ttf', $font['files']['I']['path'] );
		$this->assertSame( 'DejaVuSerifCondensed.ttf', $font['files']['BI']['path'] );

		/* Delete bold/italics fonts */
		$request->set_param( 'bold', '' );
		$request->set_param( 'italics', '' );

		$response = rest_get_server()->dispatch( $request );
		$font     = $response->get_data();

		$this->assertSame( 'Lato2', $font['label'] );
		$this->assertSame( 'lato', $font['id'] );
		$this->assertMatchesRegularExpression( '/DejaVuSans([0-9]{5})\.ttf$/', $font['files']['R']['path'] );
		$this->assertSame( 'DejaVuSerifCondensed.ttf', $font['files']['BI']['path'] );

		/* A face the request cleared leaves no row at all, rather than a row pointing at nothing */
		$this->assertArrayNotHasKey( 'B', $font['files'] );
		$this->assertArrayNotHasKey( 'I', $font['files'] );
	}

	public function test_update_item_permission_failed() {
		GPDFAPI::add_pdf_font(
			[
				'font_name' => 'Lato',
				'regular'   => PDF_PLUGIN_DIR . '/tools/phpunit/data/fonts/DejaVuSans.ttf',
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
				'regular'   => PDF_PLUGIN_DIR . '/tools/phpunit/data/fonts/DejaVuSans.ttf',
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
				'regular'   => PDF_PLUGIN_DIR . '/tools/phpunit/data/fonts/DejaVuSans.ttf',
			]
		);

		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'POST', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts/lato' );

		/* JSON file masquerading as a ttf file */
		$test_file = PDF_PLUGIN_DIR . '/tools/phpunit/data/forms/all-form-fields.json';

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
		$test_file = PDF_PLUGIN_DIR . '/tools/phpunit/data/fonts/DejaVuSans.ttf';

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
		global $gfpdf;

		wp_set_current_user( $this->admin_user );

		/* Create font */
		GPDFAPI::add_pdf_font(
			[
				'font_name' => 'Lato',
				'regular'   => self::$test_fonts[0],
			]
		);

		/* 7.0 records fonts in the font tables; `custom_fonts` is a frozen 6.x snapshot nothing writes any more */
		$repository = $gfpdf->get_font_repository();
		unlink( $repository->get_font_dir() . $repository->get( 'lato' )['files']['R']['path'] );

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
				'file'     => file_get_contents( self::$test_fonts[0] ),
				'name'     => 'DejaVuSans.ttf',
				'size'     => filesize( self::$test_fonts[0] ),
				'tmp_name' => self::$test_fonts[0],
				'error'    => UPLOAD_ERR_OK,
			],

			'bold'        => [
				'file'     => file_get_contents( self::$test_fonts[1] ),
				'name'     => 'DejaVuSans-Bold.ttf',
				'size'     => filesize( self::$test_fonts[1] ),
				'tmp_name' => self::$test_fonts[1],
				'error'    => UPLOAD_ERR_OK,
			],

			'italics'     => [
				'file'     => file_get_contents( self::$test_fonts[2] ),
				'name'     => 'DejaVuSansCondensed.ttf',
				'size'     => filesize( self::$test_fonts[2] ),
				'tmp_name' => self::$test_fonts[2],
				'error'    => UPLOAD_ERR_OK,
			],

			'bolditalics' => [
				'file'     => file_get_contents( self::$test_fonts[3] ),
				'name'     => 'DejaVuSerifCondensed.ttf',
				'size'     => filesize( self::$test_fonts[3] ),
				'tmp_name' => self::$test_fonts[3],
				'error'    => UPLOAD_ERR_OK,
			],
		];

		$request->set_file_params( $_FILES );
	}

	public function test_get_absolute_font_path() {
		$this->assertEmpty( $this->controller->get_absolute_font_path( '' ) );

		$this->assertSame( $this->tmp_font_location . 'font.ttf', $this->controller->get_absolute_font_path( 'font.ttf' ) );
	}

	/**
	 * The one status `add_item()` answered differently from the other two routes
	 *
	 * Unreachable over the route — `get_unique_id()` suffixes until the key is free — so the model is stubbed to
	 * throw what the catch is there for.
	 */
	public function test_an_invalid_font_id_is_a_client_error_on_every_route() {
		global $gfpdf;

		$controller = new Rest_Custom_Fonts(
			new Throwing_Custom_Fonts_Model( $gfpdf->get_font_repository() ),
			$gfpdf->log,
			$gfpdf->gform,
			$gfpdf->get_font_registry(),
			$gfpdf->get_catalog_repository(),
			$gfpdf->get_install_requests(),
			$this->tmp_font_location
		);

		$request = new WP_REST_Request( 'POST', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts' );
		$request->set_param( 'label', 'Font' );

		$error = $controller->add_item( $request );

		$this->assertSame( 'invalid_font_id', $error->get_error_code() );
		$this->assertSame( 400, $error->get_error_data()['status'] );
	}
}

/**
 * A model whose id derivation fails, for the catch that no request can reach
 */
class Throwing_Custom_Fonts_Model extends Model_Custom_Fonts {

	public function get_unique_id( string $id ): string {
		throw new GravityPdfIdException();
	}
}
