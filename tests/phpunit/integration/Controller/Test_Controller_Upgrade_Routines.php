<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Controller_Upgrade_Routines
 *
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   upgrade
 */
class Test_Controller_Upgrade_Routines extends TestCase {

	/**
	 * @var \GFPDF\Helper\Helper_Options_Fields
	 */
	protected $options;

	public function set_up(): void {
		parent::set_up();

		$this->options = \GPDFAPI::get_options_class();
	}

	public function test_6_0_0_background_process_upgrade_routine() {
		/* Check for enabled status */
		$this->options->update_option( 'background_processing', 'Enable' );

		do_action( 'gfpdf_version_changed', '5.3', '6.0.0-beta1' );

		$this->assertSame( 'Yes', $this->options->get_option( 'background_processing' ) );

		/* Check for disabled status */
		$this->options->update_option( 'background_processing', 'Disable' );

		do_action( 'gfpdf_version_changed', '5.3', '6.0.0-beta1' );

		$this->assertSame( 'No', $this->options->get_option( 'background_processing' ) );
	}

	public function test_6_12_0_clears_legacy_cleanup_tmp_dir_cron(): void {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'gfpdf_cleanup_tmp_dir' );
		$this->assertNotFalse( wp_next_scheduled( 'gfpdf_cleanup_tmp_dir' ) );

		do_action( 'gfpdf_version_changed', '6.11.0', '6.12.0' );

		$this->assertFalse( wp_next_scheduled( 'gfpdf_cleanup_tmp_dir' ) );
	}

	public function test_6_13_2_fix_tmp_folder_permissions_runs_without_crashing(): void {
		global $gfpdf;

		/* Ensure the tmp folder exists so RecursiveDirectoryIterator has a target. */
		wp_mkdir_p( $gfpdf->data->template_tmp_location );

		$test_subdir = $gfpdf->data->template_tmp_location . 'gpdf-upgrade-test/';
		wp_mkdir_p( $test_subdir );
		chmod( $test_subdir, 0700 );

		try {
			do_action( 'gfpdf_version_changed', '6.13.1', '6.13.2' );

			$this->assertDirectoryExists( $test_subdir );

			/* The routine resets permissions to match the parent dir (or 0755). */
			clearstatcache( true, $test_subdir );
			$perms = fileperms( $test_subdir ) & 0007777;
			$this->assertNotSame( 0700, $perms, 'fix_tmp_folder_permissions should have changed the directory permissions' );
		} finally {
			@rmdir( $test_subdir );
		}
	}

	public function test_6_13_2_handles_missing_tmp_folder_gracefully(): void {
		global $gfpdf;

		$original_tmp                       = $gfpdf->data->template_tmp_location;
		$gfpdf->data->template_tmp_location = '/tmp/gpdf-nonexistent-' . uniqid() . '/';

		$this->expectNotToPerformAssertions();

		try {
			do_action( 'gfpdf_version_changed', '6.13.1', '6.13.2' );
		} finally {
			$gfpdf->data->template_tmp_location = $original_tmp;
		}
	}

	public function test_6_15_0_removes_legacy_edd_sl_options_only_for_gravity_pdf(): void {
		add_option( 'edd_sl_gravity_pdf_active', 'license-data for gravity-pdf', '', 'no' );
		add_option( 'edd_sl_other_plugin_active', 'license-data for some-other-plugin', '', 'no' );
		add_option( 'gpdf_unrelated_option', 'should-survive', '', 'no' );

		do_action( 'gfpdf_version_changed', '6.13.2', '6.15.0' );

		/* The upgrade routine deletes via a raw wpdb query; bust the per-option cache so get_option re-reads the DB. */
		wp_cache_delete( 'edd_sl_gravity_pdf_active', 'options' );

		$this->assertFalse( get_option( 'edd_sl_gravity_pdf_active', false ) );
		$this->assertSame( 'license-data for some-other-plugin', get_option( 'edd_sl_other_plugin_active' ) );
		$this->assertSame( 'should-survive', get_option( 'gpdf_unrelated_option' ) );
	}

}
