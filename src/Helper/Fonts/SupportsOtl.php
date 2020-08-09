<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF_Vendor\Mpdf\Cache;
use GFPDF_Vendor\Mpdf\Fonts\FontCache;
use GFPDF_Vendor\Mpdf\MpdfException;
use GFPDF_Vendor\Mpdf\TTFontFile;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SupportsOtl {
	/**
	 * @var string
	 */
	protected $font_directory_path;

	public function __construct( string $font_directory_path ) {
		$this->font_directory_path = $font_directory_path;
	}

	public function supports_otl( $file ): bool {
		try {
			$ttf = new TTFontFile( new FontCache( new Cache( get_temp_dir() . 'mpdf' ) ), null );
			$ttf->getMetrics( $this->font_directory_path . $file, (string) time(), 0, false, false, 0xFF );

			return strlen( $ttf->familyName ) > 0;
		} catch ( MpdfException $e ) {

		}

		return false;
	}
}

