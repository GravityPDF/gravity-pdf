<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF_Vendor\Psr\Log\LoggerInterface;
use WP_Error;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Every outbound request the font system makes
 *
 * This half fetches metadata into memory — the root index, its signature, a source index, an entry file. The
 * streamed file downloads the installer needs are a second method on top of the same rules.
 *
 * The rules that are not negotiable: `https` only, `sslverify` hard-coded true (never
 * `EDD_SL_Plugin_Updater::verify_ssl()`, whose filter exists to let cheap hosts turn verification off), and a byte
 * ceiling applied *before* the request as well as after — a declared size over the cap is what stops a signed root
 * from announcing a 500 MB index.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Font_Downloader {

	/**
	 * A DROP-firewalled host has to fail the inline upgrade sync fast, so the connect timeout is well under the
	 * overall one
	 *
	 * @since 7.0
	 */
	public const CONNECT_TIMEOUT = 5;

	/**
	 * @since 7.0
	 */
	public const METADATA_TIMEOUT = 15;

	/**
	 * Hard ceilings, deliberately constants rather than filters: a host that can raise them can be told to accept
	 * a larger asset than the pipeline will ever publish
	 *
	 * @since 7.0
	 */
	public const MAX_ROOT_BYTES = 65536;

	/**
	 * @since 7.0
	 */
	public const MAX_SIGNATURE_BYTES = 1024;

	/**
	 * @since 7.0
	 */
	public const MAX_METADATA_BYTES = 2097152;

	/**
	 * @var LoggerInterface
	 * @since 7.0
	 */
	protected $log;

	public function __construct( LoggerInterface $log ) {
		$this->log = $log;
	}

	/**
	 * Fetch one metadata document into memory
	 *
	 * @param array $expected `sha256` to verify against, `size` the body must match exactly, `max_bytes` ceiling,
	 *                        `allow_redirect` for the root alone, `request_args` a third-party record's query args
	 *
	 * @return string|WP_Error The body
	 *
	 * @since 7.0
	 */
	public function fetch( string $url, array $expected = [] ) {
		$max_bytes = (int) ( $expected['max_bytes'] ?? static::MAX_METADATA_BYTES );

		$error = $this->check_url( $url );
		if ( $error !== null ) {
			return $error;
		}

		/* Refused before a byte is requested, which is what closes "a signed root declares a 500 MB index" */
		if ( isset( $expected['size'] ) && (int) $expected['size'] > $max_bytes ) {
			return new WP_Error(
				'font_too_large',
				sprintf( 'The file at %s declares %d bytes, over the %d byte ceiling', $url, (int) $expected['size'], $max_bytes )
			);
		}

		$request_url = $url;
		if ( count( (array) ( $expected['request_args'] ?? [] ) ) > 0 ) {
			$request_url = add_query_arg( $expected['request_args'], $url );
		}

		$response = $this->request( $request_url, $max_bytes );

		/* The root alone may follow exactly one redirect, and the target is re-checked as https */
		if ( ! is_wp_error( $response ) && ( $expected['allow_redirect'] ?? false ) && $this->is_redirect( $response ) ) {
			$location = wp_remote_retrieve_header( $response, 'location' );

			$error = $this->check_url( (string) $location );
			if ( $error !== null ) {
				return $error;
			}

			$response = $this->request( (string) $location, $max_bytes );
		}

		if ( is_wp_error( $response ) ) {
			$this->log->error(
				'Font request failed',
				[
					'url'   => $url,
					'error' => $response->get_error_message(),
				]
			);

			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			return new WP_Error( 'font_http_error', sprintf( 'The request for %s returned %d', $url, $code ) );
		}

		$body = (string) wp_remote_retrieve_body( $response );

		return $this->verify( $url, $body, $expected, $max_bytes );
	}

	/**
	 * The same shape WordPress sends by default, so a third-party root learns nothing it would not from any other
	 * WordPress request
	 *
	 * Nothing reads it on our own fonts host: with no code in the request path there is no sync telemetry at all.
	 *
	 * @since 7.0
	 */
	public function get_user_agent(): string {
		global $wp_version;

		return sprintf(
			'Gravity PDF/%s (WordPress/%s; Gravity Forms/%s; %s)',
			PDF_EXTENDED_VERSION,
			$wp_version,
			class_exists( 'GFForms' ) ? \GFForms::$version : 'unknown',
			home_url()
		);
	}

	/**
	 * @return array|WP_Error
	 *
	 * @since 7.0
	 */
	protected function request( string $url, int $max_bytes ) {
		return wp_safe_remote_get(
			$url,
			[
				/* Hard-coded: this must never become a filter a host can answer with false */
				'sslverify'           => true,
				'redirection'         => 0,
				'timeout'             => static::METADATA_TIMEOUT,
				'connect_timeout'     => static::CONNECT_TIMEOUT,
				'limit_response_size' => $max_bytes,
				'user-agent'          => $this->get_user_agent(),
			]
		);
	}

	/**
	 * Size first, then hash: a 12 MB body is never read whole only to be rejected on length
	 *
	 * @return string|WP_Error
	 *
	 * @since 7.0
	 */
	protected function verify( string $url, string $body, array $expected, int $max_bytes ) {
		$length = strlen( $body );

		if ( $length > $max_bytes ) {
			return new WP_Error( 'font_too_large', sprintf( 'The file at %s is %d bytes, over the %d byte ceiling', $url, $length, $max_bytes ) );
		}

		if ( isset( $expected['size'] ) && $length !== (int) $expected['size'] ) {
			return new WP_Error( 'font_size_mismatch', sprintf( 'The file at %s is %d bytes, not the %d the index lists', $url, $length, (int) $expected['size'] ) );
		}

		if ( isset( $expected['sha256'] ) && ! hash_equals( (string) $expected['sha256'], hash( 'sha256', $body ) ) ) {
			return new WP_Error( 'font_hash_mismatch', sprintf( 'The file at %s does not match the hash the index lists', $url ) );
		}

		return $body;
	}

	/**
	 * @since 7.0
	 */
	protected function check_url( string $url ): ?WP_Error {
		if ( stripos( $url, 'https://' ) !== 0 ) {
			return new WP_Error( 'font_insecure_url', sprintf( '%s is not an https URL', $url ) );
		}

		return null;
	}

	/**
	 * @since 7.0
	 */
	protected function is_redirect( array $response ): bool {
		$code = (int) wp_remote_retrieve_response_code( $response );

		return in_array( $code, [ 301, 302, 307, 308 ], true ) && wp_remote_retrieve_header( $response, 'location' ) !== '';
	}
}
