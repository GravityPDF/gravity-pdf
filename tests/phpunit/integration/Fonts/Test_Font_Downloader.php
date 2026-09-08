<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\MocksHttpRequests;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;
use WP_Error;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Font_Downloader
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Font_Downloader extends TestCase {

	use MocksHttpRequests;

	/**
	 * @var Font_Downloader
	 */
	public $downloader;

	public function set_up(): void {
		parent::set_up();

		$this->downloader = new Font_Downloader( GPDFAPI::get_log_class() );
	}

	public function tear_down(): void {
		$this->unmock_http();

		parent::tear_down();
	}

	public function test_a_body_comes_back_verbatim() {
		$this->mock_http( [ 'fonts.gravitypdf.com' => '{"schema":1}' ] );

		$this->assertSame( '{"schema":1}', $this->downloader->fetch( 'https://fonts.gravitypdf.com/v1/index.json' ) );
	}

	public function test_a_matching_hash_passes_and_a_mismatched_one_fails() {
		$body = '{"schema":1}';

		$this->mock_http( [ 'fonts.gravitypdf.com' => $body ] );

		$this->assertSame( $body, $this->downloader->fetch( 'https://fonts.gravitypdf.com/v1/x.json', [ 'sha256' => hash( 'sha256', $body ) ] ) );

		$result = $this->downloader->fetch( 'https://fonts.gravitypdf.com/v1/x.json', [ 'sha256' => str_repeat( 'a', 64 ) ] );

		$this->assertWPError( $result );
		$this->assertSame( 'font_hash_mismatch', $result->get_error_code() );
	}

	public function test_a_body_that_is_not_the_declared_size_fails() {
		$this->mock_http( [ 'fonts.gravitypdf.com' => 'four' ] );

		$result = $this->downloader->fetch( 'https://fonts.gravitypdf.com/v1/x.json', [ 'size' => 99 ] );

		$this->assertWPError( $result );
		$this->assertSame( 'font_size_mismatch', $result->get_error_code() );
	}

	public function test_a_declared_size_over_the_ceiling_is_refused_before_any_request() {
		$this->mock_http( [ 'fonts.gravitypdf.com' => 'x' ] );

		/* This is what stops a signed root announcing a 500 MB index */
		$result = $this->downloader->fetch(
			'https://fonts.gravitypdf.com/v1/index.json',
			[
				'size'      => 500 * 1024 * 1024,
				'max_bytes' => Font_Downloader::MAX_ROOT_BYTES,
			]
		);

		$this->assertWPError( $result );
		$this->assertSame( 'font_too_large', $result->get_error_code() );
		$this->assertSame( [], $this->requested_urls(), 'no request should have been made' );
	}

	public function test_a_body_over_the_ceiling_is_refused_after_the_request() {
		$this->mock_http( [ 'fonts.gravitypdf.com' => str_repeat( 'x', 2048 ) ] );

		$result = $this->downloader->fetch( 'https://fonts.gravitypdf.com/v1/index.json.sig', [ 'max_bytes' => Font_Downloader::MAX_SIGNATURE_BYTES ] );

		$this->assertWPError( $result );
		$this->assertSame( 'font_too_large', $result->get_error_code() );
	}

	/**
	 * @dataProvider provider_insecure_urls
	 */
	public function test_a_non_https_url_is_refused_without_a_request( string $url ) {
		$this->mock_http( [ 'example' => 'x' ] );

		$result = $this->downloader->fetch( $url );

		$this->assertWPError( $result );
		$this->assertSame( 'font_insecure_url', $result->get_error_code() );
		$this->assertSame( [], $this->requested_urls() );
	}

	public function provider_insecure_urls(): array {
		return [
			'plain http' => [ 'http://fonts.example.com/v1/index.json' ],
			'protocol relative' => [ '//fonts.example.com/v1/index.json' ],
			'ftp' => [ 'ftp://fonts.example.com/v1/index.json' ],
			'empty' => [ '' ],
		];
	}

	public function test_a_non_200_is_an_error() {
		$this->mock_http( [ 'fonts.gravitypdf.com' => [ 'code' => 500, 'body' => 'nope' ] ] );

		$result = $this->downloader->fetch( 'https://fonts.gravitypdf.com/v1/index.json' );

		$this->assertWPError( $result );
		$this->assertSame( 'font_http_error', $result->get_error_code() );
	}

	public function test_a_transport_error_comes_back_as_is() {
		$this->mock_http( [ 'fonts.gravitypdf.com' => new WP_Error( 'http_request_failed', 'Connection timed out' ) ] );

		$result = $this->downloader->fetch( 'https://fonts.gravitypdf.com/v1/index.json' );

		$this->assertWPError( $result );
		$this->assertSame( 'http_request_failed', $result->get_error_code() );
	}

	public function test_ssl_verification_and_redirects_are_not_negotiable() {
		$this->mock_http( [ 'fonts.gravitypdf.com' => 'ok' ] );

		$this->downloader->fetch( 'https://fonts.gravitypdf.com/v1/index.json' );

		$args = $this->request_args_for( 'index.json' );

		/* sslverify must never become a filter a cheap host can answer with false */
		$this->assertTrue( $args['sslverify'] );
		$this->assertSame( 0, $args['redirection'] );
		$this->assertSame( Font_Downloader::CONNECT_TIMEOUT, $args['connect_timeout'] );
	}

	public function test_a_hash_addressed_fetch_does_not_follow_a_redirect() {
		$this->mock_http(
			[
				'index.json' => [ 'code' => 302, 'headers' => [ 'location' => 'https://fonts.gravitypdf.com/v1/moved.json' ] ],
				'moved.json' => 'moved body',
			]
		);

		$result = $this->downloader->fetch( 'https://fonts.gravitypdf.com/v1/index.json' );

		$this->assertWPError( $result );
		$this->assertSame( 'font_http_error', $result->get_error_code() );
	}

	public function test_the_root_may_follow_exactly_one_redirect() {
		$this->mock_http(
			[
				'index.json' => [ 'code' => 302, 'headers' => [ 'location' => 'https://fonts.gravitypdf.com/v1/moved.json' ] ],
				'moved.json' => 'moved body',
			]
		);

		$result = $this->downloader->fetch( 'https://fonts.gravitypdf.com/v1/index.json', [ 'allow_redirect' => true ] );

		$this->assertSame( 'moved body', $result );
		$this->assertCount( 2, $this->requested_urls() );
	}

	public function test_a_redirect_to_plain_http_is_refused() {
		$this->mock_http(
			[
				'index.json' => [ 'code' => 302, 'headers' => [ 'location' => 'http://evil.example.com/index.json' ] ],
				'evil'       => 'evil body',
			]
		);

		$result = $this->downloader->fetch( 'https://fonts.gravitypdf.com/v1/index.json', [ 'allow_redirect' => true ] );

		$this->assertWPError( $result );
		$this->assertSame( 'font_insecure_url', $result->get_error_code() );
		$this->assertCount( 1, $this->requested_urls(), 'the redirect target must not have been fetched' );
	}

	public function test_the_user_agent_names_the_four_versions() {
		global $wp_version;

		$agent = $this->downloader->get_user_agent();

		$this->assertStringContainsString( 'Gravity PDF/' . PDF_EXTENDED_VERSION, $agent );
		$this->assertStringContainsString( 'WordPress/' . $wp_version, $agent );
		$this->assertStringContainsString( 'Gravity Forms/', $agent );
		$this->assertStringContainsString( home_url(), $agent );
	}

	public function test_a_built_in_request_carries_no_query_args() {
		$this->mock_http( [ 'fonts.gravitypdf.com' => 'ok' ] );

		$this->downloader->fetch( 'https://fonts.gravitypdf.com/v1/index.json' );

		/* A licence key must never reach the fonts host or become part of its CDN cache key */
		$this->assertSame( 'https://fonts.gravitypdf.com/v1/index.json', $this->requested_urls()[0] );
	}

	public function test_a_third_party_records_request_args_are_appended() {
		$this->mock_http( [ 'fonts.example.com' => 'ok' ] );

		$this->downloader->fetch( 'https://fonts.example.com/v1/index.json', [ 'request_args' => [ 'token' => 'abc' ] ] );

		$this->assertStringContainsString( 'token=abc', $this->requested_urls()[0] );
	}
}
