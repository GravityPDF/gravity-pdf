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
 * @group   helper
 * @group   fonts
 */
class Test_SupportsOtl extends WP_UnitTestCase {

	public function test_supports_otl_returns_false_for_non_ttf_content(): void {
		$tmp_dir = sys_get_temp_dir() . '/';
		$fake    = $tmp_dir . 'fake_font.ttf';
		file_put_contents( $fake, 'this is not a TTF file' );

		$checker = new SupportsOtl( $tmp_dir );
		$result  = $checker->supports_otl( 'fake_font.ttf' );

		$this->assertFalse( $result );

		unlink( $fake );
	}

	public function test_supports_otl_returns_true_for_valid_ttf(): void {
		$checker = new SupportsOtl( dirname( __DIR__, 5 ) . '/tests/e2e/utilities/resources/' );
		$this->assertTrue( $checker->supports_otl( 'Roboto-Regular.ttf' ) );
	}
}
