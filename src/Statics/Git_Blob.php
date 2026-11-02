<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

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
 * Git blob SHA-1s, as the font manifests record them
 *
 * @since 6.19.0
 */
class Git_Blob {

	/**
	 * The hash `git hash-object` computes for a file of a known size
	 *
	 * @since 6.19.0
	 */
	public static function sha1( string $path, int $size ): string {
		$hash = hash_init( 'sha1' );
		hash_update( $hash, 'blob ' . $size . "\0" );

		/* An unreadable file has no hash, so it matches nothing */
		if ( ! is_file( $path ) || ! is_readable( $path ) || ! hash_update_file( $hash, $path ) ) {
			return '';
		}

		return hash_final( $hash );
	}

	/**
	 * Whether a file has the expected size and git blob SHA-1
	 *
	 * @since 6.19.0
	 */
	public static function matches( string $path, int $size, string $sha ): bool {
		clearstatcache( true, $path );

		if ( ! is_file( $path ) || filesize( $path ) !== $size ) {
			return false;
		}

		$hash = self::sha1( $path, $size );

		return $hash !== '' && hash_equals( $sha, $hash );
	}
}
