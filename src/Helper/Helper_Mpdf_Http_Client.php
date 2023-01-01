<?php

namespace GFPDF\Helper;

use \GFPDF_Vendor\Mpdf\Http\Response;
use \GFPDF_Vendor\Mpdf\Http\ClientInterface;
use \GFPDF_Vendor\Mpdf\MpdfException;
use \GFPDF_Vendor\Mpdf\Log\Context;
use \Psr\Http\Message\RequestInterface;
use \Psr\Log\LoggerAwareInterface;
use \Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 6.5.3
 */
class Helper_Mpdf_Http_Client implements ClientInterface, LoggerAwareInterface {
	private $logger;
	private $debug;

	public function __construct( LoggerInterface $logger, $debug = false ) {
		$this->logger = $logger;
		$this->debug  = $debug;
	}

	/**
	 * Make a network request using wp_remote_get() and return a PSR-7 Response
	 *
	 * @param RequestInterface $request
	 *
	 * @return Response
	 * @throws MpdfException
	 *
	 * @since 6.5.3
	 */
	public function sendRequest( RequestInterface $request ) {
		if ( null === $request->getUri() ) {
			return new Response();
		}

		$url = $request->getUri();
		$this->logger->debug( \sprintf( 'Fetching (wp_remote_get()) content of remote URL "%s"', $url ), [ 'context' => Context::REMOTE_CONTENT ] );

		$http_call_args = apply_filters(
			'gfpdf_http_request_arguments',
			[
				'timeout' => 10,
			]
		);
		$http_call      = wp_remote_get( (string) $url, $http_call_args );

		if ( is_wp_error( $http_call ) ) {
			$this->logger->error( $http_call->get_error_message(), [ 'context' => Context::REMOTE_CONTENT ] );
			if ( $this->debug ) {
				throw new MpdfException( $http_call->get_error_message() );
			}

			return new Response();
		}

		$headers     = wp_remote_retrieve_headers( $http_call );
		$status_code = wp_remote_retrieve_response_code( $http_call );
		$body        = wp_remote_retrieve_body( $http_call );

		$response = new Response( $status_code, $headers->getAll(), $body );

		if ( $status_code !== 200 ) {
			$message = \sprintf( 'HTTP error: %d', $status_code );
			$this->logger->error( $message, [ 'context' => Context::REMOTE_CONTENT ] );
			if ( $this->debug ) {
				throw new MpdfException( $message );
			}
		}

		return $response;
	}

	/**
	 * @param LoggerInterface $logger
	 *
	 * @return void
	 *
	 * @since 6.5.3
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}
}
