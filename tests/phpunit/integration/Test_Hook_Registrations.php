<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Integration;

use GFPDF\Tests\Concerns\AssertsSnapshots;
use WP_UnitTestCase;

/**
 * Snapshots the callbacks the documentation shows developers unhooking by object and method name, as hooked once
 * WordPress finished booting. Rewiring the bootstrap must leave this file unchanged.
 *
 * @group snapshot
 */
class Test_Hook_Registrations extends WP_UnitTestCase {

	use AssertsSnapshots;

	/* The docs for both hooks say any of the plugin's middleware can be removed with GPDFAPI::get_mvc_class() */
	private const DOCUMENTED = [
		'gfpdf_field_middleware',
		'gfpdf_pdf_middleware',
	];

	public function test_hook_registrations_match_snapshot(): void {
		$lines = [];
		foreach ( self::DOCUMENTED as $hook ) {
			foreach ( $GLOBALS['gfpdf_tests_booted_hooks'][ $hook ] ?? [] as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$lines[] = "$hook $priority " . $this->describe_callback( $callback['function'] ) . " {$callback['accepted_args']}";
				}
			}
		}
		sort( $lines, SORT_STRING );

		$this->assertMatchesSnapshot( 'hook-registrations.txt', implode( "\n", $lines ) . "\n" );
	}

	/**
	 * @param callable $callback
	 */
	private function describe_callback( $callback ): string {
		/* A closure renders as Closure::__invoke, which could not be unhooked */
		if ( ! is_array( $callback ) || ! is_object( $callback[0] ) ) {
			is_callable( $callback, true, $name );

			return $name;
		}

		$name = get_class( $callback[0] ) . '->' . $callback[1];

		/* Unhooking needs the instance GPDFAPI::get_mvc_class() returns, so a stray copy is flagged */
		$short     = substr( (string) strrchr( '\\' . get_class( $callback[0] ), '\\' ), 1 );
		$singleton = $GLOBALS['gfpdf']->singleton->get_class( $short );

		return $singleton === $callback[0] ? $name : $name . ' [not-singleton]';
	}
}
