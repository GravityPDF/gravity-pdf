<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fonts
 */
class Test_SupportsOtl extends TestCase {

	public function test_supports_otl_returns_false_for_non_ttf_content(): void {
		$tmp_dir = sys_get_temp_dir() . '/';
		$fake    = $tmp_dir . 'fake_font.ttf';
		file_put_contents( $fake, 'this is not a TTF file' );

		$checker = new SupportsOtl( $tmp_dir );
		$result  = $checker->supports_otl( 'fake_font.ttf' );

		$this->assertFalse( $result );

		unlink( $fake );
	}

	/**
	 * SupportsOtl::supports_otl() returns true for a known-good TTF.
	 *
	 * Uses the TTF bundled with Gravity Forms inside the test vendor directory.
	 */
	public function test_supports_otl_returns_true_for_valid_ttf(): void {
		$wp_phpunit_font = ABSPATH . '../vendor/wp-phpunit/wp-phpunit/data/fonts/';
		$font_file       = 'OpenSans-Regular.ttf';

		if ( ! file_exists( $wp_phpunit_font . $font_file ) ) {
			$this->markTestSkipped( 'OpenSans-Regular.ttf test fixture not present; cannot exercise the truthy path.' );
		}

		$checker = new SupportsOtl( $wp_phpunit_font );
		$this->assertTrue( $checker->supports_otl( $font_file ) );
	}
}
