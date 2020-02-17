<?php

namespace GFPDF\Helper;

use Spatie\UrlSigner\Exceptions\InvalidSignatureKey;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
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
	 * @throws \Spatie\UrlSigner\Exceptions\InvalidSignatureKey
	 *
	 * @since 5.2
	 */
	public function sign( $url, $expiration ) {
		$secret_key = \GPDFAPI::get_plugin_option( 'signed_secret_token', '' );

		/* If no secret key exists, generate it */
		if ( empty( $secret_key ) ) {
			$secret_key = wp_generate_password( 64 );
			\GPDFAPI::update_plugin_option( 'signed_secret_token', $secret_key );
		}

		$url_signer = new Helper_Sha256_Url_Signer( $secret_key );

		if ( empty( $expiration ) ) {
			$expiration = intval( \GPDFAPI::get_plugin_option( 'logged_out_timeout', '20' ) ) . ' minutes';
		}

		$date    = new \DateTime();
		$timeout = $date->modify( $expiration );

		return $url_signer->sign( $url, $timeout );
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
		$secret_key = \GPDFAPI::get_plugin_option( 'signed_secret_token', '' );

		try {
			$url_signer = new Helper_Sha256_Url_Signer( $secret_key );
			return $url_signer->validate( $url );
		} catch ( InvalidSignatureKey $e ) {
			return false;
		}
	}
}
