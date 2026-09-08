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
 * Report a call to a method Gravity PDF has removed, instead of letting it be fatal
 *
 * Used by every class that lost a method, and a `use` line is the record that one did.
 *
 * Returning `null` is not a substitute for the method: a filter callback routed through here nulls the value it was
 * handed, and `is_callable()` now answers true for any name. The notice is what tells a site which call to fix.
 *
 * @since 7.0
 */
trait Helper_Trait_Removed_Methods {

	/**
	 * The methods this class has lost, mapped to the release each one went in
	 *
	 * Only worth declaring once a class has lost methods in more than one release. Anything left out is reported as
	 * 7.0, the round of removals this trait was introduced for.
	 *
	 * @return array<string, string>
	 *
	 * @since 7.0
	 */
	protected static function get_removed_methods(): array {
		return [];
	}

	/**
	 * Report a call to an instance method that no longer exists
	 *
	 * @param string $name      The method being called
	 * @param array  $arguments The arguments it was called with
	 *
	 * @since 7.0
	 */
	public function __call( $name, $arguments ) {
		static::report_removed_method( $name );
	}

	/**
	 * Report a call to a static method that no longer exists
	 *
	 * @param string $name      The method being called
	 * @param array  $arguments The arguments it was called with
	 *
	 * @since 7.0
	 */
	public static function __callStatic( $name, $arguments ) {
		static::report_removed_method( $name );
	}

	/**
	 * Emit the deprecation notice naming the release the method went in
	 *
	 * @param string $name The method being called
	 *
	 * @since 7.0
	 */
	protected static function report_removed_method( string $name ): void {
		$version = static::get_removed_methods()[ $name ] ?? '7.0';

		_deprecated_function( esc_html( static::class . '::' . $name ), esc_html( $version ) );
	}
}
