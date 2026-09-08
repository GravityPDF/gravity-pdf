<?php

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once PDF_PLUGIN_DIR . 'vendor/autoload.php';
require_once PDF_PLUGIN_DIR . 'api.php';



/* QueryPath Backwards Compatibility Support */
class_alias( '\GFPDF_Vendor\QueryPath\QueryPath', '\GFPDF_Vendor\QueryPath' );

/*
 * 7.0 moved the font classes out of `GFPDF\Helper\` — they own tables, HTTP and a queue, and were never helpers.
 * Only the five that shipped in 6.x are aliased. Registered as an autoloader rather than aliased up front so the
 * new class is loaded only if something still asks for the old name.
 */
spl_autoload_register(
	function ( $classname ) {
		static $moved = [
			'GFPDF\Helper\Fonts\FlushCache'        => 'GFPDF\Fonts\FlushCache',
			'GFPDF\Helper\Fonts\LocalFile'         => 'GFPDF\Fonts\LocalFile',
			'GFPDF\Helper\Fonts\LocalFilesystem'   => 'GFPDF\Fonts\LocalFilesystem',
			'GFPDF\Helper\Fonts\SupportsOtl'       => 'GFPDF\Fonts\SupportsOtl',
			'GFPDF\Helper\Fonts\TtfFontValidation' => 'GFPDF\Fonts\TtfFontValidation',
		];

		if ( isset( $moved[ $classname ] ) ) {
			class_alias( $moved[ $classname ], $classname );
		}
	}
);

/* Load global functions file */
require_once PDF_PLUGIN_DIR . 'vendor_prefixed/mpdf/mpdf/src/functions.php';
require_once PDF_PLUGIN_DIR . 'vendor_prefixed/gravitypdf/querypath/src/qp_functions.php';
