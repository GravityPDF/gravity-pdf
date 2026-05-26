<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Mpdf;

use GFPDF_Vendor\Mpdf\MpdfException;
use GFPDF_Vendor\Mpdf\PsrHttpMessageShim\Request as Payload;
use GFPDF_Vendor\Psr\Log\NullLogger;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 */
class Test_Request extends TestCase {

	/** @var callable|null */
	private $http_mock;

	public function set_up() {
		parent::set_up();

		/* Mock wp_remote_get so the suite never hits GitHub for license.txt — that fetch was flaky.
		 * Only intercept the well-formed https URLs; the schemeless URLs in the wp_error tests must
		 * still take the natural WP_Error path through wp_remote_get's URL validation. */
		$this->http_mock = function ( $preempt, $args, $url ) {
			if ( strpos( $url, 'https://raw.githubusercontent.com/WordPress/WordPress/refs/heads/master/license1.txt' ) === 0 ) {
				return [
					'response' => [ 'code' => 404, 'message' => 'Not Found' ],
					'body'     => '',
					'headers'  => [],
					'cookies'  => [],
					'filename' => null,
				];
			}

			if ( strpos( $url, 'https://raw.githubusercontent.com/WordPress/WordPress/refs/heads/master/license.txt' ) === 0 ) {
				return [
					'response' => [ 'code' => 200, 'message' => 'OK' ],
					'body'     => "WordPress - Web publishing software\n\nCopyright 2011-2024 by the contributors\n",
					'headers'  => [],
					'cookies'  => [],
					'filename' => null,
				];
			}

			return $preempt;
		};

		add_filter( 'pre_http_request', $this->http_mock, 10, 3 );
	}

	public function tear_down() {
		if ( $this->http_mock !== null ) {
			remove_filter( 'pre_http_request', $this->http_mock, 10 );
			$this->http_mock = null;
		}

		parent::tear_down();
	}

	public function test_send_request_success() {
		$request = new Request();
		$request->setLogger( new NullLogger() );
		$response = $request->sendRequest( new Payload( 'GET', 'https://raw.githubusercontent.com/WordPress/WordPress/refs/heads/master/license.txt' ) );

		$this->assertStringContainsString( 'WordPress - Web publishing software', (string) $response->getBody() );
	}

	public function test_send_request_wp_error() {
		$request = new Request();
		$request->setLogger( new NullLogger() );
		$response = $request->sendRequest( new Payload( 'GET', 'raw.githubusercontent.com/WordPress/WordPress/refs/heads/master/license.txt' ) );

		$this->assertEmpty( (string) $response->getBody() );
	}

	public function test_send_request_wp_error_with_debug() {
		$this->expectException( MpdfException::class );

		$request = new Request( true );
		$request->setLogger( new NullLogger() );
		$request->sendRequest( new Payload( 'GET', 'raw.githubusercontent.com/WordPress/WordPress/refs/heads/master/license.txt' ) );
	}

	public function test_send_request_status_error() {
		$request = new Request();
		$request->setLogger( new NullLogger() );
		$response = $request->sendRequest( new Payload( 'GET', 'https://raw.githubusercontent.com/WordPress/WordPress/refs/heads/master/license1.txt' ) );

		$this->assertSame( 404, $response->getStatusCode() );
		$this->assertEmpty( (string) $response->getBody() );
	}

	public function test_send_request_status_error_with_debug() {
		$this->expectException( MpdfException::class );

		$request = new Request( true );
		$request->setLogger( new NullLogger() );
		$request->sendRequest( new Payload( 'GET', 'https://raw.githubusercontent.com/WordPress/WordPress/refs/heads/master/license1.txt' ) );
	}
}
