<?php
/**
 * Derive the code points the bundled faces cover, straight from their `cmap` tables.
 *
 * Shared by `generate-bundled-coverage.php` and `Test_Bundled_Coverage`, so the check that the committed class is
 * current runs the generator's own derivation rather than a second reading of it.
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Code points no font needs to cover for the document to be right: the ones a PDF never draws.
 *
 * Without these the gate fires on every document that contains a newline, and the whole point of it is to cost one
 * failed PCRE pass on a Latin site.
 */
const GFPDF_COVERAGE_IGNORED = [
	[ 0x0000, 0x0020 ], /* C0 controls and space */
	[ 0x007F, 0x00A0 ], /* C1 controls and no-break space */
	[ 0x200B, 0x200F ], /* zero-width and bidi marks */
	[ 0x2028, 0x202E ], /* line/paragraph separators and bidi overrides */
	[ 0x2060, 0x2064 ], /* word joiner and invisible operators */
	[ 0xFEFF, 0xFEFF ], /* byte-order mark */
];

/**
 * The covered code points of every `.ttf` in a directory, collapsed to ranges
 *
 * @return array<int, array{0: int, 1: int}> Ascending, non-overlapping, non-adjacent
 */
function gfpdf_bundled_coverage_ranges( string $dir ): array {
	$covered = [];

	foreach ( GFPDF_COVERAGE_IGNORED as $range ) {
		for ( $code = $range[0]; $code <= $range[1]; $code++ ) {
			$covered[ $code ] = true;
		}
	}

	$files = glob( $dir . '/*.ttf' );
	sort( $files );

	foreach ( $files as $file ) {
		foreach ( gfpdf_cmap_code_points( $file ) as $code ) {
			$covered[ $code ] = true;
		}
	}

	$codes = array_keys( $covered );
	sort( $codes );

	return gfpdf_collapse_ranges( $codes );
}

/**
 * Every code point one TrueType file maps to a real glyph
 *
 * @return int[]
 */
function gfpdf_cmap_code_points( string $file ): array {
	$font = file_get_contents( $file );
	$cmap = gfpdf_ttf_table_offset( $font, 'cmap' );

	if ( $cmap === null ) {
		throw new RuntimeException( sprintf( '%s has no cmap table', basename( $file ) ) );
	}

	$offset = gfpdf_cmap_subtable_offset( $font, $cmap );

	if ( $offset === null ) {
		throw new RuntimeException( sprintf( '%s has no Unicode cmap subtable', basename( $file ) ) );
	}

	$format = gfpdf_uint16( $font, $offset );

	if ( $format === 4 ) {
		return gfpdf_cmap_format_4( $font, $offset );
	}

	if ( $format === 12 ) {
		return gfpdf_cmap_format_12( $font, $offset );
	}

	throw new RuntimeException( sprintf( '%s uses unsupported cmap format %d', basename( $file ), $format ) );
}

/**
 * Where a table lives in the file, or null when the font does not carry it
 */
function gfpdf_ttf_table_offset( string $font, string $tag ): ?int {
	$count = gfpdf_uint16( $font, 4 );

	for ( $i = 0; $i < $count; $i++ ) {
		$record = 12 + ( $i * 16 );

		if ( substr( $font, $record, 4 ) === $tag ) {
			return gfpdf_uint32( $font, $record + 8 );
		}
	}

	return null;
}

/**
 * The best Unicode subtable in a cmap: full-repertoire before BMP-only, Windows before Unicode-platform
 *
 * A face carrying both gets the format 12 table, since the format 4 one cannot express anything above U+FFFF and
 * every astral code point would otherwise read as uncovered.
 */
function gfpdf_cmap_subtable_offset( string $font, int $cmap ): ?int {
	$ranked = [
		'3/10' => 0,
		'0/4'  => 1,
		'0/6'  => 2,
		'3/1'  => 3,
		'0/3'  => 4,
	];

	$best  = null;
	$count = gfpdf_uint16( $font, $cmap + 2 );

	for ( $i = 0; $i < $count; $i++ ) {
		$record   = $cmap + 4 + ( $i * 8 );
		$platform = gfpdf_uint16( $font, $record );
		$encoding = gfpdf_uint16( $font, $record + 2 );
		$rank     = $ranked[ $platform . '/' . $encoding ] ?? null;

		if ( $rank === null || ( $best !== null && $rank >= $best[0] ) ) {
			continue;
		}

		$best = [ $rank, $cmap + gfpdf_uint32( $font, $record + 4 ) ];
	}

	return $best === null ? null : $best[1];
}

/**
 * @return int[]
 */
