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
	 * Deletes the mPDF tmp directory, and stops serving PDFs cached with the old fonts
	 *
	 * @since 6.0
	 * @since 7.0 Bumps the PDF cache generation
	 */
	public static function flush(): void {
		$misc = GPDFAPI::get_misc_class();
		$data = GPDFAPI::get_data_class();
		$misc->cleanup_dir( $data->mpdf_tmp_location );

		Cache::bump_generation();
	}
}
