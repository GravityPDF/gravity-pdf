<?php

namespace GFPDF\Controller;

use WP_UnitTestCase;

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
class Test_Controller_Upgrade_Routines extends WP_UnitTestCase {

	/**
	 * @var \GFPDF\Helper\Helper_Options_Fields
	 */
	protected $options;

	public function set_up() {
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
