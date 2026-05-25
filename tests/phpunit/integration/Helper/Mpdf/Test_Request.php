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
