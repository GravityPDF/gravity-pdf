<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

/**
 * Compares a value with a file under tests/phpunit/snapshots/. `yarn test:php:snapshots` rewrites the files.
 */
trait AssertsSnapshots {

	protected function assertMatchesSnapshot( string $name, string $actual ): void {
		$file = dirname( __DIR__ ) . '/snapshots/' . $name;

		if ( getenv( 'GPDF_UPDATE_SNAPSHOTS' ) ) {
			wp_mkdir_p( dirname( $file ) );
			file_put_contents( $file, $actual ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		$this->assertFileExists( $file, sprintf( 'No snapshot for %s. Run `yarn test:php:snapshots` to create it.', $name ) );
		$this->assertStringEqualsFile(
			$file,
			$actual,
			sprintf( '%s changed. If the change is deliberate, run `yarn test:php:snapshots` and commit the diff.', $name )
		);
	}
}
