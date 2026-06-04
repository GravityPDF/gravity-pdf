<?php

namespace GFPDF\Helper;

use GPDFAPI;
use DateTime;
use SodiumException;
use Exception;

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
 * Class Helper_Url_Signer
 *
 * @package GFPDF\Helper
 */
class Helper_Url_Signer implements Helper_Interface_Url_Signer {

	/**
	 * Sign a URL with a secret token
	 *
	 * @param string $url
	 * @param string $expiration
	 *
	 * @return string

	 * @since 5.2
	 */
	public function sign( $url, $expiration ) {
		$log = GPDFAPI::get_log_class();

		$log->notice(
			'Begin PDF URL signing process',
			[
				'url'        => $url,
				'expiration' => $expiration,
			]
		);

		$secret_key = GPDFAPI::get_plugin_option( 'signed_secret_token', '' );

		/* If no secret key exists, generate it */
		if ( empty( $secret_key ) ) {
			$secret_key = $this->generate_secret_key();
			GPDFAPI::update_plugin_option( 'signed_secret_token', $secret_key );
		}

		/* If the secret key is ever corrupted, create a new one */
		if ( ! is_string( $secret_key ) || strlen( $secret_key ) !== 64 ) {
			$secret_key = $this->generate_secret_key();
			GPDFAPI::update_plugin_option( 'signed_secret_token', $secret_key );
		}

		try {
			$url_signer = new Helper_Sha256_Url_Signer( $secret_key );

			/* Use default timeout if no expiration passed, or expiration is invalid */
			if ( empty( $expiration ) || (bool) strtotime( $expiration ) === false ) {
				$expiration = ( (int) GPDFAPI::get_plugin_option( 'logged_out_timeout', '20' ) ) . ' minutes';
			}

			$log->notice( 'PDF URL signing period: ' . $expiration, [ 'url' => $url ] );

			$date = new DateTime();
			$log->notice( 'PDF URL signing date: ' . $date->format( DateTime::W3C ), [ 'url' => $url ] );

			$timeout = $date->modify( $expiration );
			$log->notice( 'PDF URL signing expiration date: ' . $timeout->format( DateTime::W3C ), [ 'url' => $url ] );

			/* Normalize URL to fix vendor error parsing invalid URL params */
			$url_parts          = wp_parse_url( $url );
			$uri_query          = $url_parts['query'] ?? '';
			$url_parts['query'] = '_QHASH=' . base64_encode( $uri_query ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$url                = $this->build_url( $url_parts );

			/* Sign normalized URL, then extract the arguments and append to original URL */
			$signed_url         = $url_signer->sign( $url, $timeout );
			$signed_parts       = wp_parse_url( $signed_url );
			$uri_query         .= str_replace( $url_parts['query'], '', $signed_parts['query'] ?? '' );
			$url_parts['query'] = ltrim( $uri_query, '&' );

			$signed_url = $this->build_url( $url_parts );

			$log->notice(
				'End PDF URL signing process',
				[
					'signed_url' => $signed_url,
					'url'        => $url,
					'expiration' => $expiration,
				]
			);

			return $signed_url;
		} catch ( Exception $e ) {
			$log->error(
				'PDF URL signing process failed',
				[
					'url'        => $url,
					'expiration' => $expiration,
				]
			);

			return $url;
		}
	}

	/**
	 * Verify if the signed URL is valid
	 *
	 * @param string $url
	 *
	 * @return bool
	 *
	 * @since 5.2
	 */
	public function verify( $url ) {
		if ( $this->validate( $this->get_encoded_url( $url ) ) ) {
			return true;
		}

		return $this->validate( $url );
	}

	/**
	 * Verify the signature on the URL of the current request
	 *
	 * @return bool
	 *
	 * @since 7.0
	 */
	public function verify_current_request() {
		$protocol = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
		$domain   = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : '';
		$request  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

		return $this->verify( esc_url_raw( $protocol . $domain . $request ) );
	}

	/**
	 * Validate the URL and return the results
	 *
	 * @param string $url
	 *
	 * @return bool
	 *
	 * @since 6.14.2
	 */
	protected function validate( $url ) {
		$secret_key = GPDFAPI::get_plugin_option( 'signed_secret_token', '' );

		try {
			$url_signer = new Helper_Sha256_Url_Signer( $secret_key );

			/* Do legacy URL checks */
			if ( $url_signer->validate( $url ) ) {
				return true;
			}

			if ( $url_signer->validate( rawurldecode( $url ) ) ) {
				return true;
			}

			/* Swap the protocol and test again. We use strtr for simultaneous replacement to avoid the sequential swap pitfall. */
			$protocols = [
				'https://' => 'http://',
				'http://'  => 'https://',
			];

			if ( $url_signer->validate( strtr( $url, $protocols ) ) ) {
				return true;
			}

			/* Strip the trailing slash right before the url param and test again */
			$trailing_slash = [
				'/?' => '?',
				'?'  => '/?',
			];

			if ( $url_signer->validate( strtr( $url, $trailing_slash ) ) ) {
				return true;
			}
		} catch ( Exception $e ) {
			// do nothing
		}

		return false;
	}

	/**
	 * Return an encoded version of the URL
	 *
	 * This resolves errors in the vendor URI library when processing array URL params (e.g. item[]=1)
	 *
	 * @param string $url
	 *
	 * @return string
	 *
	 * @since 6.14.2
	 */
	protected function get_encoded_url( $url ) {
		/* Get the signature URL params */
		$url_pieces = explode( '?', $url, 2 );
		$base       = $url_pieces[0] ?? '';
		$query      = $url_pieces[1] ?? '';

		$qs = [];
		wp_parse_str( $query, $qs );

		$expires   = $qs['expires'] ?? '';
		$signature = $qs['signature'] ?? '';

		/* Remove the signature params, encode the query, then put it back together */
		$delimeter = count( $qs ) > 2 ? '&' : '';

		$signature_url_params = "expires={$expires}&signature={$signature}";
		$query                = str_replace( "$delimeter$signature_url_params", '', $query );
		$query                = '_QHASH=' . base64_encode( $query ) . '&' . $signature_url_params; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		return implode( '?', [ $base, $query ] );
	}

	/**
	 * Generates a random 64-character string for use as the secret key
	 *
	 * @return string
	 *
	 * @since 6.4.0
	 */
	protected function generate_secret_key(): string {
		try {
			return sodium_bin2hex( sodium_crypto_secretbox_keygen() );
		} catch ( SodiumException $e ) {
			/* Do nothing */
		}

		/* Fallback to cut down version of wp_generate_password() without the `random_password` filter */
		$chars    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';
		$password = '';
		for ( $i = 0; $i < 64; $i++ ) {
			$password .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
		}

		return $password;
	}

	/**
	 * Reverses `parse_url()`
	 *
	 * @param array $parsed_url
	 *
	 * @return string
	 *
	 * @since 6.14.2
	 */
	protected function build_url( $parsed_url ) {
		$scheme = isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] . '://' : '';
		$host   = $parsed_url['host'] ?? '';
		$port   = isset( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : '';
		$user   = $parsed_url['user'] ?? '';
		$pass   = isset( $parsed_url['pass'] ) ? ':' . $parsed_url['pass'] : '';
		$pass   = ( $user || $pass ) ? "$pass@" : '';
		$path   = $parsed_url['path'] ?? '';
		$query  = isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '';

		return urldecode( "$scheme$user$pass$host$port$path$query" );
	}
}
