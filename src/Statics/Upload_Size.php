<?php

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
 * The size PHP itself will accept an upload up to
 *
 * wp_max_upload_size() isn't it: that also applies the multisite media upload limit and the `upload_size_limit` filter.
 *
 * @since 6.18.0
 */
class Upload_Size {

	/**
	 * @return int Bytes, or 0 for no limit
	 *
	 * @since 6.18.0
	 */
	public static function get_php_limit(): int {
		return static::calculate_php_limit( ini_get( 'upload_max_filesize' ), ini_get( 'post_max_size' ) );
	}

	/**
	 * The lower of the two ini limits, where 0 (or less) means no limit
	 *
	 * @param string|false $upload_max_filesize The `upload_max_filesize` ini value, e.g. "2M"
	 * @param string|false $post_max_size       The `post_max_size` ini value
	 *
	 * @return int Bytes, or 0 for no limit
	 *
	 * @since 6.18.0
	 */
	public static function calculate_php_limit( $upload_max_filesize, $post_max_size ): int {
		$limits = array_filter(
			[
				wp_convert_hr_to_bytes( (string) $upload_max_filesize ),
				wp_convert_hr_to_bytes( (string) $post_max_size ),
			],
			static function ( $bytes ) {
				return $bytes > 0;
			}
		);

		return $limits ? min( $limits ) : 0;
	}
}
