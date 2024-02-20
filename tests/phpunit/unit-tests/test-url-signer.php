<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Url_Signer;
use GFPDF_Vendor\Spatie\UrlSigner\Exceptions\InvalidExpiration;
use GFPDF_Vendor\Spatie\UrlSigner\Exceptions\InvalidSignatureKey;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * @since 5.2
 * @group url-signer
 */
class Test_Url_Signer extends WP_UnitTestCase {

	/**
	 * @throws InvalidExpiration|InvalidSignatureKey
	 *
	 * @since 5.2
	 */
	public function test_sign_and_verify() {
		$signer = new Helper_Url_Signer();
		$url    = 'https://test.com';

		$this->assertNotEquals( $url, $signer->sign( $url, '+ 1 day' ) );
		$this->assertTrue( $signer->verify( $signer->sign( $url, '+ 1 day' ) ) );
		$this->assertFalse( $signer->verify( $url ) );
	}

	public function test_random_password_filter_disabled() {
		/* Delete the existing token (if any) */
		\GPDFAPI::delete_plugin_option( 'signed_secret_token' );

		/* Sign the URL */
		$signer = new Helper_Url_Signer();
		$url    = 'https://test.com';
		$signer->sign( $url, '+ 1 day' );

		/* Verify the token generated is 64 characters */
		$secret_token = \GPDFAPI::get_plugin_option( 'signed_secret_token' );
		$this->assertSame( 64, strlen( $secret_token ) );
	}
}
