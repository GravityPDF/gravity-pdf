<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF\Exceptions\GravityPdfFontDownloadException;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fonts
 */
class Test_RemoteFontDownloader extends TestCase {

	/**
	 * A public IP literal. Using an IP (instead of a hostname) keeps wp_http_validate_url() from
	 * making a DNS lookup, so these tests never touch the network.
	 */
	private const REMOTE_URL = 'http://93.184.216.34/fonts/Chewy.ttf';

	/** @var array The args wp_remote_get() was called with */
	private array $request_args = [];

	private RemoteFontDownloader $downloader;

	public function set_up(): void {
		parent::set_up();

		$this->downloader   = new RemoteFontDownloader();
		$this->request_args = [];
	}

	public function tear_down(): void {
		$this->downloader->cleanup();

		parent::tear_down();
	}

	/**
	 * Short-circuit wp_remote_get() and write $body to the stream target, mimicking a real download
	 *
	 * @param callable|string $body String written to the stream target, or a callable given the target path
	 */
	private function mock_http( $body = '', int $status = 200, ?\WP_Error $error = null ): void {
		add_filter(
			'pre_http_request',
			function ( $preempt, $args ) use ( $body, $status, $error ) {
				$this->request_args = $args;

				if ( $error !== null ) {
					return $error;
				}

				if ( ! empty( $args['filename'] ) ) {
					if ( is_callable( $body ) ) {
						$body( $args['filename'] );
					} else {
						file_put_contents( $args['filename'], $body );
					}
				}

				return [
					'headers'  => [],
					'body'     => '',
					'response' => [
						'code'    => $status,
						'message' => get_status_header_desc( $status ),
					],
					'cookies'  => [],
					'filename' => $args['filename'] ?? null,
				];
			},
			10,
			2
		);
	}

	private function chewy(): string {
		return (string) file_get_contents( PDF_PLUGIN_DIR . '/tools/phpunit/data/fonts/Chewy.ttf' );
	}

	/**
	 * @dataProvider provider_is_url
	 */
	public function test_is_url( bool $expected, string $value ): void {
		$this->assertSame( $expected, RemoteFontDownloader::is_url( $value ) );
	}

	public function provider_is_url(): array {
		return [
			'http'            => [ true, 'http://example.com/font.ttf' ],
			'https'           => [ true, 'https://example.com/font.ttf' ],
			'uppercase'       => [ true, 'HTTPS://example.com/font.ttf' ],
			'ftp'             => [ true, 'ftp://example.com/font.ttf' ],
			'file'            => [ true, 'file:///etc/passwd' ],
			'php stream'      => [ true, 'php://filter/resource=/etc/passwd' ],
			'absolute path'   => [ false, '/var/www/fonts/font.ttf' ],
			'windows path'    => [ false, 'C:\\fonts\\font.ttf' ],
			'relative path'   => [ false, 'fonts/font.ttf' ],
			'protocol-less'   => [ false, '//example.com/font.ttf' ],
			'empty'           => [ false, '' ],
			'name only'       => [ false, 'font.ttf' ],
		];
	}

	/**
	 * @dataProvider provider_rejected_urls
	 */
	public function test_download_rejects_url( string $url, string $expected_message ): void {
		$this->mock_http();

		$this->expectException( GravityPdfFontDownloadException::class );
		$this->expectExceptionMessage( $expected_message );

		$this->downloader->download( $url );
	}

