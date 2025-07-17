<?php

namespace GFPDF\Helper\Mpdf;

use GFPDF_Vendor\Mpdf\Cache as MpdfCache;

/**
 * @since 6.13.0
 */
class Cache extends MpdfCache {

	/**
	 * Override to ensure folder permissions are being set based on the parent directory
	 *
	 * @param $basePath
	 *
	 * @return bool
	 *
	 * @since 6.13.0
	 */
	protected function createDirectory( $basePath ) {
		if ( ! wp_mkdir_p( $basePath ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Do nothing here. Let Gravity PDF's cron cleanup handle old temp files.
	 *
	 * @return void
	 *
	 * @since 6.13.0
	 */
	public function clearOld() {}
}
