<?php

namespace GFPDF\Helper;

/**
 * Extend the Mpdf object
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
	This file is part of Gravity PDF.

	Gravity PDF – Copyright (c) 2019, Blue Liquid Designs

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * @since 5.2
 */
class Helper_Mpdf extends \Mpdf\Mpdf {

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
	 * @throws \Mpdf\MpdfException
	 * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
	 * @throws \setasign\Fpdi\PdfParser\Filter\FilterException
	 * @throws \setasign\Fpdi\PdfParser\PdfParserException
	 * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
	 * @throws \setasign\Fpdi\PdfReader\PdfReaderException
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
	 * @throws \Mpdf\MpdfException
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
	 * @since 5.2
	 * @throws \Mpdf\MpdfException
	 */
	public function WriteHTML( $html, $mode = \Mpdf\HTMLParserMode::DEFAULT_MODE, $init = true, $close = true ) {

		/* Prevent error if incorrect mode is passed */
		if ( in_array( $mode, \Mpdf\HTMLParserMode::getAllModes(), true ) === false ) {
			$mode = \Mpdf\HTMLParserMode::DEFAULT_MODE;
		}

		/*
		 * Cast $html to string by default to prevent warning when null is passed by custom templates that
		 * reference variables that don't exist
		 */
		parent::WriteHTML( (string) $html, $mode, $init, $close );
	}
}
