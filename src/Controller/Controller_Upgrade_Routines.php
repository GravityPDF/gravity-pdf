<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Model\Model_Custom_Fonts;
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
	 * @var Helper_Abstract_Options
	 */
	protected $options;

	/**
	 * @var Helper_Data
	 */
	protected $data;

	public function __construct( Helper_Abstract_Options $options, Helper_Data $data ) {
		$this->options = $options;
		$this->data    = $data;
	}

	/**
	 * @since 6.0
	 */
	public function init(): void {
		add_action( 'gfpdf_version_changed', [ $this, 'maybe_run_upgrade' ], 10, 2 );
		add_action( 'gfpdf_plugin_installed', [ $this, 'record_deprecated_functionality' ] );
	}

	/**
	 * @since 6.0
	 */
	public function maybe_run_upgrade( string $old_version, string $current_version ): void {
		if ( version_compare( $current_version, '6.0.0-beta1', '>=' ) && version_compare( $old_version, '6.0.0-beta1', '<' ) ) {
			$this->update_background_processing_values();
			$this->upgrade_custom_fonts();
		}

		if ( version_compare( $current_version, '6.13.2', '>=' ) && version_compare( $old_version, '6.13.2', '<' ) ) {
			$this->fix_tmp_folder_permissions();
		}

		if ( version_compare( $current_version, '6.16.0', '>=' ) && version_compare( $old_version, '6.16.0', '<' ) ) {
			$this->remove_legacy_update_cache();
			$this->remove_legacy_license_check_cron();
		}

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
		$folders = [ $this->data->template_tmp_location ];

		/* If the mPDF tmp directory is moved outside the GPDF tmp directory, fix the folder permissions separately */
		if ( strpos( $this->data->mpdf_tmp_location, $this->data->template_tmp_location ) !== 0 ) {
			$folders[] = $this->data->mpdf_tmp_location;
		}

		foreach ( $folders as $folder ) {
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
