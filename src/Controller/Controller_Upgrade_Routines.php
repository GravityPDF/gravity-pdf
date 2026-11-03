<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Model\Model_Font_Downloader;
use GFPDF\Model\Model_Custom_Fonts;
use GFPDF\Model\Model_Font_Sources;
use GFPDF\Model\Model_Install;
use GFPDF\Statics\Deprecation;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Controller_Upgrade_Routines
 *
 * @package GFPDF\Controller
 */
class Controller_Upgrade_Routines {

	/**
	 * The default font of a site upgraded from before 6.19 with none saved
	 *
	 * @since 6.19.0
	 */
	const PRE_6_19_DEFAULT_FONT = 'dejavusanscondensed';

	/**
	 * @var Helper_Abstract_Options
	 */
	protected $options;

	/**
	 * @var Helper_Data
	 */
	protected $data;

	/**
	 * @var Helper_Misc
	 */
	protected $misc;

	public function __construct( Helper_Abstract_Options $options, Helper_Data $data, Helper_Misc $misc ) {
		$this->options = $options;
		$this->data    = $data;
		$this->misc    = $misc;
	}

	/**
	 * @since 6.0
	 */
	public function init(): void {
		add_action( 'gfpdf_version_changed', [ $this, 'maybe_run_upgrade' ], 10, 2 );
		add_action( 'gfpdf_plugin_installed', [ $this, 'record_deprecated_functionality' ] );
		add_action( 'gfpdf_plugin_installed', [ $this, 'set_new_install_default_font' ] );
	}

	/**
	 * @param mixed $old_version     The version upgraded from, '' or false on a new install
	 * @param mixed $current_version The version now running
	 *
	 * @since 6.0
	 * @since 6.19.0 Takes any value, as other code can fire gfpdf_version_changed
	 */
	public function maybe_run_upgrade( $old_version, $current_version ): void {
		if ( ! is_string( $current_version ) || $current_version === '' ) {
			return;
		}

		/* get_option() gives false before a version is recorded */
		$old_version = is_string( $old_version ) ? $old_version : '';

		if ( version_compare( $current_version, '6.0.0-beta1', '>=' ) && version_compare( $old_version, '6.0.0-beta1', '<' ) ) {
			$this->update_background_processing_values();
			$this->upgrade_custom_fonts();
		}

		/* Remove scheduled event(s) so the event can be reregistered with a new frequency */
		if ( version_compare( $current_version, '6.12.0', '>=' ) && version_compare( $old_version, '6.12.0', '<' ) ) {
			wp_clear_scheduled_hook( 'gfpdf_cleanup_tmp_dir' );
		}

		if ( version_compare( $current_version, '6.13.2', '>=' ) && version_compare( $old_version, '6.13.2', '<' ) ) {
			$this->fix_tmp_folder_permissions();
		}

		if ( version_compare( $current_version, '6.16.0', '>=' ) && version_compare( $old_version, '6.16.0', '<' ) ) {
			$this->remove_legacy_update_cache();
			$this->remove_legacy_license_check_cron();
		}

		if ( $old_version !== '' && version_compare( $current_version, '6.18.0', '>=' ) && version_compare( $old_version, '6.18.0', '<' ) ) {
			$this->fix_logged_out_timeout_out_of_range();
			$this->remove_nested_mpdf_cache();
			$this->keep_processing_submitted_rich_text_merge_tags();
		}

		/* A site upgraded from before Arimo was the default keeps DejaVu Sans Condensed, even if it never saved one */
		if ( $old_version !== '' && version_compare( $old_version, '6.19.0', '<' ) ) {
			$this->save_unset_default_font( self::PRE_6_19_DEFAULT_FONT );
			$this->adopt_font_source_records();

			$this->schedule_core_font_cleanup();
		}

		if ( version_compare( $current_version, '7.0.0-beta1', '>=' ) && version_compare( $old_version, '7.0.0-beta1', '<' ) ) {
			Model_Install::write_tmp_htaccess( $this->data->template_tmp_location, true );
		}

		/* Ungated, so every install and release has mPDF's cache folders in place before the first render */
		$this->misc->create_mpdf_cache_folders();

		/* Deliberately ungated: every release is a chance for a new round of removals to arrive. Runs last, so it
		   reflects the routines above */
		$this->record_deprecated_functionality();
	}

