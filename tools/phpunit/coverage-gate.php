<?php
/**
 * Fail the build if overall line coverage falls below the Phase 0 baseline.
 *
 * Usage: php tools/phpunit/coverage-gate.php [path/to/clover.xml] [min-percent]
 *
 * Defaults: tmp/coverage/report-xml/baseline.xml at 76.33%.
 * Baseline is documented in tests/phpunit/COVERAGE_BASELINE.md and is meant to
 * ratchet upward quarterly — update the constant below when raising it.
 */

const MIN_COVERAGE_PERCENT = 76.33;

$xml_path    = $argv[1] ?? 'tmp/coverage/report-xml/baseline.xml';
$min_percent = isset( $argv[2] ) ? (float) $argv[2] : MIN_COVERAGE_PERCENT;

$xml = @simplexml_load_file( $xml_path );
if ( false === $xml ) {
	fwrite( STDERR, "coverage-gate: cannot read $xml_path\n" );
	exit( 1 );
}

if ( ! isset( $xml->project->metrics ) ) {
	fwrite( STDERR, "coverage-gate: <project><metrics> missing in $xml_path\n" );
	exit( 1 );
}

$totals     = $xml->project->metrics;
$statements = (int) $totals['statements'];
$covered    = (int) $totals['coveredstatements'];
$percent    = $statements > 0 ? ( $covered / $statements * 100 ) : 0.0;

printf(
	"coverage: %d/%d statements covered (%.2f%%); floor: %.2f%%\n",
	$covered,
	$statements,
	$percent,
	$min_percent
);

if ( round( $percent, 2 ) < $min_percent ) {
	fwrite( STDERR, sprintf( "FAIL: coverage %.2f%% below floor %.2f%%\n", $percent, $min_percent ) );
	exit( 1 );
}

echo "OK\n";
