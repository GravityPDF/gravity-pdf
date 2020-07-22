<?php

namespace GFPDF\Helper;

use GFPDF_Vendor\Spatie\UrlSigner\BaseUrlSigner;

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
 * Class Helper_Sha256_Url_Signer
 *
 * @package GFPDF\Helper
 */
class Helper_Sha256_Url_Signer extends BaseUrlSigner {

	/**
	 * Generate a token to identify the secure action.
	 *
	 * @param \GFPDF_Vendor\League\Url\UrlImmutable|string $url
	 * @param string                          $expiration
	 *
	 * @return string
	 */
	protected function createSignature( $url, $expiration ) {
		$token_data = [
			'expires' => $expiration,
			'url'     => (string) $url,
		];

		$token = rawurlencode( base64_encode( json_encode( $token_data ) ) );

		return hash_hmac( 'sha256', $token, $this->signatureKey );
	}
}
