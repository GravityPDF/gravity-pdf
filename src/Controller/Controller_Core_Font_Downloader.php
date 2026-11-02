<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Model\Model_Core_Font_Downloader;
use GFPDF\Model\Model_Required_Fonts;

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
 * Queues the background font sync on triggers that change the required fonts, and runs it
 *
 * @since 6.19.0
 */
class Controller_Core_Font_Downloader extends Helper_Abstract_Controller implements Helper_Interface_Actions {

	/**
	 * @var Model_Core_Font_Downloader
	 *
	 * @since 6.19.0
	 */
	public $model;

	/**
	 * @var Model_Required_Fonts
	 *
	 * @since 6.19.0
	 */
	protected $required_fonts;

	public function __construct( Model_Core_Font_Downloader $model, Model_Required_Fonts $required_fonts ) {
		$this->model          = $model;
		$this->required_fonts = $required_fonts;
		$this->model->setController( $this );
	}

	/**
	 * @return void
	 *
	 * @since 6.19.0
	 */
	public function init() {
		$this->add_actions();
	}

	/**
	 * @return void
	 *
	 * @since 6.19.0
	 */
	public function add_actions() {
		add_action( Model_Core_Font_Downloader::SYNC_EVENT, [ $this->model, 'sync' ] );

		foreach ( [ 'gfpdf_plugin_installed', 'gfpdf_version_changed', 'gfpdf_font_sources_changed' ] as $hook ) {
			add_action( $hook, [ $this->model, 'schedule_sync' ] );
		}

		add_action( 'upgrader_process_complete', [ $this, 'maybe_sync_translations' ], 10, 2 );

		/* Both hooks pass the saved settings second */
		add_action( 'add_option_gfpdf_settings', [ $this, 'maybe_sync_default_font' ], 10, 2 );
		add_action( 'update_option_gfpdf_settings', [ $this, 'maybe_sync_default_font' ], 10, 2 );

		add_action( 'gfpdf_post_update_pdf', [ $this, 'maybe_sync_pdf_fonts' ] );
	}

	/**
	 * Sync when a core language pack installs; plugin and theme packs don't change the required fonts
	 *
	 * @param mixed $upgrader
	 * @param mixed $options
	 *
	 * @since 6.19.0
	 */
	public function maybe_sync_translations( $upgrader, $options ): void {
		if ( ! is_array( $options ) || ( $options['type'] ?? '' ) !== 'translation' || ! is_array( $options['translations'] ?? null ) ) {
			return;
		}

		foreach ( $options['translations'] as $translation ) {
			if ( is_array( $translation ) && ( $translation['type'] ?? '' ) === 'core' ) {
				$this->model->schedule_sync();

				return;
			}
		}
	}

	/**
	 * Sync when the saved default font has files to download
	 *
	 * @param mixed $unused   The old settings, or the option name
	 * @param mixed $settings The saved settings
	 *
	 * @since 6.19.0
	 */
	public function maybe_sync_default_font( $unused, $settings ): void {
		if ( is_array( $settings ) && $this->required_fonts->needs_download( $settings['default_font'] ?? '' ) ) {
			$this->model->schedule_sync();
		}
	}

	/**
	 * Sync when a saved PDF's fonts have files to download
	 *
	 * @param mixed $pdf PDF settings
	 *
	 * @since 6.19.0
	 */
	public function maybe_sync_pdf_fonts( $pdf ): void {
		if ( is_array( $pdf ) && $this->required_fonts->pdf_needs_download( $pdf ) ) {
			$this->model->schedule_sync();
		}
	}
}
