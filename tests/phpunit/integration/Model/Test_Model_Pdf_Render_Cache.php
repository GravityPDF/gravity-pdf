<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use Exception;
use GFPDF\Helper\Helper_PDF;
use GFPDF\Helper\Helper_Render_Health;
use GFPDF\Helper\Helper_Url_Signer;
use GFPDF\Statics\Cache;
use GFPDF\Tests\Concerns\SpawnsPhpProcesses;
use GFPDF\Tests\Concerns\UsesLockableTmpLocation;
use GFPDF\Tests\Integration\TestCase;
use WP_Error;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * How process_and_save_pdf() shares one render between concurrent requests, and keeps degraded renders out of the cache
 *
 * @group model
 * @group pdf
 * @group slow
 */
class Test_Model_Pdf_Render_Cache extends TestCase {

	use SpawnsPhpProcesses;
	use UsesLockableTmpLocation;

	const REMOTE_IMAGE = 'https://1.1.1.1/gfpdf-render-cache.png';

	/** @var Model_PDF */
	private $model;

	/** @var int */
	private $renders = 0;

	/** @var callable[] */
	private $image_filters = [];

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

		$this->use_lockable_tmp_location();

		$this->model = new Model_PDF( $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->notices, $gfpdf->templates, new Helper_Url_Signer() );

