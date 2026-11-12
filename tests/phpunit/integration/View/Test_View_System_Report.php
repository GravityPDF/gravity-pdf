<?php
declare( strict_types=1 );

namespace GFPDF\View;

use GFPDF\Model\Model_Hook_Listener_Sources;
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

	public function test_memory_limit_success() {
		$this->assertStringContainsString( 'Unlimited', $this->view->memory_limit_markup( -1 ) );
		$this->assertStringContainsString( '128MB', $this->view->memory_limit_markup( 128 ) );

		$output = $this->view->memory_limit_markup( 96 );

		$this->assertStringContainsString( '96MB', $output );
		$this->assertStringContainsString( 'you have at least 128MB', $output );
	}

	public function test_temp_folder_protected_success() {
		$this->assertStringNotContainsString( 'temporary directory is publicly accessible', $this->view->get_temp_folder_protected( true ) );
		$this->assertStringContainsString( 'temporary directory is publicly accessible', $this->view->get_temp_folder_protected( false ) );
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

	public function test_deprecated_hook_listeners_are_named_by_the_injected_lookup() {
		$view = new View_System_Report( [], new Stub_Hook_Listener_Sources() );

		add_filter( 'gfpdf_settings_tools', '__return_empty_array' );
		add_filter( 'gfpdf_settings_tools', 'strtolower' );
		add_filter( 'gfpdf_post_tools_settings_page', '__return_empty_array' );

		$detail = $view->get_deprecated_feature_detail(
			'tools_tab_hooks',
			[
				'gfpdf_post_tools_settings_page' => 1,
				'gfpdf_settings_tools'           => 2,
			]
		);

		$this->assertSame( 'gfpdf_post_tools_settings_page has 1 listener (<My> Add-on), gfpdf_settings_tools has 2 listeners (<My> Add-on, custom code)', $detail['value_export'] );
		$this->assertStringContainsString( '(&lt;My&gt; Add-on, custom code)', $detail['value'] );
	}
}

/**
 * Names WordPress' own functions after an add-on, and leaves PHP's untraced
 */
class Stub_Hook_Listener_Sources extends Model_Hook_Listener_Sources {
	public function get_source( $callback ): string {
		return $callback === '__return_empty_array' ? '<My> Add-on' : '';
	}

}
