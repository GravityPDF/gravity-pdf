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

	/** @var callable */
	private $http_mock;

	public function set_up(): void {
		parent::set_up();

		/* Mock pre_http_request so the suite doesn't hit GitHub for license.txt (live fetch was flaky).
		 * Schemeless URLs in the wp_error tests bypass this mock and take wp_remote_get's WP_Error path. */
		$this->http_mock = function ( $preempt, $args, $url ) {
			if ( strpos( $url, 'https://raw.githubusercontent.com/WordPress/WordPress/refs/heads/master/license1.txt' ) === 0 ) {
				return [
					'response' => [ 'code' => 404 ],
					'body'     => '',
				];
			}

			if ( strpos( $url, 'https://raw.githubusercontent.com/WordPress/WordPress/refs/heads/master/license.txt' ) === 0 ) {
				return [
					'response' => [ 'code' => 200 ],
					'body'     => "WordPress - Web publishing software\n",
				];
			}

			return $preempt;
		};

		add_filter( 'pre_http_request', $this->http_mock, 10, 3 );
	}

	public function tear_down(): void {
		remove_filter( 'pre_http_request', $this->http_mock, 10 );
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
