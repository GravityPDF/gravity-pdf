<?php

declare( strict_types=1 );

namespace GFPDF\View;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group   view
 * @group   shortcodes
 */
class Test_View_Shortcodes extends TestCase {

	private View_Shortcodes $view;

	public function set_up(): void {
		parent::set_up();
		$this->view = new View_Shortcodes();
	}

	public function test_no_entry_id_renders_admin_error_message() {
		$html = $this->view->no_entry_id();

		$this->assertStringContainsString( 'No Gravity Form entry ID passed to Gravity PDF', $html );
		$this->assertStringContainsString( 'gravitypdf-error', $html );
		$this->assertStringContainsString( '(Admin Only Message)', $html );
	}

	public function test_invalid_pdf_config_renders_admin_error_message() {
		$html = $this->view->invalid_pdf_config();

		$this->assertStringContainsString( 'Could not get Gravity PDF configuration', $html );
		$this->assertStringContainsString( 'gravitypdf-error', $html );
	}

	public function test_pdf_not_active_renders_admin_error_message() {
		$html = $this->view->pdf_not_active();

		$this->assertStringContainsString( 'PDF link not displayed because PDF is inactive', $html );
		$this->assertStringContainsString( 'gravitypdf-error', $html );
	}

	public function test_conditional_logic_not_met_renders_admin_error_message() {
		$html = $this->view->conditional_logic_not_met();

		$this->assertStringContainsString( 'conditional logic requirements have not been met', $html );
		$this->assertStringContainsString( 'gravitypdf-error', $html );
	}

	public function test_display_gravitypdf_shortcode_emits_anchor_with_attributes() {
		$html = $this->view->display_gravitypdf_shortcode(
			[
				'url'     => 'https://example.test/pdf/abc/',
				'class'   => 'pdf-link',
				'classes' => 'btn primary',
				'type'    => 'download',
				'text'    => 'Download PDF',
			]
		);

		$this->assertStringContainsString( 'href="https://example.test/pdf/abc/"', $html );
		$this->assertStringContainsString( 'pdf-link', $html );
		$this->assertStringContainsString( 'btn primary', $html );
		$this->assertStringContainsString( 'Download PDF', $html );
		$this->assertStringContainsString( 'rel="nofollow"', $html );
		$this->assertStringNotContainsString( 'target="_blank"', $html );
	}

	public function test_display_gravitypdf_shortcode_targets_blank_for_view_type() {
		$html = $this->view->display_gravitypdf_shortcode(
			[
				'url'     => 'https://example.test/pdf/abc/',
				'class'   => 'pdf-link',
				'classes' => '',
				'type'    => 'view',
				'text'    => 'View PDF',
			]
		);

		$this->assertStringContainsString( 'target="_blank"', $html );
	}
}
