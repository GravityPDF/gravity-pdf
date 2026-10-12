<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group  statics
 * @since  6.18.0
 */
class Test_Upload_Size extends TestCase {

	/**
	 * @dataProvider provider_calculate_php_limit
	 *
	 * @param string|false $upload_max_filesize
	 * @param string|false $post_max_size
	 */
	public function test_calculate_php_limit( $upload_max_filesize, $post_max_size, int $expected ) {
		$this->assertSame( $expected, Upload_Size::calculate_php_limit( $upload_max_filesize, $post_max_size ) );
	}

	public function provider_calculate_php_limit(): array {
		return [
			'upload_max_filesize is lower' => [ '2M', '8M', 2 * MB_IN_BYTES ],
			'post_max_size is lower'       => [ '64M', '16M', 16 * MB_IN_BYTES ],
			'gigabytes'                    => [ '2G', '1G', GB_IN_BYTES ],
			'no PHP limits'                => [ '0', '0', 0 ],
			'no post_max_size limit'       => [ '2M', '0', 2 * MB_IN_BYTES ],
			'no upload_max_filesize limit' => [ '0', '8M', 8 * MB_IN_BYTES ],
			'ini_get() failed'             => [ false, false, 0 ],
		];
	}
}
