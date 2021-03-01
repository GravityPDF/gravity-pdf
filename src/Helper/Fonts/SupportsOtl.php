<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF_Vendor\Mpdf\Cache;
use GFPDF_Vendor\Mpdf\Fonts\FontCache;
use GFPDF_Vendor\Mpdf\MpdfException;
use GFPDF_Vendor\Mpdf\TTFontFile;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SupportsOtl
 *
 * @package GFPDF\Helper\Fonts
 *
 * @since 6.0
 */
class SupportsOtl {
	/**
	 * @var string
	 */
	protected $font_directory_path;

	public function __construct( string $font_directory_path ) {
		$this->font_directory_path = $font_directory_path;
	}

	/**
	 * Processes the font file to see if it supports OTL tables
	 *
	 * @param string $file The filename of a font file to check
	 *
	 * @since 6.0
	 */
	public function supports_otl( string $file ): bool {
		try {
			$ttf = new TTFontFile( new FontCache( new Cache( get_temp_dir() . 'mpdf' ) ), apply_filters( 'gpdf_mpdf_font_descriptor', 'win' ) );
			$ttf->getMetrics( $this->font_directory_path . $file, (string) time(), 0, false, false, 0xFF );

			return strlen( $ttf->familyName ) > 0;
		} catch ( MpdfException $e ) {

		}

		return false;
	}
}

