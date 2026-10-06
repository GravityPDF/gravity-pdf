<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Integration;

use GFPDF\Tests\Concerns\AssertsSnapshots;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use WP_UnitTestCase;

/**
 * Snapshots every hook the plugin fires with its argument count, so a renamed hook or a dropped argument fails.
 *
 * @group snapshot
 */
class Test_Hook_Inventory extends WP_UnitTestCase {

	use AssertsSnapshots;

	/* Hook functions: [ kind, the argument holding the hook's arguments as an array, or null when they follow the name ] */
	private const FIRES = [
		'apply_filters'            => [ 'filter', null ],
		'apply_filters_ref_array'  => [ 'filter', 1 ],
		'apply_filters_deprecated' => [ 'filter', 1 ],
		'gf_apply_filters'         => [ 'filter', null ],
		'do_action'                => [ 'action', null ],
		'do_action_ref_array'      => [ 'action', 1 ],
		'do_action_deprecated'     => [ 'action', 1 ],
		'gf_do_action'             => [ 'action', null ],
	];

	/**
	 * Plugin methods that fire a hook their caller names: [ kind, name argument, extra-arguments array argument,
	 * arguments passed before the extras, suffixed names also fired ]. Update an entry when its method changes.
	 */
	private const WRAPPERS = [
		'deprecation::apply_filters' => [ 'filter', 0, 1, 0, [] ],
		'save_pdf_and_do_action'     => [ 'action', 2, 3, 5, [ '_{form_id}' ] ],
	];

	public function test_hook_inventory_matches_snapshot(): void {
		$root  = dirname( __DIR__, 3 );
		$files = [ $root . '/pdf.php', $root . '/api.php', $root . '/gravity-pdf-updater.php' ];
		foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/src' ) ) as $file ) {
			if ( $file->getExtension() === 'php' ) {
				$files[] = $file->getPathname();
			}
		}