function gfpdf_cmap_format_4( string $font, int $offset ): array {
	$segments = gfpdf_uint16( $font, $offset + 6 ) >> 1;
	$ends     = $offset + 14;
	$starts   = $ends + ( $segments * 2 ) + 2;
	$deltas   = $starts + ( $segments * 2 );
	$indexes  = $deltas + ( $segments * 2 );
	$codes    = [];

	for ( $i = 0; $i < $segments; $i++ ) {
		$start = gfpdf_uint16( $font, $starts + ( $i * 2 ) );
		$end   = gfpdf_uint16( $font, $ends + ( $i * 2 ) );
		$delta = gfpdf_uint16( $font, $deltas + ( $i * 2 ) );
		$range = gfpdf_uint16( $font, $indexes + ( $i * 2 ) );

		/* The spec's terminating segment, which maps nothing */
		if ( $start === 0xFFFF ) {
			continue;
		}

		for ( $code = $start; $code <= $end; $code++ ) {
			if ( $range === 0 ) {
				$glyph = ( $code + $delta ) & 0xFFFF;
			} else {
				$at    = $indexes + ( $i * 2 ) + $range + ( ( $code - $start ) * 2 );
				$glyph = gfpdf_uint16( $font, $at );
				$glyph = $glyph === 0 ? 0 : ( $glyph + $delta ) & 0xFFFF;
			}

			if ( $glyph !== 0 ) {
				$codes[] = $code;
			}
		}
	}

	return $codes;
}

/**
 * @return int[]
 */
function gfpdf_cmap_format_12( string $font, int $offset ): array {
	$groups = gfpdf_uint32( $font, $offset + 12 );
	$codes  = [];

	for ( $i = 0; $i < $groups; $i++ ) {
		$group = $offset + 16 + ( $i * 12 );
		$start = gfpdf_uint32( $font, $group );
		$end   = gfpdf_uint32( $font, $group + 4 );

		/* Glyph 0 is `.notdef`, and only the group's first code point can land on it */
		if ( gfpdf_uint32( $font, $group + 8 ) === 0 ) {
			$start++;
		}

		for ( $code = $start; $code <= $end; $code++ ) {
			$codes[] = $code;
		}
	}

	return $codes;
}

/**
 * @param int[] $codes Ascending
 *
 * @return array<int, array{0: int, 1: int}>
 */
function gfpdf_collapse_ranges( array $codes ): array {
	$ranges = [];

	foreach ( $codes as $code ) {
		$last = count( $ranges ) - 1;

		if ( $last >= 0 && $ranges[ $last ][1] + 1 === $code ) {
			$ranges[ $last ][1] = $code;

			continue;
		}

		$ranges[] = [ $code, $code ];
	}

	return $ranges;
}

/**
 * The PCRE character class matching everything the bundled faces do not cover
 *
 * @param array<int, array{0: int, 1: int}> $ranges
 */
function gfpdf_bundled_coverage_pattern( array $ranges ): string {
	$class = '';

	foreach ( $ranges as $range ) {
		$class .= sprintf( '\x{%04X}', $range[0] );

		if ( $range[1] !== $range[0] ) {
			$class .= sprintf( '-\x{%04X}', $range[1] );
		}
	}

	return '/[^' . $class . ']/u';
}

function gfpdf_uint16( string $font, int $offset ): int {
	return ( ord( $font[ $offset ] ) << 8 ) | ord( $font[ $offset + 1 ] );
}

function gfpdf_uint32( string $font, int $offset ): int {
	return ( gfpdf_uint16( $font, $offset ) << 16 ) | gfpdf_uint16( $font, $offset + 2 );
}

/**
 * The source of `src/Fonts/Bundled_Coverage.php`
 *
 * @param array<int, array{0: int, 1: int}> $ranges
 */
function gfpdf_bundled_coverage_class( array $ranges ): string {
	$pattern = str_replace( '\\', '\\\\', gfpdf_bundled_coverage_pattern( $ranges ) );

	return <<<PHP
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
	 * What the bundled faces can draw, as one character class
	 *
	 * GENERATED — do not edit. Run `php tools/fonts/generate-bundled-coverage.php` after changing `fonts/`.
	 *
	 * Read from the faces' own cmaps because a hand-written approximation gets it wrong in the direction that
	 * hides misses: Arimo has nothing in the Arabic presentation forms (U+FB50–FDFF) and nothing in halfwidth
	 * Katakana, so a class written from block names would silently cover both.
	 *
	 * @package GFPDF\Fonts
	 *
	 * @since 7.0
	 */
	class Bundled_Coverage {

		/**
		 * Matches any code point the bundled faces cannot draw
		 *
		 * @since 7.0
		 */
		public const UNCOVERED = '$pattern';
	}

	PHP;
}
