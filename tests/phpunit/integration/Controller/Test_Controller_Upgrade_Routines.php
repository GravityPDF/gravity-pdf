<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Fonts\Catalog_Sync;
use GFPDF\Fonts\Font_Lock;
use GFPDF\Fonts\Font_Schema;
use GFPDF\Statics\Deprecation;
use GFPDF\Tests\Concerns\CreatesLegacyDownloadUrls;
use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\MocksHttpRequests;
use GFPDF\Tests\Concerns\PublishesFontIndexes;
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
	use HasCatalogRows;
	use MocksHttpRequests;
	use PublishesFontIndexes;

	/**
	 * @var \GFPDF\Helper\Helper_Options_Fields
	 */
	protected $options;

	public function set_up(): void {
		parent::set_up();

		$this->options = \GPDFAPI::get_options_class();

		/* The 7.0 gate syncs the catalog inline, so every case in this file is sealed off from the network: an
		   unrouted request gets a 404 rather than reaching fonts.gravitypdf.com */
		$this->mock_http( [] );
		$this->generate_font_signing_key();
		$this->reset_catalog_state();
	}

	public function tear_down(): void {
		global $gfpdf;

		/* The font gate below rebuilds it; the rest of the class never touches it, so this is free either way */
		$gfpdf->font_repository = null;
		$gfpdf->catalog_sync    = null;

		$this->unmock_http();
		$this->reset_catalog_state();

		parent::tear_down();
	}

	protected function reset_catalog_state(): void {
		$this->drop_catalog_rows();

		delete_site_option( Catalog_Sync::OPTION );
		delete_site_option( Catalog_Sync::GENERATED_OPTION );
		( new Font_Lock() )->release( Catalog_Sync::LOCK );
	}

	/**
	 * Write a shipped-index stand-in and hand back its path
	 */
	protected function seed_file(): string {
		$file = wp_tempnam( 'packs-seed' );

		file_put_contents( $file, (string) wp_json_encode( [ 'schema' => 1, 'entries' => [ $this->pack_entry( 'emoji' ) ] ] ) );

		return $file;
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

	/**
	 * Step 2. A site has no catalogue at all until this runs, so browsing, adoption and every install path would
	 * be looking at an empty table for up to an hour if the sync were left to the scheduled listener.
	 */
	public function test_7_0_0_fills_the_font_catalog_inline() {
		global $gfpdf;

		$this->publish( [ $this->pack_entry( 'emoji' ), $this->pack_entry( 'arabic' ) ] );

		$gfpdf->catalog_sync = $this->sync();

		/* Control: the gate is what runs it, not the action */
		do_action( 'gfpdf_version_changed', '6.15.0', '6.16.0' );

		$this->assertSame( 0, $this->catalog_repository()->search( 'packs' )['total'] );

		do_action( 'gfpdf_version_changed', '6.17.0', '7.0.0' );

		$this->assertSame( [ 'emoji', 'arabic' ], wp_list_pluck( $this->catalog_repository()->search( 'packs' )['entries'], 'entry' ) );
		$this->assertGreaterThan( 0, $gfpdf->catalog_sync->get_record( 'packs' )['synced'] );
	}

	/**
	 * A blocked-egress upgrade is the offline install path's starting point: the catalogue still has to be
	 * browsable and installable from hand-placed files, which needs rows.
	 */
	public function test_7_0_0_seeds_the_font_catalog_when_the_first_sync_fails() {
		global $gfpdf;

		$seed = $this->seed_file();

		$this->publish( [ $this->pack_entry( 'emoji' ) ], [], [ 'index.json' => [ 'code' => 500 ] ] );

		$gfpdf->catalog_sync = $this->sync( null, $seed );

		do_action( 'gfpdf_version_changed', '6.17.0', '7.0.0' );

		$record = $gfpdf->catalog_sync->get_record( 'packs' );

		$this->assertSame( 1, $this->catalog_repository()->search( 'packs' )['total'] );
		$this->assertTrue( $record['seeded'] );
		$this->assertSame( 0, $record['synced'], 'a failed sync must not leave the seed reporting freshness' );
		$this->assertNotSame( '', $record['last_error'] );

		unlink( $seed );
	}

	/**
	 * The seed is the fallback, not a second source of rows: a sync that worked must be left exactly as it is
	 */
	public function test_7_0_0_does_not_seed_over_a_successful_sync() {
		global $gfpdf;

		$seed = $this->seed_file();

		$this->publish( [ $this->pack_entry( 'cjk' ) ] );

		$gfpdf->catalog_sync = $this->sync( null, $seed );

		do_action( 'gfpdf_version_changed', '6.17.0', '7.0.0' );

		$this->assertSame( [ 'cjk' ], wp_list_pluck( $this->catalog_repository()->search( 'packs' )['entries'], 'entry' ) );
		$this->assertFalse( $gfpdf->catalog_sync->get_record( 'packs' )['seeded'] );

		unlink( $seed );
	}

	/**
	 * Declared last: it moves the schema version and rebuilds the shared repository, so nothing that assumes the
	 * state the bootstrap left should run after it.
	 *
	 * The version option is the observable. `ensure_ready()` writes it only after the migration and every
	 * population pass have succeeded, so seeing it current is seeing the whole routine finish.
	 */
	public function test_7_0_0_migrates_the_font_tables_before_the_first_render() {
		global $gfpdf;

		$schema = new Font_Schema( \GPDFAPI::get_log_class() );

		/*
		 * Both copies, since `is_current()` accepts either and multisite keeps a network one. An older version
		 * rather than no version at all: `update_option()` compares against the cached value, so a deleted row is
		 * the harder of the two to put back.
		 */
		update_option( Font_Schema::VERSION_OPTION, '6.0.0', true );

		if ( is_multisite() ) {
			update_site_option( Font_Schema::VERSION_OPTION, '6.0.0' );
		}

		/* `ensure_ready()` short-circuits on a per-instance flag, and the shared repository is long since ready */
		$gfpdf->font_repository = null;

		/* Control: the routine's other branches leave the tables alone, so the gate is what is being read */
		do_action( 'gfpdf_version_changed', '6.15.0', '6.16.0' );

		$this->assertSame( '6.0.0', get_option( Font_Schema::VERSION_OPTION ) );

		do_action( 'gfpdf_version_changed', '6.17.0', '7.0.0' );

		$this->assertSame( $schema->get_version(), get_option( Font_Schema::VERSION_OPTION ) );
	}

}
