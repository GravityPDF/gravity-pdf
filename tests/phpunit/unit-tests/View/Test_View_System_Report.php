<?php
declare( strict_types=1 );

namespace GFPDF\View;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
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
class Test_View_System_Report extends WP_UnitTestCase {
	/**
	 * @var View_System_Report
	 */
	protected $view;

	public function set_up() {
		parent::set_up();

		$this->view = new View_System_Report();
	}

	public function test_memory_limit_success() {
		$this->assertStringContainsString( 'Unlimited', $this->view->memory_limit_markup( -1 ) );
		$this->assertStringContainsString( '128MB', $this->view->memory_limit_markup( 128 ) );

		$output = $this->view->memory_limit_markup( 96 );

		$this->assertStringContainsString( '96MB', $output );
		$this->assertStringContainsString( 'you have at least 128MB', $output );
	}

	public function test_temp_folder_protected_success() {
		$this->assertStringNotContainsString( 'be publicly accessed', $this->view->get_temp_folder_protected( true ) );
		$this->assertStringContainsString( 'be publicly accessed', $this->view->get_temp_folder_protected( false ) );
	}

	public function test_allow_url_fopen_success() {
		$this->assertStringNotContainsString( 'is disabled', $this->view->get_allow_url_fopen( true ) );
		$this->assertStringContainsString( 'is disabled', $this->view->get_allow_url_fopen( false ) );
	}

	public function test_get_icon() {
		$this->assertStringContainsString( 'yes', $this->view->get_icon( true ) );
		$this->assertStringContainsString( 'error', $this->view->get_icon( false ) );

	}

	public function test_maybe_get_active_icon() {
		$this->assertStringContainsString( 'yes', $this->view->maybe_get_active_icon( true ) );
		$this->assertEmpty( $this->view->maybe_get_active_icon( false ) );

	}

}
