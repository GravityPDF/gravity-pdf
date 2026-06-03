<?php
/**
 * Emit a merged Clover XML by union-merging per-line counts across inputs.
 *
 * Usage: php tools/phpunit/coverage-merge.php <out.xml> <in1.xml> [<in2.xml> ...]
 *
 * The first input is the structural base; per-line counts are unioned across
 * all inputs and per-file + project <metrics> are recomputed so downstream
 * tooling (commenter actions, gates) reads correct totals.
 */

require __DIR__ . '/coverage-merge-lib.php';

if ( $argc < 3 ) {
	fwrite( STDERR, "Usage: php coverage-merge.php <out.xml> <in1.xml> [<in2.xml> ...]\n" );
	exit( 1 );
}

$out    = $argv[1];
$inputs = array_slice( $argv, 2 );

$union = coverage_union_lines( $inputs );
$xml   = coverage_load_clover( $inputs[0] );

$metric_keys = [
	'methods',
	'coveredmethods',
	'conditionals',
	'coveredconditionals',
	'statements',
	'coveredstatements',
	'elements',
	'coveredelements',
];

$project_metrics = array_fill_keys( $metric_keys, 0 );

foreach ( $xml->xpath( '//file' ) as $f ) {
	$name         = (string) $f['name'];
	$file_metrics = array_fill_keys( $metric_keys, 0 );

	foreach ( $f->line as $line ) {
		$num           = (int) $line['num'];
		$count         = $union[ $name ][ $num ] ?? 0;
		$line['count'] = (string) $count;

		$covered = $count > 0;
		$file_metrics['elements']++;
		if ( $covered ) {
			$file_metrics['coveredelements']++;
		}

		switch ( (string) $line['type'] ) {
			case 'stmt':
				$file_metrics['statements']++;
				if ( $covered ) {
					$file_metrics['coveredstatements']++;
				}
				break;
			case 'cond':
				$file_metrics['conditionals']++;
				if ( $covered ) {
					$file_metrics['coveredconditionals']++;
				}
				break;
			case 'method':
				$file_metrics['methods']++;
				if ( $covered ) {
					$file_metrics['coveredmethods']++;
				}
				break;
		}
	}

	foreach ( $file_metrics as $k => $v ) {
		$f->metrics[ $k ]       = (string) $v;
		$project_metrics[ $k ] += $v;
	}
}

foreach ( $project_metrics as $k => $v ) {
	$xml->project->metrics[ $k ] = (string) $v;
}

$xml->asXML( $out );
