<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Model\Model_Custom_Fonts;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
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
}