	/**
	 * Record the deprecated functionality this site uses, for the admin notices to read
	 *
	 * Taken at install and on every version change, since the notices would otherwise have to detect it on each of
	 * the admin pages they can appear on. The system report and Site Health screens keep the record current in
	 * between, detecting live as they do.
	 *
	 * @since 6.17.0
	 */
	public function record_deprecated_functionality(): void {
		Deprecation::refresh_signals();
	}

	/**
	 * Save Arimo as a new install's default font, unless one is already saved
	 *
	 * @since 6.19.0
	 */
	public function set_new_install_default_font(): void {
		$this->save_unset_default_font( Model_Font_Sources::ARIMO );
	}

	/**
	 * Save the default font when the settings have none or an empty one. Leaves corrupt (non-array) settings alone
	 *
	 * @since 6.19.0
	 */
	protected function save_unset_default_font( string $font ): void {
		/* The raw option, since get_option() would fall back to the field's Arimo default */
		$settings = get_option( 'gfpdf_settings', [] );

		/* update_option() would fatal writing into corrupt settings, which read as empty anyway */
		if ( ! is_array( $settings ) ) {
			return;
		}

		/* An empty font counts as unset: PDFs fall back to DejaVu Sans Condensed while the settings screen shows Arimo */
		if ( empty( $settings['default_font'] ) ) {
			$this->options->update_option( 'default_font', $font );
		}
	}

	/**
	 * Remove the custom font records the font sources now serve, such as the Arimo copies premium templates install
	 *
	 * @since 6.19.0
	 */
	protected function adopt_font_source_records(): void {
		$font_sources = \GPDFAPI::get_mvc_class( 'Model_Font_Sources' );
		if ( ! $font_sources instanceof Model_Font_Sources ) {
			return;
		}

		foreach ( array_keys( $font_sources->get_sources() ) as $source_id ) {
			$font_sources->adopt_records( $source_id );
		}
	}

	/**
	 * Queue the removal of the core fonts a new install wouldn't have. It keeps the fonts every site needs and runs in
	 * the background, as every site on every network shares the fonts folder.
	 *
	 * @since 6.19.0
	 */
	protected function schedule_core_font_cleanup(): void {
		$downloader = \GPDFAPI::get_mvc_class( 'Model_Font_Downloader' );
		if ( $downloader instanceof Model_Font_Downloader ) {
			/* Sites yet to upgrade will save DejaVu Sans Condensed as their default when they do */
			$downloader->schedule_cleanup( self::PRE_6_19_DEFAULT_FONT );
		}
	}

	/**
	 * Remove a saved Logged Out Timeout below the new 1-minute minimum, so it reads as the 20-minute default, and cap
	 * one above the new 1-week maximum
	 *
	 * @since 6.18.0
	 */
	protected function fix_logged_out_timeout_out_of_range(): void {
		$settings = get_option( 'gfpdf_settings', [] );

		if ( ! isset( $settings['logged_out_timeout'] ) ) {
			return;
		}

		if ( (int) $settings['logged_out_timeout'] < 1 ) {
			$this->options->delete_option( 'logged_out_timeout' );
		} elseif ( (int) $settings['logged_out_timeout'] > Helper_Abstract_Options::LOGGED_OUT_TIMEOUT_MAX ) {
			$this->options->update_option( 'logged_out_timeout', Helper_Abstract_Options::LOGGED_OUT_TIMEOUT_MAX );
		}
	}

	/**
	 * Keep processing the merge tags submitted in rich text fields on a site upgraded from an earlier version. Stored
	 * network-wide, so every subsite on a Multisite keeps them
	 *
	 * @since 6.18.0
	 */
	protected function keep_processing_submitted_rich_text_merge_tags(): void {
		update_site_option( Helper_Abstract_Fields::PROCESS_SUBMITTED_RICH_TEXT_MERGE_TAGS, true );
	}

	/**
	 * Remove the cache mPDF kept a folder deeper, at tmp/mpdf/mpdf, before its tempDir became the tmp folder
	 *
	 * @since 6.18.0
	 */
	protected function remove_nested_mpdf_cache(): void {
		$nested_cache = $this->misc->get_mpdf_cache_location() . '/mpdf';

		if ( is_dir( $nested_cache ) ) {
			$this->misc->rmdir( $nested_cache );
		}
	}

	/**
	 * Update Background Processing values to new Toggle button format
	 *
	 * @since 6.0
	 */
	protected function update_background_processing_values(): void {
		$value     = $this->options->get_option( 'background_processing' );
		$new_value = $value === 'Enable' ? 'Yes' : 'No';

		$this->options->update_option( 'background_processing', $new_value );
	}

