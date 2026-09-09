<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Helper\Helper_Data;
use GFPDF_Vendor\Psr\Log\LoggerInterface;
use WP_Error;

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
 * Every outbound request the font system makes
 *
 * `fetch()` pulls a metadata document into memory — the root index, its signature, a source index, an entry file.
 * `download()` streams a font file to disk under the same rules, because a 17 MB TTF read into a string is a
 * memory limit waiting to be hit on a shared host.
 *
 * The rules that are not negotiable: `https` only, `sslverify` hard-coded true (never
 * `EDD_SL_Plugin_Updater::verify_ssl()`, whose filter exists to let cheap hosts turn verification off), and a byte
 * ceiling applied *before* the request as well as after — a declared size over the cap is what stops a signed root
 * from announcing a 500 MB index.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Font_Downloader {

	/**
	 * A DROP-firewalled host has to fail the inline upgrade sync fast, so the connect timeout is well under the
	 * overall one
	 *
	 * @since 7.0
	 */
	public const CONNECT_TIMEOUT = 5;

	/**
	 * @since 7.0
	 */
	public const METADATA_TIMEOUT = 15;

	/**
	 * Hard ceilings, deliberately constants rather than filters: a host that can raise them can be told to accept
	 * a larger asset than the pipeline will ever publish
	 *
	 * @since 7.0
	 */
	public const MAX_ROOT_BYTES = 65536;

	/**
	 * @since 7.0
	 */
	public const MAX_SIGNATURE_BYTES = 1024;

	/**
	 * @since 7.0
	 */
	public const MAX_METADATA_BYTES = 2097152;

	/**
	 * Sun-ExtB, the largest asset after the spike-5 swaps, is 17,632,200 bytes. Anything the pipeline cannot get
	 * under this must be split or subset there rather than have the ceiling raised here
	 *
	 * @since 7.0
	 */
	public const MAX_FILE_BYTES = 26214400;

	/**
	 * @since 7.0
	 */
	public const FILE_TIMEOUT = 45;

	/**
	 * Room left over the file's own size, so an install cannot be the thing that fills a disk
	 *
	 * @since 7.0
	 */
	public const DISK_HEADROOM = 52428800;

	/**
	 * @var LoggerInterface
	 * @since 7.0
	 */
	protected $log;

	/**
	 * Read for `template_font_location` at download time, not construction: this class is built during bootstrap
	 * and that path is not set until `Controller_Install::setup_defaults()` runs
	 *
	 * @var Helper_Data
	 * @since 7.0
	 */
	protected $data;

	public function __construct( LoggerInterface $log, Helper_Data $data ) {
		$this->log  = $log;
		$this->data = $data;
	}

	/**
	 * Fetch one metadata document into memory
	 *
	 * @param array $expected `sha256` to verify against, `size` the body must match exactly, `max_bytes` ceiling,
	 *                        `allow_redirect` for the root alone, `request_args` a third-party record's query args
	 *
	 * @return string|WP_Error The body
	 *
	 * @since 7.0
	 */
	public function fetch( string $url, array $expected = [] ) {
		$max_bytes = (int) ( $expected['max_bytes'] ?? static::MAX_METADATA_BYTES );

		$error = $this->check_url( $url );
		if ( $error !== null ) {
			return $error;
		}

		/* Refused before a byte is requested, which is what closes "a signed root declares a 500 MB index" */
		if ( isset( $expected['size'] ) && (int) $expected['size'] > $max_bytes ) {
			return new WP_Error(
				'font_too_large',
				sprintf( 'The file at %s declares %d bytes, over the %d byte ceiling', $url, (int) $expected['size'], $max_bytes )
			);
		}

		$request_url = $url;
		if ( count( (array) ( $expected['request_args'] ?? [] ) ) > 0 ) {
			$request_url = add_query_arg( $expected['request_args'], $url );
		}

		$response = $this->request( $request_url, $max_bytes );

		/* The root alone may follow exactly one redirect, and the target is re-checked as https */
		if ( ! is_wp_error( $response ) && ( $expected['allow_redirect'] ?? false ) && $this->is_redirect( $response ) ) {
			$location = wp_remote_retrieve_header( $response, 'location' );

			$error = $this->check_url( (string) $location );
			if ( $error !== null ) {
				return $error;
			}

			$response = $this->request( (string) $location, $max_bytes );
		}

		if ( is_wp_error( $response ) ) {
			$this->log->error(
				'Font request failed',
				[
					'url'   => $url,
					'error' => $response->get_error_message(),
				]
			);

			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			return new WP_Error( 'font_http_error', sprintf( 'The request for %s returned %d', $url, $code ) );
		}

		$body = (string) wp_remote_retrieve_body( $response );

		return $this->verify( $url, $body, $expected, $max_bytes );
	}

	/**
	 * Stream one font file to a private temp name and return that path once it verifies
	 *
	 * Never writes to its destination: the caller renames the returned `.part` into place (§4.3 Installer step 3),
	 * which is atomic because `.tmp/` sits at the fonts-dir root and is therefore the same filesystem. The name
	 * carries a `uniqid()`, so two requests fetching the same file cannot interleave bytes in a shared target — the
	 * failure mode that makes a truncated font look like a hash mismatch.
	 *
	 * **Every non-success exit unlinks its own `.part`**, exceptions included; only a process kill can orphan one,
	 * and `cleanup_tmp_dir()` sweeps those hourly.
	 *
	 * @param array $expected `sha256` and `size` from the entry file (both required — a download is bounded by what
	 *                        the index declares, which is why no sfnt parse is needed), `name` for the temp file,
	 *                        `timeout` to override the background default, `request_args` for a third-party source
	 *
	 * @return string|WP_Error Path to the verified `.part` file
	 *
	 * @since 7.0
	 */
	public function download( string $url, array $expected = [] ) {
		$error = $this->check_url( $url );
		if ( $error !== null ) {
			return $error;
		}

		$size = (int) ( $expected['size'] ?? 0 );

		/* Refused before a byte is requested, exactly as `fetch()` refuses an over-sized index */
		if ( $size > static::MAX_FILE_BYTES ) {
			return new WP_Error(
				'font_too_large',
				sprintf( 'The file at %s declares %d bytes, over the %d byte ceiling', $url, $size, static::MAX_FILE_BYTES )
			);
		}

		$error = $this->check_disk_space( $url, $size );
		if ( $error !== null ) {
			return $error;
		}

		$part = $this->part_path( (string) ( $expected['name'] ?? 'font' ) );
		if ( is_wp_error( $part ) ) {
			return $part;
		}

		$done = false;

		try {
			$response = $this->stream( $url, $part, $expected );

			if ( is_wp_error( $response ) ) {
				$this->log->error(
					'Font download failed',
					[
						'url'   => $url,
						'error' => $response->get_error_message(),
					]
				);

				return $response;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( $code !== 200 ) {
				return new WP_Error( 'font_http_error', sprintf( 'The request for %s returned %d', $url, $code ) );
			}

			$error = $this->verify_file( $url, $part, $expected );
			if ( $error !== null ) {
				return $error;
			}

			$done = true;

			return $part;
		} finally {
			if ( ! $done ) {
				$this->unlink_part( $part );
			}
		}
	}

	/**
	 * The same shape WordPress sends by default, so a third-party root learns nothing it would not from any other
	 * WordPress request
	 *
	 * Nothing reads it on our own fonts host: with no code in the request path there is no sync telemetry at all.
	 *
	 * @since 7.0
	 */
	public function get_user_agent(): string {
		global $wp_version;

		return sprintf(
			'Gravity PDF/%s (WordPress/%s; Gravity Forms/%s; %s)',
			PDF_EXTENDED_VERSION,
			$wp_version,
			class_exists( 'GFForms' ) ? \GFForms::$version : 'unknown',
			home_url()
		);
	}

	/**
	 * @return array|WP_Error
	 *
	 * @since 7.0
	 */
	protected function request( string $url, int $max_bytes ) {
		return wp_safe_remote_get(
			$url,
			[
				/* Hard-coded: this must never become a filter a host can answer with false */
				'sslverify'           => true,
				'redirection'         => 0,
				'timeout'             => static::METADATA_TIMEOUT,
				'connect_timeout'     => static::CONNECT_TIMEOUT,
				'limit_response_size' => $max_bytes,
				'user-agent'          => $this->get_user_agent(),
			]
		);
	}

	/**
	 * Size first, then hash: a 12 MB body is never read whole only to be rejected on length
	 *
	 * @return string|WP_Error
	 *
	 * @since 7.0
	 */
	protected function verify( string $url, string $body, array $expected, int $max_bytes ) {
		$length = strlen( $body );

		if ( $length > $max_bytes ) {
			return new WP_Error( 'font_too_large', sprintf( 'The file at %s is %d bytes, over the %d byte ceiling', $url, $length, $max_bytes ) );
		}

		if ( isset( $expected['size'] ) && $length !== (int) $expected['size'] ) {
			return new WP_Error( 'font_size_mismatch', sprintf( 'The file at %s is %d bytes, not the %d the index lists', $url, $length, (int) $expected['size'] ) );
		}

		if ( isset( $expected['sha256'] ) && ! hash_equals( (string) $expected['sha256'], hash( 'sha256', $body ) ) ) {
			return new WP_Error( 'font_hash_mismatch', sprintf( 'The file at %s does not match the hash the index lists', $url ) );
		}

		return $body;
	}

	/**
	 * @since 7.0
	 */
	protected function check_url( string $url ): ?WP_Error {
		if ( stripos( $url, 'https://' ) !== 0 ) {
			return new WP_Error( 'font_insecure_url', sprintf( '%s is not an https URL', $url ) );
		}

		return null;
	}

	/**
	 * @return array|WP_Error
	 *
	 * @since 7.0
	 */
	protected function stream( string $url, string $part, array $expected ) {
		$request_url = $url;
		if ( count( (array) ( $expected['request_args'] ?? [] ) ) > 0 ) {
			$request_url = add_query_arg( $expected['request_args'], $url );
		}

		return wp_safe_remote_get(
			$request_url,
			[
				/* Hard-coded: this must never become a filter a host can answer with false */
				'sslverify'           => true,
				'redirection'         => 0,
				'timeout'             => (int) ( $expected['timeout'] ?? $this->get_file_timeout() ),
				'connect_timeout'     => static::CONNECT_TIMEOUT,
				'limit_response_size' => static::MAX_FILE_BYTES,
				'stream'              => true,
				'filename'            => $part,
				'user-agent'          => $this->get_user_agent(),
				/* A TTF does not compress, and hash-of-bytes stays trivial when nothing decodes on the way in */
				'headers'             => [ 'Accept-Encoding' => 'identity' ],
			]
		);
	}

	/**
	 * The one filter over a font download's wall time, background fetches only
	 *
	 * Trigger 3's inline batch has its own 10 s constant instead: a submitter waiting on a render is not the place
	 * to honour a host's 45-second patience.
	 *
	 * @since 7.0
	 */
	public function get_file_timeout(): int {
		return max( 1, (int) apply_filters( 'gfpdf_font_download_timeout', static::FILE_TIMEOUT ) );
	}

	/**
	 * Size first, then hash, same order and same reason as the in-memory half
	 *
	 * @since 7.0
	 */
	protected function verify_file( string $url, string $part, array $expected ): ?WP_Error {
		if ( ! is_file( $part ) ) {
			return new WP_Error( 'font_download_empty', sprintf( 'The download of %s wrote no file', $url ) );
		}

		$length = (int) filesize( $part );

		if ( isset( $expected['size'] ) && $length !== (int) $expected['size'] ) {
			return new WP_Error( 'font_size_mismatch', sprintf( 'The file at %s is %d bytes, not the %d the index lists', $url, $length, (int) $expected['size'] ) );
		}

		if ( isset( $expected['sha256'] ) && ! hash_equals( (string) $expected['sha256'], (string) hash_file( 'sha256', $part ) ) ) {
			return new WP_Error( 'font_hash_mismatch', sprintf( 'The file at %s does not match the hash the index lists', $url ) );
		}

		return null;
	}

	/**
	 * A private temp name under the fonts directory, so the later `rename()` never crosses a filesystem
	 *
	 * @return string|WP_Error
	 *
	 * @since 7.0
	 */
	protected function part_path( string $name ) {
		$dir = $this->get_tmp_dir();

		if ( ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'font_tmp_unwritable', sprintf( 'The font temp directory %s could not be created', $dir ) );
		}

		$name = sanitize_file_name( $name );

		return $dir . ( $name !== '' ? $name : 'font' ) . '.' . uniqid( '', true ) . '.part';
	}

	/**
	 * @since 7.0
	 */
	public function get_tmp_dir(): string {
		return trailingslashit( $this->data->template_font_location ) . '.tmp/';
	}

	/**
	 * Refuse rather than half-write when the disk cannot hold the file plus room to work in
	 *
	 * A distinct code, not a fake hash mismatch: a run of these is a host problem the admin can act on, and
	 * `Missing_Font_Files_Check` reports it as one. A host where `disk_free_space()` is disabled proceeds.
	 *
	 * @since 7.0
	 */
	protected function check_disk_space( string $url, int $size ): ?WP_Error {
		if ( $size <= 0 ) {
			return null;
		}

		$free = $this->free_space();

		if ( $free === null || $free >= ( $size + static::DISK_HEADROOM ) ) {
			return null;
		}

		return new WP_Error(
			'font_disk_full',
			sprintf( 'There is not enough free disk space to download %s: %d bytes needed, %d free', $url, $size + static::DISK_HEADROOM, (int) $free )
		);
	}

	/**
	 * Free bytes on the fonts filesystem, or null where the host will not say
	 *
	 * Its own method so the branch above is reachable from a test: nothing else can make a real disk nearly full.
	 *
	 * @since 7.0
	 */
	protected function free_space(): ?float {
		if ( ! function_exists( 'disk_free_space' ) ) {
			return null;
		}

		/* An open_basedir restriction warns rather than returning false */
		$free = @disk_free_space( $this->data->template_font_location ); //phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		return $free === false ? null : (float) $free;
	}

	/**
	 * @since 7.0
	 */
	protected function unlink_part( string $part ): void {
		if ( is_file( $part ) ) {
			@unlink( $part ); //phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.unlink_unlink -- a temp file we own; a failure here is the hourly sweep's problem
		}
	}

	/**
	 * @since 7.0
	 */
	protected function is_redirect( array $response ): bool {
		$code = (int) wp_remote_retrieve_response_code( $response );

		return in_array( $code, [ 301, 302, 307, 308 ], true ) && wp_remote_retrieve_header( $response, 'location' ) !== '';
	}
}
