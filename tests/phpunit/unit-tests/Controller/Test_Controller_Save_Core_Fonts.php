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

	public function tear_down() {
		unset( $_POST['font_name'] );

		parent::tear_down();
	}

	/**
	 * @dataProvider provider_failed_download
	 *
	 * @since 6.18.0
	 */
	public function test_failed_download_removes_the_partly_written_font( $response ) {
		global $gfpdf;

		$font = $gfpdf->data->template_font_location . 'Aegean.otf';
		wp_mkdir_p( $gfpdf->data->template_font_location );
		file_put_contents( $font, 'partial' );

		$_POST['font_name'] = 'Aegean.otf';
		add_filter(
			'pre_http_request',
			function () use ( $response ) {
				return $response;
			}
		);

		$controller = new Controller_Save_Core_Fonts( $gfpdf->log, $gfpdf->data, $gfpdf->misc );
		$method     = new ReflectionMethod( $controller, 'download_and_save_font' );
		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}

		$this->assertFalse( $method->invoke( $controller ) );
		$this->assertFileDoesNotExist( $font );
	}

	public function provider_failed_download(): array {
		return [
			'request error' => [ new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ) ],
			'rate limited'  => [
				[
					'headers'  => [],
					'body'     => 'Too Many Requests',
					'response' => [ 'code' => 429 ],
					'cookies'  => [],
				],
			],
		];
	}
}
