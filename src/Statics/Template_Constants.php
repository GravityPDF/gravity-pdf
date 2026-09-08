<?php

namespace GFPDF\Statics;

use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Templates;

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
 * The v3-era path constants and `$gfpdfe_data` global PDF templates rely on
 *
 * A supported extension point for template authors, defined lazily on the first `Helper_PDF` construction so the
 * template location filters have settled by the time the paths are resolved. `define()` is process-global, so
 * everything built after that first render sees them; core reads none of them.
 *
 * `$gfpdfe_data` is the `Helper_Data` object itself rather than a copy, so templates that mutate it see their changes
 * where the rest of the plugin does.
 *
 * @since 7.0
 */
class Template_Constants {

	/**
	 * Whether the first render of the request has already defined them
	 *
	 * @var bool
	 *
	 * @since 7.0
	 */
	protected static $defined = false;

	/**
	 * Define the v3 path constants and the `$gfpdfe_data` global for PDF templates
	 *
	 * @param Helper_Data      $data
	 * @param Helper_Templates $templates
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public static function maybe_define( Helper_Data $data, Helper_Templates $templates ) {
		if ( static::$defined ) {
			return;
		}

		static::$defined = true;

		$constants = [
			'PDF_SAVE_LOCATION'         => $data->template_tmp_location,
			'PDF_FONT_LOCATION'         => $data->template_font_location,
			'PDF_TEMPLATE_LOCATION'     => $templates->get_template_path(),
			'PDF_TEMPLATE_URL_LOCATION' => $templates->get_template_url(),
		];

		foreach ( $constants as $name => $value ) {
			/* Guarded individually so a third party that got there first doesn't trigger a redefinition notice */
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		$GLOBALS['gfpdfe_data'] = $data;
	}
}
