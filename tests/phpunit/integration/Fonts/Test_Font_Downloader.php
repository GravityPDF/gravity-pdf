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

		$this->downloader = new Font_Downloader( GPDFAPI::get_log_class(), GPDFAPI::get_data_class() );
	}

	public function tear_down(): void {
		$this->unmock_http();

		foreach ( glob( $this->downloader->get_tmp_dir() . '*.part' ) ?: [] as $part ) {
			unlink( $part );
		}

		parent::tear_down();
	}

	/**
	 * Every `.part` currently in the fonts temp directory
	 *
	 * The unlink-on-failure guarantee is only observable from outside, so most of the streamed cases below assert
	 * on this rather than on the return value alone.
	 *
	 * @return string[]
	 */
	protected function parts(): array {
		return glob( $this->downloader->get_tmp_dir() . '*.part' ) ?: [];
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
	 * The concurrent batch bypasses `WP_Http::request()`, so every rule that class would have applied has to be
	 * applied by hand — and each of these refuses before a single handle is opened
	 */
	public function test_the_batch_refuses_a_non_https_url_before_opening_anything() {
		$results = $this->downloader->download_multiple(
			[
				'one' => [
					'url'  => 'http://fonts.example.com/v1/fonts/A.ttf',
					'size' => 10,
					'name' => 'A.ttf',
				],
			]
		);

		$this->assertWPError( $results['one'] );
		$this->assertSame( 'font_insecure_url', $results['one']->get_error_code() );
		$this->assertSame( [], $this->parts() );
	}

	/**
	 * A signed root could name a loopback or private address; `wp_safe_remote_get()` would have refused it and so
	 * does this. `WP_HTTP_BLOCK_EXTERNAL` and `WP_ACCESSIBLE_HOSTS` sit on the same arm, and are constants a test
	 * in this process cannot set.
	 */
	public function test_the_batch_refuses_a_url_wordpress_itself_would_not_request() {
		$results = $this->downloader->download_multiple(
			[
				'one' => [
					'url'  => 'https://127.0.0.1/v1/fonts/A.ttf',
					'size' => 10,
					'name' => 'A.ttf',
				],
			]
		);

		$this->assertWPError( $results['one'] );
		$this->assertSame( 'font_invalid_url', $results['one']->get_error_code() );
		$this->assertSame( [], $this->parts() );
	}

	/**
	 * A submitter is waiting on this one, so the filter a host raises for background patience must not reach it
	 */
	public function test_the_batch_does_not_take_the_background_download_timeout() {
		add_filter( 'gfpdf_font_download_timeout', static function () {
			return 300;
		} );

		$this->assertSame( 300, $this->downloader->get_file_timeout() );
		$this->assertSame( 10, Font_Downloader::INLINE_TIMEOUT );

		remove_all_filters( 'gfpdf_font_download_timeout' );
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

	public function test_a_streamed_download_lands_in_a_part_file() {
		$body = str_repeat( 'A', 2048 );

		$this->mock_http( [ 'fonts.gravitypdf.com' => $body ] );

		$part = $this->downloader->download(
			'https://fonts.gravitypdf.com/v1/files/fonts-v1.0.0/A.ttf',
			[
				'sha256' => hash( 'sha256', $body ),
				'size'   => strlen( $body ),
				'name'   => 'A.ttf',
			]
		);

		$this->assertIsString( $part );
		$this->assertStringEndsWith( '.part', $part );
		$this->assertSame( $body, file_get_contents( $part ) );

		/* The caller renames it into place, so the downloader must not have written the destination itself */
		$this->assertStringContainsString( '/.tmp/', $part );
	}

	/**
	 * @dataProvider provider_failed_downloads
	 */
	public function test_a_failed_download_leaves_no_part_behind( $response, string $expected, array $overrides = [] ) {
		$this->mock_http( [ 'fonts.gravitypdf.com' => $response ] );

		$result = $this->downloader->download(
			'https://fonts.gravitypdf.com/v1/files/fonts-v1.0.0/A.ttf',
			array_merge(
				[
					'sha256' => hash( 'sha256', 'the right bytes' ),
					'size'   => 15,
					'name'   => 'A.ttf',
				],
				$overrides
			)
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $expected, $result->get_error_code() );

		/* The whole point of the per-attempt name: a failure cannot leave a half-file for the next attempt to find */
		$this->assertSame( [], $this->parts() );
	}

	public function provider_failed_downloads(): array {
		return [
			'a hash mismatch'   => [ 'the wrong bytes', 'font_hash_mismatch' ],
			'a size mismatch'   => [ 'short', 'font_size_mismatch' ],
			'an HTTP error'     => [ [ 'body' => '', 'code' => 500 ], 'font_http_error' ],
			/* Passed through rather than wrapped, so the transport's own message reaches the log and the error ring */
			'a transport error' => [ new WP_Error( 'http_request_failed', 'Operation timed out' ), 'http_request_failed' ],
		];
	}

	public function test_a_declared_size_over_the_file_ceiling_is_refused_before_any_request() {
		$this->mock_http( [ 'fonts.gravitypdf.com' => 'never reached' ] );

		$result = $this->downloader->download(
			'https://fonts.gravitypdf.com/v1/files/huge.ttf',
			[ 'size' => Font_Downloader::MAX_FILE_BYTES + 1 ]
		);

		$this->assertSame( 'font_too_large', $result->get_error_code() );
		$this->assertSame( [], $this->requested_urls(), 'an over-cap file must cost no bytes at all' );
		$this->assertSame( [], $this->parts() );
	}

	public function test_a_non_https_download_is_refused_without_a_request() {
		$this->mock_http( [ 'fonts.gravitypdf.com' => 'ok' ] );

		$result = $this->downloader->download( 'http://fonts.gravitypdf.com/v1/files/A.ttf', [ 'size' => 10 ] );

		$this->assertSame( 'font_insecure_url', $result->get_error_code() );
		$this->assertSame( [], $this->requested_urls() );
	}

	public function test_two_attempts_at_one_file_never_share_a_target() {
		$body = str_repeat( 'A', 64 );

		$this->mock_http( [ 'fonts.gravitypdf.com' => $body ] );

		$expected = [
			'sha256' => hash( 'sha256', $body ),
			'size'   => strlen( $body ),
			'name'   => 'A.ttf',
		];

		$first  = $this->downloader->download( 'https://fonts.gravitypdf.com/v1/files/A.ttf', $expected );
		$second = $this->downloader->download( 'https://fonts.gravitypdf.com/v1/files/A.ttf', $expected );

		/* Concurrent attempts interleaving bytes in a shared target is what makes a truncated font look like a
		   hash mismatch, so the names must differ even for the same file */
		$this->assertNotSame( $first, $second );
		$this->assertCount( 2, $this->parts() );
	}

	public function test_a_font_fetch_is_streamed_and_uncompressed() {
		$body = str_repeat( 'A', 32 );

		$this->mock_http( [ 'fonts.gravitypdf.com' => $body ] );

		$this->downloader->download(
			'https://fonts.gravitypdf.com/v1/files/A.ttf',
			[
				'sha256' => hash( 'sha256', $body ),
				'size'   => strlen( $body ),
			]
		);

		$args = $this->request_args_for( 'A.ttf' );

		$this->assertTrue( $args['stream'] );
		$this->assertTrue( $args['sslverify'] );
		$this->assertSame( 0, $args['redirection'] );
		$this->assertSame( Font_Downloader::MAX_FILE_BYTES, $args['limit_response_size'] );

		/* A TTF does not compress, and nothing decoding on the way in keeps hash-of-bytes trivial */
		$this->assertSame( 'identity', $args['headers']['Accept-Encoding'] );
	}

	public function test_the_background_timeout_is_filterable() {
		$this->assertSame( Font_Downloader::FILE_TIMEOUT, $this->downloader->get_file_timeout() );

		add_filter( 'gfpdf_font_download_timeout', fn() => 90 );

		$this->assertSame( 90, $this->downloader->get_file_timeout() );

		/* A host answering with nonsense must not turn into an instant-timeout loop */
		add_filter( 'gfpdf_font_download_timeout', fn() => 0, 20 );

		$this->assertSame( 1, $this->downloader->get_file_timeout() );

		remove_all_filters( 'gfpdf_font_download_timeout' );
	}

	/**
	 * A distinct code rather than a fake hash mismatch: a run of these is a host problem the admin can act on, and
	 * the health check keys on it
	 */
	public function test_a_full_disk_is_refused_before_any_request() {
		$this->mock_http( [ 'fonts.gravitypdf.com' => 'never reached' ] );

		$downloader = new class( GPDFAPI::get_log_class(), GPDFAPI::get_data_class() ) extends Font_Downloader {
			protected function free_space(): ?float {
				return 1024.0;
			}
		};

		$result = $downloader->download( 'https://fonts.gravitypdf.com/v1/files/A.ttf', [ 'size' => 2048 ] );

		$this->assertSame( 'font_disk_full', $result->get_error_code() );
		$this->assertSame( [], $this->requested_urls() );
	}

	public function test_a_host_that_will_not_report_free_space_proceeds() {
		$body = str_repeat( 'A', 16 );

		$this->mock_http( [ 'fonts.gravitypdf.com' => $body ] );

		$downloader = new class( GPDFAPI::get_log_class(), GPDFAPI::get_data_class() ) extends Font_Downloader {
			protected function free_space(): ?float {
				return null;
			}
		};

		$part = $downloader->download(
			'https://fonts.gravitypdf.com/v1/files/A.ttf',
			[
				'sha256' => hash( 'sha256', $body ),
				'size'   => strlen( $body ),
			]
		);

		$this->assertIsString( $part, 'open_basedir hiding disk_free_space() must not block every install' );

		unlink( $part );
	}
}
