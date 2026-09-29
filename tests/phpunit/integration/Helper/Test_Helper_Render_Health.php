<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 */
class Test_Helper_Render_Health extends TestCase {

	public function test_a_render_starts_healthy_and_can_be_marked() {
		Helper_Render_Health::begin();
		$this->assertFalse( Helper_Render_Health::is_degraded() );

		Helper_Render_Health::mark();
		$this->assertTrue( Helper_Render_Health::is_degraded() );
		$this->assertTrue( Helper_Render_Health::end() );

		Helper_Render_Health::begin();
		$this->assertFalse( Helper_Render_Health::end() );
	}

	public function test_a_nested_render_keeps_the_outer_flag() {
		Helper_Render_Health::begin();
		Helper_Render_Health::mark();

		Helper_Render_Health::begin();
		$this->assertFalse( Helper_Render_Health::is_degraded() );
		$this->assertFalse( Helper_Render_Health::end() );

		$this->assertTrue( Helper_Render_Health::end() );

		Helper_Render_Health::begin();

		Helper_Render_Health::begin();
		Helper_Render_Health::mark();
		$this->assertTrue( Helper_Render_Health::end() );

		$this->assertFalse( Helper_Render_Health::end() );
	}

	public function test_a_mark_outside_a_render_is_dropped() {
		Helper_Render_Health::mark();
		$this->assertFalse( Helper_Render_Health::is_degraded() );

		Helper_Render_Health::begin();
		$this->assertFalse( Helper_Render_Health::end() );
		$this->assertFalse( Helper_Render_Health::end(), 'Ending with no render open is harmless' );
	}
}
