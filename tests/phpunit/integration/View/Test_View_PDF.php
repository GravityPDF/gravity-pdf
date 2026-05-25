<?php

declare( strict_types=1 );

namespace GFPDF\View;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group   view
 * @group   pdf
 */
class Test_View_PDF extends TestCase {

	/** @var View_PDF */
	private $view;

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ] );
	}

	public function set_up() {
		global $gfpdf;

		parent::set_up();

		$this->view = new View_PDF(
			[],
			$gfpdf->gform,
			$gfpdf->log,
			$gfpdf->options,
			$gfpdf->data,
			$gfpdf->misc,
			$gfpdf->templates
		);
	}

	public function tear_down() {
		unset( $_GET['data'] );
		remove_all_filters( 'gfpdf_pdf_form_title_html' );
		remove_all_filters( 'gfpdf_current_form_object' );
		parent::tear_down();
	}

	public function test_maybe_view_form_data_returns_false_without_data_query_param() {
		$this->assertFalse( $this->view->maybe_view_form_data() );
	}

	public function test_allow_pdf_html_returns_merged_tag_allowlist() {
		$result = $this->view->allow_pdf_html( [] );

		$this->assertArrayHasKey( 'form', $result );
		$this->assertArrayHasKey( 'input', $result );
	}

	public function test_allow_pdf_css_returns_merged_style_allowlist() {
		$result = $this->view->allow_pdf_css( [ 'caller-supplied' => true ] );

		$this->assertContains( 'page-break-before', $result );
		$this->assertContains( 'box-shadow', $result );
		$this->assertArrayHasKey( 'caller-supplied', $result );
	}

	public function test_autoprocess_core_template_options_returns_unchanged_for_advanced_template() {
		$html     = '<p>original</p>';
		$entry    = [ 'form_id' => 0 ];
		$settings = [
			'template'          => 'zadani',
			'advanced_template' => 'Yes',
		];

		$this->assertSame( $html, $this->view->autoprocess_core_template_options( $html, [], $entry, $settings ) );
	}

	public function test_autoprocess_core_template_options_prepends_styles_for_standard_template() {
		$form     = $this->form( 'all-form-fields' );
		$html     = '<p>original</p>';
		$entry    = [ 'form_id' => $form['id'] ];
		$settings = [ 'template' => 'zadani' ];

		$result = $this->view->autoprocess_core_template_options( $html, $form, $entry, $settings );

		$this->assertStringEndsWith( $html, $result );
		$this->assertStringContainsString( '<style>', $result );
	}

	public function test_show_form_title_outputs_nothing_when_disabled() {
		ob_start();
		$this->view->show_form_title( false, [ 'title' => 'My Form' ] );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_show_form_title_outputs_filtered_html_when_enabled() {
		add_filter(
			'gfpdf_pdf_form_title_html',
			static fn() => '<h1 data-test="title">FILTERED</h1>'
		);

		ob_start();
		$this->view->show_form_title( true, [ 'title' => 'My Form' ] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'FILTERED', $output );
	}
}
