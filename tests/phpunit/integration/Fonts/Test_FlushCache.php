<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_FlushCache
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_FlushCache extends TestCase {

	/**
	 * @var string
	 */
	public $cache_dir;

	public function set_up(): void {
		parent::set_up();

		$this->cache_dir = FlushCache::get_font_cache_dir();

		wp_mkdir_p( $this->cache_dir );
	}

	protected function seed_cache( string ...$fontkeys ): void {
		foreach ( $fontkeys as $fontkey ) {
			foreach ( [ 'mtx.json', 'cw.dat', 'gid.dat' ] as $extension ) {
				file_put_contents( $this->cache_dir . $fontkey . '.' . $extension, 'cached' );
			}
		}
	}

	protected function cached( string $fontkey ): bool {
		return is_file( $this->cache_dir . $fontkey . '.mtx.json' );
	}

	public function test_flush_cache() {
		$data = \GPDFAPI::get_data_class();
		wp_mkdir_p( $data->mpdf_tmp_location );

		$file = $data->mpdf_tmp_location . '/test';

		touch( $file );
		$this->assertFileExists( $file );

		FlushCache::flush();

		$this->assertFileDoesNotExist( $file );
	}

	public function test_it_drops_every_face_of_one_key() {
		$this->seed_cache( 'lato', 'latoB', 'latoI', 'latoBI' );

		FlushCache::flush_font( 'lato' );

		foreach ( [ 'lato', 'latoB', 'latoI', 'latoBI' ] as $fontkey ) {
			$this->assertFalse( $this->cached( $fontkey ), $fontkey . ' should have been dropped' );
		}
	}

	/**
	 * The reason for four exact prefixes rather than one `lato*` glob
	 */
	public function test_it_leaves_a_key_that_merely_starts_the_same_alone() {
		$this->seed_cache( 'lato', 'latolight', 'latolightB' );

		FlushCache::flush_font( 'lato' );

		$this->assertFalse( $this->cached( 'lato' ) );
		$this->assertTrue( $this->cached( 'latolight' ) );
		$this->assertTrue( $this->cached( 'latolightB' ) );
	}

	/**
	 * mPDF caches under `{tempDir}/mpdf`, not at `tempDir` — a wrong directory here is silent, the glob simply
	 * matches nothing and a same-size swap keeps rendering the old face
	 */
	public function test_the_cache_directory_is_the_one_mpdf_actually_writes_to() {
		global $gfpdf;

		$this->assertSame(
			trailingslashit( $gfpdf->data->mpdf_tmp_location ) . 'mpdf/ttfontdata/',
			FlushCache::get_font_cache_dir()
		);
	}
}
