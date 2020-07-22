<?php

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

/**
 * < v6 Backwards compatibility for common-use non-scoped vendor packages
 *
 * @internal load after other WordPress plugins so they can define these functions beforehand
 *
 * @TODO - only load this if the user upgraded
 */
add_action( 'init', function() {
	if ( ! function_exists( 'qp' ) ) {
		require_once( PDF_PLUGIN_DIR . 'vendor_prefixed/querypath/querypath/src/qp_functions.php' );

		function qp() {
			return \GFPDF_Vendor\qp(...func_get_args());
		}

		function htmlqp() {
			return \GFPDF_Vendor\htmlqp(...func_get_args());
		}

		function html5qp() {
			return \GFPDF_Vendor\html5qp(...func_get_args());
		}
	}

	if( ! class_exists( '\Mpdf\Mpdf' ) ) {
		class_alias('GFPDF_Vendor\\Mpdf\\Mpdf', 'Mpdf\\Mpdf' );
		class_alias('GFPDF_Vendor\\Mpdf\\Config\\ConfigVariables', 'Mpdf\\Config\\ConfigVariables' );
		class_alias('GFPDF_Vendor\\Mpdf\\Config\\FontVariables', 'Mpdf\\Config\\FontVariables' );
	}
}, 9999 );