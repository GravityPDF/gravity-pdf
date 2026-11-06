<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Model\Model_Font_Downloader;
use GFPDF\Model\Model_Required_Fonts;
use GPDFAPI;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

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
class Controller_Font_Downloader extends Helper_Abstract_Controller implements Helper_Interface_Actions {

	/**
	 * @var Model_Font_Downloader
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

	/**
	 * Pair the downloader with this controller, which triggers its background sync
	 *
	 * @since 6.19.0
	 */
	public function __construct( Model_Font_Downloader $model, Model_Required_Fonts $required_fonts ) {
		$this->model          = $model;
		$this->required_fonts = $required_fonts;
		$this->model->setController( $this );
	}

	/**
	 * Hook the background sync and the events that schedule it
	 *
	 * @return void
	 *
	 * @since 6.19.0
	 */
	public function init() {
		$this->add_actions();

		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * The Font Manager's downloaded fonts list and delete, beside the custom font routes
	 *
	 * @since 6.19.0
	 */
	public function register_endpoints(): void {
		register_rest_route(
			Helper_Data::REST_API_BASENAME . 'v1',
			'/fonts/downloaded',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_downloaded_fonts' ],
				'permission_callback' => [ $this, 'can_view_fonts' ],
			]
		);

		register_rest_route(
			Helper_Data::REST_API_BASENAME . 'v1',
			'/fonts/downloaded/(?P<key>[a-z0-9_\-]+)',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_downloaded_font' ],
				'permission_callback' => [ $this, 'can_delete_fonts' ],
			]
		);
	}

	/**
	 * @return array{fonts: array, can_delete: bool}
	 *
	 * @since 6.19.0
	 */
	public function get_downloaded_fonts(): array {
		return [
			'fonts'      => $this->model->get_downloaded_fonts(),
			'can_delete' => $this->can_delete_fonts(),
		];
	}

	/**
	 * @return array|WP_Error
	 *
	 * @since 6.19.0
	 */
	public function delete_downloaded_font( WP_REST_Request $request ) {
		return $this->model->delete_downloaded_font( (string) $request->get_param( 'key' ) );
	}

	/**
	 * The custom font routes' capability
	 *
	 * @since 6.19.0
	 */
	public function can_view_fonts(): bool {
		$allowed = GPDFAPI::get_form_class()->has_capability( 'gravityforms_edit_forms' );
		if ( ! $allowed ) {
			GPDFAPI::get_log_class()->warning( 'Permission denied: user does not have "gravityforms_edit_forms" capabilities' );
		}

		return $allowed;
	}

	/**
	 * Super admins only on Multisite, as the fonts folder is shared by the network
	 *
	 * @since 6.19.0
	 */
	public function can_delete_fonts(): bool {
		return $this->can_view_fonts() && ( ! is_multisite() || is_super_admin() );
	}

	/**
	 * Run the sync on its cron event, and schedule one when the fonts a site needs may have changed
	 *
	 * @return void
	 *
	 * @since 6.19.0
	 */
	public function add_actions() {
		add_action( Model_Font_Downloader::SYNC_EVENT, [ $this->model, 'sync' ] );

		add_action( 'gfpdf_plugin_installed', [ $this->model, 'schedule_sync' ] );
		add_action( 'gfpdf_version_changed', [ $this->model, 'schedule_sync' ] );
		add_action( 'upgrader_process_complete', [ $this, 'maybe_sync_translations' ], 10, 2 );

		/* Both hooks pass the saved settings second */
		add_action( 'add_option_gfpdf_settings', [ $this, 'maybe_sync_default_font' ], 10, 2 );
		add_action( 'update_option_gfpdf_settings', [ $this, 'maybe_sync_default_font' ], 10, 2 );

		/* Every form write, so a PDF saved by Gravity PDF, a form import or the form editor */
		add_action( 'gform_post_update_form_meta', [ $this, 'maybe_sync_form_fonts' ], 10, 3 );
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
	 * Sync when a newly saved default font has files to download
	 *
	 * @param mixed $old      The old settings, or the option name
	 * @param mixed $settings The saved settings
	 *
	 * @since 6.19.0
	 */
	public function maybe_sync_default_font( $old, $settings ): void {
		if ( ! is_array( $settings ) ) {
			return;
		}

		/* The settings hold every option, so skip saves that leave the default font alone */
		$font = $settings['default_font'] ?? '';
		if ( is_array( $old ) && ( $old['default_font'] ?? '' ) === $font ) {
			return;
		}

		if ( $this->required_fonts->needs_download( $font ) ) {
			$this->model->schedule_sync();
		}
	}

	/**
	 * Sync when a saved form has a PDF whose fonts have files to download
	 *
	 * @param mixed $form_meta The form's meta as JSON
	 * @param mixed $form_id
	 * @param mixed $meta_name The meta column written
	 *
	 * @since 6.19.0
	 */
	public function maybe_sync_form_fonts( $form_meta, $form_id = 0, $meta_name = '' ): void {
		if ( $meta_name !== 'display_meta' ) {
			return;
		}

		foreach ( $this->required_fonts->get_form_pdfs( $form_meta, is_numeric( $form_id ) ? (int) $form_id : 0 ) as $pdf ) {
			if ( $this->required_fonts->pdf_needs_download( $pdf ) ) {
				$this->model->schedule_sync();

				return;
			}
		}
	}
}
