<?php

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once PDF_PLUGIN_DIR . 'vendor/autoload.php';
require_once PDF_PLUGIN_DIR . 'src/deprecated.php';
require_once PDF_PLUGIN_DIR . 'api.php';



/* QueryPath Backwards Compatibility Support */
class_alias( '\GFPDF_Vendor\QueryPath\QueryPath', '\GFPDF_Vendor\QueryPath' );

/* Load global functions file */
require_once PDF_PLUGIN_DIR . 'vendor_prefixed/mpdf/mpdf/src/functions.php';
require_once PDF_PLUGIN_DIR . 'vendor_prefixed/gravitypdf/querypath/src/qp_functions.php';
