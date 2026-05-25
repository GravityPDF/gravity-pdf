<?php
/**
 * Helpers for unioning per-line coverage across multiple Clover XML files.
 *
 * Used by coverage-gate.php and coverage-baseline.php to combine single-site
 * and multisite PHPUnit clover output.
 */

function coverage_load_clover( $path ) {
	$xml = @simplexml_load_file( $path );
	if ( false === $xml ) {
		fwrite( STDERR, "coverage: cannot read $path\n" );
		exit( 1 );
	}
	return $xml;
}

/**
 * Returns [ file => [ line_num => max_count ] ] across all clover inputs.
 */
function coverage_union_lines( array $paths ) {
	$out = [];
	foreach ( $paths as $path ) {
		$xml = coverage_load_clover( $path );
		foreach ( $xml->xpath( '//file' ) as $f ) {
			$name = (string) $f['name'];
			foreach ( $f->line as $line ) {
				$num   = (int) $line['num'];
				$count = (int) $line['count'];
				if ( ! isset( $out[ $name ][ $num ] ) || $out[ $name ][ $num ] < $count ) {
					$out[ $name ][ $num ] = $count;
				}
			}
		}
	}
	return $out;
}

/**
 * Returns [ statements_total, covered_statements ] from the union of inputs.
 *
 * Uses the first input's `<file><line type="stmt">` markers as the
 * canonical statement set; counts a line as covered if it has count > 0
 * in any input.
 */
function coverage_merge_totals( array $paths ) {
	$union = coverage_union_lines( $paths );
	$base  = coverage_load_clover( $paths[0] );

	$st  = 0;
	$cov = 0;
	foreach ( $base->xpath( '//file' ) as $f ) {
		$name = (string) $f['name'];
		foreach ( $f->line as $line ) {
			if ( (string) $line['type'] !== 'stmt' ) {
				continue;
			}
			$st++;
			if ( $union[ $name ][ (int) $line['num'] ] > 0 ) {
				$cov++;
			}
		}
	}

	return [ $st, $cov ];
}
