<?php

/*
 * Release gate: scans the zip for code patterns typical of injected malware, failing on hits missing from the baseline.
 * Usage: php tools/release/scan.php <zip> [--update-baseline]
 * Hits are keyed by path, rules and a hash of the whitespace-normalised statement, so line shifts don't churn the baseline.
 */

const SINKS        = [ 'assert', 'create_function', 'shell_exec', 'system', 'passthru', 'exec', 'popen', 'proc_open', 'pcntl_exec' ];
const DECODERS     = [ 'base64_decode', 'gzinflate', 'gzuncompress', 'gzdecode', 'str_rot13', 'hex2bin', 'convert_uudecode' ];
const SUPERGLOBALS = [ '$_GET', '$_POST', '$_REQUEST', '$_COOKIE', '$_SERVER', '$_FILES' ];
const IGNORABLE    = [ T_COMMENT, T_DOC_COMMENT, T_WHITESPACE ];
// Reported on their own.
const STANDALONE   = [ 'eval', 'backtick', 'preg-e', 'decode-chain', 'blob', ...SINKS ];
// Weak signals, reported only in these pairs; they're also labelled whenever a standalone rule fires.
const PAIRS        = [ [ 'variable-call', 'decode' ], [ 'include', 'superglobal' ] ];
const CONTEXT      = [ 'decode', 'superglobal', 'variable-call', 'include' ];
const PHP_EXT      = '/\.(php\d?|phtml|pht|phar|inc)$/i';
const LONG_LINE    = 5000;
const BLOB         = 1000;

// Not getopt(): it stops at the first non-option, silently ignoring a trailing --update-baseline.
$update   = in_array( '--update-baseline', $argv, true );
$zip_path = array_values( array_diff( array_slice( $argv, 1 ), [ '--update-baseline' ] ) )[0] ?? null;
$baseline = __DIR__ . '/scan-baseline.txt';

if ( $zip_path === null ) {
	fwrite( STDERR, "usage: php {$argv[0]} <zip> [--update-baseline]\n" );
	exit( 1 );
}

$zip = new ZipArchive();
if ( $zip->open( $zip_path, ZipArchive::RDONLY ) !== true ) {
	fwrite( STDERR, "FAIL: cannot open {$zip_path}\n" );
	exit( 1 );
}

$hits = [];
for ( $i = 0; $i < $zip->numFiles; $i++ ) {
	$name = $zip->getNameIndex( $i );
	if ( substr( $name, -1 ) === '/' ) {
		continue;
	}

	$path = preg_replace( '#^gravity-pdf/#', '', $name );
	$code = $zip->getFromIndex( $i );

	if ( preg_match( PHP_EXT, $path ) ) {
		if ( preg_match( '#^(build|src/assets)/#', $path ) ) {
			add_hit( $hits, $path, 'php-in-assets' );
		}
		scan_php( $hits, $path, $code );
	} elseif ( stripos( $code, '<?php' ) !== false ) {
		add_hit( $hits, $path, 'php-tag-in-non-php' );
	} elseif ( preg_match( '/\.m?js$/i', $path ) ) {
		scan_js( $hits, $path, $code );
	}
}
$zip->close();

ksort( $hits );

if ( $update ) {
	$lines = [
		'# Known hits for tools/release/scan.php: path, rules, statement hash (- for per-file rules), count, snippet.',
		'# Review new hits, then refresh with: php tools/release/scan.php <zip> --update-baseline',
	];
	foreach ( $hits as $key => $hit ) {
		$lines[] = implode( "\t", [ $key, count( $hit['lines'] ), $hit['snippet'] ] );
	}
	file_put_contents( $baseline, implode( "\n", $lines ) . "\n" );
	printf( "Wrote %d hits to %s\n", count( $hits ), $baseline );
	exit( 0 );
}

$known = [];
foreach ( is_file( $baseline ) ? file( $baseline, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) : [] as $line ) {
	if ( $line[0] !== '#' ) {
		$fields = explode( "\t", $line );

		$known[ implode( "\t", array_slice( $fields, 0, 3 ) ) ] = (int) $fields[3];
	}
}

$new   = array_filter( $hits, fn( $hit, $key ) => count( $hit['lines'] ) > ( $known[ $key ] ?? 0 ), ARRAY_FILTER_USE_BOTH );
$stale = array_filter( $known, fn( $count, $key ) => $count > count( $hits[ $key ]['lines'] ?? [] ), ARRAY_FILTER_USE_BOTH );

