<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package GFPDF\Statics
 *
 * @group   statics
 */
class Test_Debug extends TestCase {

	/**
	 * Test env defines WP_ENVIRONMENT_TYPE='local', so the !production branch
	 * fires regardless of the debug_mode option — both code paths land here.
	 */
	public function test_is_enabled_in_non_production_environment() {
		global $gfpdf;

		$gfpdf->options->update_option( 'debug_mode', 'No' );
		$this->assertTrue( Debug::is_enabled(), 'non-production env should enable debug regardless of option' );

		$gfpdf->options->update_option( 'debug_mode', 'Yes' );
		$this->assertTrue( Debug::is_enabled(), 'explicit Yes should also enable debug' );
	}

	public function test_can_view_requires_logging_capability() {
		$admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );
		$this->assertTrue( Debug::can_view() );

		$subscriber = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber );
		$this->assertFalse( Debug::can_view() );
	}

	public function test_is_enabled_and_can_view_requires_both() {
		$admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );
		$this->assertTrue( Debug::is_enabled_and_can_view() );

		$subscriber = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber );
		$this->assertFalse( Debug::is_enabled_and_can_view() );
	}
}
