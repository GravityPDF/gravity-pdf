<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

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
 * @package GFPDF\Helper\Fonts
 *
 * @since 6.0
 */
class FlushCache {

	/**
	 * Deletes mPDF's font data, which goes stale when a font changes
	 *
	 * @param string $font_id Only delete this font's data. Leave empty to delete every font's
	 *
	 * @since 6.0
	 * @since 6.17.3 Deletes only font data, leaving PDFs being generated their temp files, and accepts a font ID
	 */
	public static function flush( string $font_id = '' ): void {
		$misc      = GPDFAPI::get_misc_class();
		$font_data = GPDFAPI::get_data_class()->mpdf_tmp_location . '/mpdf/ttfontdata';

		$misc->create_mpdf_cache_folders();

		if ( $font_id === '' ) {
			$misc->cleanup_dir( $font_data );

			return;
		}

		/* mPDF names a font's files after its ID plus the style: roboto.mtx.json, robotoB.cw.dat, robotoBI.gid.dat */
		$pattern = '/^' . preg_quote( $font_id, '/' ) . '(B|I|BI)?\./';

		foreach ( scandir( $font_data ) ?: [] as $file ) {
			if ( preg_match( $pattern, $file ) ) {
				wp_delete_file( $font_data . '/' . $file );
			}
		}
	}
}