echo "### Code scan\n\n";
printf( "%d hits, %d in the baseline, %d new, %d stale.\n", count( $hits ), count( $known ), count( $new ), count( $stale ) );

if ( $stale ) {
	echo "\nStale baseline entries (fewer or no longer in the zip, safe to drop with --update-baseline):\n\n```\n" . implode( "\n", array_keys( $stale ) ) . "\n```\n";
}

if ( $new ) {
	echo "\n**FAIL: new suspicious code.** Review each hit; only if it is legitimate, refresh the baseline with `php tools/release/scan.php <zip> --update-baseline`.\n\n```\n";
	foreach ( $new as $key => $hit ) {
		printf( "%s:%s  [%s]\n    %s\n", strtok( $key, "\t" ), implode( ',', array_filter( $hit['lines'] ) ) ?: '-', $hit['rules'], $hit['snippet'] );
	}
	echo "```\n";
	exit( 1 );
}

echo "\nOK: no new suspicious code.\n";

// A null $identity keys the hit by path and rule alone, so it is counted per file rather than per statement.
function add_hit( array &$hits, string $path, string $rules, string $snippet = '', int $line = 0, ?string $identity = null ): void {
	$snippet = trim( preg_replace( '/\s+/', ' ', $snippet ) );
	$key     = implode( "\t", [ $path, $rules, $identity === null ? '-' : substr( sha1( $snippet ), 0, 12 ) ] );

	$hits[ $key ]            = $hits[ $key ] ?? [ 'rules' => $rules, 'snippet' => mb_strimwidth( $snippet, 0, 160, '...', 'UTF-8' ), 'lines' => [] ];
	$hits[ $key ]['lines'][] = $line;
}

// Flags each statement whose tokens hit a rule; the statement's tokens (no comments) are what's hashed.
function scan_php( array &$hits, string $path, string $code ): void {
	foreach ( strlen( $code ) > LONG_LINE ? explode( "\n", $code ) : [] as $n => $line ) {
		if ( strlen( $line ) > LONG_LINE ) {
			// Vendor data files are full of these and change on every upgrade, so only the count per file is tracked.
			add_hit( $hits, $path, 'long-line', $line, $n + 1 );
		}
	}

	$statement    = [];
	$text         = '';
	$line         = 0;
	$prev         = null;
	$prev2        = null;
	$depth        = 0;
	$decode_depth = null;
	$decoded      = [];
	$tokens       = token_get_all( $code );

	foreach ( $tokens as $i => $token ) {
		[ $id, $value ] = is_array( $token ) ? $token : [ null, $token ];

		if ( in_array( $id, IGNORABLE, true ) || in_array( $id, [ T_INLINE_HTML, T_OPEN_TAG, T_CLOSE_TAG ], true ) ) {
			$text .= $id === T_WHITESPACE ? ' ' : '';
			continue;
		}

		$line  = $line ?: ( is_array( $token ) ? $token[2] : 0 );
		$text .= $value;
		$depth = $value === '(' ? $depth + 1 : ( $value === ')' ? $depth - 1 : $depth );

		$is_name = $id === T_STRING || ( defined( 'T_NAME_FULLY_QUALIFIED' ) && $id === T_NAME_FULLY_QUALIFIED );
		$call    = ( $is_name || $id === T_VARIABLE || $value === ')' ) && ( $tokens[ next_significant( $tokens, $i ) ] ?? null ) === '(';
		$name    = $is_name ? strtolower( ltrim( $value, '\\' ) ) : '';

		// A global function call: not a method, declaration, or namespaced function (PHP 7 tokenises `\exec` as two tokens).
		$is_function = $call && $is_name
			&& ! in_array( $prev, [ T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW, T_CONST ], true )
			&& ! ( $prev === T_NS_SEPARATOR && in_array( $prev2, [ T_STRING, T_NAMESPACE ], true ) )
			&& ! ( defined( 'T_NULLSAFE_OBJECT_OPERATOR' ) && $prev === T_NULLSAFE_OBJECT_OPERATOR );

		if ( $id === T_EVAL ) {
			$statement['eval'] = true;
		} elseif ( $value === '`' ) {
			$statement['backtick'] = true;
		} elseif ( in_array( $id, [ T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE ], true ) ) {
			$statement['include'] = true;
		} elseif ( $id === T_VARIABLE && in_array( $value, SUPERGLOBALS, true ) ) {
			$statement['superglobal'] = true;
		} elseif ( $call && ! $is_name ) {
			$statement['variable-call'] = true;
			// Calling a variable assigned from a decoder earlier in the file, e.g. `$f = base64_decode(...); $f(...)`.
			if ( isset( $decoded[ $value ] ) ) {
				$statement['decode'] = true;
			}
		} elseif ( $is_function && in_array( $name, SINKS, true ) ) {
			$statement[ $name ] = true;
		} elseif ( $is_function && in_array( $name, DECODERS, true ) ) {
			// Nested decoders, e.g. gzinflate(base64_decode(...)).
			if ( $decode_depth !== null && $decode_depth < $depth ) {
				$statement['decode-chain'] = true;
			}
			$decode_depth        = min( $decode_depth ?? $depth, $depth );
			$statement['decode'] = true;
		} elseif ( $is_function && $name === 'preg_replace' && is_e_modifier( $tokens, $i ) ) {
			$statement['preg-e'] = true;
		} elseif ( $id === T_CONSTANT_ENCAPSED_STRING && strlen( $value ) > BLOB && preg_match( '/^.[A-Za-z0-9+\/=\\\\x]+.$/s', $value ) ) {
			$statement['blob'] = true;
		}

		if ( in_array( $value, [ ';', '{', '}' ], true ) ) {
			if ( isset( $statement['decode'] ) && preg_match( '/^\s*(\$\w+)\s*=[^=]/', $text, $match ) ) {
				$decoded[ $match[1] ] = true;
			}
			flush_statement( $hits, $path, $statement, $text, $line );
			$statement    = [];
			$text         = '';
			$line         = 0;
			$depth        = 0;
			$decode_depth = null;
		}

		$prev2 = $prev;
		$prev  = $id;
	}

	flush_statement( $hits, $path, $statement, $text, $line );
}

