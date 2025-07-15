<?php

namespace GFPDF\Helper\Mpdf;

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
	 * @var bool Whether to throw an exception on an error
	 */
	protected $debug;

	public function __construct( $debug = false ) {
		$this->debug = $debug;
	}

	/**
	 * Use WordPress functions for remote requests
	 *
	 * @param RequestInterface $request
	 *
	 * @return Response
	 * @throws MpdfException
	 *
	 * @since 6.13.0
	 */
	public function sendRequest( RequestInterface $request ) {

		/* Not a valid request */
		if ( null === $request->getUri() ) {
			return new Response();
		}

		$response = new Response();
		$url      = (string) $request->getUri();

		$this->logger->debug( \sprintf( 'Fetching content of remote URL "%s"', $url ), [ 'context' => LogContext::REMOTE_CONTENT ] );

		/* Make a GET request to the URL */
		$request_args = apply_filters(
			'gfpdf_remote_request_args',
			[
				'reject_unsafe_urls' => true,
			]
		);

		$request = wp_remote_get( $url, (array) $request_args );

		/* Handle any request errors */
		if ( is_wp_error( $request ) ) {
			$message = \sprintf( 'Remote request error for %s: "%s: %s"', $url, $request->get_error_code(), $request->get_error_message() );
			$this->logger->error( $message, [ 'context' => LogContext::REMOTE_CONTENT ] );

			if ( $this->debug ) {
				throw new MpdfException( esc_html( $message ) );
			}

			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $request );
		if ( empty( $status_code ) || ! str_starts_with( (string) $status_code, '2' ) ) {
			$message = \sprintf( 'HTTP error "%d" for %s', $status_code, $url );
			$this->logger->error( $message, [ 'context' => LogContext::REMOTE_CONTENT ] );

			if ( $this->debug ) {
				throw new MpdfException( esc_html( $message ) );
			}

			return $response->withStatus( $status_code );
		}

		/* Return the request body */
		$response_body = wp_remote_retrieve_body( $request );

		return $response->withStatus( $status_code )->withBody( Stream::create( $response_body ) );
	}
}
