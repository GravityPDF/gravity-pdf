<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Integration\Controller;

use Controller_Activation;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   activation
 */
class Test_Controller_Activation extends TestCase {

	public function tear_down(): void {
		/*
		 * Defensive cleanup: tests in this class schedule these cron events to
		 * verify deactivation() clears them. If an assertion or fatal happens
		 * before deactivation() runs, the events would leak into later classes
		 * (Test_PDF asserts gfpdf_cleanup_tmp_dir is scheduled and would pass
		 * for the wrong reason on a stale leftover).
		 */
		wp_clear_scheduled_hook( 'gfpdf_cleanup_tmp_dir' );
		wp_clear_scheduled_hook( 'gfpdf_network_update_check' );
		wp_clear_scheduled_hook( 'gfpdf_bulk_license_check' );

		parent::tear_down();
	}

	public function test_deactivation_clears_scheduled_hooks() {
		wp_schedule_event( time() + 100, 'daily', 'gfpdf_cleanup_tmp_dir' );
		wp_schedule_event( time() + 100, 'daily', 'gfpdf_network_update_check' );
		wp_schedule_event( time() + 100, 'daily', 'gfpdf_bulk_license_check' );

		Controller_Activation::deactivation();

		$this->assertFalse( wp_next_scheduled( 'gfpdf_cleanup_tmp_dir' ) );
		$this->assertFalse( wp_next_scheduled( 'gfpdf_network_update_check' ) );
		$this->assertFalse( wp_next_scheduled( 'gfpdf_bulk_license_check' ) );
	}

	public function test_deactivation_removes_plugin_rewrite_rules() {
		global $gfpdf;

		$rules = [
			'^' . $gfpdf->data->permalink                              => 'index.php?gpdf=1',
			'^some/other/rule'                                         => 'index.php?other=1',
		];
		update_option( 'rewrite_rules', $rules );

		Controller_Activation::deactivation();

		$updated = get_option( 'rewrite_rules' );
		$this->assertArrayNotHasKey( '^' . $gfpdf->data->permalink, $updated );
		$this->assertArrayHasKey( '^some/other/rule', $updated );
	}

	public function test_deactivation_leaves_rewrite_rules_when_no_plugin_rules_present() {
		$rules = [ '^some/other/rule' => 'index.php?other=1' ];
		update_option( 'rewrite_rules', $rules );

		Controller_Activation::deactivation();

		$this->assertSame( $rules, get_option( 'rewrite_rules' ) );
	}

	public function test_deactivation_flushes_template_transient_cache() {
		global $gfpdf;

		set_transient( $gfpdf->data->template_transient_cache, 'cached', HOUR_IN_SECONDS );

		Controller_Activation::deactivation();

		$this->assertFalse( get_transient( $gfpdf->data->template_transient_cache ) );
	}
}
