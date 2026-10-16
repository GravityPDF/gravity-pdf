<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF\Statics\Cache;
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
	 * Empties mPDF's cache and recreates its folders, and stops serving PDFs cached with the old fonts
	 *
	 * @since 6.0
	 * @since 6.17.3 Empties mPDF's cache folder rather than its tempDir, and recreates the folders
	 * @since 7.0 Bumps the PDF cache generation
	 */
	public static function flush(): void {
		$misc = GPDFAPI::get_misc_class();
		$data = GPDFAPI::get_data_class();
		$misc->cleanup_dir( $data->mpdf_tmp_location . '/mpdf' );
		$misc->create_mpdf_cache_folders();

		Cache::bump_generation();
	}
}