		add_action(
			'gfpdf_pre_pdf_generation',
			function () {
				$this->renders++;
			}
		);
	}

	public function tear_down(): void {
		$this->restore_tmp_location();

		parent::tear_down();
	}

	public function test_a_miss_waits_for_the_render_another_process_is_making() {
		$pdf  = $this->generator();
		$file = $pdf->get_full_pdf_path();

		$lock_file = Cache::get_lock_file( $pdf->get_path() );
		wp_mkdir_p( $pdf->get_path() );

		$marker = $lock_file . '.held';
		$script = '$f = fopen( $argv[1], "c" ); flock( $f, LOCK_EX ); touch( $argv[3] ); usleep( $argv[2] * 1000 );'
			. '$tmp = $argv[4] . ".tmp"; file_put_contents( $tmp, "%PDF-1.4 rendered by another process" );'
			. 'rename( $tmp, $argv[4] ); flock( $f, LOCK_UN );';

		$process = $this->spawn_php( $script, [ $lock_file, '500', $marker, $file ] );
		$this->wait_for_file( $marker );

		$started = microtime( true );
		$saved   = $this->model->process_and_save_pdf( $pdf );
		$waited  = microtime( true ) - $started;

		proc_close( $process );

		$this->assertTrue( $saved );
		$this->assertSame( 0, $this->renders, 'The waiting request should use the other process\'s render' );
		$this->assertSame( 'hit', $this->model->get_cache_status( $file ) );
		$this->assertSame( '%PDF-1.4 rendered by another process', file_get_contents( $file ) );
		$this->assertGreaterThan( 0.3, $waited );
		$this->assertLessThan( 10, $waited );
	}

	public function test_a_timed_out_wait_renders_unprotected() {
		$pdf  = $this->generator();
		$lock = Cache::lock( $pdf->get_path() );

		add_filter(
			'gfpdf_cache_lock_wait',
			function () {
				return 0.2;
			}
		);

		try {
			$this->assertTrue( $this->model->process_and_save_pdf( $pdf ) );
		} finally {
			Cache::unlock( $lock );
		}

		$this->assertSame( 1, $this->renders );
		$this->assertSame( 'miss', $this->model->get_cache_status( $pdf->get_full_pdf_path() ) );
	}

	public function test_a_bypass_render_does_not_wait_for_the_lock() {
		$pdf  = $this->generator();
		$lock = Cache::lock( $pdf->get_path() );

		add_filter(
			'gfpdf_cache_lock_wait',
			function () {
				return 60;
			}
		);
		add_filter( 'gfpdf_override_pdf_bypass', '__return_true' );

		$started = microtime( true );

		try {
			$this->assertTrue( $this->model->process_and_save_pdf( $pdf ) );
		} finally {
			Cache::unlock( $lock );
		}

		$this->assertLessThan( 30, microtime( true ) - $started );
		$this->assertSame( 'bypass', $this->model->get_cache_status( $pdf->get_full_pdf_path() ) );
	}

	public function test_the_lock_is_released_when_the_render_throws() {
		$pdf = $this->generator();

		add_action(
			'gfpdf_pre_pdf_generation',
			function () {
				throw new Exception( 'A listener failed' );
			}
		);

		try {
			$this->model->process_and_save_pdf( $pdf );
			$this->fail( 'The exception should reach the caller' );
		} catch ( Exception $e ) {
			$this->assertSame( 'A listener failed', $e->getMessage() );
		}

		add_filter( 'gfpdf_cache_lock_wait', '__return_zero' );

		$lock = Cache::lock( $pdf->get_path() );
		$this->assertIsResource( $lock );
		Cache::unlock( $lock );

		$this->assertFalse( Helper_Render_Health::is_degraded() );
	}

	public function test_a_degraded_render_is_served_but_not_cached() {
		$this->add_remote_image( new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ) );

		$pdf        = $this->generator();
		$cache_file = $pdf->get_full_pdf_path();

		$this->assertTrue( $this->model->process_and_save_pdf( $pdf ) );

		$served = $pdf->get_full_pdf_path();
		$this->assertStringContainsString( '/uncached/', $served );
		$this->assertFileExists( $served );
		$this->assertFileDoesNotExist( $cache_file );
		$this->assertSame( 'degraded', $this->model->get_cache_status( $served ) );

		/* The next request renders again, and caches once the image loads */
		$this->add_remote_image( $this->answer( 404 ) );

		$next = $this->generator( $pdf->get_settings() );
		$this->assertSame( $cache_file, $next->get_full_pdf_path() );
		$this->assertTrue( $this->model->process_and_save_pdf( $next ) );

		$this->assertSame( 2, $this->renders );
		$this->assertSame( 'miss', $this->model->get_cache_status( $cache_file ) );
		$this->assertFileExists( $cache_file );
	}

	public function test_the_degraded_filter_keeps_a_render_out_of_the_cache() {
		add_filter( 'gfpdf_render_degraded', '__return_true' );

		$pdf = $this->generator();
		$this->assertTrue( $this->model->process_and_save_pdf( $pdf ) );
		$this->assertStringContainsString( '/uncached/', $pdf->get_full_pdf_path() );

		/* An explicit path is written where the caller asked */
		$explicit = $this->generator();
		$explicit->set_path( $this->gfpdf()->data->template_tmp_location . 'explicit-' . uniqid() );

		$path = $explicit->get_full_pdf_path();
		$this->assertTrue( $this->model->process_and_save_pdf( $explicit ) );
		$this->assertSame( $path, $explicit->get_full_pdf_path() );
		$this->assertSame( 'miss', $this->model->get_cache_status( $path ) );
	}

	public function test_with_the_cache_off_every_request_renders_to_a_one_off_path() {
		$cached     = $this->generator();
		$cache_file = $cached->get_full_pdf_path();
		wp_mkdir_p( $cached->get_path() );
		file_put_contents( $cache_file, '%PDF-1.4 cached before the cache was turned off' );

		add_filter( 'gfpdf_enable_pdf_cache', '__return_false' );

		$pdf = $this->generator( $cached->get_settings() );
		$this->assertFalse( $pdf->is_cache_path() );
		$this->assertTrue( $this->model->process_and_save_pdf( $pdf ) );

		$served = $pdf->get_full_pdf_path();
		$this->assertStringContainsString( '/uncached/', $served );
		$this->assertSame( 1, $this->renders );
		$this->assertSame( 'disabled', $this->model->get_cache_status( $served ) );
		$this->assertSame( '%PDF-1.4 cached before the cache was turned off', file_get_contents( $cache_file ) );

		/* An explicit path is written where the caller asked */
		$explicit = $this->generator();
		$explicit->set_path( $this->gfpdf()->data->template_tmp_location . 'explicit-' . uniqid() );

		$path = $explicit->get_full_pdf_path();
		$this->assertTrue( $this->model->process_and_save_pdf( $explicit ) );
		$this->assertSame( 'miss', $this->model->get_cache_status( $path ) );
	}

	public function test_a_nested_render_keeps_the_outer_render_flag() {
		$outer  = $this->generator();
		$inner  = $this->generator();
		$nested = function ( $html ) use ( $inner, &$nested ) {
			remove_filter( 'gfpdf_pdf_html_output', $nested );

			Helper_Render_Health::mark();
			$this->model->process_and_save_pdf( $inner );

			return $html;
		};

		add_filter( 'gfpdf_pdf_html_output', $nested );

		$this->assertTrue( $this->model->process_and_save_pdf( $outer ) );

		$this->assertSame( 'miss', $this->model->get_cache_status( $inner->get_full_pdf_path() ) );
		$this->assertSame( 'degraded', $this->model->get_cache_status( $outer->get_full_pdf_path() ) );
	}

	public function test_a_flag_left_by_a_direct_render_does_not_leak() {
		$this->add_remote_image( new WP_Error( 'http_request_failed', 'cURL error 6: Could not resolve host' ) );

		/* Rendering through Helper_PDF alone, as PDF for GravityView does */
		$direct = $this->generator();
		$direct->init();
		$direct->set_output_type( 'save' );
		$direct->render_html( [ 'settings' => $direct->get_settings() ], '<p>Direct render</p>' );
		$direct->generate();

		$this->assertFalse( Helper_Render_Health::is_degraded() );

		$this->remove_remote_image();

		$pdf = $this->generator();
		$this->assertTrue( $this->model->process_and_save_pdf( $pdf ) );
		$this->assertSame( 'miss', $this->model->get_cache_status( $pdf->get_full_pdf_path() ) );
	}

	/**
	 * @param array|WP_Error $answer
	 */
	private function add_remote_image( $answer ): void {
		$this->remove_remote_image();

		$this->image_filters = [
			'gfpdf_pdf_html_output' => function ( $html ) {
				return $html . '<img src="' . self::REMOTE_IMAGE . '" />';
			},
			'pre_http_request'      => function ( $preempt, $args, $url ) use ( $answer ) {
				return $url === self::REMOTE_IMAGE ? $answer : $preempt;
			},
		];

		foreach ( $this->image_filters as $hook => $callback ) {
			add_filter( $hook, $callback, 10, 3 );
		}
	}

	private function remove_remote_image(): void {
		foreach ( $this->image_filters as $hook => $callback ) {
			remove_filter( $hook, $callback, 10 );
		}

		$this->image_filters = [];
	}

	private function answer( int $code ): array {
		return [
			'response' => [ 'code' => $code ],
			'body'     => '',
		];
	}

	/**
	 * A generator with its own cache key
	 */
	private function generator( array $settings = [] ): Helper_PDF {
		global $gfpdf;

		$results  = $this->form_and_entry();
		$settings = $settings ?: array_merge(
			$results['form']['gfpdf_form_settings']['555ad84787d7e'],
			[
				'template'          => 'zadani',
				'render_cache_test' => uniqid( '', true ),
			]
		);

		$pdf = new Helper_PDF( $results['entry'], $settings, $gfpdf->gform, $gfpdf->data, $gfpdf->misc, $gfpdf->templates, $gfpdf->log );
		$pdf->set_filename( 'Render Cache' );

		return $pdf;
	}
}
