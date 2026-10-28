<?php

namespace GFPDF\Exceptions;

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
 * Class GravityPdfNotATemplateException
 *
 * An upload that doesn't contain a PDF template
 *
 * @package GFPDF\Exceptions
 *
 * @since 6.18.0
 */
class GravityPdfNotATemplateException extends GravityPdfDomainException {
}
