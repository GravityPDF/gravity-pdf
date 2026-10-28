<?php

namespace GFPDF\Helper\Mpdf;

use GFPDF_Vendor\Mpdf\Mpdf as MpdfCore;
use GFPDF_Vendor\Mpdf\HTMLParserMode;
use GFPDF_Vendor\Mpdf\MpdfException;
use GFPDF_Vendor\setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use GFPDF_Vendor\setasign\Fpdi\PdfParser\PdfParserException;
use GFPDF_Vendor\setasign\Fpdi\PdfParser\Type\PdfTypeException;

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
 * @since 6.13.0 Moved from \GFPDF\Helper\Helper_Mpdf
 */
class Mpdf extends MpdfCore {

	/**
	 * Added for backwards compatibility as it was removed in mPDF 8.0
	 *
	 * @since 5.2
	 */
	public function SetImportUse() {
		/* Do nothing */
	}

	/**
	 * Discard an array passed as the second argument instead of fataling on it
	 *
	 * mPDF reads a `$condition` string in that position ('E', 'O', 'NEXT-ODD'...) and takes a custom page size via
	 * `$newformat`, the last parameter. That has been the signature since mPDF 5.3, so a page size has never
	 * belonged there — the supported call is `AddPageByArray( [ 'sheet-size' => [ $w, $h ] ] )`, which is where the
	 * Business Plus add-on moved its own boilerplate in 1.0.3.
	 *
	 * Templates written against the boilerplate before that still call `AddPage( 'P', [ $w, $h ] )`, so the array
	 * reaches `strtoupper()`. PHP 7 warned, returned null and carried on with no condition, leaving the page at the
	 * size it already had; PHP 8 raises a TypeError and ends the request instead.
	 *
	 * Emptying the condition reproduces the PHP 7 result exactly — mPDF only ever compares it against non-empty
	 * keywords, and blanks it itself when one doesn't apply. The page size the template asked for goes on being
	 * ignored, as it always has been, so no existing PDF changes; this only takes away the fatal.
	 *
	 * @since 6.17.0
	 */
	public function AddPage( $orientation = '', $condition = '', $resetpagenum = '', $pagenumstyle = '', $suppress = '', $mgl = '', $mgr = '', $mgt = '', $mgb = '', $mgh = '', $mgf = '', $ohname = '', $ehname = '', $ofname = '', $efname = '', $ohvalue = 0, $ehvalue = 0, $ofvalue = 0, $efvalue = 0, $pagesel = '', $newformat = '' ) {
		if ( is_array( $condition ) ) {
			$condition = '';
		}

		return parent::AddPage( $orientation, $condition, $resetpagenum, $pagenumstyle, $suppress, $mgl, $mgr, $mgt, $mgb, $mgh, $mgf, $ohname, $ehname, $ofname, $efname, $ohvalue, $ehvalue, $ofvalue, $efvalue, $pagesel, $newformat );
	}

	/**
	 * Imports a page, taking mPDF's own arguments or the mPDF 7 ones legacy templates pass
	 *
	 * mPDF 7 took `( $pageNumber, $crop_x, $crop_y, $crop_w, $crop_h, $boxName )`. A second argument that isn't a
	 * string, or more than four arguments, is that form: the crop values are dropped, as mPDF 8 removed them, and
	 * `$boxName` is used.
	 *
	 * @param int         $pageNumber          The page number.
	 * @param mixed       $box                 The page boundary to import, or the mPDF 7 `$crop_x`.
	 * @param bool|null   $groupXObject        Define the form XObject as a group XObject to support transparency.
	 * @param bool|int    $importExternalLinks Import the page's external links.
	 * @param mixed       ...$legacy           The mPDF 7 `$crop_h` and `$boxName`.
	 *
	 * @return string A unique string identifying the imported page.
	 * @throws CrossReferenceException
	 * @throws PdfParserException
	 * @throws PdfTypeException
	 *
	 * @since 5.2
	 * @since 7.0 Takes mPDF's own arguments too
	 */
	public function importPage( $pageNumber = 1, $box = 'CropBox', $groupXObject = true, $importExternalLinks = true, ...$legacy ) {
		if ( ! is_string( $box ) || count( $legacy ) > 0 ) {
			$box                 = $legacy[1] ?? 'CropBox';
			$groupXObject        = true;
			$importExternalLinks = true;
		}

		/* mPDF 8.0 no longer needs the box name with `/` included */
		if ( $box[0] === '/' ) {
			$box = substr( $box, 1 );
		}

		return parent::importPage( $pageNumber, $box, $groupXObject, $importExternalLinks );
	}

	/**
	 * Draws an imported page or a template onto the page or another template.
	 *
	 * Omit one of the size parameters (width, height) to calculate the other one automatically in view to the aspect
	 * ratio.
	 *
	 * @param mixed           $tpl    The template id
	 * @param float|int|array $x      The abscissa of upper-left corner. Alternatively you could use an assoc array
	 *                                with the keys "x", "y", "width", "height", "adjustPageSize".
	 * @param float|int       $y      The ordinate of upper-left corner.
	 * @param float|int|null  $width  The width.
	 * @param float|int|null  $height The height.
	 * @param bool            $adjustPageSize
	 *
	 * @return array The size
	 * @see   Fpdi::getTemplateSize()
	 *
	 * @since 5.2
	 */
	public function useTemplate( $tpl, $x = 0, $y = 0, $width = null, $height = null, $adjustPageSize = false ) {
		$template = parent::useTemplate( $tpl, $x, $y, $width, $height, $adjustPageSize );

		/* This return signature for this method changed in mPDF 8.0 */
		$template['w'] = $template['width'] ?? 0;
		$template['h'] = $template['height'] ?? 0;

		return $template;
	}

	/**
	 * Write HTML code to the document
	 *
	 * Also used internally to parse HTML into buffers
	 *
	 * @param string $html
	 * @param int    $mode  Use HTMLParserMode constants. Controls what parts of the $html code is parsed.
	 * @param bool   $init  Clears and sets buffers to Top level block etc.
	 * @param bool   $close If false leaves buffers etc. in current state, so that it can continue a block etc.
	 *
	 * @throws MpdfException
	 * @since 5.2
	 */
	public function WriteHTML( $html, $mode = HTMLParserMode::DEFAULT_MODE, $init = true, $close = true ) {

		/* Prevent error if incorrect mode is passed */
		if ( in_array( $mode, HTMLParserMode::getAllModes(), true ) === false ) {
			$mode = HTMLParserMode::DEFAULT_MODE;
		}

		/*
		 * Cast $html to string by default to prevent warning when null is passed by custom templates that
		 * reference variables that don't exist
		 */
		parent::WriteHTML( (string) $html, $mode, $init, $close );
	}
}
