<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Model\Model_Core_Font_Downloader;
use GFPDF_Vendor\Psr\Log\LoggerInterface;
use GPDFAPI;

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
 * Class Controller_Save_Core_Fonts
 *
 * @package GFPDF\Controller
 *
 * @since   5.0
 */
class Controller_Save_Core_Fonts extends Helper_Abstract_Controller implements Helper_Interface_Actions {

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 5.0
	 */
	protected $log;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var Helper_Data
	 *
	 * @since 5.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var Helper_Misc
	 *
	 * @since 5.0
	 */
	protected $misc;

	/**
	 * @var Model_Core_Font_Downloader|null
	 *
	 * @since 6.19.0
	 */
	protected $downloader;

	/**
	 * Set up our dependencies
	 *
	 * @param LoggerInterface                 $log        Our logger class
	 * @param Helper_Data                     $data       Our plugin data store
	 * @param Helper_Misc                     $misc       Our miscellaneous class
	 * @param Model_Core_Font_Downloader|null $downloader Defaults to the registered downloader
	 *
	 * @since 5.0
	 * @since 6.19.0 Takes the core font downloader
	 */
	public function __construct( LoggerInterface $log, Helper_Data $data, Helper_Misc $misc, ?Model_Core_Font_Downloader $downloader = null ) {
		/* Assign our internal variables */
		$this->log        = $log;
		$this->data       = $data;
		$this->misc       = $misc;
		$this->downloader = $downloader;
	}

	/**
	 * Initialise our class defaults
	 *
	 * @return void
	 * @since 5.0
	 *
	 */
	public function init() {
		$this->add_actions();
	}

	/**
	 * Apply any actions needed for the welcome page
	 *
	 * @return void
	 * @since 5.0
	 *
	 */
	public function add_actions() {
		/* Register our AJAX event */
		add_action( 'wp_ajax_gfpdf_save_core_font', [ $this, 'save_core_font' ] );
	}

	/**
	 * An AJAX endpoint that handles authentication and downloading the core font
	 *
	 * @return void
	 * @since 5.0
	 *
	 */
	public function save_core_font() {
		/* User / CORS validation */
		$this->misc->handle_ajax_authentication( 'Save Core Font', 'gravityforms_edit_settings' );

		$results = $this->download_and_save_font();

		/* Return results */
		header( 'Content-Type: application/json' );
		echo wp_json_encode( $results );
		wp_die();
	}

	/**
	 * Download the requested core font, replacing any copy already on disk
	 *
	 * @return bool
	 * @since 5.0
	 * @since 6.19.0 Uses the core font downloader
	 */
	protected function download_and_save_font() {
		/* phpcs:ignore WordPress.Security.NonceVerification.Missing */
		$font_name = sanitize_text_field( wp_unslash( $_POST['font_name'] ?? '' ) );

		$downloader = $this->downloader ?? GPDFAPI::get_mvc_class( 'Model_Core_Font_Downloader' );
		if ( ! $downloader instanceof Model_Core_Font_Downloader ) {
			$this->log->error( 'Core font downloader not available' );

			return false;
		}

		return $downloader->download( $font_name, true );
	}
}
