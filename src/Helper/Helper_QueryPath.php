<?php

namespace GFPDF\Helper;

use GFPDF_Vendor\Masterminds\HTML5;
use GFPDF_Vendor\QueryPath\QueryPath;
use GFPDF_Vendor\QueryPath\DOMQuery;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 4.0
 */
class Helper_QueryPath extends QueryPath {

	/**
	 * Parse HTML5 documents as strings
	 *
	 * This uses HTML5-PHP to parse the document. In actuality, this parser does
	 * a fine job with pre-HTML5 documents in most cases, though really old HTML
	 * (like 2.0) may have some substantial quirks.
	 *
	 * @param mixed       $html     A document as a HTML string.
	 *
	 * @param string      $selector A CSS3 selector.
	 *
	 * @param array       $options
	 *                              An associative array of options, which is passed on into HTML5-PHP. Note
	 *                              that the standard QueryPath options may be ignored for this function,
	 *                              since it uses a different parser.
	 *
	 * @return DomQuery
	 *
	 * @throws QueryPath\Exception
	 * @since 4.0.3
	 */
	public function html5( $html = '', $selector = '', $options = [] ) {
		$html5  = new HTML5();
		$source = $html5->loadHTML( $html );

		return new DOMQuery( $source, $selector, $options );
	}
}
