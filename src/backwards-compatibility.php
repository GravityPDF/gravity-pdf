<?php

/**
 * Adds backwards compatibility to WordPress and Gravity Forms
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initialize $wp_scripts if it has not been set.
 * @global WP_Scripts $wp_scripts
 * @since WP 4.2.0
 * @return WP_Scripts WP_Scripts instance.
 */
if( ! function_exists( 'wp_scripts') ) {
	function wp_scripts() {
		global $wp_scripts;
		if ( ! ( $wp_scripts instanceof WP_Scripts ) ) {
			$wp_scripts = new WP_Scripts();
		}
		return $wp_scripts;
	}
}


/**
 * Initialize $wp_styles if it has not been set.
 * @global WP_Styles $wp_styles
 * @since WP 4.2.0
 * @return WP_Styles WP_Styles instance.
 */
if( ! function_exists( 'wp_styles') ) {
	function wp_styles() {
		global $wp_styles;
		if ( ! ( $wp_styles instanceof WP_Styles ) ) {
			$wp_styles = new WP_Styles();
		}
		return $wp_styles;
	}
}
