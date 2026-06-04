<?php

declare( strict_types=1 );

namespace GFPDF\View;

use Exception;
use GFPDF\Controller\Controller_PDF;
use GFPDF\Helper\Helper_Url_Signer;
use GFPDF\Model\Model_PDF;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   view
 * @group   pdf
 */
class Test_View_PDF extends TestCase {

	private View_PDF $view;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
		static::copy_test_fonts();
	}

	public static function tear_down_after_class(): void {
		static::remove_test_fonts();
		parent::tear_down_after_class();
	}

	public function set_up(): void {
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

	public function tear_down(): void {
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

	/**
	 * Test that we can successfully generate a PDF based on an entry and settings
	 *
	 * @since 4.0
	 *
	 * @group slow
	 */
	public function test_generate_pdf() {
		$this->setExpectedIncorrectUsage( 'GFPDF\View\View_PDF::generate_pdf' );

		global $gfpdf;

		/* generate_pdf() walks back via getController()->model, so wire one in for this test only. */
		$model = new Model_PDF( $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->notices, $gfpdf->templates, new Helper_Url_Signer() );
		new Controller_PDF( $model, $this->view, $gfpdf->gform, $gfpdf->log, $gfpdf->misc );

		/* Setup our form and entries */
		$results = $this->form_and_entry();
		$entry   = $results['entry'];
		$fid     = $results['form']['id'];
		$pid     = '555ad84787d7e';

		/* Get our PDF */
		$pdf = $gfpdf->options->get_pdf( $fid, $pid );

		/* Fix our template */
		$pdf['template'] = 'zadani';

		/* Add filters to force the PDF to throw and error */
		add_filter(
			'mpdf_output_destination',
			function () {
				return 'O';
			}
		);

		/* generate_pdf() drains all output buffers before streaming; restore PHPUnit's depth so beStrictAboutOutputDuringTests doesn't flag the test risky. */
		$initial_ob_level = ob_get_level();

		$caught = null;
		try {
			$this->view->generate_pdf( $entry, $pdf );
		} catch ( Exception $e ) {
			$caught = $e;
		}

		while ( ob_get_level() < $initial_ob_level ) {
			ob_start();
		}

		$this->assertNotNull( $caught, 'Expected Exception on PDF generation failure was not thrown.' );
		$this->assertSame( 'There was a problem generating your PDF', $caught->getMessage() );
	}
}
