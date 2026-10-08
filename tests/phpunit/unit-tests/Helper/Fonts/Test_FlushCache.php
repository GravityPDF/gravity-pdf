<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_FlushCache
 *
 * @package GFPDF\Helper\Fonts
 *
 * @group   helper
 * @group   fonts
 */
class Test_FlushCache extends WP_UnitTestCase {

	public function test_flush_cache() {
		$data  = \GPDFAPI::get_data_class();
		$cache = $data->mpdf_tmp_location . '/mpdf';

		wp_mkdir_p( $cache . '/ttfontdata' );
		touch( $cache . '/test' );
		touch( $cache . '/ttfontdata/dejavusans.mtx.json' );

		/* Shares mPDF's tempDir, but isn't mPDF's cache */
		$pdf = $data->template_tmp_location . 'flush-cache-test.pdf';
		touch( $pdf );

		FlushCache::flush();

		/* A PDF being generated keeps its temp files */
		$this->assertFileExists( $cache . '/test' );
		$this->assertFileDoesNotExist( $cache . '/ttfontdata/dejavusans.mtx.json' );
		$this->assertDirectoryExists( $cache . '/ttfontdata' );
		$this->assertFileExists( $pdf );

		unlink( $pdf );
		unlink( $cache . '/test' );
	}

	/**
	 * A font change only deletes that font's data, so other fonts don't need rebuilding
	 *
	 * @since 6.17.3
	 */
	public function test_flush_cache_for_one_font() {
		$font_data = \GPDFAPI::get_data_class()->mpdf_tmp_location . '/mpdf/ttfontdata/';
		wp_mkdir_p( $font_data );

		$deleted = [ 'roboto.mtx.json', 'robotoB.cw.dat', 'robotoI.gid.dat', 'robotoBI.GSUBGPOStables.dat', 'roboto.GSUB.arab.DFLT.json' ];
		$kept    = [ 'robotomono.mtx.json', 'robotomonoB.cw.dat', 'dejavusans.mtx.json', 'roboto' ];

		foreach ( array_merge( $deleted, $kept ) as $file ) {
			touch( $font_data . $file );
		}

		FlushCache::flush( 'roboto' );

		foreach ( $deleted as $file ) {
			$this->assertFileDoesNotExist( $font_data . $file );
		}

		foreach ( $kept as $file ) {
			$this->assertFileExists( $font_data . $file );
			unlink( $font_data . $file );
		}
	}
}
