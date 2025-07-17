<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF_Vendor\GravityPdf\Upload\FileInfoInterface;
use GFPDF_Vendor\GravityPdf\Upload\ValidationInterface;
use GFPDF_Vendor\GravityPdf\Upload\Exception as UploadException;
use GFPDF\Helper\Mpdf\Cache;
use GFPDF_Vendor\Mpdf\Fonts\FontCache;
use GFPDF_Vendor\Mpdf\Exception\FontException;
use GFPDF_Vendor\Mpdf\MpdfException;
use GFPDF_Vendor\Mpdf\TTFontFile;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TtfFontValidation implements ValidationInterface {

	/**
	 * Error message
	 *
	 * @var string
	 *
	 * @since 6.0
	 */
	protected $message = 'Invalid ttf font file: %s';

	/**
	 * Validate the uploaded file is a valid, readable, TTF font file
	 *
	 * @param FileInfoInterface $file
	 *
	 * @throws \GFPDF_Vendor\Mpdf\MpdfException
	 * @throws \GFPDF_Vendor\Mpdf\Exception\FontException
	 * @since 6.0
	 */
	public function validate( FileInfoInterface $file ): void {
		try {
			$data = \GPDFAPI::get_data_class();

			$ttf = new TTFontFile( new FontCache( new Cache( $data->mpdf_tmp_location . '/mpdf' ) ), apply_filters( 'gpdf_mpdf_font_descriptor', 'win' ) );
			$ttf->getMetrics( $file->getPathname(), $file->getName() );

			if ( empty( $ttf->familyName ) ) {
				throw new UploadException( 'Not a valid font file.' );
			}
		} catch ( FontException $e ) {
			throw new UploadException( 'Not a valid font file.' );
		} catch ( MpdfException $e ) {
			throw new UploadException( 'Unknown error occurred.' );
		}
	}
}
