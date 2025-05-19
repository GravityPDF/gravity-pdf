<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Misc;
use Psr\Log\LoggerInterface;

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
	 * @var string
	 *
	 * @since 5.0
	 */
	protected $github_repo = 'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/master/';

	/**
	 * Set up our dependencies
	 *
	 * @param LoggerInterface $log  Our logger class
	 * @param Helper_Data     $data Our plugin data store
	 * @param Helper_Misc     $misc Our miscellaneous class
	 *
	 * @since 5.0
	 */
	public function __construct( LoggerInterface $log, Helper_Data $data, Helper_Misc $misc ) {
		/* Assign our internal variables */
		$this->log  = $log;
		$this->data = $data;
		$this->misc = $misc;
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
	 * Stream files from remote server and save them locally
	 *
	 * @return bool
	 * @since 5.0
	 *
	 */
	protected function download_and_save_font() {

		/* Verify the font name provided is approved */
		/* phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents */
		$core_font_list = json_decode( file_get_contents( __DIR__ . '/../../dist/payload/core-fonts.json' ), true );
		if ( $core_font_list === null ) {
			$this->log->error( 'Core font list could not be loaded' );

			return false;
		}

		/* Look for a file in the font list with a matching name */
		$matching_fonts = array_filter(
			$core_font_list,
			function ( $item ) {
				/* phpcs:ignore WordPress.Security.NonceVerification.Missing */
				return $item['name'] === ( $_POST['font_name'] ?? '' );
			}
		);

		$matching_fonts = array_values( $matching_fonts );

		if ( ! isset( $matching_fonts[0] ) ) {
			$this->log->error(
				'Core Font not on the approved list',
				[
					/* phpcs:ignore WordPress.Security.NonceVerification.Missing */
					'name' => ( $_POST['font_name'] ?? '' ),
				]
			);

			return false;
		}

		/* Extra check to verify the download URL points to the correct repo */
		if ( strpos( $matching_fonts[0]['download_url'], $this->github_repo ) !== 0 ) {
			$this->log->error( 'Core font list is corrupted' );

			return false;
		}

		$res = wp_remote_get(
			$matching_fonts[0]['download_url'],
			[
				'timeout'  => 60,
				'stream'   => true,
				'filename' => $this->data->template_font_location . $matching_fonts[0]['name'],
			]
		);

		$res_code = wp_remote_retrieve_response_code( $res );

		/* Check for errors and log them to file */
		if ( is_wp_error( $res ) ) {
			$this->log->error(
				'Core Font Download Failed',
				[
					'name'             => $matching_fonts[0],
					'WP_Error_Message' => $res->get_error_message(),
					'WP_Error_Code'    => $res->get_error_code(),
				]
			);

			return false;
		}

		if ( $res_code !== 200 ) {
			$this->log->error(
				'Core Font API Response Failed',
				[
					'response_code' => wp_remote_retrieve_response_code( $res ),
				]
			);

			return false;
		}

		/* If we got here, the call was successful */

		return true;
	}
}
