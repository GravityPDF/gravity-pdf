<?php

namespace GFPDF;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( PDF_PLUGIN_DIR . 'vendor/autoload.php' );
require_once( PDF_PLUGIN_DIR . 'src/deprecated.php' );
require_once( PDF_PLUGIN_DIR . 'api.php' );
