<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF_Vendor\Mpdf\Cache;
use GFPDF_Vendor\Mpdf\Fonts\FontCache;
use GFPDF_Vendor\Mpdf\MpdfException;
use GFPDF_Vendor\Mpdf\TTFontFile;
use GFPDF_Vendor\Upload\File;
use GFPDF_Vendor\Upload\Validation\Base;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TtfFontValidation extends Base {

	/**
	 * Error message
	 *
	 * @var string
	 */
	protected $message = 'Invalid ttf font file: %s';

	/**
	 * Validate
	 *
	 * @param File $file
	 *
	 * @return bool
	 */
	public function validate( File $file ) {
		try {
			$ttf = new TTFontFile( new FontCache( new Cache( get_temp_dir() . 'mpdf' ) ), null );
			$ttf->getMetrics( $file->getPathname(), $file->getName() );

			return strlen( $ttf->familyName ) > 0;
		} catch ( MpdfException $e ) {

		}

		return false;
	}
}
