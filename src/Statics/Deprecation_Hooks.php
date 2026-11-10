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
				'label'         => __( 'Tools Tab Actions and Filters', 'gravity-pdf' ),
				'group'         => Deprecation::GROUP_DEPRECATED,
				'deprecated_in' => '6.19.0',
				'removed_in'    => '7.0',
				'url'           => 'https://docs.gravitypdf.com/developers/filters/gfpdf_settings_tools/',
				/* translators: %s: The Gravity PDF version the Tools tab hooks stop running in */
				'notice'        => __( 'Settings a plugin or custom code added to the Gravity PDF Tools tab now appear at the bottom of the General tab and will be removed in version %s. Ask your developer to update the code.', 'gravity-pdf' ),
				'hooks'         => [
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