	/**
	 * Remove legacy settings in the custom fonts data
	 *
	 * @since 6.0
	 */
	protected function upgrade_custom_fonts() {
		/** @var Model_Custom_Fonts $custom_font_model */
		$custom_font_model = \GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' );

		$fonts = $this->options->get_option( 'custom_fonts', [] );

		foreach ( $fonts as &$font ) {
			if ( isset( $font['shortname'] ) ) {
				unset( $font['shortname'] );
			}

			$font['id'] = $custom_font_model->get_font_short_name( $font['font_name'] );
		}

		$this->options->update_option( 'custom_fonts', $fonts );
	}

	/**
	 * Reset temporary folders permission
	 *
	 * This upgrade routine will try reset all temporary folder permissions to match the parent directory,
	 * or fallback to 755 if it cannot be read.
	 *
	 * @since 6.13.2
	 */
	protected function fix_tmp_folder_permissions() {
		foreach ( $this->misc->get_tmp_locations() as $folder ) {
			/* Try get the folder permission from the parent directory */
			$folder_perms = 0755;

			/* Ignore parent folder if it is `/` */
			$parent_dir = dirname( $folder ) !== '/' ? dirname( $folder ) : $folder;
			if ( is_dir( $parent_dir ) ) {
				$stat         = @stat( $parent_dir ); //phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				$folder_perms = $stat ? $stat['mode'] & 0007777 : 0755;
			}

			try {
				/* Get all directories in folder */
				$dir            = new \RecursiveDirectoryIterator( $folder, \RecursiveDirectoryIterator::SKIP_DOTS );
				$files          = new \RecursiveCallbackFilterIterator(
					$dir,
					function ( $current, $key, $iterator ) {
						return $iterator->hasChildren() || $current->isDir();
					}
				);
				$files_iterator = new \RecursiveIteratorIterator( $files, \RecursiveIteratorIterator::SELF_FIRST );

				/* Reset permissions on folder and all subdirectories */
				@chmod( $folder, $folder_perms ); // phpcs:ignore
				foreach ( $files_iterator as $file ) {
					@chmod( $file->getRealPath(), $folder_perms ); // phpcs:ignore
				}
			} catch ( \Exception $e ) {
				// do nothing
			}
		}
	}

	/**
	 * Remove Gravity PDF's legacy edd_sl_* update cache options left behind by the previous plugin updater
	 *
	 * @since 6.16.0
	 */
	protected function remove_legacy_update_cache() {
		global $wpdb;

		/* Delete via the API: a stale autoloaded row left in the cache is the cost this routine exists to remove */
		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */
		$keys = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s AND option_value LIKE %s",
				$wpdb->esc_like( 'edd_sl_' ) . '%',
				'%' . $wpdb->esc_like( 'gravity-pdf' ) . '%'
			)
		);

		foreach ( $keys as $key ) {
			delete_option( $key );
		}

		/* The failure-backoff option stores a bare timestamp, so the value filter above can't match it — target both
		   the historical (≤6.14.x) and the 6.15.0 API hosts by exact name. 6.15.0's key was autoloaded, so a site that
		   ever hit an API failure would otherwise carry a stale autoloaded option forever. */
		delete_option( 'edd_sl_failed_http_' . md5( 'https://gravitypdf.com?api=1' ) );
		delete_option( 'edd_sl_failed_http_' . md5( GPDF_API_URL ) );
	}

	/**
	 * Unschedule the deprecated per-add-on `gfpdf_<slug>_license_check` cron events, superseded by the bulk check
	 *
	 * Sweep by pattern rather than by known slug so events left by add-ons that are no longer active are also removed.
	 *
	 * @since 6.16.0
	 */
	protected function remove_legacy_license_check_cron() {
		/* No public API lists all scheduled hooks, so read the cron array via the core internal (guarded below). */
		$cron = _get_cron_array();
		if ( ! is_array( $cron ) ) {
			return;
		}

		foreach ( $cron as $events ) {
			foreach ( array_keys( $events ) as $hook ) {
				/* Match the old per-add-on events but keep the 6.16.0 bulk check that replaced them */
				if ( $hook !== 'gfpdf_bulk_license_check' && preg_match( '/^gfpdf_.+_license_check$/', $hook ) ) {
					wp_unschedule_hook( $hook );
				}
			}
		}
	}
}
