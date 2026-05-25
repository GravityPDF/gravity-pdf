<?php
/**
 * Print a per-`src/`-subdir coverage breakdown from a PHPUnit Clover XML.
 *
 * Usage: php tools/phpunit/coverage-baseline.php [path/to/clover.xml]
 *
 * Default path: tmp/coverage/report-xml/baseline.xml. See
 * tests/phpunit/COVERAGE_BASELINE.md for how to produce the input XML.
 */

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

$xml_path = $argv[1] ?? 'tmp/coverage/report-xml/baseline.xml';
$xml      = simplexml_load_file( $xml_path );
if ( false === $xml ) {
	fwrite( STDERR, "Failed to parse $xml_path\n" );
	exit( 1 );
}

$buckets = [];
$overall = [ 'st' => 0, 'covst' => 0, 'el' => 0, 'covel' => 0 ];

foreach ( $xml->xpath( '//file' ) as $f ) {
	$bucket = bucket_for( (string) $f['name'] );
	if ( null === $bucket || ! isset( $f->metrics ) ) {
		continue;
	}
	$m     = $f->metrics;
	$st    = (int) $m['statements'];
	$covst = (int) $m['coveredstatements'];
	$el    = (int) $m['elements'];
	$covel = (int) $m['coveredelements'];

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
