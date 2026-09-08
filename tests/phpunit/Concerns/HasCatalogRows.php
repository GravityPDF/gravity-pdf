<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

use GFPDF\Fonts\Catalog_Repository;

/**
 * Catalog-table fixtures: write index rows straight into the table, bypassing the sync.
 *
 * `Catalog_Sync` is the only writer of the index columns in production, so these go in through `$wpdb` rather than
 * through a seam that would only exist for tests. The WP test transaction rolls the rows back; the object cache does
 * not roll back, so every writer here bumps the catalog stamp.
 */
trait HasCatalogRows {

	protected function catalog_repository(): Catalog_Repository {
		global $gfpdf;

		return $gfpdf->get_catalog_repository();
	}

	/**
	 * Insert one catalog row, filling every column the table requires
	 *
	 * @param array $overrides Any column the default row should not use
	 */
	protected function insert_catalog_row( string $source, string $entry, array $overrides = [] ): void {
		global $gfpdf, $wpdb;

		$wpdb->insert(
			$gfpdf->get_font_repository()->get_schema()->get_catalog_table(),
			array_merge(
				[
					'source'   => $source,
					'entry'    => $entry,
					'label'    => ucfirst( $entry ),
					'version'  => 'fonts-v1.0.0',
					'coverage' => 0,
					'position' => 0,
					'license'  => 'OFL-1.1',
					'size'     => 1024,
					'files'    => 1,
					'always'   => 0,
				],
				$overrides
			)
		);

		$this->catalog_repository()->flush();
	}

	/**
	 * Remove every catalog row this test wrote
	 */
	protected function drop_catalog_rows(): void {
		global $gfpdf, $wpdb;

		$table = $gfpdf->get_font_repository()->get_schema()->get_catalog_table();

		/* phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- table name comes from Font_Schema */
		$wpdb->query( "DELETE FROM {$table}" );

		$this->catalog_repository()->flush();
	}
}
