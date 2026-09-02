<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Statics\Deprecation;
use GFPDF\Tests\Concerns\CreatesLegacyDownloadUrls;
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

	use CreatesLegacyDownloadUrls;

	/**
	 * @var \GFPDF\Helper\Helper_Options_Fields
	 */
	protected $options;

	public function set_up(): void {
		parent::set_up();

		$this->options = \GPDFAPI::get_options_class();
	}

	/**
	 * A release is the moment a new round of removals arrives, so it is where detection runs for the notices that
	 * report it — they would otherwise have to walk the database on every admin page load
	 */
	public function test_a_version_change_records_the_deprecated_functionality_in_use() {
		$form_id = $this->create_form_with_legacy_url();

		do_action( 'gfpdf_version_changed', '6.16.0', '6.17.0' );

		$this->assertSame( [ 'legacy_endpoint' ], Deprecation::get_detected_features() );

		/* Fixed on the site, so the next release clears the record the notices read */
		\GFAPI::delete_form( $form_id );
		Deprecation::flush_cache();

		do_action( 'gfpdf_version_changed', '6.17.0', '6.17.1' );

		$this->assertSame( [], Deprecation::get_detected_features() );
	}

	public function test_an_install_records_the_deprecated_functionality_in_use() {
		$this->create_form_with_legacy_url();

		do_action( 'gfpdf_plugin_installed' );

		$this->assertSame( [ 'legacy_endpoint' ], Deprecation::get_detected_features() );
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

	public function test_6_16_0_removes_legacy_update_cache() {
		update_option( 'edd_sl_version_info_123', 'a payload naming gravity-pdf' );
		update_option( 'edd_sl_failed_http_' . md5( GPDF_API_URL ), time() );

		/* An unrelated add-on's cache shares the prefix but not the value, and must survive */
		update_option( 'edd_sl_version_info_456', 'a payload naming another-plugin' );

		do_action( 'gfpdf_version_changed', '6.15.0', '6.16.0' );

		/* No cache flush here on purpose — the routine must invalidate what it deletes */
		$this->assertFalse( get_option( 'edd_sl_version_info_123' ) );
		$this->assertFalse( get_option( 'edd_sl_failed_http_' . md5( GPDF_API_URL ) ) );
		$this->assertNotFalse( get_option( 'edd_sl_version_info_456' ) );

		delete_option( 'edd_sl_version_info_456' );
	}

	public function test_6_16_0_removes_legacy_license_check_cron() {
		$future = time() + HOUR_IN_SECONDS;

		/* Deprecated per-add-on events left behind by pre-6.16.0 installs */
		wp_schedule_single_event( $future, 'gfpdf_core_booster_license_check' );
		wp_schedule_single_event( $future, 'gfpdf_business_plus_license_check' );

		/* The bulk check that replaced them, and an unrelated hook, must both survive */
		wp_schedule_single_event( $future, 'gfpdf_bulk_license_check' );
		wp_schedule_single_event( $future, 'gfpdf_cleanup_tmp_dir' );

		do_action( 'gfpdf_version_changed', '6.15.0', '6.16.0' );

		$this->assertFalse( wp_next_scheduled( 'gfpdf_core_booster_license_check' ) );
		$this->assertFalse( wp_next_scheduled( 'gfpdf_business_plus_license_check' ) );
		$this->assertNotFalse( wp_next_scheduled( 'gfpdf_bulk_license_check' ) );
		$this->assertNotFalse( wp_next_scheduled( 'gfpdf_cleanup_tmp_dir' ) );

		wp_clear_scheduled_hook( 'gfpdf_bulk_license_check' );
		wp_clear_scheduled_hook( 'gfpdf_cleanup_tmp_dir' );
	}

}