	public function provider_rejected_urls(): array {
		return [
			'ftp scheme'      => [ 'ftp://93.184.216.34/font.ttf', 'Only http:// and https:// font URLs are supported.' ],
			'file scheme'     => [ 'file:///etc/passwd', 'Only http:// and https:// font URLs are supported.' ],
			'php stream'      => [ 'php://filter/resource=/etc/passwd', 'Only http:// and https:// font URLs are supported.' ],
			'no scheme'       => [ '/var/www/fonts/font.ttf', 'Only http:// and https:// font URLs are supported.' ],
			'loopback'        => [ 'http://127.0.0.1/font.ttf', 'resolves to a restricted address' ],
			'private class a' => [ 'http://10.0.0.5/font.ttf', 'resolves to a restricted address' ],
			'private class b' => [ 'http://172.16.4.1/font.ttf', 'resolves to a restricted address' ],
			'private class c' => [ 'http://192.168.1.1/font.ttf', 'resolves to a restricted address' ],
			'link local'      => [ 'http://0.0.0.0/font.ttf', 'resolves to a restricted address' ],
			'blocked port'    => [ 'http://93.184.216.34:22/font.ttf', 'resolves to a restricted address' ],
			'credentials'     => [ 'http://user:pass@93.184.216.34/font.ttf', 'resolves to a restricted address' ],
			'not a ttf'       => [ 'http://93.184.216.34/font.otf', 'The font URL must point to a .ttf file.' ],
			'no extension'    => [ 'http://93.184.216.34/font', 'The font URL must point to a .ttf file.' ],
			'no path'         => [ 'http://93.184.216.34', 'The font URL must point to a .ttf file.' ],
		];
	}

	/**
	 * A rejected URL must never reach the HTTP layer
	 */
	public function test_download_does_not_request_rejected_url(): void {
		$this->mock_http();

		try {
			$this->downloader->download( 'http://127.0.0.1/font.ttf' );
		} catch ( GravityPdfFontDownloadException $e ) {
			$this->assertSame( [], $this->request_args );

			return;
		}

		$this->fail( 'Expected a GravityPdfFontDownloadException.' );
	}

	public function test_download_returns_file_details(): void {
		$font = $this->chewy();
		$this->mock_http( $font );

		$file = $this->downloader->download( self::REMOTE_URL );

		$this->assertSame( 'Chewy.ttf', $file['name'] );
		$this->assertSame( UPLOAD_ERR_OK, $file['error'] );
		$this->assertFileExists( $file['tmp_name'] );
		$this->assertSame( $font, file_get_contents( $file['tmp_name'] ) );
	}

	public function test_download_uses_hardened_request_args(): void {
		$this->mock_http( $this->chewy() );
		$this->downloader->download( self::REMOTE_URL );

		$this->assertTrue( $this->request_args['reject_unsafe_urls'] );
		$this->assertTrue( $this->request_args['stream'] );
		$this->assertSame( RemoteFontDownloader::MAX_FILE_SIZE + 1, $this->request_args['limit_response_size'] );
		$this->assertSame( 3, $this->request_args['redirection'] );
		$this->assertSame( 30, $this->request_args['timeout'] );
		$this->assertNotEmpty( $this->request_args['filename'] );
	}

	/**
	 * The font is streamed to a temp file, not into the custom font directory
	 */
	public function test_download_streams_to_temp_directory(): void {
		$this->mock_http( $this->chewy() );

		$file = $this->downloader->download( self::REMOTE_URL );

		$this->assertStringStartsWith( get_temp_dir(), $file['tmp_name'] );
		$this->assertFileDoesNotExist( $this->gfpdf()->data->template_font_location . 'Chewy.ttf' );
	}

	/**
	 * @dataProvider provider_filenames
	 */
	public function test_download_derives_filename_from_url( string $expected, string $url ): void {
		$this->mock_http( $this->chewy() );

		$file = $this->downloader->download( $url );

		$this->assertSame( $expected, $file['name'] );
	}

	public function provider_filenames(): array {
		return [
			'plain'            => [ 'Chewy.ttf', 'http://93.184.216.34/Chewy.ttf' ],
			'nested path'      => [ 'Chewy.ttf', 'http://93.184.216.34/a/b/c/Chewy.ttf' ],
			'query string'     => [ 'Chewy.ttf', 'http://93.184.216.34/Chewy.ttf?v=2&x=1' ],
			'fragment'         => [ 'Chewy.ttf', 'http://93.184.216.34/Chewy.ttf#frag' ],
			'encoded space'    => [ 'My-Font.ttf', 'http://93.184.216.34/My%20Font.ttf' ],
			'encoded slash'    => [ 'Chewy.ttf', 'http://93.184.216.34/fonts%2FChewy.ttf' ],
			'traversal'        => [ 'Chewy.ttf', 'http://93.184.216.34/a/../../Chewy.ttf' ],
			'uppercase ext'    => [ 'Chewy.TTF', 'http://93.184.216.34/Chewy.TTF' ],
		];
	}

