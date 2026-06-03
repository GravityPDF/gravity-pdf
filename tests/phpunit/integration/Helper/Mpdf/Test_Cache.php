<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Mpdf;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 */
class Test_Cache extends TestCase {

	/**
	 * Verify the cache directory inherits the parent directory permissions
	 *
	 * @dataProvider provider_createDirectory
	 */
	public function test_createDirectory( $permission ) {
		$basepath     = sys_get_temp_dir() . '/mpdf-cache/';
		$tmp_basepath = $basepath . 'tmp/';

		/* ensure we have a clean slate */
		@rmdir( $tmp_basepath );
		@rmdir( $basepath );

		/* Create the base directory with the correct permissions */
		mkdir( $basepath );
		chmod( $basepath, $permission );

		new Cache( $tmp_basepath );

		$base_permission = substr( decoct( fileperms( $basepath ) ), -3 );
		$tmp_permission = substr( decoct( fileperms( $tmp_basepath ) ), -3 );

		$this->assertSame( decoct( $permission ), $base_permission );
		$this->assertSame( decoct( $permission ), $tmp_permission );
	}

	public function provider_createDirectory(): array {
		return [
			[ 0755 ],
			[ 0775 ],
			[ 0777 ],
		];
	}
}
