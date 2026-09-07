<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

use GFPDF\Helper\Fonts\Font_Repository;

/**
 * Font-table fixtures: install rows, then take them and their files away again.
 *
 * The rows outlive nothing — the WP test transaction rolls the tables back — but the *files* are real, and
 * `Font_Repository` memoises per instance, so a suite that writes rows has to clean both.
 */
trait HasFontRows {

	/**
	 * The shared repository the plugin itself uses
	 */
	protected function font_repository(): Font_Repository {
		global $gfpdf;

		return $gfpdf->get_font_repository();
	}

	/**
	 * Absolute path to the uploads fonts directory, created if missing
	 */
	protected function font_dir(): string {
		$font_dir = $this->font_repository()->get_font_dir();

		wp_mkdir_p( $font_dir );

		return $font_dir;
	}

	/**
	 * Install a font row backed by a real file
	 *
	 * @param string $font_key  The mPDF key
	 * @param array  $overrides Any column the default row should not use
	 *
	 * @return int The new row's id
	 */
	protected function install_font_row( string $font_key, array $overrides = [] ): int {
		$path = 'test-' . $font_key . '.ttf';
		file_put_contents( $this->font_dir() . $path, 'ttf' );

		return $this->font_repository()->insert(
			array_merge(
				[
					'font_key' => $font_key,
					'label'    => ucfirst( $font_key ),
					'source'   => 'custom',
					'files'    => [
						'R' => [
							'path' => $path,
							'size' => 3,
						],
					],
				],
				$overrides
			)
		);
	}

	/**
	 * Drop every row and every test font file
	 *
	 * Rows go first with `$unlink_files` false, because the file sweep below is what removes them — the shared
	 * delete path would otherwise skip any file a surviving row still records.
	 */
	protected function remove_font_rows(): void {
		$repository = $this->font_repository();

		foreach ( array_keys( $repository->all() ) as $font_key ) {
			$repository->delete( $font_key, false );
		}

		foreach ( glob( $this->font_dir() . 'test-*.ttf' ) ?: [] as $file ) {
			unlink( $file );
		}
	}
}
