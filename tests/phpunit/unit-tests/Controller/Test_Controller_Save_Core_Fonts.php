<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use ReflectionMethod;
use WP_Error;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Controller_Save_Core_Fonts
 *
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   fonts
 */
class Test_Controller_Save_Core_Fonts extends WP_UnitTestCase {

	const FONT = 'DejaVuSerifCondensed.ttf';

	public function tear_down() {
		global $gfpdf;

		unset( $_POST['font_name'] );
		wp_delete_file( $gfpdf->data->template_font_location . self::FONT );

		parent::tear_down();
	}

	/**
	 * @since 6.18.0
	 */
	public function test_successful_download_keeps_the_font() {
		$font = $this->stream_font_with_response( $this->http_response( 200 ) );

		$this->assertTrue( $this->invoke_download() );
		$this->assertFileExists( $font );
	}

	/**
	 * @dataProvider provider_failed_download
	 *
	 * @since 6.18.0
	 */
	public function test_failed_download_removes_the_partly_written_font( $response ) {
		$font = $this->stream_font_with_response( $response );

		$this->assertFalse( $this->invoke_download() );
		$this->assertFileDoesNotExist( $font );
	}

	public function provider_failed_download(): array {
		return [
			'request error' => [ new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ) ],
			'rate limited'  => [ $this->http_response( 429 ) ],
		];
	}

	/**
	 * @dataProvider provider_unexpected_download
	 *
	 * @since 6.18.0
	 */
	public function test_download_that_does_not_match_the_font_list_is_removed( callable $change ) {
		$font = $this->stream_font_with_response( $this->http_response( 200 ) );

		$handle = fopen( $font, 'r+' );
		$change( $handle );
		fclose( $handle );

		$this->assertFalse( $this->invoke_download() );
		$this->assertFileDoesNotExist( $font );
	}

	public function provider_unexpected_download(): array {
		return [
			'truncated' => [
				function ( $handle ) {
					ftruncate( $handle, 1024 );
				},
			],
			'same size' => [
				function ( $handle ) {
					fwrite( $handle, 'x' );
				},
			],
		];
	}

	/**
	 * @since 6.18.0
	 */
	public function test_unapproved_font_leaves_existing_files_alone() {
		$font = $this->seed_font();

		$_POST['font_name'] = '../' . self::FONT;

		$this->assertFalse( $this->invoke_download() );
		$this->assertFileExists( $font );
	}

	/**
	 * Writes the font as WordPress does before a streamed request
	 */
	private function seed_font(): string {
		global $gfpdf;

		$font = $gfpdf->data->template_font_location . self::FONT;
		wp_mkdir_p( $gfpdf->data->template_font_location );
		copy( __DIR__ . '/../fonts/' . self::FONT, $font );

		return $font;
	}

	private function stream_font_with_response( $response ): string {
		$_POST['font_name'] = self::FONT;
		add_filter(
			'pre_http_request',
			function () use ( $response ) {
				return $response;
			}
		);

		return $this->seed_font();
	}

	private function http_response( int $code ): array {
		return [
			'headers'  => [],
			'body'     => '',
			'response' => [ 'code' => $code ],
			'cookies'  => [],
		];
	}

	private function invoke_download() {
		global $gfpdf;

		$controller = new Controller_Save_Core_Fonts( $gfpdf->log, $gfpdf->data, $gfpdf->misc );
		$method     = new ReflectionMethod( $controller, 'download_and_save_font' );
		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}

		return $method->invoke( $controller );
	}
}