function flush_statement( array &$hits, string $path, array $statement, string $text, int $line ): void {
	if ( ! $statement ) {
		return;
	}

	$report = (bool) array_intersect_key( $statement, array_flip( STANDALONE ) );
	foreach ( PAIRS as $pair ) {
		$report = $report || ! array_diff_key( array_flip( $pair ), $statement );
	}

	if ( $report ) {
		$rules = array_keys( array_intersect_key( $statement, array_flip( array_merge( STANDALONE, CONTEXT ) ) ) );
		sort( $rules );
		add_hit( $hits, $path, implode( '+', $rules ), $text, $line, $text );
	}
}

// Index of the next token that isn't whitespace or a comment.
function next_significant( array $tokens, int $i ): ?int {
	for ( $j = $i + 1, $count = count( $tokens ); $j < $count; $j++ ) {
		if ( ! is_array( $tokens[ $j ] ) || ! in_array( $tokens[ $j ][0], IGNORABLE, true ) ) {
			return $j;
		}
	}

	return null;
}

// preg_replace whose first argument is a literal pattern carrying the (PHP < 7 code-executing) e modifier.
function is_e_modifier( array $tokens, int $i ): bool {
	$token = $tokens[ next_significant( $tokens, next_significant( $tokens, $i ) ) ] ?? null;
	if ( ! is_array( $token ) || $token[0] !== T_CONSTANT_ENCAPSED_STRING ) {
		return false;
	}

	$pattern = substr( $token[1], 1, -1 );
	$closing = strtr( $pattern[0] ?? '', '({[<', ')}]>' );
	$end     = strrpos( $pattern, $closing );

	return $end > 0 && strpos( substr( $pattern, $end + 1 ), 'e' ) !== false;
}

// eval / new Function fed a decoded string; webpack's legit `new Function("return this")` doesn't decode.
function scan_js( array &$hits, string $path, string $code ): void {
	preg_match_all( '/\b(?:eval|new\s+Function)\s*\((?:(?!\beval\b|\bFunction\b)[^;]){0,200}?\b(?:atob|unescape|fromCharCode|decodeURIComponent)\s*\(/', $code, $matches, PREG_OFFSET_CAPTURE );
	foreach ( $matches[0] as [ $match, $offset ] ) {
		add_hit( $hits, $path, 'js-eval-decode', $match, substr_count( $code, "\n", 0, $offset ) + 1, $match );
	}
}
