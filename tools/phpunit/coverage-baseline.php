<?php
/**
 * Print a per-`src/`-subdir coverage breakdown from PHPUnit Clover XML.
 *
 * Usage: php tools/phpunit/coverage-baseline.php [<clover.xml> ...]
 *
 * Accepts one or more Clover paths. Multiple paths are union-merged
 * per-line so the breakdown reflects coverage from both single-site
 * and multisite PHPUnit runs. Defaults to
 * tmp/coverage/report-xml/baseline.xml. See
 * tests/phpunit/COVERAGE_BASELINE.md for how to produce the input XML.
 */

require __DIR__ . '/coverage-merge-lib.php';

function bucket_for( $path ) {
	foreach ( [ '/pdf.php', '/api.php', '/gravity-pdf-updater.php' ] as $suffix ) {
		if ( substr( $path, -strlen( $suffix ) ) === $suffix ) {
			return 'Plugin root';
		}
	}
	if ( strpos( $path, '/src/' ) === false ) {
		return null;
	}
	$rel   = explode( '/src/', $path, 2 )[1];
	$parts = explode( '/', $rel );
	if ( count( $parts ) === 1 ) {
		return 'src/ root';
	}
	if ( 'Helper' === $parts[0] && count( $parts ) >= 3 ) {
		return 'Helper/' . $parts[1];
	}
	return $parts[0];
}

$paths = array_slice( $argv, 1 );
if ( empty( $paths ) ) {
	$paths = [ 'tmp/coverage/report-xml/baseline.xml' ];
}

$union = coverage_union_lines( $paths );
$base  = coverage_load_clover( $paths[0] );

$buckets = [];
$overall = [ 'st' => 0, 'covst' => 0, 'el' => 0, 'covel' => 0 ];

foreach ( $base->xpath( '//file' ) as $f ) {
	$name   = (string) $f['name'];
	$bucket = bucket_for( $name );
	if ( null === $bucket ) {
		continue;
	}

	$st = 0; $covst = 0; $el = 0; $covel = 0;
	foreach ( $f->line as $line ) {
		$type  = (string) $line['type'];
		$count = $union[ $name ][ (int) $line['num'] ];

		$el++;
		if ( $count > 0 ) {
			$covel++;
		}
		if ( $type === 'stmt' ) {
			$st++;
			if ( $count > 0 ) {
				$covst++;
			}
		}
	}

	if ( ! isset( $buckets[ $bucket ] ) ) {
		$buckets[ $bucket ] = [ 'st' => 0, 'covst' => 0, 'el' => 0, 'covel' => 0, 'files' => 0 ];
	}
	$buckets[ $bucket ]['st']    += $st;
	$buckets[ $bucket ]['covst'] += $covst;
	$buckets[ $bucket ]['el']    += $el;
	$buckets[ $bucket ]['covel'] += $covel;
	$buckets[ $bucket ]['files']++;

	$overall['st']    += $st;
	$overall['covst'] += $covst;
	$overall['el']    += $el;
	$overall['covel'] += $covel;
}

$pct = function ( $c, $t ) {
	return $t ? ( $c / $t * 100 ) : 0.0;
};

ksort( $buckets );

printf( "%-28s  %5s  %11s  %7s  %7s\n", 'Bucket', 'Files', 'Stmts', 'Stmt %', 'Elem %' );
echo str_repeat( '-', 70 ) . "\n";
foreach ( $buckets as $name => $d ) {
	printf(
		"%-28s  %5d  %5d/%-5d  %6.2f%%  %6.2f%%\n",
		$name,
		$d['files'],
		$d['covst'],
		$d['st'],
		$pct( $d['covst'], $d['st'] ),
		$pct( $d['covel'], $d['el'] )
	);
}
echo str_repeat( '-', 70 ) . "\n";
$files_total = array_sum( array_column( $buckets, 'files' ) );
printf(
	"%-28s  %5d  %5d/%-5d  %6.2f%%  %6.2f%%\n",
	'OVERALL',
	$files_total,
	$overall['covst'],
	$overall['st'],
	$pct( $overall['covst'], $overall['st'] ),
	$pct( $overall['covel'], $overall['el'] )
);
