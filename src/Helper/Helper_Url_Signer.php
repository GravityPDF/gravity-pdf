<?php

namespace GFPDF\Helper;

use DateTime;
use GFPDF_Vendor\Spatie\UrlSigner\Exceptions\InvalidExpiration;
use GFPDF_Vendor\Spatie\UrlSigner\Exceptions\InvalidSignatureKey;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
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
	 *
	 * @throws InvalidSignatureKey
	 * @throws InvalidExpiration
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

		$signed_url = $url_signer->sign( $url, $timeout );

		$log->notice(
			'End PDF URL signing process',
			[
				'signed_url' => $signed_url,
				'url'        => $url,
				'expiration' => $expiration,
			]
		);

		return $signed_url;
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
		$secret_key = GPDFAPI::get_plugin_option( 'signed_secret_token', '' );

		try {
			$url_signer = new Helper_Sha256_Url_Signer( $secret_key );

			return $url_signer->validate( $url );
		} catch ( InvalidSignatureKey $e ) {
			return false;
		}
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
		} catch ( \SodiumException $e ) {
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
}
