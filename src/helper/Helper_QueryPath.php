<?php

namespace GFPDF\Helper;

use \Masterminds\HTML5;
use \QueryPath\DOMQuery;
use \QueryPath;

/**
 * Extends Query Path to make it more useful to us when using the HTML5 methods (which are UTF-8 compatible).
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

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
	 * @param mixed  $html
	 *   A document as a HTML string.
	 *
	 * @param string $selector
	 *   A CSS3 selector.
	 *
	 * @param array  $options
	 *   An associative array of options, which is passed on into HTML5-PHP. Note
	 *   that the standard QueryPath options may be ignored for this function,
	 *   since it uses a different parser.
	 *
	 * @return QueryPath
	 *
	 * @since 4.0.3
	 */
	public function html5( $html = '', $selector = null, $options = [] ) {
		$html5  = new HTML5();
		$source = $html5->loadHTML( $html );

		return new DOMQuery( $source, $selector, $options );
	}
}