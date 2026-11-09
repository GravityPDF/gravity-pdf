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
	 */
	public static function get_features(): array {
		return [
			'tools_tab_hooks' => [
				'label'      => __( 'Tools Tab Actions and Filters', 'gravity-pdf' ),
				'group'      => Deprecation::GROUP_UNSUPPORTED,
				'removed_in' => static::REMOVED_IN,
				'url'        => 'https://docs.gravitypdf.com/developers/filters/gfpdf_settings_tools/',
				'detect'     => [ static::class, 'get_active_hooks' ],
				/* translators: %s: The Gravity PDF version the Tools tab was removed in */
				'notice'     => __( 'Custom code on this site adds to the Tools tab, which was removed in Gravity PDF %s, so what it adds is no longer shown. Move it to the General tab.', 'gravity-pdf' ),
				'hooks'      => static::get_hooks(),
			],
		];
	}

	/**
	 * The hooks the Tools tab fired, mapped to their General tab equivalent
	 *
	 * @return array<string, string>
	 * @since 6.19.0
	 */
	protected static function get_hooks(): array {
		return [
			'gfpdf_settings_tools'           => 'gfpdf_settings_general',
			'gfpdf_post_tools_settings_page' => 'gfpdf_post_general_settings_page',
		];
	}

	/**
	 * Get the Tools tab hooks that still have a listener attached
	 *
	 * @return array<string, int> Hook name mapped to the number of listeners
	 * @since 6.19.0
	 */
	public static function get_active_hooks(): array {
		return Deprecation::get_hooks_with_listeners( static::get_hooks() );
	}

	/**
	 * @inheritDoc
	 * @since 6.19.0
	 */
	public static function get_stored_options(): array {
		return [];
	}

	/**
	 * @inheritDoc
	 * @since 6.19.0
	 */
	public static function flush_cache(): void {}
}
