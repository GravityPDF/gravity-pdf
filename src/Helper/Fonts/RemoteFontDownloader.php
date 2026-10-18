<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF\Exceptions\GravityPdfFontDownloadException;

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
 * Downloads a remote TTF font to a temporary file on disk so it can be handed to the Custom Fonts controller
 *
 * The caller owns the temporary files and must call self::cleanup() once finished with them.
 *
 * @since 6.17
 */
class RemoteFontDownloader {

	/**
	 * The maximum size, in bytes, a remote font file is allowed to be. Generous enough for the largest CJK fonts.
	 *
	 * @since 6.17
	 */
	public const MAX_FILE_SIZE = 33554432; /* 32MB */

	/**
	 * @var string[] The temporary files created by this instance
	 * @since 6.17
	 */
	protected $temp_files = [];

	/**
	 * Whether the value looks like a URL (as opposed to a path on the local filesystem)
	 *
	 * Any value with a scheme is treated as a URL so unsupported schemes (ftp://, file://, php://)
	 * are rejected by self::download() instead of falling through to the local filesystem handling.
	 *
	 * @since 6.17
	 */
	public static function is_url( string $value ): bool {
		return (bool) preg_match( '#^[a-z][a-z0-9+.\-]*://#i', $value );
	}

	/**
	 * Download a remote TTF font to a temporary file
	 *
	 * @return array A synthetic $_FILES entry for the downloaded font
	 *
	 * @throws GravityPdfFontDownloadException
	 *
	 * @since 6.17
	 */
	public function download( string $url ): array {
		$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
		if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
			throw new GravityPdfFontDownloadException( esc_html__( 'Only http:// and https:// font URLs are supported.', 'gravity-pdf' ) );
		}

		/* Blocks credentials in the URL, non-standard ports, and hosts that resolve to a private/loopback IP */
		if ( wp_http_validate_url( $url ) === false ) {
			throw new GravityPdfFontDownloadException( esc_html__( 'The font URL is not valid, or resolves to a restricted address.', 'gravity-pdf' ) );
		}

		$filename = $this->get_filename_from_url( $url );

		if ( ! function_exists( 'wp_tempnam' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$tmp_name           = wp_tempnam( $filename );
		$this->temp_files[] = $tmp_name;

		$response = wp_remote_get(
			$url,
			[
				'timeout'             => 30,
				/* Each redirect target is revalidated against reject_unsafe_urls by WP_Http */
				'redirection'         => 3,
				'reject_unsafe_urls'  => true,
				'limit_response_size' => static::MAX_FILE_SIZE + 1,
				'stream'              => true,
				'filename'            => $tmp_name,
			]
		);

		if ( is_wp_error( $response ) ) {
			/* translators: %s: the HTTP error message */
			throw new GravityPdfFontDownloadException( sprintf( esc_html__( 'Could not download the font: %s', 'gravity-pdf' ), esc_html( $response->get_error_message() ) ) );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( $status !== 200 ) {
			/* translators: %d: the HTTP status code returned by the remote server */
			throw new GravityPdfFontDownloadException( sprintf( esc_html__( 'Could not download the font. The server responded with a %d status code.', 'gravity-pdf' ), esc_html( (string) $status ) ) );
		}

		clearstatcache( true, $tmp_name );
		$size = (int) @filesize( $tmp_name ); /* phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged */

		if ( $size === 0 ) {
			throw new GravityPdfFontDownloadException( esc_html__( 'The downloaded font file is empty.', 'gravity-pdf' ) );
		}

		/* The response is capped at MAX_FILE_SIZE + 1 bytes, so anything larger is either oversized or truncated */
		if ( $size > static::MAX_FILE_SIZE ) {
			throw new GravityPdfFontDownloadException(
				sprintf(
					/* translators: %s: the maximum font file size e.g. 32 MB */
					esc_html__( 'The font file exceeds the maximum size of %s.', 'gravity-pdf' ),
					esc_html( (string) size_format( static::MAX_FILE_SIZE ) )
				)
			);
		}

		return [
			'name'     => $filename,
			'tmp_name' => $tmp_name,
			'error'    => UPLOAD_ERR_OK,
		];
	}

	/**
	 * Delete any temporary files created by this instance
	 *
	 * @since 6.17
	 */
	public function cleanup(): void {
		foreach ( $this->temp_files as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}

		$this->temp_files = [];
	}

	/**
	 * Derive a safe .ttf filename from the URL path
	 *
	 * @throws GravityPdfFontDownloadException
	 *
	 * @since 6.17
	 */
	protected function get_filename_from_url( string $url ): string {
		$path = rawurldecode( (string) wp_parse_url( $url, PHP_URL_PATH ) );

		/* basename() before sanitizing so any traversal segments in the decoded path are discarded */
		$filename = sanitize_file_name( basename( $path ) );

		if ( strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) ) !== 'ttf' ) {
			throw new GravityPdfFontDownloadException( esc_html__( 'The font URL must point to a .ttf file.', 'gravity-pdf' ) );
		}

		return $filename;
	}
}
