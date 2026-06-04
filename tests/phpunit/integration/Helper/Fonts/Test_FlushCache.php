<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF\Tests\Integration\TestCase;

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
class Test_FlushCache extends TestCase {

	public function test_flush_cache() {
		$data = \GPDFAPI::get_data_class();
		wp_mkdir_p( $data->mpdf_tmp_location );

		$file = $data->mpdf_tmp_location . '/test';

		touch( $file );
		$this->assertFileExists( $file );

		FlushCache::flush();

		$this->assertFileDoesNotExist( $file );
	}
}
