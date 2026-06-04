<?php

declare( strict_types=1 );

namespace GFPDF\View;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group   view
 * @group   uninstaller
 */
class Test_View_Uninstaller extends TestCase {

	public function test_uninstall_button_template_renders_form_and_button_for_authorised_args(): void {
		$view = new View_Uninstaller();

		ob_start();
		$result = $view->uninstall_button(
			[
				'icon'  => '<i class="dashicons dashicons-admin-generic"></i>',
				'title' => 'Gravity PDF',
			]
		);
		$output = ob_get_clean();

		$this->assertTrue( $result );
		$this->assertStringContainsString( 'gform-settings-panel__addon-uninstall', $output );
		$this->assertStringContainsString( 'name="uninstall_addon"', $output );
		$this->assertStringContainsString( 'Gravity PDF', $output );
		$this->assertStringContainsString( 'name="gf_addon_uninstall"', $output );
	}
}
