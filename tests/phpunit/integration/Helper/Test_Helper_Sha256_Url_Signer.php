<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use DateTime;
use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Spatie\UrlSigner\Exceptions\InvalidSignatureKey;

/**
 * @group   helper
 * @group   url-signer
 */
class Test_Helper_Sha256_Url_Signer extends TestCase {

	private const KEY = 'test-secret-key-for-phpunit-assertions-only';

	private function make_signer(): Helper_Sha256_Url_Signer {
		return new Helper_Sha256_Url_Signer( self::KEY );
	}

	public function test_sign_appends_signature_and_expires_params(): void {
		$signer = $this->make_signer();
		$signed = $signer->sign( 'https://example.com/pdf/123/', new DateTime( '+1 day' ) );

		$this->assertStringContainsString( 'expires=', $signed );
		$this->assertStringContainsString( 'signature=', $signed );
	}

	public function test_sign_returns_modified_url(): void {
		$url    = 'https://example.com/pdf/abc/';
		$signer = $this->make_signer();
		$signed = $signer->sign( $url, new DateTime( '+1 day' ) );

		$this->assertNotSame( $url, $signed );
	}

	public function test_validate_accepts_own_signed_url(): void {
		$signer = $this->make_signer();
		$signed = $signer->sign( 'https://example.com/pdf/abc/', new DateTime( '+1 day' ) );

		$this->assertTrue( $signer->validate( $signed ) );
	}

	public function test_validate_rejects_url_signed_with_different_key(): void {
		$signer_a = $this->make_signer();
		$signer_b = new Helper_Sha256_Url_Signer( 'different-key' );

		$signed_by_a = $signer_a->sign( 'https://example.com/', new DateTime( '+1 day' ) );

		$this->assertFalse( $signer_b->validate( $signed_by_a ) );
	}

	public function test_validate_rejects_tampered_url(): void {
		$signer = $this->make_signer();
		$signed = $signer->sign( 'https://example.com/?p=1', new DateTime( '+1 day' ) );

		$tampered = str_replace( 'p=1', 'p=999', $signed );

		$this->assertFalse( $signer->validate( $tampered ) );
	}

	public function test_validate_rejects_unsigned_url(): void {
		$signer = $this->make_signer();

		$this->assertFalse( $signer->validate( 'https://example.com/?p=1' ) );
	}

	public function test_signature_is_deterministic_for_same_input(): void {
		$signer  = $this->make_signer();
		$expires = new DateTime( '@9999999999' );

		$signed_a = $signer->sign( 'https://example.com/stable/', $expires );
		$signed_b = $signer->sign( 'https://example.com/stable/', $expires );

		$this->assertSame( $signed_a, $signed_b );
	}

	public function test_constructor_throws_for_empty_key(): void {
		$this->expectException( InvalidSignatureKey::class );
		new Helper_Sha256_Url_Signer( '' );
	}
}
