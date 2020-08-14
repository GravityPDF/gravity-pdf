<?php

namespace GFPDF\Deprecated;

/**
 * Class Vendor_Aliasing
 *
 * @package GFPDF\Deprecated
 */
class Vendor_Aliasing {

	/**
	 * All vendor packages are moved under the namespace \GFPDF_Vendor\* in v6.0
	 * To reduce the likelihood of problems, common functions and classes are aliased back to their original
	 * namespace if a user has upgraded from a pre-6.0 release.
	 *
	 * @internal can be disabled with the filter `gfpdf_disable_vendor_aliasing`
	 *
	 * @since 6.0
	 */
	public static function maybe_alias_vendor_packages(): bool {
		if (
			! \GPDFAPI::get_plugin_option( 'vendor_aliasing', false ) ||
			apply_filters( 'gfpdf_disable_vendor_aliasing', false )
		) {
			return false;
		}

		if ( ! function_exists( 'qp' ) ) {
			require_once( PDF_PLUGIN_DIR . 'vendor_prefixed/querypath/querypath/src/qp_functions.php' );

			function qp() {
				return \GFPDF_Vendor\qp( ...func_get_args() );
			}

			function htmlqp() {
				return \GFPDF_Vendor\htmlqp( ...func_get_args() );
			}

			function html5qp() {
				return \GFPDF_Vendor\html5qp( ...func_get_args() );
			}
		}

		if ( ! class_exists( '\Mpdf\Mpdf' ) ) {
			class_alias( 'GFPDF_Vendor\\Mpdf\\Mpdf', 'Mpdf\\Mpdf' );
			class_alias( 'GFPDF_Vendor\\Mpdf\\MpdfException', 'Mpdf\\MpdfException' );
			class_alias( 'GFPDF_Vendor\\Mpdf\\Config\\ConfigVariables', 'Mpdf\\Config\\ConfigVariables' );
			class_alias( 'GFPDF_Vendor\\Mpdf\\Config\\FontVariables', 'Mpdf\\Config\\FontVariables' );
		}

		return true;
	}

}
