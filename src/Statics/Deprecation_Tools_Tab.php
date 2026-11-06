<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use GFPDF\Helper\Helper_Interface_Deprecated_Features;

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
 * The global settings Tools tab, which Gravity PDF 6.19.0 removed
 *
 * @since 6.19.0
 * @deprecated 6.19.0 Not a registered provider; Deprecation_Hooks reports `tools_tab_hooks`
 */
class Deprecation_Tools_Tab implements Helper_Interface_Deprecated_Features {

	/**
	 * @var string
	 * @since 6.19.0
	 */
	const REMOVED_IN = '6.19.0';

	/**
	 * @inheritDoc
	 * @since 6.19.0
	 * @deprecated 6.19.0
	 */
	public static function get_features(): array {
		_deprecated_function( __METHOD__, '6.19.0', Deprecation_Hooks::class . '::get_features()' );

		return [ 'tools_tab_hooks' => Deprecation_Hooks::get_features()['tools_tab_hooks'] ];
	}

	/**
	 * The hooks the Tools tab fired, mapped to their General tab equivalent
	 *
	 * @return array<string, string>
	 * @since 6.19.0
	 */
	protected static function get_hooks(): array {
		return Deprecation_Hooks::get_features()['tools_tab_hooks']['hooks'];
	}

	/**
	 * Get the Tools tab hooks that still have a listener attached
	 *
	 * @return array<string, int> Hook name mapped to the number of listeners
	 * @since 6.19.0
	 * @deprecated 6.19.0
	 */
	public static function get_active_hooks(): array {
		_deprecated_function( __METHOD__, '6.19.0', Deprecation::class . '::get_signals()' );

		return Deprecation::get_hooks_with_listeners( static::get_hooks() );
	}

	/**
	 * @inheritDoc
	 * @since 6.19.0
	 * @deprecated 6.19.0
	 */
	public static function get_stored_options(): array {
		_deprecated_function( __METHOD__, '6.19.0', Deprecation_Hooks::class . '::get_stored_options()' );

		return [];
	}

	/**
	 * @inheritDoc
	 * @since 6.19.0
	 * @deprecated 6.19.0
	 */
	public static function flush_cache(): void {
		_deprecated_function( __METHOD__, '6.19.0', Deprecation::class . '::flush_cache()' );
	}
}
