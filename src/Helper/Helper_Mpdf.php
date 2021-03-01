<?php

namespace GFPDF\Helper;

use GFPDF_Vendor\Mpdf\HTMLParserMode;
use GFPDF_Vendor\Mpdf\Mpdf;
use GFPDF_Vendor\Mpdf\MpdfException;
use GFPDF_Vendor\setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use GFPDF_Vendor\setasign\Fpdi\PdfParser\PdfParserException;
use GFPDF_Vendor\setasign\Fpdi\PdfParser\Type\PdfTypeException;

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
 * @since 5.2
 */
class Helper_Mpdf extends Mpdf {

	/**
	 * Added for backwards compatibility as it was removed in mPDF 8.0
	 *
	 * @since 5.2
	 */
	public function SetImportUse() {
		/* Do nothing */
	}

	/**
	 * @param int    $pageNumber The page number.
	 * @param null   $crop_x     mPDF 8.0 removed this parameter
	 * @param null   $crop_y     mPDF 8.0 removed this parameter
	 * @param int    $crop_w     mPDF 8.0 removed this parameter
	 * @param int    $crop_h     mPDF 8.0 removed this parameter
	 * @param string $boxName    The page boundary to import.
	 *
	 * @return string A unique string identifying the imported page.
	 * @throws CrossReferenceException
	 * @throws PdfParserException
	 * @throws PdfTypeException
	 *
	 * @since 5.2
	 */
	public function importPage( $pageNumber = 1, $crop_x = null, $crop_y = null, $crop_w = 0, $crop_h = 0, $boxName = 'CropBox' ) {
		/* mPDF 8.0 no longer needs the box name with `/` included */
		if ( $boxName[0] === '/' ) {
			$boxName = substr( $boxName, 1 );
		}

		/* The signature of this method has changed */

		return parent::importPage( $pageNumber, $boxName );
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
		$template['w'] = isset( $template['width'] ) ? $template['width'] : 0;
		$template['h'] = isset( $template['height'] ) ? $template['height'] : 0;

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
