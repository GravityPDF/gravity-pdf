<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

/**
 * Tracks filesystem paths a test creates and removes them in tear_down().
 *
 * Phase 3 of the PHPUnit refactor will adopt this for the font-copy / PDF-output
 * tests that currently scatter cleanup logic across set_up() and tearDown().
 * No tests use the trait in Phase 1 — it ships now so adopters can pull it in
 * without inventing the helper later.
 */
trait CleansFilesystem {

	/**
	 * @var array<int, string> Absolute paths queued for cleanup.
	 */
	private $gfpdf_cleanup_paths = [];

	/**
	 * Queue a path for removal in tear_down(). Files and directories supported.
	 *
	 * @param string $path Absolute filesystem path.
	 */
	protected function register_path_for_cleanup( $path ) {
		$this->gfpdf_cleanup_paths[] = $path;
	}

	/**
	 * Removes every queued path. Call from a subclass tear_down() override
	 * BEFORE parent::tear_down() — WP teardown can drop temp dirs we created.
	 */
	protected function clean_registered_paths() {
		foreach ( $this->gfpdf_cleanup_paths as $path ) {
			$this->remove_path( $path );
		}

		$this->gfpdf_cleanup_paths = [];
	}

	/**
	 * Recursive remove for both files and directories.
	 *
	 * @param string $path Absolute filesystem path.
	 */
	private function remove_path( $path ) {
		if ( ! file_exists( $path ) && ! is_link( $path ) ) {
			return;
		}

		if ( is_file( $path ) || is_link( $path ) ) {
			@unlink( $path );

			return;
		}

		$items = scandir( $path );
		if ( false === $items ) {
			return;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$this->remove_path( $path . DIRECTORY_SEPARATOR . $item );
		}

		@rmdir( $path );
	}
}
