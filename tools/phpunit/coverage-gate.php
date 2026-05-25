<?php
/**
 * Fail the build if overall line coverage falls below the documented floor.
 *
 * Usage:
 *   php tools/phpunit/coverage-gate.php [<clover.xml> ...]
 *
 * Accepts one or more Clover paths. Multiple paths are union-merged
 * per-line so the gate reflects coverage from both single-site and
 * multisite PHPUnit runs.
 *
 * Ratchet the floor upward by editing MIN_COVERAGE_PERCENT below;
 * tests/phpunit/COVERAGE_BASELINE.md documents the current value.
 */

const MIN_COVERAGE_PERCENT = 81.45;

require __DIR__ . '/coverage-merge-lib.php';

$paths = array_slice( $argv, 1 );
if ( empty( $paths ) ) {
	$paths = [ 'tmp/coverage/report-xml/baseline.xml' ];
}

[ $statements, $covered ] = coverage_merge_totals( $paths );
$percent                  = $statements > 0 ? ( $covered / $statements * 100 ) : 0.0;

printf(
	"coverage: %d/%d statements covered (%.2f%%); floor: %.2f%%\n",
	$covered,
	$statements,
	$percent,
	MIN_COVERAGE_PERCENT
);

if ( round( $percent, 2 ) < MIN_COVERAGE_PERCENT ) {
	fwrite( STDERR, sprintf( "FAIL: coverage %.2f%% below floor %.2f%%\n", $percent, MIN_COVERAGE_PERCENT ) );
	exit( 1 );
}

echo "OK\n";
