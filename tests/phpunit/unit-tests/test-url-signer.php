<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Url_Signer;
use GFPDF_Vendor\Spatie\UrlSigner\Exceptions\InvalidExpiration;
use GFPDF_Vendor\Spatie\UrlSigner\Exceptions\InvalidSignatureKey;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
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
	 * @dataProvider provider_sign_and_verify
	 *
	 * @since        5.2
	 */
	public function test_sign_and_verify( $url ) {
		$signer = new Helper_Url_Signer();

		$this->assertNotEquals( $url, $signer->sign( $url, '+ 1 day' ) );
		$this->assertFalse( $signer->verify( $url ) );
		$this->assertStringNotContainsString( '_QHASH', $signer->sign( $url, '+ 1 day' ) );

		$this->assertTrue( $signer->verify( $signer->sign( $url, '+ 5 minutes' ) ) );
		$this->assertTrue( $signer->verify( $signer->sign( $url, '+ 1 day' ) ) );
		$this->assertTrue( $signer->verify( $signer->sign( $url, '+ 1 month' ) ) );
		$this->assertTrue( $signer->verify( $signer->sign( $url, '+ 12 hour' ) ) );
		$this->assertTrue( $signer->verify( $signer->sign( $url, '+ 1 year' ) ) );
	}

	public function test_signed_url_expiry_is_capped() {
		$signer  = new Helper_Url_Signer();
		$expires = function ( $expiration ) use ( $signer ) {
			wp_parse_str( wp_parse_url( $signer->sign( 'https://test.com/', $expiration ), PHP_URL_QUERY ), $query );

			return (int) $query['expires'];
		};

		$year = strtotime( '+1 year' );
		$this->assertEqualsWithDelta( $year, $expires( '+1 year' ), 5 );
		$this->assertEqualsWithDelta( $year, $expires( '+100 years' ), 5 );
		$this->assertEqualsWithDelta( strtotime( '+1 week' ), $expires( '+1 week' ), 5 );

		/* The cap can be raised, and a capped URL still verifies */
		$this->assertTrue( $signer->verify( $signer->sign( 'https://test.com/', '+100 years' ) ) );

		$filter = function () {
			return '+2 years';
		};
		add_filter( 'gfpdf_signed_url_max_expiration', $filter );
		$this->assertEqualsWithDelta( strtotime( '+2 years' ), $expires( '+100 years' ), 5 );

		remove_filter( 'gfpdf_signed_url_max_expiration', $filter );

		/* An invalid cap falls back to a year rather than leaving the URL uncapped */
		add_filter( 'gfpdf_signed_url_max_expiration', '__return_empty_string' );
		$this->assertEqualsWithDelta( $year, $expires( '+100 years' ), 5 );
		remove_filter( 'gfpdf_signed_url_max_expiration', '__return_empty_string' );
	}

	public function provider_sign_and_verify() {
		return [
			[ 'https://test.com' ],
			[ 'https://test.com/' ],
			[ 'https://test.com/this-is-my-url' ],
			[ 'https://test.com/pdf/69d83eeb6d0d1/3/' ],
			[ 'https://test.com/pdf/69d83eeb6d0d1/3/?' ],
			[ 'https://test.com/pdf/69d83eeb6d0d1/3' ],
			[ 'https://test.com:8000/pdf/69d83eeb6d0d1/3/' ],
			[ 'https://test.com:8000/pdf/69d83eeb6d0d1/3/#fragment' ],
			[ 'http://test.com/?p=123' ],
			[ 'https://test.com/?a=&b=1' ], // Empty value case
			[ 'https://test.com/?gpdf=1&pid=69d83f41f27fc&lid=1' ],
			[ 'https://test.com:8000/?gpdf=1&pid=69d83f41f27fc&lid=1' ],
			[ 'https://test.com:8000/?gpdf=1&pid=69d83f41f27fc&lid=1#fragment-stuff' ],
			[ 'https://test.com:8000/?gpdf=1&pid=69d83f41f27fc&lid=1&sample[]=1&sample[]=2' ],
			[ 'https://test.com:8000/?gpdf=1&pid=69d83f41f27fc&lid=1&sample[bob][]=1a&sample[bob][]=2' ],
			[ 'https://test.com:8000/?gpdf=1&pid=69d83f41f27fc&lid=1&sample[bob][]=1a&sample[bob][]=2bsample[alice][]=1b' ],
			[ 'https://test.com:8000/?gpdf=1&pid=69d83f41f27fc&lid=1&sample[bob][]=1a&sample[bob][]=2bsample[alice][]=1b#fragment-stuff' ],
		];
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

	/**
	 * Verify that signatures are protocol-agnostic (http <-> https)
	 */
	public function test_protocol_agnostic_verification() {
		$signer = new Helper_Url_Signer();

		$signed = $signer->sign( 'http://test.com/pdf/69d83eeb6d0d1/3/', '+ 1 day' );
		$this->assertTrue( $signer->verify( $signed ) );

		/* Swap http to https and verify. The implementation handles this swap internally. */
		$swapped = str_replace( 'http://', 'https://', $signed );
		$this->assertTrue( $signer->verify( $swapped ) );

		/* Swap back and verify */
		$signed_https = $signer->sign( 'https://test.com/pdf/69d83eeb6d0d1/3/', '+ 1 day' );
		$swapped_http = str_replace( 'https://', 'http://', $signed_https );
		$this->assertTrue( $signer->verify( $swapped_http ) );
	}

	/**
	 * Verify that the signer handles trailing slash normalization by the browser
	 */
	public function test_trailing_slash_normalization() {
		$signer = new Helper_Url_Signer();

		$signed = $signer->sign( 'https://test.com/path?p=123', '+ 1 day' );
		$this->assertTrue( $signer->verify( $signed ) );
		$this->assertTrue( $signer->verify( str_replace( '/path?', '/path/?', $signed ) ) );

		$signed = $signer->sign( 'https://test.com/path/?p=123', '+ 1 day' );
		$this->assertTrue( $signer->verify( $signed ) );
		$this->assertTrue( $signer->verify( str_replace( '/path/?', '/path?', $signed ) ) );
	}

	/**
	 * Verify that changes to the query params correctly fail verification (Forgery protection)
	 */
	public function test_forgery_protection() {
		$signer = new Helper_Url_Signer();
		$url    = 'https://test.com/?p=123&sample[]=1';

		$signed = $signer->sign( $url, '+ 1 day' );

		/* Modify p */
		$forged_p = str_replace( 'p=123', 'p=456', $signed );
		$this->assertFalse( $signer->verify( $forged_p ) );

		/* Modify sample array */
		$forged_arr = str_replace( 'sample[]=1', 'sample[]=2', $signed );
		$this->assertFalse( $signer->verify( $forged_arr ) );

		/* Add a param */
		$forged_add = $signed . '&new=456';
		$this->assertFalse( $signer->verify( $forged_add ) );
	}

	/**
	 * Verify that URLs signed with the legacy (non-tunneled) logic still verify correctly
	 */
	public function test_legacy_signing() {
		$signer = new Helper_Url_Signer();

		/*
		 * We simulate a legacy signature by using the vendor library directly,
		 * bypassing the _QHASH tunneling added to sign().
		 */
		$secret_key    = \GPDFAPI::get_plugin_option( 'signed_secret_token' );
		$url_signer    = new \GFPDF\Helper\Helper_Sha256_Url_Signer( $secret_key );
		$url           = 'https://test.com/?p=123';
		$expires       = ( new \DateTime() )->modify( '+ 1 day' );
		$legacy_signed = $url_signer->sign( $url, $expires );

		/* Verify it passes via verify_legacy_signature pathway */
		$this->assertTrue( $signer->verify( $legacy_signed ) );
	}

	/**
	 * Verify that expired URLs fail to verify
	 */
	public function test_expiration_failure() {
		$signer = new Helper_Url_Signer();
		$url    = 'https://test.com/?p=123';

		/* Sign with a valid future date */
		$signed = $signer->sign( $url, '+ 1 microsecond' );

		sleep( 1 );

		$this->assertFalse( $signer->verify( $signed ) );
	}
}
