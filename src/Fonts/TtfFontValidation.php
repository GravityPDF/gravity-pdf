<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

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
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
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
	 * Read with checksum verification on, which is `getMetrics()`'s fourth argument: every table is compared
	 * against the checksum the font's own table directory states for it. Without it a font whose tables have been
	 * altered since it was built parses perfectly and renders the wrong glyphs, silently, the first time text
	 * reaches the damaged part — corrupting `GSUB` or `GPOS` by hand throws nothing at any stage and produces a
	 * byte-identical PDF for text that misses it. The checksum is the only thing here that notices.
	 *
	 * Partial by mPDF's own rule: `maxStrLenRead` skips any table of 200 KB or more, which on most fonts is `glyf`
	 * and nothing else. So this covers the structural tables — the character map, the layout tables, the names and
	 * the metrics — and leaves the outlines unchecked. Worth having for what it does cover.
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
			$ttf->getMetrics( $file->getPathname(), $file->getName(), 0, true );

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
