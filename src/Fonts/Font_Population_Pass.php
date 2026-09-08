<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

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
 * A one-shot pass that turns something already on the site into font rows
 *
 * `Font_Repository::ensure_ready()` runs these in the order they were added, once per site, inside the migration
 * lock. Each is responsible for claiming what it owns, so a later pass never re-registers a file an earlier one
 * took.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
interface Font_Population_Pass {

	/**
	 * @return int How many rows the pass created
	 *
	 * @since 7.0
	 */
	public function run(): int;
}
