<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Helper\Helper_Options_Fields;
use GFPDF\Model\Model_Font_Downloader;
use GFPDF\Model\Model_Font_Sources;
use GFPDF\Model\Model_Install;
use GFPDF\Statics\Deprecation;
use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\CreatesLegacyDownloadUrls;
use GFPDF\Tests\Concerns\UnregistersMvcClasses;
use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger;

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

	use CopiesFontFixtures;
	use CreatesLegacyDownloadUrls;
	use UnregistersMvcClasses;

	/**
	 * @var \GFPDF\Helper\Helper_Options_Fields
	 */
	protected $options;

	public function set_up(): void {
		parent::set_up();

		$this->options = \GPDFAPI::get_options_class();
	}

	public function tear_down(): void {
		$this->remove_font_fixtures();

		/* A subsite leaves these behind */
		global $wp_settings_errors, $wp_rewrite;
		$wp_settings_errors = [];
		$wp_rewrite->init();

		parent::tear_down();
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

	public function test_7_0_0_hardens_the_tmp_htaccess() {
		$file     = \GPDFAPI::get_data_class()->template_tmp_location . '.htaccess';
		$original = is_file( $file ) ? file_get_contents( $file ) : null;

		try {
			file_put_contents( $file, "deny from all\n" );
			do_action( 'gfpdf_version_changed', '6.18.0', '7.0.0' );
			$this->assertStringEqualsFile( $file, Model_Install::TMP_HTACCESS );

			file_put_contents( $file, 'Require ip 127.0.0.1' );
			do_action( 'gfpdf_version_changed', '6.18.0', '7.0.0' );
			$this->assertStringEqualsFile( $file, 'Require ip 127.0.0.1', 'A customised file is left alone' );

			unlink( $file );
			do_action( 'gfpdf_version_changed', '6.18.0', '7.0.0' );
			$this->assertStringEqualsFile( $file, Model_Install::TMP_HTACCESS );

			file_put_contents( $file, 'deny from all' );
			do_action( 'gfpdf_version_changed', '7.0.0', '7.0.1' );
			$this->assertStringEqualsFile( $file, 'deny from all', 'Only the upgrade to 7.0 replaces it' );
		} finally {
			$original === null ? @unlink( $file ) : file_put_contents( $file, $original );
		}
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

	public function test_6_18_0_fixes_a_logged_out_timeout_out_of_range() {
		$settings = $this->options->get_settings();

		foreach ( [ 0, '-5' ] as $timeout ) {
			$this->options->update_settings( array_merge( $settings, [ 'logged_out_timeout' => $timeout ] ) );
			do_action( 'gfpdf_version_changed', '6.17.2', '6.18.0' );
			$this->assertArrayNotHasKey( 'logged_out_timeout', get_option( 'gfpdf_settings' ) );
		}

		/* One over a week is capped at a week */
		$this->options->update_settings( array_merge( $settings, [ 'logged_out_timeout' => '525600' ] ) );
		do_action( 'gfpdf_version_changed', '6.17.2', '6.18.0' );
		$this->assertSame( Helper_Options_Fields::LOGGED_OUT_TIMEOUT_MAX, get_option( 'gfpdf_settings' )['logged_out_timeout'] );

		/* A real timeout is left alone */
		foreach ( [ '33', (string) Helper_Options_Fields::LOGGED_OUT_TIMEOUT_MAX ] as $timeout ) {
			$this->options->update_settings( array_merge( $settings, [ 'logged_out_timeout' => $timeout ] ) );
			do_action( 'gfpdf_version_changed', '6.17.2', '6.18.0' );
			$this->assertSame( $timeout, get_option( 'gfpdf_settings' )['logged_out_timeout'] );
		}

		$this->options->update_settings( $settings );
	}

	public function test_6_18_0_removes_the_nested_mpdf_cache() {
		$cache  = \GPDFAPI::get_data_class()->mpdf_tmp_location . '/mpdf';
		$nested = $cache . '/mpdf/ttfontdata';

		wp_mkdir_p( $nested );
		touch( $nested . '/dejavusans.mtx.json' );

		do_action( 'gfpdf_version_changed', '6.17.2', '6.18.0' );

		$this->assertDirectoryDoesNotExist( $cache . '/mpdf' );
		$this->assertDirectoryExists( $cache . '/ttfontdata' );
	}

	public function test_6_18_0_keeps_processing_submitted_rich_text_merge_tags_when_upgrading() {
		$option = Helper_Abstract_Fields::PROCESS_SUBMITTED_RICH_TEXT_MERGE_TAGS;

		/* An install fires the version change with no previous version */
		do_action( 'gfpdf_version_changed', '', '7.0.0' );
		$this->assertFalse( get_site_option( $option ) );

		do_action( 'gfpdf_version_changed', '6.18.0', '7.0.0' );
		$this->assertFalse( get_site_option( $option ) );

		do_action( 'gfpdf_version_changed', '6.17.2', '7.0.0' );
		$this->assertTrue( get_site_option( $option ) );
	}

	public function test_a_new_install_defaults_to_arimo() {
		$this->options->delete_option( 'default_font' );

		do_action( 'gfpdf_plugin_installed' );
		do_action( 'gfpdf_version_changed', '', '6.19.0' );

		$this->assertSame( 'arimo', get_option( 'gfpdf_settings' )['default_font'] );
	}

	public function test_an_install_keeps_a_saved_default_font() {
		$this->options->update_option( 'default_font', 'freeserif' );

		do_action( 'gfpdf_plugin_installed' );

		$this->assertSame( 'freeserif', get_option( 'gfpdf_settings' )['default_font'] );
	}

	public function test_an_upgrade_keeps_the_saved_default_font() {
		$this->options->update_option( 'default_font', 'freeserif' );

		do_action( 'gfpdf_version_changed', '6.18.0', '6.19.0' );

		$this->assertSame( 'freeserif', get_option( 'gfpdf_settings' )['default_font'] );
	}

	public function test_an_upgrade_saves_the_old_default_font_when_none_was_saved() {
		$this->options->delete_option( 'default_font' );

		do_action( 'gfpdf_version_changed', '6.19.0', '6.19.1' );
		$this->assertArrayNotHasKey( 'default_font', get_option( 'gfpdf_settings', [] ) );

		do_action( 'gfpdf_version_changed', '6.18.0', '6.19.0' );
		$this->assertSame( 'dejavusanscondensed', get_option( 'gfpdf_settings' )['default_font'] );
	}

	public function test_an_empty_default_font_counts_as_unsaved() {
		update_option( 'gfpdf_settings', [ 'default_font' => '' ] );
		do_action( 'gfpdf_plugin_installed' );
		$this->assertSame( 'arimo', get_option( 'gfpdf_settings' )['default_font'] );

		update_option( 'gfpdf_settings', [ 'default_font' => '' ] );
		do_action( 'gfpdf_version_changed', '6.18.0', '6.19.0' );
		$this->assertSame( 'dejavusanscondensed', get_option( 'gfpdf_settings' )['default_font'] );
	}

	/**
	 * @dataProvider data_corrupt_settings
	 *
	 * @param mixed $settings
	 */
	public function test_the_default_font_save_leaves_corrupt_settings_alone( $settings ) {
		update_option( 'gfpdf_settings', $settings );

		do_action( 'gfpdf_plugin_installed' );
		do_action( 'gfpdf_version_changed', '6.18.0', '6.19.0' );

		$this->assertSame( $settings, get_option( 'gfpdf_settings' ) );
	}

	public function data_corrupt_settings(): array {
		return [
			'string'  => [ 'corrupt' ],
			'integer' => [ '5' ],
		];
	}

	public function test_6_19_0_adopts_template_arimo_and_core_font_records() {
		wp_mkdir_p( \GPDFAPI::get_data_class()->template_font_location );
		$this->copy_fixture( 'Chewy.ttf', 'Arimo-Regular.ttf' );
		$this->add_record( 'arimo', [ 'regular' => 'Arimo-Regular.ttf' ] );
		$this->copy_fixture( 'DejaVuSansCondensed.ttf', 'DejaVuSansCondensed85274.ttf' );
		$this->add_record( 'dejavusanscondensed', [ 'regular' => 'DejaVuSansCondensed85274.ttf' ] );

		$log = new TestHandler();
		\GPDFAPI::get_log_class()->pushHandler( $log );

		/* A fatal later in the upgrade request runs the routine again before the version is saved */
		try {
			$this->with_mvc_class(
				'Model_Font_Sources',
				$this->get_model_with_legacy( 'Chewy.ttf' ),
				function () {
					do_action( 'gfpdf_version_changed', '6.18.0', '6.19.0' );
					do_action( 'gfpdf_version_changed', '6.18.0', '6.19.0' );
				}
			);
		} finally {
			\GPDFAPI::get_log_class()->popHandler();
		}

		$this->assertSame( [], \GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' )->get_custom_fonts() );
		$this->assertFalse( $log->hasRecordThatContains( 'Could not remove a custom font record', Logger::ERROR ) );
	}

	/**
	 * @dataProvider data_font_source_adoption_versions
	 */
	public function test_font_sources_adopt_records_only_when_upgrading_to_6_19_0( string $old_version, string $new_version, array $adopted ) {
		$model = new class( \GPDFAPI::get_data_class(), \GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' ), \GPDFAPI::get_log_class() ) extends Model_Font_Sources {
			/** @var string[] */
			public $adopted = [];

			public function adopt_records( string $source_id ): array {
				$this->adopted[] = $source_id;

				return [];
			}
		};

		$this->with_mvc_class(
			'Model_Font_Sources',
			$model,
			function () use ( $old_version, $new_version ) {
				do_action( 'gfpdf_version_changed', $old_version, $new_version );
			}
		);

		$this->assertSame( $adopted, $model->adopted );
	}

	public function data_font_source_adoption_versions(): array {
		return [
			'upgrade to 6.19.0'  => [ '6.18.0', '6.19.0', [ 'bundled', 'core' ] ],
			'6.19.0 beta'        => [ '6.19.0-beta1', '6.19.0', [ 'bundled', 'core' ] ],
			'upgrade from 6.19'  => [ '6.19.0', '6.19.1', [] ],
			'new install'        => [ '', '6.19.0', [] ],
		];
	}

	public function test_6_19_0_skips_adoption_without_the_font_sources_model() {
		$this->options->delete_option( 'default_font' );

		$this->without_mvc_class(
			'Model_Font_Sources',
			function () {
				do_action( 'gfpdf_version_changed', '6.18.0', '6.19.0' );
			}
		);

		$this->assertSame( 'dejavusanscondensed', get_option( 'gfpdf_settings' )['default_font'] );
	}

	public function test_a_new_install_with_no_recorded_version_defaults_to_arimo() {
		$this->options->delete_option( 'default_font' );

		do_action( 'gfpdf_plugin_installed' );
		do_action( 'gfpdf_version_changed', false, '6.19.0' );

		$this->assertSame( 'arimo', get_option( 'gfpdf_settings' )['default_font'] );
	}

	/**
	 * @dataProvider provider_junk_versions
	 *
	 * @param mixed $current_version
	 */
	public function test_a_junk_current_version_runs_no_routines( $current_version ) {
		$this->options->delete_option( 'default_font' );

		do_action( 'gfpdf_version_changed', '6.18.0', $current_version );

		$this->assertArrayNotHasKey( 'default_font', get_option( 'gfpdf_settings', [] ) );
	}

	public function provider_junk_versions(): array {
		return [
			'empty'  => [ '' ],
			'null'   => [ null ],
			'false'  => [ false ],
			'array'  => [ [ '6.19.0' ] ],
			'number' => [ 619 ],
		];
	}

	/**
	 * An install fires the version change too, as no version has been recorded yet
	 */
	public function test_a_version_change_creates_the_mpdf_cache_folders() {
		$cache = \GPDFAPI::get_data_class()->mpdf_tmp_location . '/mpdf';

		wp_mkdir_p( $cache );
		\GPDFAPI::get_misc_class()->rmdir( $cache );

		do_action( 'gfpdf_version_changed', '6.18.0', '6.18.1' );

		$this->assertDirectoryExists( $cache . '/ttfontdata' );
	}

	/**
	 * @dataProvider data_core_font_cleanup
	 */
	public function test_core_fonts_outside_the_required_set_are_removed_on_the_upgrade_to_6_19( string $old_version, string $new_version, bool $removed ) {
		$this->set_installer_manifest();
		$this->options->delete_option( 'default_font' );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'DejaVuSansCondensed.ttf' );

		$this->upgrade( $old_version, $new_version );

		$this->assertSame( ! $removed, is_file( $this->font_path( 'DejaVuSerifCondensed.ttf' ) ) );
		$this->assertFileExists( $this->font_path( 'DejaVuSansCondensed.ttf' ), 'The default font the upgrade saves is kept' );
	}

	public function data_core_font_cleanup(): array {
		return [
			'upgrade to 6.19'     => [ '6.18.0', '6.19.0', true ],
			'upgrade past 6.19'   => [ '6.17.2', '7.0.0', true ],
			'a new install'       => [ '', '6.19.0', false ],
			'upgrade within 6.19' => [ '6.19.0', '6.19.1', false ],
		];
	}

	public function test_the_upgrade_leaves_the_core_font_cleanup_to_cron() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );

		do_action( 'gfpdf_version_changed', '6.18.0', '6.19.0' );

		$this->assertNotFalse( wp_next_scheduled( Model_Font_Downloader::CLEANUP_EVENT ) );
		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	public function test_the_core_font_cleanup_keeps_the_required_fonts() {
		$this->set_installer_manifest();
		$this->options->update_option( 'default_font', 'dejavuserifcondensed' );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'DejaVuSansCondensed.ttf' );

		$this->upgrade( '6.18.0', '6.19.0' );

		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertFileDoesNotExist( $this->font_path( 'DejaVuSansCondensed.ttf' ) );
	}

	public function test_the_core_font_cleanup_keeps_fonts_when_downloads_are_off() {
		$this->set_installer_manifest();
		add_filter( 'gfpdf_auto_install_fonts', '__return_false' );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );

		$this->upgrade( '6.18.0', '6.19.0' );

		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	public function test_the_core_font_cleanup_runs_once() {
		$this->set_installer_manifest();
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->upgrade( '6.18.0', '6.19.0' );
		$this->assertFileDoesNotExist( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertTrue( get_network_option( get_main_network_id(), Model_Font_Downloader::CLEANUP ) );

		/* Another site upgrading, or this one again, finds the folder already cleaned */
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->upgrade( '6.18.0', '6.19.0' );
		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	public function test_the_core_font_cleanup_keeps_every_sites_fonts() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->set_installer_manifest();
		$this->options->update_option( 'default_font', 'arimo' );
		update_blog_option( self::factory()->blog->create(), 'gfpdf_settings', [ 'default_font' => 'dejavuserifcondensed' ] );

		/* A site yet to upgrade, with no default font saved, will save DejaVu Sans Condensed */
		self::factory()->blog->create();

		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'DejaVuSansCondensed.ttf' );

		$this->upgrade( '6.18.0', '6.19.0' );

		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertFileExists( $this->font_path( 'DejaVuSansCondensed.ttf' ) );
	}

	/**
	 * Run the version change, then the cleanup it queues
	 */
	private function upgrade( string $old_version, string $new_version ): void {
		do_action( 'gfpdf_version_changed', $old_version, $new_version );
		do_action( Model_Font_Downloader::CLEANUP_EVENT );
	}

	private function font_path( string $file ): string {
		return \GPDFAPI::get_data_class()->template_font_location . $file;
	}
}
