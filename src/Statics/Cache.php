<?php

namespace GFPDF\Statics;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 6.12.0
 */
class Cache {

	protected static $template_tmp_location = null;

	public static function get_path( $form, $entry, $pdf_settings ) {
		return self::get_basepath() . self::get_hash( $form, $entry, $pdf_settings ) . '/';
	}

	public static function get_hash( $form, $entry, $pdf_settings ) {
		return sprintf(
			'%1$d-%2$d-%3$s',
			$form['id'] ?? 0,
			$entry['id'] ?? 0,
			wp_hash( wp_json_encode( [ $form, $entry, $pdf_settings ] ) )
		);
	}

	protected static function get_basepath() {
		if ( self::$template_tmp_location !== null ) {
			return self::$template_tmp_location;
		}

		$data      = \GPDFAPI::get_data_class();
		$base_path = $data->template_tmp_location;
		if ( is_multisite() ) {
			$base_path .= get_current_blog_id();
		}

		self::$template_tmp_location = trailingslashit( $base_path ) . 'cache/';

		return self::$template_tmp_location;
	}
}
