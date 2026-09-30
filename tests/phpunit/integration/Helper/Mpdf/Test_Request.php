<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Mpdf;

use GFPDF\Helper\Helper_Render_Health;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger;
use GFPDF_Vendor\Mpdf\MpdfException;
use GFPDF_Vendor\Mpdf\PsrHttpMessageShim\Request as Payload;
use GFPDF_Vendor\Psr\Log\NullLogger;
use GFPDF\Tests\Integration\TestCase;
use WP_Error;

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

	/** @var array<string, array|WP_Error> Answers for pre_http_request, keyed by URL */
	private $answers = [];

	/** @var string[] URLs that reached pre_http_request */
	private $requested = [];

	public function set_up(): void {
		parent::set_up();

		/* Hosts the pre-check accepts without a DNS lookup: the home host and public IP literals */
		$this->answers = [
			home_url( '/license.txt' )  => $this->answer( 200, "WordPress - Web publishing software\n" ),
			home_url( '/license1.txt' ) => $this->answer( 404 ),
		];

		$this->http_mock = function ( $preempt, $args, $url ) {
			$this->requested[] = $url;

			return $this->answers[ $url ] ?? $preempt;
		};

		add_filter( 'pre_http_request', $this->http_mock, 10, 3 );

		Helper_Render_Health::begin();
	}

	public function tear_down(): void {
		Helper_Render_Health::end();

		remove_filter( 'pre_http_request', $this->http_mock, 10 );
		parent::tear_down();
	}

	public function test_send_request_success() {
		$response = $this->request()->sendRequest( new Payload( 'GET', home_url( '/license.txt' ) ) );

		$this->assertStringContainsString( 'WordPress - Web publishing software', (string) $response->getBody() );
		$this->assertFalse( Helper_Render_Health::is_degraded() );
	}

	public function test_send_request_wp_error() {
		$response = $this->request()->sendRequest( new Payload( 'GET', 'raw.githubusercontent.com/WordPress/WordPress/refs/heads/master/license.txt' ) );

		$this->assertEmpty( (string) $response->getBody() );
	}

	public function test_send_request_wp_error_with_debug() {
		$this->expectException( MpdfException::class );

		$this->request( true )->sendRequest( new Payload( 'GET', 'raw.githubusercontent.com/WordPress/WordPress/refs/heads/master/license.txt' ) );
	}

	public function test_send_request_status_error() {
		$response = $this->request()->sendRequest( new Payload( 'GET', home_url( '/license1.txt' ) ) );

		$this->assertSame( 404, $response->getStatusCode() );
		$this->assertEmpty( (string) $response->getBody() );
	}

	public function test_send_request_status_error_with_debug() {
		$this->expectException( MpdfException::class );

		$this->request( true )->sendRequest( new Payload( 'GET', home_url( '/license1.txt' ) ) );
	}

	/**
	 * @dataProvider provider_degraded_responses
	 */
	public function test_a_failure_that_may_pass_next_time_marks_the_render_degraded( $answer, bool $degraded ) {
		$this->answers['https://1.1.1.1/image.png'] = $answer;

		$this->request()->sendRequest( new Payload( 'GET', 'https://1.1.1.1/image.png' ) );

		$this->assertSame( $degraded, Helper_Render_Health::is_degraded() );
	}

	public function provider_degraded_responses(): array {
		return [
			'timeout' => [ new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ), true ],
			'408'     => [ $this->answer( 408 ), true ],
			'429'     => [ $this->answer( 429 ), true ],
			'500'     => [ $this->answer( 500 ), true ],
			'503'     => [ $this->answer( 503 ), true ],
			'404'     => [ $this->answer( 404 ), false ],
			'403'     => [ $this->answer( 403 ), false ],
			'200'     => [ $this->answer( 200, 'image' ), false ],
		];
	}

	public function test_an_unsafe_url_is_rejected_without_a_request() {
		$request = $this->request();

		foreach ( [ 1, 2, 3 ] as $i ) {
			$response = $request->sendRequest( new Payload( 'GET', "http://10.0.0.1/image-$i.png" ) );
			$this->assertSame( '', (string) $response->getBody() );
		}

		$this->assertSame( [], $this->requested );
		$this->assertFalse( Helper_Render_Health::is_degraded(), 'A rejected URL gives the same answer every render' );

		/* Rejections didn't count toward the breaker, so the host is fetched once it is allowed */
		add_filter( 'http_request_host_is_external', '__return_true' );
		$this->answers['http://10.0.0.1/image-4.png'] = $this->answer( 200, 'image' );

		$this->assertSame( 'image', (string) $request->sendRequest( new Payload( 'GET', 'http://10.0.0.1/image-4.png' ) )->getBody() );
	}

	public function test_an_unsafe_url_is_fetched_when_the_check_is_filtered_off() {
		$this->answers['http://10.0.0.1/image.png'] = $this->answer( 200, 'image' );

		add_filter(
			'gfpdf_remote_request_args',
			function ( $args ) {
				$args['reject_unsafe_urls'] = false;

				return $args;
			}
		);

		$response = $this->request()->sendRequest( new Payload( 'GET', 'http://10.0.0.1/image.png' ) );

		$this->assertSame( 'image', (string) $response->getBody() );
		$this->assertSame( [ 'http://10.0.0.1/image.png' ], $this->requested );
	}

	public function test_a_host_that_does_not_resolve_is_a_failure() {
		$logger  = new TestHandler();
		$request = $this->request( false, $logger );

		$request->sendRequest( new Payload( 'GET', 'https://nonexistent.invalid/one.png' ) );

		$this->assertTrue( Helper_Render_Health::is_degraded() );
		$this->assertSame( [], $this->requested );
		$this->assertSame( 0, $this->count_logs( $logger, 'Skipping further remote requests' ) );

		$request->sendRequest( new Payload( 'GET', 'https://nonexistent.invalid/two.png' ) );
		$this->assertSame( 1, $this->count_logs( $logger, 'Skipping further remote requests to https://nonexistent.invalid:443' ) );

		$request->sendRequest( new Payload( 'GET', 'https://nonexistent.invalid/three.png' ) );
		$this->assertSame( 1, $this->count_logs( $logger, 'Skipped remote request for https://nonexistent.invalid/three.png' ) );
		$this->assertSame( [], $this->requested );
	}

	public function test_one_failure_is_a_blip() {
		$request = $this->request();

		$this->fetch( $request, 'https://1.1.1.1/a.png', new WP_Error( 'http_request_failed', 'timeout' ) );
		$this->fetch( $request, 'https://1.1.1.1/b.png', $this->answer( 200, 'image' ) );

		$this->assertSame( [ 'https://1.1.1.1/a.png', 'https://1.1.1.1/b.png' ], $this->requested );
	}

	public function test_two_consecutive_failures_skip_the_host_but_not_others() {
		$request = $this->request();

		$this->fetch( $request, 'https://1.1.1.1/a.png', new WP_Error( 'http_request_failed', 'timeout' ) );
		$this->fetch( $request, 'https://1.1.1.1/b.png', $this->answer( 503 ) );
		$this->fetch( $request, 'https://1.1.1.1/c.png', $this->answer( 200, 'image' ) );
		$this->fetch( $request, 'https://8.8.8.8/d.png', $this->answer( 200, 'image' ) );

		$this->assertSame( [ 'https://1.1.1.1/a.png', 'https://1.1.1.1/b.png', 'https://8.8.8.8/d.png' ], $this->requested );

		/* The port is part of the host */
		$this->fetch( $request, 'https://1.1.1.1:8080/e.png', $this->answer( 200, 'image' ) );
		$this->assertContains( 'https://1.1.1.1:8080/e.png', $this->requested );
	}

	/**
	 * @dataProvider provider_answers_that_reset_the_count
	 */
	public function test_an_answer_from_the_host_resets_its_count( array $answer ) {
		$request = $this->request();

		$this->fetch( $request, 'https://1.1.1.1/a.png', new WP_Error( 'http_request_failed', 'timeout' ) );
		$this->fetch( $request, 'https://1.1.1.1/b.png', $answer );
		$this->fetch( $request, 'https://1.1.1.1/c.png', $this->answer( 500 ) );
		$this->fetch( $request, 'https://1.1.1.1/d.png', $this->answer( 200, 'image' ) );

		$this->assertCount( 4, $this->requested );
	}

	public function provider_answers_that_reset_the_count(): array {
		return [
			'200' => [ $this->answer( 200, 'image' ) ],
			'404' => [ $this->answer( 404 ) ],
			'429' => [ $this->answer( 429 ) ],
		];
	}

	public function test_408_and_5xx_add_to_the_count() {
		$request = $this->request();

		$this->fetch( $request, 'https://1.1.1.1/a.png', $this->answer( 500 ) );
		$this->fetch( $request, 'https://1.1.1.1/b.png', $this->answer( 429 ) );
		$this->fetch( $request, 'https://1.1.1.1/c.png', $this->answer( 408 ) );
		$this->fetch( $request, 'https://1.1.1.1/d.png', $this->answer( 502 ) );
		$this->fetch( $request, 'https://1.1.1.1/e.png', $this->answer( 200, 'image' ) );

		$this->assertCount( 4, $this->requested );
	}

	public function test_a_skipped_fetch_marks_the_render_and_logs_the_trip_once() {
		$logger  = new TestHandler();
		$request = $this->request( false, $logger );

		$this->fetch( $request, 'https://1.1.1.1/a.png', $this->answer( 500 ) );
		$this->fetch( $request, 'https://1.1.1.1/b.png', $this->answer( 500 ) );

		Helper_Render_Health::end();
		Helper_Render_Health::begin();

		$response = $this->fetch( $request, 'https://1.1.1.1/c.png', $this->answer( 200, 'image' ) );
		$this->fetch( $request, 'https://1.1.1.1/d.png', $this->answer( 200, 'image' ) );

		$this->assertSame( '', (string) $response->getBody() );
		$this->assertTrue( Helper_Render_Health::is_degraded() );
		$this->assertSame( 1, $this->count_logs( $logger, 'Skipping further remote requests' ) );
	}

	public function test_a_skipped_fetch_throws_in_debug_mode() {
		$request = $this->request( true );

		foreach ( [ 'a', 'b' ] as $name ) {
			try {
				$this->fetch( $request, "https://1.1.1.1/$name.png", $this->answer( 500 ) );
			} catch ( MpdfException $e ) {
				/* A failed fetch throws in debug mode too */
			}
		}

		$this->expectException( MpdfException::class );
		$this->expectExceptionMessage( 'Skipped remote request' );

		$this->fetch( $request, 'https://1.1.1.1/c.png', $this->answer( 200, 'image' ) );
	}

	public function test_a_new_render_tries_a_host_the_last_one_gave_up_on() {
		$request = $this->request();

		$this->fetch( $request, 'https://1.1.1.1/a.png', $this->answer( 500 ) );
		$this->fetch( $request, 'https://1.1.1.1/b.png', $this->answer( 500 ) );

		$response = $this->fetch( $this->request(), 'https://1.1.1.1/c.png', $this->answer( 200, 'image' ) );

		$this->assertSame( 'image', (string) $response->getBody() );
		$this->assertCount( 3, $this->requested );
	}

	/**
	 * @param array|WP_Error $answer
	 */
	private function fetch( Request $request, string $url, $answer ) {
		$this->answers[ $url ] = $answer;

		return $request->sendRequest( new Payload( 'GET', $url ) );
	}

	private function answer( int $code, string $body = '' ): array {
		return [
			'response' => [ 'code' => $code ],
			'body'     => $body,
		];
	}

	private function request( bool $debug = false, ?TestHandler $handler = null ): Request {
		$request = new Request( $debug );
		$request->setLogger( $handler ? new Logger( 'test', [ $handler ] ) : new NullLogger() );

		return $request;
	}

	private function count_logs( TestHandler $handler, string $prefix ): int {
		return count(
			array_filter(
				$handler->getRecords(),
				function ( $record ) use ( $prefix ) {
					return strpos( $record['message'], $prefix ) === 0;
				}
			)
		);
	}
}
