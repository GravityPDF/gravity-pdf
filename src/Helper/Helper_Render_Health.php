<?php

namespace GFPDF\Helper;

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
 * Tracks whether the PDF being rendered came out degraded, e.g. a remote image timed out, so it isn't cached
 *
 * Each render opens its own scope, so a PDF rendered inside another (a template calling create_pdf()) never changes
 * the outer render's flag. A mark made outside any scope is dropped.
 *
 * @since 7.0
 */
class Helper_Render_Health {

	/**
	 * @var bool[] One degraded flag per render in progress, innermost last
	 *
	 * @since 7.0
	 */
	protected static $scopes = [];

	/**
	 * Start tracking a render
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public static function begin() {
		static::$scopes[] = false;
	}

	/**
	 * Stop tracking the innermost render
	 *
	 * @return bool Whether that render was degraded
	 *
	 * @since 7.0
	 */
	public static function end() {
		return (bool) array_pop( static::$scopes );
	}

	/**
	 * Flag the innermost render as degraded
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public static function mark() {
		if ( ! empty( static::$scopes ) ) {
			static::$scopes[ count( static::$scopes ) - 1 ] = true;
		}
	}

	/**
	 * Whether the innermost render has been flagged as degraded
	 *
	 * @return bool
	 *
	 * @since 7.0
	 */
	public static function is_degraded() {
		return ! empty( static::$scopes ) && end( static::$scopes );
	}
}
