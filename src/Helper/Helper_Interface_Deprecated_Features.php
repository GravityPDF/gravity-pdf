<?php

declare( strict_types=1 );

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
 * Describes a set of Gravity PDF functionality being removed, for Deprecation to detect and report on
 *
 * @since 6.17.0
 */
interface Helper_Interface_Deprecated_Features {

	/**
	 * The features this class contributes to the deprecation registry
	 *
	 * Each entry is keyed by the ID it is detected and reported under, and holds:
	 *
	 * - `label`      (string)   The name the feature is known by to the user
	 * - `group`      (string)   Deprecation::GROUP_UNSUPPORTED or Deprecation::GROUP_DEPRECATED
	 * - `removed_in` (string)   The Gravity PDF version the feature is, or will be, removed in
	 * - `url`        (string)   Where the upgrade instructions live
	 * - `detect`     (callable) Returns what was found on this site, and an empty array when the feature is unused
	 *
	 * A feature the default sentence describes badly declares one more:
	 *
	 * - `notice`     (string)   What becomes of the feature, taking the removal version as `%s`. Read by every
	 *                           surface, so it has to make sense with nothing else around it
	 *
	 * A feature made up of hooks declares two more, so Deprecation::apply_filters() can warn the listeners by name:
	 *
	 * - `hooks`         (array<string, string>) Each deprecated hook mapped to its replacement, '' when none exists
	 * - `deprecated_in` (string)                The Gravity PDF version the hooks were deprecated in
	 * - `hook_prefix`   (string)                Optional. Claims any hook starting with it as well, which is how
	 *                                           the dynamic hooks are matched
	 *
	 * @return array<string, array>
	 * @since 6.17.0
	 */
	public static function get_features(): array;

	/**
	 * The WordPress options this class stores its own detection data in, for the uninstaller to remove
	 *
	 * @return string[] Option names, empty when the class stores nothing of its own
	 * @since 6.17.0
	 */
	public static function get_stored_options(): array;
}
