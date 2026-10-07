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

		$this->assertFileDoesNotExist( $cache . '/test' );
		$this->assertFileDoesNotExist( $cache . '/ttfontdata/dejavusans.mtx.json' );
		$this->assertDirectoryExists( $cache . '/ttfontdata' );
		$this->assertFileExists( $pdf );

		unlink( $pdf );
	}
}
