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
 * @TODO
 */
class Debug {
	public static function is_enabled(): bool {
		$options = \GPDFAPI::get_options_class();

		$pdf_debug_mode            = $options->get_option( 'debug_mode', 'No' ) === 'Yes';
		$wp_production_environment = function_exists( 'wp_get_environment_type' ) && wp_get_environment_type() !== 'production';

		return $pdf_debug_mode && ! $wp_production_environment;
	}

	public static function can_view(): bool {
		$gform = \GPDFAPI::get_form_class();

		return $gform->has_capability( 'gravityforms_logging' );

	}

	public static function is_enabled_and_can_view(): bool {
		return static::is_enabled() && static::can_view();
	}
}
