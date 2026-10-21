<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Misc;
use GFPDF_Vendor\Psr\Log\LoggerInterface;

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
		$core_font_list = json_decode( file_get_contents( __DIR__ . '/../../build/payload/core-fonts.json' ), true );
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

		$this->misc->create_folder( $this->data->template_font_location );

		$font_path = $this->data->template_font_location . $matching_fonts[0]['name'];

		$res = wp_remote_get(
			$matching_fonts[0]['download_url'],
			[
				'timeout'  => 60,
				'stream'   => true,
				'filename' => $font_path,
			]
		);

		$res_code = wp_remote_retrieve_response_code( $res );

		/* A failed download still leaves the file it streamed into, which would hide the install notice */
		if ( is_wp_error( $res ) || $res_code !== 200 ) {
			wp_delete_file( $font_path );
		}

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

		if ( ! $this->is_expected_file( $font_path, (int) $matching_fonts[0]['size'], $matching_fonts[0]['sha'] ) ) {
			wp_delete_file( $font_path );

			$this->log->error(
				'Core Font Verification Failed',
				[
					'name' => $matching_fonts[0]['name'],
					'size' => $matching_fonts[0]['size'],
					'sha'  => $matching_fonts[0]['sha'],
				]
			);

			return false;
		}

		/* If we got here, the call was successful */

		return true;
	}

	/**
	 * Check a downloaded file against the size and git blob SHA-1 GitHub lists for it in core-fonts.json
	 *
	 * @param string $file
	 * @param int    $size
	 * @param string $sha
	 *
	 * @return bool
	 *
	 * @since 6.18.0
	 */
	protected function is_expected_file( $file, $size, $sha ) {
		clearstatcache( true, $file );

		if ( ! is_file( $file ) || filesize( $file ) !== $size ) {
			return false;
		}

		$hash = hash_init( 'sha1' );
		hash_update( $hash, 'blob ' . $size . "\0" );
		hash_update_file( $hash, $file );

		return hash_equals( $sha, hash_final( $hash ) );
	}
}
