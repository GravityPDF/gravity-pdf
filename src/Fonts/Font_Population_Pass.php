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
 * A one-shot pass that turns something already on the site into font rows, or brings the rows it finds up to date
 *
 * `Font_Repository::ensure_ready()` runs these in the order they were added, once per site, inside the migration
 * lock. Each is responsible for claiming what it owns, so a later pass never re-registers a file an earlier one
 * took — and one that reads rows rather than writing them runs after the passes that write them.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
interface Font_Population_Pass {

	/**
	 * @return int How many rows the pass wrote
	 *
	 * @since 7.0
	 */
	public function run(): int;
}
