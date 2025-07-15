<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Mpdf;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 */
class Test_Cache extends WP_UnitTestCase {

	/**
	 * Verify the cache directory inherits the parent directory permissions
	 *
	 * @dataProvider provider_createDirectory
	 */
	public function test_createDirectory( $permission ) {
		$basepath = sys_get_temp_dir() . '/mpdf/';
		wp_mkdir_p( $basepath );
		chmod( $basepath, $permission );

		$tmp_basepath = $basepath . 'tmp/';
		@rmdir( $tmp_basepath );

		$cache = new Cache( $tmp_basepath );

		$tmp_permission = substr( decoct( fileperms( $tmp_basepath ) ), -3 );

		$this->assertSame( decoct( $permission ), $tmp_permission );
	}

	public function provider_createDirectory() {
		return [
			[ 0755 ],
			[ 0775 ],
			[ 0777 ],
		];
	}
}
