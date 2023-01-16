<?php

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once PDF_PLUGIN_DIR . 'vendor/autoload.php';
require_once PDF_PLUGIN_DIR . 'src/deprecated.php';
require_once PDF_PLUGIN_DIR . 'api.php';

class_alias( '\GFPDF_Vendor\QueryPath\QueryPath', '\GFPDF_Vendor\QueryPath' ); /* Backwards compatibility support */
require_once PDF_PLUGIN_DIR . 'vendor_prefixed/gravitypdf/querypath/src/qp_functions.php';
