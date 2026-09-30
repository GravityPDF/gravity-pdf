<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

/**
 * Runs a second PHP process beside the test, for races one process can't stage (flock handovers, torn writes).
 */
trait SpawnsPhpProcesses {

	/**
	 * Start `php -r $script` with $args as $argv[1..n], output discarded
	 *
	 * @return resource
	 */
	protected function spawn_php( string $script, array $args ) {
		$pipes   = [];
		$process = proc_open(
			array_merge( [ PHP_BINARY, '-r', $script ], $args ),
			[
				1 => [ 'file', '/dev/null', 'w' ],
				2 => [ 'file', '/dev/null', 'w' ],
			],
			$pipes
		);

		$this->assertIsResource( $process, 'Could not start a second PHP process' );

		return $process;
	}

	protected function wait_for_file( string $file ): void {
		$deadline = microtime( true ) + 10;

		while ( microtime( true ) < $deadline && ! is_file( $file ) ) {
			usleep( 5000 );
		}

		$this->assertFileExists( $file, 'The second process never got going' );
	}
}
