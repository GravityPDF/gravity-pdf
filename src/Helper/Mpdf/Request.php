<?php

namespace GFPDF\Helper\Mpdf;

use GFPDF\Helper\Helper_Render_Health;
use GFPDF_Vendor\Mpdf\Http\ClientInterface;
use GFPDF_Vendor\Mpdf\Log\Context as LogContext;
use GFPDF_Vendor\Mpdf\MpdfException;
use GFPDF_Vendor\Mpdf\PsrHttpMessageShim\Response;
use GFPDF_Vendor\Mpdf\PsrHttpMessageShim\Stream;
use GFPDF_Vendor\Mpdf\PsrLogAwareTrait\PsrLogAwareTrait;
use GFPDF_Vendor\Psr\Http\Message\RequestInterface;
use GFPDF_Vendor\Psr\Log\LoggerAwareInterface;

/**
 * @since 6.13.0
 */
class Request implements ClientInterface, LoggerAwareInterface {
	use PsrLogAwareTrait;

	/**
	 * Consecutive failures (timeouts, DNS errors, 408s or 5xx) before a host's remaining requests are skipped
	 *
	 * @since 7.0
	 */
	const FAILURES_BEFORE_UNAVAILABLE = 2;

	/**
	 * @var bool Whether to throw an exception on an error
	 */
	protected $debug;

	/**
	 * @var int[] Consecutive failures, keyed by scheme://host:port. A new instance is made for every PDF.
	 *
	 * @since 7.0
	 */
	protected $host_failures = [];

	public function __construct( $debug = false ) {
		$this->debug = $debug;
	}

	/**
	 * Use WordPress functions for remote requests
	 *
	 * A timeout, DNS error, 408, 429 or 5xx marks the render degraded, so the PDF isn't cached. After
	 * FAILURES_BEFORE_UNAVAILABLE consecutive failures, the rest of that host's requests are skipped.
	 *
	 * @param RequestInterface $request
	 *
	 * @return Response
	 * @throws MpdfException
	 *
	 * @since 6.13.0
	 * @since 7.0 Adds the per-host circuit breaker, and rejects unsafe URLs before making the request
	 */
	public function sendRequest( RequestInterface $request ) {

		/* Not a valid request */
		if ( null === $request->getUri() ) {
			return new Response();
		}

		$response = new Response();
		$url      = (string) $request->getUri();
		$host     = $this->get_host_key( $url );

		if ( ( $this->host_failures[ $host ] ?? 0 ) >= self::FAILURES_BEFORE_UNAVAILABLE ) {
			Helper_Render_Health::mark();

			return $this->handle_error( \sprintf( 'Skipped remote request for %s because %s is unavailable', $url, $host ), $response, 'debug' );
		}

		$this->logger->debug( \sprintf( 'Fetching content of remote URL "%s"', $url ), [ 'context' => LogContext::REMOTE_CONTENT ] );

		/* Make a GET request to the URL */
		$request_args = (array) apply_filters(
			'gfpdf_remote_request_args',
			[
				'reject_unsafe_urls' => true,
			]
		);

		/* WordPress would refuse the URL anyway, but with the same error code as a timeout */
		if ( ! empty( $request_args['reject_unsafe_urls'] ) && ! wp_http_validate_url( $url ) ) {
			if ( $this->is_unresolved_host( $url ) ) {
				return $this->handle_failure( $host, \sprintf( 'Remote request error for %s: the host could not be resolved', $url ), $response );
			}

			return $this->handle_error( \sprintf( 'Remote request for %s was rejected as an unsafe URL', $url ), $response );
		}

		$request = wp_remote_get( $url, $request_args );

		/* Handle any request errors */
		if ( is_wp_error( $request ) ) {
			return $this->handle_failure( $host, \sprintf( 'Remote request error for %s: "%s: %s"', $url, $request->get_error_code(), $request->get_error_message() ), $response );
		}

		$status_code = (int) wp_remote_retrieve_response_code( $request );
		$message     = \sprintf( 'HTTP error "%d" for %s', $status_code, $url );

		if ( $status_code < 100 || $status_code === 408 || $status_code >= 500 ) {
			return $this->handle_failure( $host, $message, $status_code < 100 || $status_code > 599 ? $response : $response->withStatus( $status_code ) );
		}

		/* Any other answer shows the host is up */
		unset( $this->host_failures[ $host ] );

		if ( ! str_starts_with( (string) $status_code, '2' ) ) {
			/* A 404 renders the same every time, but a rate-limited request may succeed next time */
			if ( $status_code === 429 ) {
				Helper_Render_Health::mark();
			}

			return $this->handle_error( $message, $response->withStatus( $status_code ) );
		}

		/* Return the request body */
		$response_body = wp_remote_retrieve_body( $request );

		return $response->withStatus( $status_code )->withBody( Stream::create( $response_body ) );
	}

	/**
	 * Record a failure that could succeed on another render, and trip the host's breaker if it keeps failing
	 *
	 * @param string   $host
	 * @param string   $message
	 * @param Response $response
	 *
	 * @return Response
	 * @throws MpdfException
	 *
	 * @since 7.0
	 */
	protected function handle_failure( $host, $message, Response $response ) {
		Helper_Render_Health::mark();

		$this->host_failures[ $host ] = ( $this->host_failures[ $host ] ?? 0 ) + 1;
		if ( $this->host_failures[ $host ] === self::FAILURES_BEFORE_UNAVAILABLE ) {
			$this->logger->warning(
				\sprintf( 'Skipping further remote requests to %s for this PDF after %d consecutive failures', $host, self::FAILURES_BEFORE_UNAVAILABLE ),
				[ 'context' => LogContext::REMOTE_CONTENT ]
			);
		}

		return $this->handle_error( $message, $response );
	}

	/**
	 * Log a failed request, and throw in debug mode
	 *
	 * @param string   $message
	 * @param Response $response
	 * @param string   $level    The log level
	 *
	 * @return Response
	 * @throws MpdfException In debug mode
	 *
	 * @since 7.0
	 */
	protected function handle_error( $message, Response $response, $level = 'error' ) {
		$this->logger->log( $level, $message, [ 'context' => LogContext::REMOTE_CONTENT ] );

		if ( $this->debug ) {
			throw new MpdfException( esc_html( $message ) );
		}

		return $response;
	}

	/**
	 * wp_http_validate_url() also rejects a host name that doesn't resolve, which is a transient failure
	 *
	 * @param string $url
	 *
	 * @return bool
	 *
	 * @since 7.0
	 */
	protected function is_unresolved_host( $url ) {
		$host = trim( (string) wp_parse_url( $url, PHP_URL_HOST ), '.' );
		if ( $host === '' || filter_var( trim( $host, '[]' ), FILTER_VALIDATE_IP ) !== false ) {
			return false;
		}

		if ( strtolower( $host ) === strtolower( (string) wp_parse_url( get_option( 'home' ), PHP_URL_HOST ) ) ) {
			return false;
		}

		return gethostbyname( $host ) === $host;
	}

	/**
	 * @param string $url
	 *
	 * @return string The lowercased scheme://host:port
	 *
	 * @since 7.0
	 */
	protected function get_host_key( $url ) {
		$parts  = wp_parse_url( $url ) ?: [];
		$scheme = strtolower( $parts['scheme'] ?? '' );
		$port   = $parts['port'] ?? ( $scheme === 'https' ? 443 : 80 );

		return strtolower( \sprintf( '%s://%s:%d', $scheme, $parts['host'] ?? '', $port ) );
	}
}