		$hooks = [];
		foreach ( $files as $file ) {
			array_push( $hooks, ...$this->find_hooks( file_get_contents( $file ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}

		$hooks = array_unique( $hooks );
		sort( $hooks, SORT_STRING );

		$this->assertMatchesSnapshot( 'hooks.txt', implode( "\n", $hooks ) . "\n" );
	}

	/**
	 * @return list<string> One "kind name argument-count" line per hook fired
	 */
	private function find_hooks( string $source ): array {
		$tokens = array_values(
			array_filter(
				token_get_all( $source ),
				static function ( $token ): bool {
					return ! is_array( $token ) || ! in_array( $token[0], [ T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ], true );
				}
			)
		);

		$hooks = [];
		foreach ( $tokens as $i => $token ) {
			$function = $this->called_function( $tokens, $i );
			if ( $function === '' ) {
				continue;
			}

			if ( isset( self::WRAPPERS[ $function ] ) ) {
				[ $kind, $name_arg, $array_arg, $base, $suffixes ] = self::WRAPPERS[ $function ];

				$args  = $this->split_arguments( $tokens, $i + 1 );
				$name  = $this->render( $args[ $name_arg ] );
				$count = (string) ( $base + (int) $this->count_array( $args[ $array_arg ] ?? [] ) );
				foreach ( array_merge( [ '' ], $suffixes ) as $suffix ) {
					$hooks[] = "$kind $name$suffix $count";
				}
				continue;
			}

			if ( ! isset( self::FIRES[ $function ] ) ) {
				continue;
			}

			[ $kind, $array_arg ] = self::FIRES[ $function ];

			$args  = $this->split_arguments( $tokens, $i + 1 );
			$name  = $this->render( $this->resolve_variable( $tokens, $i, $args[0] ) );
			$count = $array_arg === null ? $this->count_arguments( array_slice( $args, 1 ) ) : $this->count_array( $args[ $array_arg ] ?? [] );

			$hooks[] = "$kind $name $count";
		}

		return $hooks;
	}

	/**
	 * The lower-cased name called at $i ("class::method" when static), or '' when $i is not a call.
	 *
	 * @param list<array|string> $tokens
	 */
	private function called_function( array $tokens, int $i ): string {
		$token = $tokens[ $i ];
		$names = [ T_STRING ];
		if ( defined( 'T_NAME_FULLY_QUALIFIED' ) ) {
			$names[] = T_NAME_FULLY_QUALIFIED; /* PHP 8 reads `\do_action` as one token */
		}

		if ( ! is_array( $token ) || ! in_array( $token[0], $names, true ) || ( $tokens[ $i + 1 ] ?? '' ) !== '(' ) {
			return '';
		}

		$name     = strtolower( ltrim( $token[1], '\\' ) );
		$previous = is_array( $tokens[ $i - 1 ] ?? null ) ? $tokens[ $i - 1 ][0] : null;

		if ( in_array( $previous, [ T_FUNCTION, T_NEW ], true ) ) {
			return '';
		}

		if ( $previous === T_DOUBLE_COLON ) {
			$class = $tokens[ $i - 2 ][1] ?? '';

			return strtolower( substr( $class, (int) strrpos( '\\' . $class, '\\' ) ) ) . '::' . $name;
		}

		/* An instance method named like a hook function is not WordPress's */
		return $previous === T_OBJECT_OPERATOR && ! isset( self::WRAPPERS[ $name ] ) ? '' : $name;
	}

	/**
	 * Swaps a variable hook name for its last assignment in the same function. A parameter stays a variable.
	 *
	 * @param list<array|string> $tokens
	 * @param list<array|string> $name
	 *
	 * @return list<array|string>
	 */
	private function resolve_variable( array $tokens, int $call, array $name ): array {
		if ( count( $name ) !== 1 || ! is_array( $name[0] ) || $name[0][0] !== T_VARIABLE ) {
			return $name;
		}

		for ( $i = $call - 1; $i > 0 && ! ( is_array( $tokens[ $i ] ) && $tokens[ $i ][0] === T_FUNCTION ); $i-- ) {
			if ( is_array( $tokens[ $i ] ) && $tokens[ $i ][1] === $name[0][1] && ( $tokens[ $i + 1 ] ?? '' ) === '=' ) {
				$value = [];
				for ( $j = $i + 2; ( $tokens[ $j ] ?? ';' ) !== ';'; $j++ ) {
					$value[] = $tokens[ $j ];
				}

				return $value;
			}
		}

		return $name;
	}

	/**
	 * A literal name without its quotes, or the expression that builds a dynamic one.
	 *
	 * @param list<array|string> $tokens
	 */
	private function render( array $tokens ): string {
		if ( count( $tokens ) === 1 && is_array( $tokens[0] ) && $tokens[0][0] === T_CONSTANT_ENCAPSED_STRING ) {
			return substr( $tokens[0][1], 1, -1 );
		}

		return implode(
			'',
			array_map(
				static function ( $token ): string {
					return is_array( $token ) ? $token[1] : $token;
				},
				$tokens
			)
		);
	}

	/**
	 * Splits the arguments of the call or array literal opened at $open.
	 *
	 * @param list<array|string> $tokens
	 *
	 * @return list<list<array|string>>
	 */
	private function split_arguments( array $tokens, int $open ): array {
		$args  = [ [] ];
		$depth = 0;

		for ( $i = $open + 1, $total = count( $tokens ); $i < $total; $i++ ) {
			$token = $tokens[ $i ];
			$text  = is_array( $token ) ? '' : $token; /* Only bare punctuation is structure, never text inside a string */

			if ( in_array( $text, [ '(', '[', '{' ], true ) || ( is_array( $token ) && in_array( $token[0], [ T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ], true ) ) ) {
				$depth++;
			} elseif ( in_array( $text, [ ')', ']', '}' ], true ) ) {
				if ( $depth === 0 ) {
					break;
				}
				$depth--;
			} elseif ( $text === ',' && $depth === 0 ) {
				$args[] = [];
				continue;
			}

			$args[ count( $args ) - 1 ][] = $token;
		}

		/* A trailing comma, or no arguments at all, leaves an empty last argument */
		if ( end( $args ) === [] ) {
			array_pop( $args );
		}

		return $args;
	}

	/**
	 * The argument count, or "N+" when one is unpacked with `...`.
	 *
	 * @param list<list<array|string>> $args
	 */
	private function count_arguments( array $args ): string {
		foreach ( $args as $arg ) {
			if ( is_array( $arg[0] ?? null ) && $arg[0][0] === T_ELLIPSIS ) {
				return ( count( $args ) - 1 ) . '+';
			}
		}

		return (string) count( $args );
	}

	/**
	 * The element count of an array literal, or "?" when the argument is not one.
	 *
	 * @param list<array|string> $arg
	 */
	private function count_array( array $arg ): string {
		if ( $arg === [] ) {
			return '0';
		}

		return $arg[0] === '[' ? $this->count_arguments( $this->split_arguments( $arg, 0 ) ) : '?';
	}
}
