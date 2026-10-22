<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Exceptions\GravityPdfFontNotFoundException;
use GFPDF\Helper\Fonts\LocalFile;
use GFPDF\Helper\Fonts\LocalFilesystem;
use GFPDF\Helper\Helper_Data;
use GFPDF\Model\Model_Custom_Fonts;
use GFPDF\Statics\Upload_Size;
use GFPDF_Vendor\GravityPdf\Upload\ErrorCode;
use GFPDF_Vendor\GravityPdf\Upload\Exception as UploadException;
use GFPDF_Vendor\GravityPdf\Upload\File;
use GFPDF_Vendor\GravityPdf\Upload\FileInfoInterface;
use GFPDF_Vendor\GravityPdf\Upload\Storage\FileSystem;
use GPDFAPI;
use ReflectionMethod;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
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

	const NOT_A_FONT = __DIR__ . '/../json/all-form-fields.json';

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

	public function set_up() {
		global $gfpdf;

		parent::set_up();

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

	public function tear_down() {
		global $gfpdf;

		$_FILES = [];

		$gfpdf->misc->cleanup_dir( $this->tmp_font_location );

		foreach ( $this->test_fonts as $font ) {
			if ( is_file( $font ) ) {
				unlink( $font );
			}
		}

		$gfpdf->options->update_option( 'custom_fonts', [] );

		parent::tear_down();

		error_reporting( E_ALL );
	}

	public function test_register_endpoints() {
		$rest   = rest_get_server();
		$routes = $rest->get_routes( Helper_Data::REST_API_BASENAME . 'v1' );

		$this->assertArrayHasKey( '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts', $routes );
		$this->assertArrayHasKey( '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts/(?P<id>[a-z0-9\-]+)', $routes );
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

		$request = $this->new_font_request();
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

	/**
	 * @since 6.18.0
	 */
	public function test_add_item_recreates_a_missing_font_folder() {
		global $gfpdf;

		wp_set_current_user( $this->admin_user );
		$gfpdf->misc->rmdir( $this->tmp_font_location );

		$request = $this->new_font_request();
		$this->set_all_file_params( $request );

		$font = rest_get_server()->dispatch( $request )->get_data();

		$this->assertSame( 'font', $font['id'] ?? null );
		$this->assertFileExists( $this->tmp_font_location . 'DejaVuSans.ttf' );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_add_item_keeps_the_filename_the_upload_library_saves() {
		wp_set_current_user( $this->admin_user );

		$request = $this->new_font_request();
		$this->set_font_files(
			$request,
			[
				'regular' => [ 'DejaVu.Sans.TTF', $this->test_fonts[0] ],
				'bold'    => [ 'DejaVuSans (Bold).ttf', $this->test_fonts[1] ],
			]
		);

		$font = rest_get_server()->dispatch( $request )->get_data();

		$this->assertSame( $this->tmp_font_location . 'DejaVu-Sans.ttf', $font['regular'] );
		$this->assertSame( $this->tmp_font_location . 'DejaVuSans -Bold.ttf', $font['bold'] );
		$this->assertFileExists( $font['regular'] );
		$this->assertFileExists( $font['bold'] );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_add_item_saves_styles_uploaded_under_the_same_filename() {
		wp_set_current_user( $this->admin_user );

		$request = $this->new_font_request();
		$this->set_font_files(
			$request,
			[
				'regular' => [ 'Font.ttf', $this->test_fonts[0] ],
				'bold'    => [ 'Font.ttf', $this->test_fonts[1] ],
			]
		);

		$font = rest_get_server()->dispatch( $request )->get_data();

		$this->assertSame( $this->tmp_font_location . 'Font.ttf', $font['regular'] );
		$this->assertMatchesRegularExpression( '/Font[0-9]{5}\.ttf$/', $font['bold'] );
		$this->assertFileExists( $font['bold'] );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_add_item_saves_under_a_free_name_when_a_folder_has_the_name() {
		wp_set_current_user( $this->admin_user );
		mkdir( $this->tmp_font_location . 'Font.ttf' );

		$request = $this->new_font_request();
		$this->set_font_files( $request, [ 'regular' => [ 'Font.ttf', $this->test_fonts[0] ] ] );

		$font = rest_get_server()->dispatch( $request )->get_data();

		$this->assertMatchesRegularExpression( '/Font[0-9]{5}\.ttf$/', $font['regular'] );
		$this->assertFileExists( $font['regular'] );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_add_item_saves_a_taken_name_at_the_length_limit() {
		wp_set_current_user( $this->admin_user );

		/* The longest name the library keeps alongside .ttf */
		$name = str_repeat( 'a', 251 ) . '.ttf';

		$request = $this->new_font_request();
		$this->set_font_files(
			$request,
			[
				'regular' => [ $name, $this->test_fonts[0] ],
				'bold'    => [ $name, $this->test_fonts[1] ],
			]
		);

		$font = rest_get_server()->dispatch( $request )->get_data();

		$this->assertSame( $this->tmp_font_location . $name, $font['regular'] );
		$this->assertMatchesRegularExpression( '/\/a{246}[0-9]{5}\.ttf$/', $font['bold'] );
		$this->assertFileExists( $font['bold'] );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_add_item_stops_retrying_when_every_name_is_taken() {
		global $gfpdf;

		wp_set_current_user( $this->admin_user );

		$filesystem = get_class(
			new class( $this->tmp_font_location ) extends LocalFilesystem {
				public static $attempts = 0;

				protected function reserveDestination( string $destinationFile, FileInfoInterface $fileInfo ): void {
					++self::$attempts;

					throw new UploadException( 'Taken', $fileInfo, ErrorCode::DESTINATION_EXISTS );
				}
			}
		);

		$controller = new Controller_Custom_Fonts( $this->model, $gfpdf->log, $gfpdf->gform, $this->tmp_font_location, $filesystem, LocalFile::class );

		$request = $this->new_font_request();
		$this->set_font_files( $request, [ 'regular' => [ 'DejaVuSans.ttf', $this->test_fonts[0] ] ] );

		$error = $controller->add_item( $request );

		$this->assertSame( 10, $filesystem::$attempts );
		$this->assertStringContainsString( 'could not be saved', $error->get_error_message( 'font_validation_error' )['regular'] );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_add_item_reports_every_rejected_style() {
		wp_set_current_user( $this->admin_user );

		$request = $this->new_font_request();
		$this->set_font_files(
			$request,
			[
				'regular' => [ 'DejaVuSans.ttf', self::NOT_A_FONT ],
				'bold'    => [ 'DejaVuSans-Bold.ttf', self::NOT_A_FONT ],
			]
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( [ 'regular', 'bold' ], array_keys( $response->get_data()['message'] ) );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_add_item_saves_nothing_when_a_style_is_rejected() {
		wp_set_current_user( $this->admin_user );

		$request = $this->new_font_request();
		$this->set_font_files(
			$request,
			[
				'regular' => [ 'DejaVuSans.ttf', $this->test_fonts[0] ],
				'bold'    => [ 'DejaVuSans-Bold.ttf', self::NOT_A_FONT ],
			]
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( [ 'bold' => 'The upload is not a valid TTF file' ], $response->get_data()['message'] );
		$this->assertFileDoesNotExist( $this->tmp_font_location . 'DejaVuSans.ttf' );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_add_item_removes_the_saved_styles_when_storing_another_fails() {
		global $gfpdf;

		wp_set_current_user( $this->admin_user );

		/* Storage fails on the second style */
		$filesystem = get_class(
			new class( $this->tmp_font_location ) extends LocalFilesystem {
				public static $moves = 0;

				protected function moveUploadedFile( string $source, string $destination ): bool {
					return ++self::$moves === 1 && parent::moveUploadedFile( $source, $destination );
				}
			}
		);

		$controller = new Controller_Custom_Fonts( $this->model, $gfpdf->log, $gfpdf->gform, $this->tmp_font_location, $filesystem, LocalFile::class );

		$request = $this->new_font_request();
		$this->set_font_files(
			$request,
			[
				'regular' => [ 'DejaVuSans.ttf', $this->test_fonts[0] ],
				'bold'    => [ 'DejaVuSans-Bold.ttf', $this->test_fonts[1] ],
			]
		);

		$error = $controller->add_item( $request );

		$this->assertSame( 'font_validation_error', $error->get_error_code() );
		$this->assertStringContainsString( 'could not be saved', $error->get_error_message( 'font_validation_error' )['bold'] );
		$this->assertFileDoesNotExist( $this->tmp_font_location . 'DejaVuSans.ttf' );
		$this->assertFileDoesNotExist( $this->tmp_font_location . 'DejaVuSans-Bold.ttf' );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_add_item_removes_the_uploaded_files_when_a_font_file_goes_missing() {
		global $gfpdf;

		wp_set_current_user( $this->admin_user );

		$controller = new class( $this->model, $gfpdf->log, $gfpdf->gform, $this->tmp_font_location, LocalFilesystem::class, LocalFile::class ) extends Controller_Custom_Fonts {
			protected function does_fonts_support_otl( array $files ): bool {
				throw new GravityPdfFontNotFoundException( '{}' );
			}
		};

		$request = $this->new_font_request();
		$this->set_all_file_params( $request );

		$error = $controller->add_item( $request );

		$this->assertSame( 'font_file_gone_missing', $error->get_error_code() );
		$this->assertFileDoesNotExist( $this->tmp_font_location . 'DejaVuSans.ttf' );
		$this->assertFileDoesNotExist( $this->tmp_font_location . 'DejaVuSerifCondensed.ttf' );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_add_item_removes_the_uploaded_files_when_the_font_is_not_saved() {
		wp_set_current_user( $this->admin_user );

		$request = $this->new_font_request();
		$this->set_all_file_params( $request );

		$this->block_settings_update();

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'database_error', $response->get_data()['code'] );
		$this->assertFileDoesNotExist( $this->tmp_font_location . 'DejaVuSans.ttf' );
		$this->assertFileDoesNotExist( $this->tmp_font_location . 'DejaVuSerifCondensed.ttf' );
	}

	public function test_add_item_permission_failed() {
		$request = $this->new_font_request();
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

		$request = $this->new_font_request();

		/* JSON file masquerading as a ttf file */
		$this->set_font_files( $request, [ 'regular' => [ 'DejaVuSans.ttf', self::NOT_A_FONT ] ] );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'The upload is not a valid TTF file', $response->get_data()['message']['regular'] );

		/* TTF file masquerading as a JSON file */
		$this->set_font_files( $request, [ 'regular' => [ 'DejaVuSans.json', $this->test_fonts[0] ] ] );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * @dataProvider provider_add_item_upload_error_message
	 */
	public function test_add_item_upload_error_message( int $upload_error, string $expected ) {
		wp_set_current_user( $this->admin_user );

		/* A lower media limit, like a multisite network's Max upload file size, isn't the one PHP refused the file at */
		add_filter(
			'upload_size_limit',
			function () {
				return KB_IN_BYTES;
			}
		);

		$request = $this->new_font_request();
		$this->set_font_files( $request, [ 'regular' => [ 'DejaVuSans.ttf', '', $upload_error ] ] );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertStringContainsString( $expected, $response->get_data()['message']['regular'] );
	}

	public function provider_add_item_upload_error_message(): array {
		$too_large = sprintf( 'The font file is larger than the %s upload limit', size_format( Upload_Size::get_php_limit() ) );

		return [
			'over upload_max_filesize' => [ UPLOAD_ERR_INI_SIZE, $too_large ],
			'over MAX_FILE_SIZE'       => [ UPLOAD_ERR_FORM_SIZE, $too_large ],
			'partial transfer'         => [ UPLOAD_ERR_PARTIAL, 'could not be saved' ],
			'no tmp dir'               => [ UPLOAD_ERR_NO_TMP_DIR, 'could not be saved' ],
			'disk write failed'        => [ UPLOAD_ERR_CANT_WRITE, 'could not be saved' ],
		];
	}

	public function test_upload_error_message_for_a_storage_failure() {
		$_FILES = [
			'regular' => [
				'name'     => 'DejaVuSans.ttf',
				'tmp_name' => $this->test_fonts[0],
				'error'    => UPLOAD_ERR_OK,
			],
		];

		$method = new ReflectionMethod( $this->controller, 'get_upload_error_message' );
		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}

		$file = new File( 'regular', new FileSystem( $this->tmp_font_location ) );

		$this->assertStringContainsString( 'could not be saved', $method->invoke( $this->controller, $file, ErrorCode::MOVE_FAILED ) );
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
		$request = $this->update_font_request( 'lato' );
		$request->set_param( 'label', 'Font' );
		$this->set_all_file_params( $request );

		$response = rest_get_server()->dispatch( $request );
		$font     = $response->get_data();

		$this->assertIsArray( $font );
		$this->assertSame( 'Font', $font['font_name'] );
		$this->assertSame( 'lato', $font['id'] );
		$this->assertSame( 255, $font['useOTL'] );
		$this->assertSame( 75, $font['useKashida'] );
		$this->assertMatchesRegularExpression( '/DejaVuSans([0-9]{5})\.ttf$$/', $font['regular'] );
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
		$this->assertMatchesRegularExpression( '/DejaVuSans([0-9]{5})\.ttf$$/', $font['regular'] );
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
		$this->assertMatchesRegularExpression( '/DejaVuSans([0-9]{5})\.ttf$$/', $font['regular'] );
		$this->assertSame( '', $font['bold'] );
		$this->assertSame( '', $font['italics'] );
		$this->assertStringEndsWith( 'DejaVuSerifCondensed.ttf', $font['bolditalics'] );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_update_item_deletes_the_files_it_replaces_or_removes() {
		wp_set_current_user( $this->admin_user );
		$font = $this->add_font_with_all_styles();

		$request = $this->update_font_request( $font['id'] );
		$request->set_param( 'bold', '' );
		$this->set_font_files( $request, [ 'regular' => [ 'DejaVuSans.ttf', $this->test_fonts[0] ] ] );

		$updated = rest_get_server()->dispatch( $request )->get_data();

		$this->assertSame( '', $updated['bold'] );
		$this->assertNotSame( $font['regular'], $updated['regular'] );
		$this->assertFileExists( $updated['regular'] );
		$this->assertFileDoesNotExist( $font['regular'] );
		$this->assertFileDoesNotExist( $font['bold'] );
		$this->assertFileExists( $font['italics'] );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_update_item_keeps_the_font_when_an_upload_is_rejected() {
		wp_set_current_user( $this->admin_user );
		$font = $this->add_font_with_all_styles();

		$request = $this->update_font_request( $font['id'] );
		$request->set_param( 'bold', '' );
		$this->set_font_files( $request, [ 'regular' => [ 'DejaVuSans.ttf', self::NOT_A_FONT ] ] );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( $font, $this->model->get_font_by_id( $font['id'] ) );
		$this->assertFileExists( $font['regular'] );
		$this->assertFileExists( $font['bold'] );
	}

	/**
	 * @dataProvider provider_update_item_failure
	 *
	 * @since 6.18.0
	 */
	public function test_update_item_keeps_the_font_as_it_was_when_it_is_not_saved( string $failure ) {
		wp_set_current_user( $this->admin_user );
		$font = $this->add_font_with_all_styles();

		$request = $this->update_font_request( $font['id'] );
		$request->set_param( 'bold', '' );
		$this->set_font_files( $request, [ 'regular' => [ 'Replacement.ttf', $this->test_fonts[0] ] ] );

		if ( $failure === 'database' ) {
			$this->block_settings_update();
		} else {
			$request->set_param( 'useKashida', 200 );
		}

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( $font, $this->model->get_font_by_id( $font['id'] ) );
		$this->assertFileExists( $font['regular'] );
		$this->assertFileExists( $font['bold'] );
		$this->assertFileDoesNotExist( $this->tmp_font_location . 'Replacement.ttf' );
	}

	public function provider_update_item_failure(): array {
		return [
			'database save fails'           => [ 'database' ],
			'invalid Kashida after the move' => [ 'kashida' ],
		];
	}

	/**
	 * @since 6.18.0
	 */
	public function test_update_item_keeps_an_upload_saved_under_the_name_of_a_missing_file() {
		wp_set_current_user( $this->admin_user );
		$font = $this->add_font_with_all_styles();

		/* The new upload takes the free name of the file the font is replacing */
		unlink( $font['regular'] );

		$request = $this->update_font_request( $font['id'] );
		$this->set_font_files( $request, [ 'regular' => [ basename( $font['regular'] ), $this->test_fonts[0] ] ] );

		$updated = rest_get_server()->dispatch( $request )->get_data();

		$this->assertSame( $font['regular'], $updated['regular'] );
		$this->assertFileExists( $updated['regular'] );
	}

	public function test_update_item_permission_failed() {
		GPDFAPI::add_pdf_font(
			[
				'font_name' => 'Lato',
				'regular'   => __DIR__ . '/../fonts/DejaVuSans.ttf',
			]
		);

		$request = $this->update_font_request( 'lato' );
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
		$request = $this->update_font_request( 'lato' );
		$request->set_param( 'label', 'Font-Name' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	public function test_update_item_invalid_font_id() {
		wp_set_current_user( $this->admin_user );

		/* Test with an invalid label */
		$request = $this->update_font_request( 'lato' );
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

		$request = $this->update_font_request( 'lato' );

		/* JSON file masquerading as a ttf file */
		$this->set_font_files( $request, [ 'regular' => [ 'DejaVuSans.ttf', self::NOT_A_FONT ] ] );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );

		/* TTF file masquerading as a JSON file */
		$this->set_font_files( $request, [ 'regular' => [ 'DejaVuSans.json', $this->test_fonts[0] ] ] );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	public function test_delete_item_success() {
		wp_set_current_user( $this->admin_user );

		/* Create font */
		$request = $this->new_font_request();
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
		$this->set_font_files(
			$request,
			[
				'regular'     => [ 'DejaVuSans.ttf', $this->test_fonts[0] ],
				'bold'        => [ 'DejaVuSans-Bold.ttf', $this->test_fonts[1] ],
				'italics'     => [ 'DejaVuSansCondensed.ttf', $this->test_fonts[2] ],
				'bolditalics' => [ 'DejaVuSerifCondensed.ttf', $this->test_fonts[3] ],
			]
		);
	}

	protected function new_font_request(): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts' );
		$request->set_param( 'label', 'Font' );

		return $request;
	}

	protected function update_font_request( string $id ): WP_REST_Request {
		return new WP_REST_Request( 'POST', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts/' . $id );
	}

	/**
	 * @param array $files Each font style mapped to the [ filename, path, upload error (optional) ] uploaded for it
	 */
	protected function set_font_files( WP_REST_Request $request, array $files ) {
		$_FILES = array_map(
			function ( array $file ): array {
				$error = $file[2] ?? UPLOAD_ERR_OK;

				return [
					'name'     => $file[0],
					'size'     => $error === UPLOAD_ERR_OK ? filesize( $file[1] ) : 0,
					'tmp_name' => $file[1],
					'error'    => $error,
				];
			},
			$files
		);

		$request->set_file_params( $_FILES );
	}

	protected function add_font_with_all_styles(): array {
		$request = $this->new_font_request();
		$this->set_all_file_params( $request );

		return rest_get_server()->dispatch( $request )->get_data();
	}

	/* Make the next settings save report a failure */
	protected function block_settings_update() {
		add_filter(
			'pre_update_option_gfpdf_settings',
			function ( $value, $old_value ) {
				return $old_value;
			},
			10,
			2
		);
	}

	public function test_get_absolute_font_path() {
		$this->assertEmpty( $this->controller->get_absolute_font_path( '' ) );

		$this->assertSame( $this->tmp_font_location . 'font.ttf', $this->controller->get_absolute_font_path( 'font.ttf' ) );
	}
}
