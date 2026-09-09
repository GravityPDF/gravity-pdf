<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GPDFAPI;

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
 * Class FlushCache
 *
 * @package GFPDF\Fonts
 *
 * @since 6.0
 */
class FlushCache {

	/**
	 * Deletes the mPDF tmp directory
	 *
	 * @since 6.0
	 */
	public static function flush(): void {
		$misc = GPDFAPI::get_misc_class();
		$data = GPDFAPI::get_data_class();
		$misc->cleanup_dir( $data->mpdf_tmp_location );
	}

	/**
	 * Drop the metrics cache of one font key and its three styled faces
	 *
	 * mPDF regenerates a font's cache when the file's size or `useOTL` flag differs from the cached entry, which
	 * covers an ordinary install or update. It cannot see a **same-size swap** — a variants change putting a
	 * different 400-weight file behind Regular — so that one case has to say so explicitly.
	 *
	 * Four exact prefixes rather than one `<key>*` glob: `lato*` would take `latolight`'s cache with it, and
	 * `flush()`'s whole-directory sweep forces every other font to re-parse (seconds, on a 17 MB CJK face).
	 *
	 * @since 7.0
	 */
	public static function flush_font( string $font_key ): void {
		$misc = GPDFAPI::get_misc_class();

		foreach ( [ '', 'B', 'I', 'BI' ] as $suffix ) {
			foreach ( glob( static::get_font_cache_dir() . $font_key . $suffix . '.*' ) ?: [] as $file ) {
				$misc->unlink( $file );
			}
		}
	}

	/**
	 * Where mPDF keeps its parsed font metrics
	 *
	 * mPDF puts its cache under `{tempDir}/mpdf`, not at `tempDir` itself (`ServiceFactory::getServices()`), so the
	 * two `mpdf` segments are not a typo — `mpdf_tmp_location` is ours and the second is mPDF's own.
	 *
	 * @since 7.0
	 */
	public static function get_font_cache_dir(): string {
		return trailingslashit( GPDFAPI::get_data_class()->mpdf_tmp_location ) . 'mpdf/ttfontdata/';
	}
}
