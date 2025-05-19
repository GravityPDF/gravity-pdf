<?php

namespace GFPDF\Exceptions;

use DomainException;

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
 * Class GravityPdfDomainException
 *
 * @package GFPDF\Exceptions
 *
 * @since 6.0
 */
class GravityPdfDomainException extends DomainException {
}
