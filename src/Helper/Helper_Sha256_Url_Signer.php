<?php

namespace GFPDF\Helper;

use GFPDF_Vendor\Spatie\UrlSigner\BaseUrlSigner;

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
 * Class Helper_Sha256_Url_Signer
 *
 * @package GFPDF\Helper
 */
class Helper_Sha256_Url_Signer extends BaseUrlSigner {

	/**
	 * @var int|null
	 *
	 * @since 6.18.0
	 */
	protected $max_timestamp;

	/**
	 * @param string   $signature_key
	 * @param int|null $max_timestamp URLs expiring after this are rejected, even with a valid signature
	 *
	 * @since 6.18.0
	 */
	public function __construct( $signature_key, $max_timestamp = null ) {
		parent::__construct( $signature_key );

		$this->max_timestamp = $max_timestamp;
	}

	/**
	 * Check the timestamp is in the future, and no later than the cap
	 *
	 * @param int $timestamp
	 *
	 * @return bool
	 *
	 * @since 6.18.0
	 */
	protected function isFuture( $timestamp ) {
		return parent::isFuture( $timestamp ) && ( $this->max_timestamp === null || (int) $timestamp <= $this->max_timestamp );
	}

	/**
	 * Generate a token to identify the secure action.
	 *
	 * @param string $url
	 * @param string $expiration
	 *
	 * @return string
	 */
	protected function createSignature( $url, $expiration ) {
		$token_data = [
			'expires' => $expiration,
			'url'     => (string) $url,
		];

		/* phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode */
		$token = rawurlencode( base64_encode( wp_json_encode( $token_data ) ) );

		return hash_hmac( 'sha256', $token, $this->signatureKey );
	}
}
