<?php

declare( strict_types=1 );

namespace GFPDF\View;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group   view
 * @group   actions
 */
class Test_View_Actions extends TestCase {

	private View_Actions $view;

	public function set_up(): void {
		parent::set_up();
		$this->view = new View_Actions();
	}

	public function test_get_action_buttons_renders_button_text_and_action_type() {
		$html = $this->view->get_action_buttons( 'install_fonts', 'Install Fonts' );

		$this->assertStringContainsString( 'Install Fonts', $html );
		$this->assertStringContainsString( 'value="gfpdf_install_fonts"', $html );
		$this->assertStringContainsString( 'Dismiss Notice', $html );
	}

	public function test_get_action_buttons_omits_dismiss_when_disabled() {
		$html = $this->view->get_action_buttons( 'install_fonts', 'Install Fonts', 'disabled' );

		$this->assertStringContainsString( 'Install Fonts', $html );
		$this->assertStringNotContainsString( 'Dismiss Notice', $html );
	}

	public function test_deprecated_features_lists_each_one_and_links_to_its_upgrade_guide() {
		$html = $this->view->deprecated_features( [ 'legacy_templates', 'legacy_endpoint' ], 'deprecated_features', 'View the system report' );

		$this->assertStringContainsString( 'Support for Legacy Templates will be removed in Gravity PDF 7.0.', $html );
		$this->assertStringContainsString( 'upgrade/legacy-templates/', $html );

		$this->assertStringContainsString( 'Support for legacy download URLs will be removed in Gravity PDF 7.0.', $html );
		$this->assertStringContainsString( 'upgrade/legacy-download-urls/', $html );

		/* The notice can be acted on or dismissed, and one dismissal covers everything it listed */
		$this->assertStringContainsString( 'View the system report', $html );
		$this->assertStringContainsString( 'value="gfpdf_deprecated_features"', $html );
		$this->assertStringContainsString( 'Dismiss Notice', $html );
	}

	public function test_core_font_concatenates_notice_and_disabled_buttons() {
		$html = $this->view->core_font( 'install_core_fonts', 'Install Now' );

		$this->assertStringContainsString( 'Core PDF fonts', $html );
		$this->assertStringContainsString( 'Install Now', $html );
		$this->assertStringNotContainsString( 'Dismiss Notice', $html );
		$this->assertLessThan(
			strpos( $html, 'Install Now' ),
			strpos( $html, 'Core PDF fonts' ),
			'Notice should appear before the action buttons'
		);
	}
}
