<?php
/**
 * Regenerate `src/Fonts/Bundled_Coverage.php` from the bundled faces' cmap tables.
 *
 * Run from the plugin root whenever the files in `fonts/` change:
 *
 *     php tools/fonts/generate-bundled-coverage.php
 *
 * `Test_Bundled_Coverage` runs the same derivation and fails when the committed class has drifted.
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

require_once __DIR__ . '/bundled-coverage.php';

$root   = dirname( __DIR__, 2 );
$ranges = gfpdf_bundled_coverage_ranges( $root . '/fonts' );

file_put_contents( $root . '/src/Fonts/Bundled_Coverage.php', gfpdf_bundled_coverage_class( $ranges ) );

printf( "Wrote src/Fonts/Bundled_Coverage.php: %d ranges\n", count( $ranges ) );
