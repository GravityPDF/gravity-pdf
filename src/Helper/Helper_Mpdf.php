<?php

namespace GFPDF\Helper;

use GFPDF\Helper\Mpdf\Mpdf;

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
 * @since     5.2
 * @depecated 6.13.0 Use \GFPDF\Helper\Mpdf\Mpdf instead
 */
class Helper_Mpdf extends Mpdf {
}
