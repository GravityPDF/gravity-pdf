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
 * Removals that leave nothing behind but the hooks they fired
 *
 * @since 6.19.0
 */
class Deprecation_Hooks implements Helper_Interface_Deprecated_Features {

	/**
	 * @inheritDoc
	 * @since 6.19.0
	 */
	public static function get_features(): array {
		return [
			'tools_tab_hooks' => [
				'label'      => __( 'Tools Tab Actions and Filters', 'gravity-pdf' ),
				'group'      => Deprecation::GROUP_UNSUPPORTED,
				'removed_in' => '6.19.0',
				'url'        => 'https://docs.gravitypdf.com/developers/filters/gfpdf_settings_tools/',
				/* translators: %s: The Gravity PDF version the Tools tab was removed in */
				'notice'     => __( 'Custom code on this site adds to the Tools tab, which was removed in Gravity PDF %s, so what it adds is no longer shown. Move it to the General tab.', 'gravity-pdf' ),
				'hooks'      => [
					'gfpdf_settings_tools'           => 'gfpdf_settings_general',
					'gfpdf_post_tools_settings_page' => 'gfpdf_post_general_settings_page',
				],
			],
		];
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
