<?php

declare( strict_types=1 );

namespace GFPDF\Fonts\Health;

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
 * Where a health issue's remedy lives
 *
 * One place, because these are client-side routes: the Font Manager is a React app on the PDF settings screen and
 * its routes move with it. A check that built its own URL would be a second thing to remember.
 *
 * @package GFPDF\Fonts\Health
 *
 * @since 7.0
 */
class Font_Manager_Urls {

	/**
	 * @since 7.0
	 */
	public static function manager(): string {
		return admin_url( 'admin.php?page=gf_settings&subview=PDF#/fontmanager' );
	}

	/**
	 * One installed font's detail view — Replace, Delete, and the faces it has
	 *
	 * @since 7.0
	 */
	public static function font( string $font_key ): string {
		return static::manager() . '/' . rawurlencode( $font_key );
	}

	/**
	 * One catalogue entry's view, where Install and Reinstall live
	 *
	 * @param string $id `{source}/{entry}`
	 *
	 * @since 7.0
	 */
	public static function entry( string $id ): string {
		return static::manager() . '/' . $id;
	}
}
