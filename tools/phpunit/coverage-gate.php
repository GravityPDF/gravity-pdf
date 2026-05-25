<?php
/**
 * Fail the build if overall line coverage falls below the Phase 0 baseline.
 *
 * Usage: php tools/phpunit/coverage-gate.php [path/to/clover.xml]
 *
 * Defaults to tmp/coverage/report-xml/baseline.xml. Ratchet the floor upward
 * by editing MIN_COVERAGE_PERCENT below; the value is also documented in
 * tests/phpunit/COVERAGE_BASELINE.md.
 */

const MIN_COVERAGE_PERCENT = 76.33;

$xml_path = $argv[1] ?? 'tmp/coverage/report-xml/baseline.xml';

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
	MIN_COVERAGE_PERCENT
);

if ( round( $percent, 2 ) < MIN_COVERAGE_PERCENT ) {
	fwrite( STDERR, sprintf( "FAIL: coverage %.2f%% below floor %.2f%%\n", $percent, MIN_COVERAGE_PERCENT ) );
	exit( 1 );
}

echo "OK\n";
