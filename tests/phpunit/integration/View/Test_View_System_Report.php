<?php
declare( strict_types=1 );

namespace GFPDF\View;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_View_System_Report
 *
 * @package GFPDF\View
 *
 * @group   view
 * @group   system-report
 */
class Test_View_System_Report extends TestCase {
	/**
	 * @var View_System_Report
	 */
	protected $view;

	public function set_up(): void {
		parent::set_up();

		$this->view = new View_System_Report();
	}

	public function test_a_failing_check_shows_the_fix_on_screen_only() {
		$row = $this->view->get_check( [ 'label' => 'Check' ], 'check passed', $this->view->get_fix( 'Fix it <now>.', 'https://example.com/?a=1&b=2', 'How' ) );

		$this->assertFalse( $row['is_valid'] );
		$this->assertSame( 'Fix it &lt;now&gt;. <a href="https://example.com/?a=1&#038;b=2">How</a>', $row['validation_message'] );
		$this->assertSame( '', $row['validation_message_export'] );
	}

	public function test_a_passing_check_keeps_its_summary() {
		$row = $this->view->get_check( [ 'label' => 'Check' ], 'check <passed>' );

		$this->assertTrue( $row['is_valid'] );
		$this->assertSame( 'check &lt;passed&gt;', $row['summary'] );
		$this->assertArrayNotHasKey( 'validation_message', $row );
	}

	public function test_get_code_lines() {
		$this->assertSame( '<code>a.ttf</code><br><code>&lt;b&gt;.ttf</code>', $this->view->get_code_lines( [ 'a.ttf', '<b>.ttf' ] ) );
	}
}
