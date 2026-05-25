<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Mpdf;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   debug
 */
class Test_Controller_Debug extends TestCase {

	/**
	 * @var Controller_Debug
	 */
	private $controller;

	public function set_up() {
		global $gfpdf;

		parent::set_up();

		$this->controller = new Controller_Debug( $gfpdf->data, $gfpdf->options, $gfpdf->templates );
	}

	public function test_init_registers_hooks() {
		remove_all_actions( 'update_option_gfpdf_settings' );
		remove_all_filters( 'gfpdf_mpdf_class' );

		$this->controller->init();

		$this->assertNotFalse( has_action( 'update_option_gfpdf_settings', [ $this->controller, 'maybe_flush_transient_cache' ] ) );
		$this->assertNotFalse( has_filter( 'gfpdf_mpdf_class', [ $this->controller, 'maybe_add_pdf_stats' ] ) );
	}

	public function test_maybe_flush_transient_cache_flushes_when_debug_mode_toggled_on() {
		global $gfpdf;

		set_transient( $gfpdf->data->template_transient_cache, 'cached', HOUR_IN_SECONDS );

		$this->controller->maybe_flush_transient_cache( [ 'debug_mode' => 'No' ], [ 'debug_mode' => 'Yes' ] );

		$this->assertFalse( get_transient( $gfpdf->data->template_transient_cache ) );
	}

	public function test_maybe_flush_transient_cache_skips_when_already_enabled() {
		global $gfpdf;

		set_transient( $gfpdf->data->template_transient_cache, 'cached', HOUR_IN_SECONDS );

		$this->controller->maybe_flush_transient_cache( [ 'debug_mode' => 'Yes' ], [ 'debug_mode' => 'Yes' ] );

		$this->assertSame( 'cached', get_transient( $gfpdf->data->template_transient_cache ) );
	}

	public function test_maybe_flush_transient_cache_skips_when_debug_mode_absent() {
		global $gfpdf;

		set_transient( $gfpdf->data->template_transient_cache, 'cached', HOUR_IN_SECONDS );

		$this->controller->maybe_flush_transient_cache( [], [] );

		$this->assertSame( 'cached', get_transient( $gfpdf->data->template_transient_cache ) );
	}

	public function test_maybe_add_pdf_stats_appends_stats_when_debug_mode_on() {
		global $gfpdf;

		$gfpdf->options->update_option( 'debug_mode', 'Yes' );

		$mpdf = new Helper_Mpdf( [ 'mode' => 'c', 'tempDir' => sys_get_temp_dir() ] );
		$this->controller->maybe_add_pdf_stats( $mpdf );

		$output = $mpdf->Output( '', 'S' );
		$this->assertNotEmpty( $output );
	}

	public function test_maybe_add_pdf_stats_returns_mpdf_unchanged_when_debug_mode_off() {
		global $gfpdf;

		$gfpdf->options->update_option( 'debug_mode', 'No' );

		$mpdf   = new Helper_Mpdf( [ 'mode' => 'c', 'tempDir' => sys_get_temp_dir() ] );
		$result = $this->controller->maybe_add_pdf_stats( $mpdf );

		$this->assertSame( $mpdf, $result );
	}
}