	public function test_download_throws_on_http_error(): void {
		$this->mock_http( '', 200, new \WP_Error( 'http_request_failed', 'Connection timed out' ) );

		$this->expectException( GravityPdfFontDownloadException::class );
		$this->expectExceptionMessage( 'Connection timed out' );

		$this->downloader->download( self::REMOTE_URL );
	}

	/**
	 * @dataProvider provider_error_status_codes
	 */
	public function test_download_throws_on_non_200_response( int $status ): void {
		$this->mock_http( $this->chewy(), $status );

		$this->expectException( GravityPdfFontDownloadException::class );
		$this->expectExceptionMessage( sprintf( 'The server responded with a %d status code.', $status ) );

		$this->downloader->download( self::REMOTE_URL );
	}

	public function provider_error_status_codes(): array {
		return [ [ 301 ], [ 401 ], [ 403 ], [ 404 ], [ 500 ] ];
	}

	public function test_download_throws_on_empty_response(): void {
		$this->mock_http( '' );

		$this->expectException( GravityPdfFontDownloadException::class );
		$this->expectExceptionMessage( 'The downloaded font file is empty.' );

		$this->downloader->download( self::REMOTE_URL );
	}

	/**
	 * Write a sparse file of $bytes — the size the transport truncates an oversized response to
	 */
	private function mock_http_body_of_size( int $bytes ): void {
		$this->mock_http(
			function ( $path ) use ( $bytes ) {
				$fh = fopen( $path, 'w' );
				fseek( $fh, $bytes - 1 );
				fwrite( $fh, 'a' );
				fclose( $fh );
			}
		);
	}

	public function test_download_throws_when_response_exceeds_max_size(): void {
		$this->mock_http_body_of_size( RemoteFontDownloader::MAX_FILE_SIZE + 1 );

		$this->expectException( GravityPdfFontDownloadException::class );
		$this->expectExceptionMessage( 'exceeds the maximum size' );

		$this->downloader->download( self::REMOTE_URL );
	}

	public function test_download_accepts_response_at_max_size(): void {
		$this->mock_http_body_of_size( RemoteFontDownloader::MAX_FILE_SIZE );

		$file = $this->downloader->download( self::REMOTE_URL );

		$this->assertSame( RemoteFontDownloader::MAX_FILE_SIZE, filesize( $file['tmp_name'] ) );
	}

	public function test_cleanup_deletes_temp_files(): void {
		$this->mock_http( $this->chewy() );

		$first  = $this->downloader->download( self::REMOTE_URL );
		$second = $this->downloader->download( self::REMOTE_URL );

		$this->assertNotSame( $first['tmp_name'], $second['tmp_name'] );

		$this->downloader->cleanup();

		$this->assertFileDoesNotExist( $first['tmp_name'] );
		$this->assertFileDoesNotExist( $second['tmp_name'] );
	}

	/**
	 * A failed download must not leave the placeholder temp file behind
	 */
	public function test_cleanup_deletes_temp_file_after_failed_download(): void {
		$this->mock_http( '', 404 );

		try {
			$this->downloader->download( self::REMOTE_URL );
			$this->fail( 'Expected a GravityPdfFontDownloadException.' );
		} catch ( GravityPdfFontDownloadException $e ) {
			$temp_files = glob( get_temp_dir() . 'Chewy-*.tmp' );

			$this->downloader->cleanup();

			$this->assertNotEmpty( $temp_files );
			foreach ( $temp_files as $file ) {
				$this->assertFileDoesNotExist( $file );
			}
		}
	}

	public function test_cleanup_is_idempotent(): void {
		$this->mock_http( $this->chewy() );
		$file = $this->downloader->download( self::REMOTE_URL );

		$this->downloader->cleanup();
		$this->downloader->cleanup();

		$this->assertFileDoesNotExist( $file['tmp_name'] );
	}
}
