<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Url_Signer;
use GFPDF\Model\Model_PDF;
use GFPDF\Tests\Integration\TestCase;
use GFPDF\View\View_PDF;
use ReflectionMethod;

/**
 * Characterization tests for Controller_PDF — pin observable behaviour as a
 * safety net for the Phase 6 Model_PDF refactor. Note: end-to-end PDF
 * generation flows are already covered by Test_PDF and Test_Slow_PDF_Processes.
 *
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   pdf
 */
class Test_Controller_PDF extends TestCase {

	/**
	 * @var Controller_PDF
	 */
	private $controller;

	public function set_up() {
		global $gfpdf;

		parent::set_up();

		$model = new Model_PDF( $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->notices, $gfpdf->templates, new Helper_Url_Signer() );
		$view  = new View_PDF( [], $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );

		$this->controller = new Controller_PDF( $model, $view, $gfpdf->gform, $gfpdf->log, $gfpdf->misc );
	}

	public function tear_down() {
		unset(
			$GLOBALS['wp']->query_vars['gpdf'],
			$GLOBALS['wp']->query_vars['pid'],
			$GLOBALS['wp']->query_vars['lid'],
			$_GET['gf_pdf'],
			$_GET['fid'],
			$_GET['lid'],
			$_GET['template'],
			$_GET['html'],
			$_GET['raw']
		);

		parent::tear_down();
	}

	public function test_init_schedules_cleanup_cron_when_missing() {
		wp_clear_scheduled_hook( 'gfpdf_cleanup_tmp_dir' );

		$this->controller->init();

		$this->assertNotFalse( wp_next_scheduled( 'gfpdf_cleanup_tmp_dir' ) );

		wp_clear_scheduled_hook( 'gfpdf_cleanup_tmp_dir' );
	}

	public function test_init_does_not_double_schedule_when_already_present() {
		wp_clear_scheduled_hook( 'gfpdf_cleanup_tmp_dir' );
		wp_schedule_event( 1000, 'hourly', 'gfpdf_cleanup_tmp_dir' );
		$existing = wp_next_scheduled( 'gfpdf_cleanup_tmp_dir' );

		$this->controller->init();

		$this->assertSame( $existing, wp_next_scheduled( 'gfpdf_cleanup_tmp_dir' ) );

		wp_clear_scheduled_hook( 'gfpdf_cleanup_tmp_dir' );
	}

	public function test_init_registers_pdf_endpoint_and_middleware_hooks() {
		remove_all_actions( 'parse_request' );
		remove_all_filters( 'gfpdf_pdf_middleware' );
		remove_all_filters( 'gfpdf_pdf_html_output' );

		$this->controller->init();

		$this->assertNotFalse( has_action( 'parse_request', [ $this->controller, 'process_pdf_endpoint' ] ) );
		$this->assertNotFalse( has_action( 'parse_request', [ $this->controller, 'process_legacy_pdf_endpoint' ] ) );
		$this->assertNotFalse( has_filter( 'gfpdf_pdf_middleware' ) );
		$this->assertNotFalse( has_filter( 'gfpdf_pdf_html_output' ) );
	}

	public function test_add_pre_pdf_hooks_registers_kses_filters() {
		remove_all_filters( 'wp_kses_allowed_html' );
		remove_all_filters( 'safe_style_css' );

		$this->controller->add_pre_pdf_hooks();

		$this->assertNotFalse( has_filter( 'wp_kses_allowed_html' ) );
		$this->assertNotFalse( has_filter( 'safe_style_css' ) );
	}

	public function test_remove_pre_pdf_hooks_unregisters_kses_filters() {
		$this->controller->add_pre_pdf_hooks();
		$this->controller->remove_pre_pdf_hooks();

		$this->assertFalse( has_filter( 'wp_kses_allowed_html', [ $this->controller->view, 'allow_pdf_html' ] ) );
		$this->assertFalse( has_filter( 'safe_style_css', [ $this->controller->view, 'allow_pdf_css' ] ) );
	}

	public function test_prevent_index_defines_donotcachepage_constant() {
		$this->controller->prevent_index();

		$this->assertTrue( defined( 'DONOTCACHEPAGE' ) );
		$this->assertTrue( DONOTCACHEPAGE );
	}

	public function test_process_pdf_endpoint_returns_silently_without_query_vars() {
		$GLOBALS['wp']->query_vars = [];

		$this->controller->process_pdf_endpoint();

		$this->assertTrue( true );
	}

	public function test_process_legacy_pdf_endpoint_returns_silently_without_legacy_params() {
		$_GET = [];

		$this->controller->process_legacy_pdf_endpoint();

		$this->assertTrue( true );
	}

	public function test_sgoptimizer_html_minification_fix_emits_doing_it_wrong() {
		$this->setExpectedIncorrectUsage( 'GFPDF\Controller\Controller_PDF::sgoptimizer_html_minification_fix' );

		$this->controller->sgoptimizer_html_minification_fix();
	}

	public function test_add_view_html_debugger_passes_through_non_string_input() {
		$result = $this->invoke_protected( 'add_view_html_debugger', [ null, [], [], [], null ] );

		$this->assertNull( $result );
	}

	public function test_add_view_html_debugger_passes_through_when_html_param_absent() {
		unset( $_GET['html'] );

		$result = $this->invoke_protected( 'add_view_html_debugger', [ '<p>original</p>', [], [], [], null ] );

		$this->assertSame( '<p>original</p>', $result );
	}

	public function test_included_nested_forms_in_cache_hash_returns_data_when_entry_id_missing() {
		$result = $this->invoke_protected( 'included_nested_forms_in_cache_hash', [ [ 'foo' => 'bar' ], [], [], [] ] );

		$this->assertSame( [ 'foo' => 'bar' ], $result );
	}

	public function test_included_nested_forms_in_cache_hash_returns_data_when_gpnf_entry_class_missing() {
		if ( class_exists( '\GPNF_Entry' ) ) {
			$this->markTestSkipped( 'GPNF_Entry available, cannot exercise the missing-class branch.' );
		}

		$result = $this->invoke_protected( 'included_nested_forms_in_cache_hash', [ [ 'foo' => 'bar' ], [], [ 'id' => 1 ], [] ] );

		$this->assertSame( [ 'foo' => 'bar' ], $result );
	}

	public function test_add_current_form_object_hooks_returns_form_unchanged_when_id_missing() {
		$result = $this->invoke_protected( 'add_current_form_object_hooks', [ [ 'fields' => [] ], [], 'source' ] );

		$this->assertSame( [ 'fields' => [] ], $result );
	}

	private function invoke_protected( string $method, array $args ) {
		return ( new ReflectionMethod( $this->controller, $method ) )->invokeArgs( $this->controller, $args );
	}
}
