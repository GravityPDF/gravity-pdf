<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

use WP_Error;

/**
 * Intercept outbound HTTP through `pre_http_request` and answer from a routing table.
 *
 * Every font test that would otherwise reach the network uses this. Requests are recorded so a test can assert on
 * what was sent — the URL, and the headers the plugin promises never to include.
 */
trait MocksHttpRequests {

	/**
	 * @var array<int, array{url: string, args: array}>
	 */
	protected $http_requests = [];

	/**
	 * @var callable|null
	 */
	private $http_filter;

	/**
	 * Answer any request whose URL contains one of the given substrings
	 *
	 * @param array $routes substring => a body string, or `[ 'body' => …, 'code' => …, 'headers' => … ]`, or a
	 *                      WP_Error. A request matching nothing gets a 404, so a test never silently reaches out.
	 */
	protected function mock_http( array $routes ): void {
		$this->unmock_http();

		$this->http_filter = function ( $preempt, $args, $url ) use ( $routes ) {
			$this->http_requests[] = [
				'url'  => $url,
				'args' => $args,
			];

			foreach ( $routes as $needle => $response ) {
				if ( strpos( $url, (string) $needle ) === false ) {
					continue;
				}

				if ( $response instanceof WP_Error ) {
					return $response;
				}

				$response = is_array( $response ) ? $response : [ 'body' => $response ];

				return [
					'headers'  => $response['headers'] ?? [],
					'body'     => $response['body'] ?? '',
					'response' => [
						'code'    => $response['code'] ?? 200,
						'message' => 'OK',
					],
					'cookies'  => [],
					'filename' => null,
				];
			}

			return [
				'headers'  => [],
				'body'     => '',
				'response' => [
					'code'    => 404,
					'message' => 'Not Found',
				],
				'cookies'  => [],
				'filename' => null,
			];
		};

		add_filter( 'pre_http_request', $this->http_filter, 10, 3 );
	}

	protected function unmock_http(): void {
		if ( $this->http_filter !== null ) {
			remove_filter( 'pre_http_request', $this->http_filter, 10 );
			$this->http_filter = null;
		}

		$this->http_requests = [];
	}

	/**
	 * Every URL requested, in order
	 *
	 * @return string[]
	 */
	protected function requested_urls(): array {
		return wp_list_pluck( $this->http_requests, 'url' );
	}

	/**
	 * The args of the first request whose URL contains the needle
	 */
	protected function request_args_for( string $needle ): ?array {
		foreach ( $this->http_requests as $request ) {
			if ( strpos( $request['url'], $needle ) !== false ) {
				return $request['args'];
			}
		}

		return null;
	}
}
