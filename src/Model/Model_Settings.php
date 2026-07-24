<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Addon;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Form;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Options_Fields;
use GFPDF\Helper\Helper_Templates;
use Psr\Log\LoggerInterface;

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
 * Model_Welcome_Screen
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class Model_Settings extends Helper_Abstract_Model {

	/**
	 * Errors with the global form submission process are stored here
	 *
	 * @var array
	 *
	 * @since    4.0
	 *
	 * @Internal Deprecated method
	 */
	public $form_settings_errors;

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var Helper_Form
	 *
	 * @since 4.0
	 */
	protected $gform;

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 *
	 * @var Helper_Notices
	 *
	 * @since 4.0
	 */
	protected $notices;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	protected $options;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Holds our Helper_Templates object
	 * used to ease access to our PDF templates
	 *
	 * @var Helper_Templates
	 *
	 * @since 4.0
	 */
	protected $templates;

	/**
	 * Set up our dependencies
	 *
	 * @param Helper_Abstract_Form    $gform   Our abstracted Gravity Forms helper functions
	 * @param LoggerInterface         $log     Our logger class
	 * @param Helper_Notices          $notices Our notice class used to queue admin messages and errors
	 * @param Helper_Abstract_Options $options Our options class which allows us to access any settings
	 * @param Helper_Data             $data    Our plugin data store
	 * @param Helper_Misc             $misc    Our miscellaneous class
	 * @param Helper_Templates        $templates
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Form $gform, LoggerInterface $log, Helper_Notices $notices, Helper_Abstract_Options $options, Helper_Data $data, Helper_Misc $misc, Helper_Templates $templates ) {

		/* Assign our internal variables */
		$this->gform     = $gform;
		$this->log       = $log;
		$this->options   = $options;
		$this->notices   = $notices;
		$this->data      = $data;
		$this->misc      = $misc;
		$this->templates = $templates;
	}

	/**
	 * If any errors have been passed back from the options.php page we will highlight the actual fields that caused them
	 *
	 * @param array $settings The get_registered_fields() array
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function highlight_errors( $settings ) {

		/* We fire too late to tap into get_settings_error() so our data storage holds the details */
		$errors = get_transient( 'settings_errors' );

		/* Loop through errors if any and highlight the appropriate settings */
		if ( is_array( $errors ) && count( $errors ) > 0 ) {
			foreach ( $errors as $error ) {

				/* Skip over if not an error */
				if ( $error['type'] !== 'error' ) {
					continue;
				}

				/* Loop through our data until we find a match */
				$found = false;
				foreach ( $settings as $key => &$group ) {
					foreach ( $group as $id => &$item ) {
						if ( $item['id'] === $error['code'] ) {
							$item['class'] = ( isset( $item['class'] ) ) ? $item['class'] . ' gfield_error' : 'gfield_error';
							$found         = true;
							break;
						}
					}

					/* exit outer loop */
					if ( $found ) {
						break;
					}
				}
			}
		}

		return $settings;
	}

	/**
	 * Turn capabilities into more friendly strings
	 *
	 * @param string $cap The wordpress-style capability
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function style_capabilities( $cap ) {
		$cap = str_replace( 'gravityforms', 'gravity_forms', $cap );
		$cap = str_replace( '_', ' ', $cap );
		$cap = ucwords( $cap );

		return $cap;
	}

	/**
	 * Gets all the template information for use with our JS template selector
	 *
	 * @param array $strings
	 *
	 * @return array
	 *
	 * @since 4.1
	 */
	public function get_template_data( $strings ) {
		$strings['templateList']          = $this->templates->get_all_template_info();
		$strings['activeDefaultTemplate'] = $this->options->get_option( 'default_template' );

		$form_id = (int) rgget( 'id' );

		if ( $form_id ) {
			$pid = ( rgget( 'pid' ) ) ? sanitize_html_class( rgget( 'pid' ) ) : false;
			if ( $pid === false ) {
				$pid = rgpost( 'gform_pdf_id' ) ? sanitize_html_class( rgpost( 'gform_pdf_id' ) ) : false;
			}

			$pdf = $this->options->get_pdf( $form_id, $pid );

			if ( ! is_wp_error( $pdf ) ) {
				$strings['activeTemplate'] = $pdf['template'];
			}
		}

		return $strings;
	}

	/**
	 * Include License fields in the PDF Settings for each registered add-on
	 *
	 * @param array $fields The licensing fields
	 *
	 * @return array
	 *
	 * @since 4.2
	 */
	public function register_addons_for_licensing( $fields ) {

		foreach ( $this->data->addon as $addon ) {
			$slug = $addon->get_slug();

			$fields[ 'license_' . $slug ] = [
				'id'   => 'license_' . $slug,
				'name' => $addon->get_short_name(),
				'type' => 'license',
				'data' => $addon,
			];

			$fields[ 'license_' . $slug . '_message' ] = [
				'id'    => 'license_' . $slug . '_message',
				'type'  => 'hidden',
				'class' => 'gfpdf-hidden',
			];

			$fields[ 'license_' . $slug . '_status' ] = [
				'id'    => 'license_' . $slug . '_status',
				'type'  => 'hidden',
				'class' => 'gfpdf-hidden',
			];
		}

		return $fields;
	}

	/**
	 * Check the current add-on license key status and do an API call if the status isn't already active and the
	 * license key has been included. Update special hidden "message" and "license" fields with API response
	 *
	 * @param array $input The $_POST data provided by the Settings API
	 *
	 * @return array
	 *
	 * @since 4.2
	 */
	public function maybe_active_licenses( $input ) {

		$settings = $this->options->get_settings();

		/* Check if we are submitting our settings and there's an active key */
		foreach ( $this->data->addon as $addon ) {
			$option_key = 'license_' . $addon->get_slug();
			if ( ! isset( $input[ $option_key ] ) ) {
				continue;
			}

			/* An admin-managed key is authoritative — ignore the submitted value and don't burn an activation */
			if ( $addon->is_license_admin_managed() ) {
				continue;
			}

			/* Check if the license key is now empty */
			if ( trim( $input[ $option_key ] ) === '' ) {
				$input[ $option_key . '_message' ] = '';
				$input[ $option_key . '_status' ]  = '';

				/* Sync the in-memory copy too, else get_license_status() returns the stale prior value this request */
				$addon->update_license_info(
					[
						'license' => '',
						'status'  => '',
						'message' => '',
					]
				);

				/* Clearing the field removes the license as surely as the Deactivate button does — drop the cached
				   package. Every add-on posts an empty field on an unrelated save, so only flush when a key was set. */
				if ( ! empty( $settings[ $option_key ] ) ) {
					$addon->flush_update_cache();
				}

				continue;
			}

			/* Ensure the un-hashed license key saved in the database is not overridden by the hashed license when resubmitting form */
			if ( isset( $settings[ $option_key ] ) && sha1( $settings[ $option_key ] ) === $input[ $option_key ] ) {
				$input[ $option_key ] = $settings[ $option_key ];
			}

			/* Run license activation if a new key was submitted, or the existing key isn't valid */
			if (
				! in_array( $input[ $option_key . '_status' ] ?? '', [ 'active', 'valid' ], true ) ||
				( isset( $settings[ $option_key ] ) && $settings[ $option_key ] !== $input[ $option_key ] )
			) {
				$results = $addon->activate_license( $input[ $option_key ] );

				$input[ $option_key . '_message' ] = $results['message'];
				$input[ $option_key . '_status' ]  = $results['status'];
			}
		}

		/* Persist the authoritative key/status for admin-managed addons */
		foreach ( $this->data->addon as $addon ) {
			if ( ! $addon->is_license_admin_managed() ) {
				continue;
			}

			$option_key                        = 'license_' . $addon->get_slug();
			$input[ $option_key ]              = $addon->get_license_key();
			$input[ $option_key . '_message' ] = $addon->get_license_message();
			$input[ $option_key . '_status' ]  = $addon->get_license_status();
		}

		return $input;
	}

	/**
	 * An AJAX endpoint for processing license deactivations
	 *
	 * @Internal Expected parameters include:
	 *           $_POST['addon_name']
	 *           $_POST['license']
	 *
	 * @since    4.2
	 */
	public function process_license_deactivation() {

		/* User / CORS validation */
		$this->misc->handle_ajax_authentication( 'Deactivate License', 'gravityforms_edit_settings', 'gfpdf_deactivate_license' );

		/** @var Helper_Abstract_Addon $addon */
		/* phpcs:ignore WordPress.Security.NonceVerification.Missing */
		$addon = $this->data->addon[ ( $_POST['addon_name'] ?? '' ) ] ?? false;

		/* Check add-on currently installed */
		if ( empty( $addon ) ) {
			$this->log->error( 'AJAX Endpoint Error' );

			echo wp_json_encode(
				[
					'error' => wp_kses(
						sprintf(
							/* translators: 1: Opening <a> tag, 2: Closing </a> tag */
							__( 'An unknown error occurred, and your license key may not have been correctly deactivated. %1$sLogin to your GravityPDF.com account%2$s and check if your site has been unlinked from the key.', 'gravity-pdf' ),
							'<a href="https://gravitypdf.com/account/licenses/">',
							'</a>'
						),
						[ 'a' => [ 'href' => [] ] ]
					),
				]
			);

			wp_die();
		}

		if ( ! empty( $addon->get_license_status() ) && ! $addon->deactivate_license() ) {
			echo wp_json_encode(
				[
					'error' => wp_kses(
						sprintf(
							/* translators: 1: Opening <a> tag, 2: Closing </a> tag */
							__( 'An API error occurred and your license key may not have been correctly deactivated. %1$sLogin to your GravityPDF.com account%2$s and check if your site has been unlinked from the key.', 'gravity-pdf' ),
							'<a href="https://gravitypdf.com/account/licenses/">',
							'</a>'
						),
						[ 'a' => [ 'href' => [] ] ]
					),
				]
			);

			wp_die();
		}

		/* All Access Pass licenses are site-activated, not product-activated. Deactivate other addons linked to license */
		$extra = [];
		foreach ( $this->data->addon as $addon ) {
			if ( ! $addon->has_license_auto_deactivated() ) {
				continue;
			}

			$extra[] = $addon->get_slug();
		}

		$this->log->notice( 'AJAX – Successfully Deactivated License' );

		$message = empty( $extra ) ?
			esc_html__( 'License key deactivated.', 'gravity-pdf' ) :
			esc_html__( 'Access Pass license key deactivated.', 'gravity-pdf' );

		echo wp_json_encode(
			[
				'success' => $message,
				'extra'   => $extra,
			]
		);

		wp_die();
	}

	/**
	 * Optimize plugin version checks by combining them into a single request
	 *
	 * @param array $api_params
	 *
	 * @return array
	 *
	 * @since 6.16.0
	 */
	public function licensing_bulk_get_version_api_params( $api_params ) {
		/* Collect each add-on updater, dropping any that haven't been initialized (a third-party subclass may return null) */
		$products = array_filter(
			array_map(
				function ( $addon ) {
					return $addon->get_plugin_updater();
				},
				$this->data->addon
			)
		);

		/* Include the core plugin only when its updater exists (absent on the non-canonical wordpress.org build) */
		if ( ! empty( $this->data->updater ) ) {
			$products = array_merge( [ 'gravity-pdf' => $this->data->updater ], $products );
		}

		/* Nothing to bundle — leave the individual request untouched */
		if ( empty( $products ) ) {
			return $api_params;
		}

		$bulk_api_params = [];
		foreach ( $products as $product ) {
			$bulk_api_params[] = $product->get_version_api_params();
		}

		return [
			'edd_action' => 'get_version',
			'products'   => $bulk_api_params,
		];
	}

	/**
	 * Handle the bulk get_version API response: cache each bundled product against its own updater and hand back the
	 * product that initiated the request for its normal caching path.
	 *
	 * @param mixed  $response    The decoded API response — an array of product objects, or a non-array passed straight through
	 * @param array  $api_data    The initiating updater's API data (part of the filter signature; unused here)
	 * @param string $plugin_file The initiating plugin file, used to single out which product to hand back
	 *
	 * @return \stdClass|mixed|null The initiating product object, null when it isn't in the response, or $response unchanged when not an array
	 *
	 * @since 6.16.0
	 */
	public function licensing_bulk_get_version_api_response( $response, $api_data, $plugin_file ) {
		if ( ! is_array( $response ) ) {
			return $response;
		}

		/* Map each bundled updater by the slug it sent (the plugin-folder basename the API echoes back), so a response
		   links to the right product even when an add-on's registered slug differs from its folder — e.g. a renamed folder. */
		$updaters = [];
		foreach ( $this->data->addon as $addon ) {
			$updater = $addon->get_plugin_updater();
			if ( $updater ) {
				$updaters[ $updater->get_slug() ] = $updater;
			}
		}

		if ( ! empty( $this->data->updater ) ) {
			$updaters[ $this->data->updater->get_slug() ] = $this->data->updater;
		}

		$initial_request_data = null;

		foreach ( $response as $product ) {
			if ( ! isset( $product->slug, $updaters[ $product->slug ] ) ) {
				continue; // couldn't link response to a product, skip.
			}

			$updater = $updaters[ $product->slug ];

			/* skip the product that initialized the request, as it'll be handled in the return method */
			if ( $updater->get_plugin_file() !== $plugin_file ) {
				$updater->set_version_info_cache( $updater->standardize_api_response( $product ) );
			} else {
				$initial_request_data = $product;
			}
		}

		return $initial_request_data;
	}

	/**
	 * Check the status of licenses from the API in bulk
	 *
	 * @return bool
	 *
	 * @since 6.16.0
	 */
	public function licensing_bulk_license_check() {
		if ( $this->misc->is_secondary_network_site( PDF_PLUGIN_BASENAME ) ) {
			return false;
		}

		$addons = $this->data->addon;
		if ( empty( $addons ) ) {
			return false;
		}

		$bulk_api_params = [];
		$grouped_addons  = [];

		foreach ( $addons as $addon ) {
			/* Skip if no license key has been saved */
			if ( empty( $addon->get_license_key() ) ) {
				continue;
			}

			$updater = $addon->get_plugin_updater();
			if ( ! $updater ) {
				continue;
			}

			$bulk_api_params[]                               = $updater->get_version_api_params();
			$grouped_addons[ $addon->get_edd_download_id() ] = $addon;
		}

		if ( empty( $bulk_api_params ) ) {
			return false;
		}

		$response = wp_remote_post(
			$this->data->store_url,
			[
				'timeout' => 15,
				'body'    => [
					'edd_action' => 'check_license',
					'products'   => $bulk_api_params,
				],
			]
		);

		/* Check for problems contacting the licensing server */
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			$this->log->error(
				'Failed to contact remote API for bulk license status check.',
				[
					'error' => is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_response_code( $response ),
				]
			);

			wp_schedule_single_event( strtotime( '+3 hour' ), 'gfpdf_bulk_license_check' );

			return false;
		}

		/* Check for a malformed response */
		$body          = wp_remote_retrieve_body( $response );
		$license_check = json_decode( $body );
		if ( ! is_array( $license_check ) ) {
			/* Log the decoded body so the redactor can traverse nested keys; cap the raw fallback to bound any leak */
			$this->log->error(
				'Invalid response returned from bulk license status check.',
				[ 'response' => $license_check ?? substr( $body, 0, 500 ) ]
			);

			wp_schedule_single_event( strtotime( '+3 hour' ), 'gfpdf_bulk_license_check' );

			return false;
		}

		/* Loop over the response and update any licenses that have changed */
		foreach ( $license_check as $addon_response ) {
			if ( ! isset( $addon_response->item_id, $addon_response->license, $grouped_addons[ $addon_response->item_id ] ) ) {
				$this->log->error( 'Invalid response for individual addon during bulk license status check.', [ 'response' => $addon_response ] );

				continue;
			}

			$addon = $grouped_addons[ $addon_response->item_id ];
			if ( $addon->get_license_status() === $addon_response->license ) {
				$this->log->notice(
					'License status has not changed.',
					[
						'response' => $addon_response,
						'slug'     => $addon->get_slug(),
					]
				);

				continue;
			}

			$addon->update_license_status_from_response(
				$addon->get_license_key(),
				[
					'response' => [ 'code' => 200 ],
					'body'     => wp_json_encode( $addon_response ),
				],
				true
			);

		}

		return true;
	}

	/**
	 * Force the network to display update nags when the plugin is only activated on a subsite
	 *
	 * @return void
	 *
	 * @since 6.16.0
	 */
	public function run_network_update_check() {
		if ( ! is_multisite() || is_main_site() ) {
			return;
		}

		/* Network-activated installs receive update checks through the normal flow, so don't re-arm the event there */
		if ( $this->misc->is_secondary_network_site( PDF_PLUGIN_BASENAME ) ) {
			return;
		}

		/*
		 * Skip a full wp_plugin_update() check and instead force the `pre_set_site_transient_update_plugins` filter to
		 * do its job, which many addons (including Gravity PDF) use to inject update info. Run in the current (subsite)
		 * context, not the main site: update_plugins is network-global, so re-injecting here fires check_update() where
		 * the per-site add-on is active and its cached network package is found — no per-updater API request.
		 */
		$update_info = get_site_transient( 'update_plugins' );
		if ( $update_info !== false ) {
			set_site_transient( 'update_plugins', $update_info );
		}

		/* Re-arm the one-off event so the next run re-syncs to the primary site's plugin update schedule */
		$this->schedule_network_update_check();
	}

	/**
	 * Schedule the next one-off network update check, one minute after the primary site's plugin update check
	 *
	 * A self-rescheduling single event (rather than a fixed recurring one) recomputes the offset every cycle, so it
	 * re-syncs whenever WordPress reschedules its own plugin update check.
	 *
	 * @return void
	 *
	 * @since 6.16.0
	 */
	public function schedule_network_update_check() {
		switch_to_blog( get_main_site_id() );
		$timestamp = wp_next_scheduled( 'wp_update_plugins' );
		restore_current_blog();

		if ( $timestamp === false ) {
			$timestamp = time() + 12 * HOUR_IN_SECONDS;
		}

		/* wp_next_scheduled() returns a past time when the primary site's cron is quiet; floor to the future so the
		   self-rescheduling event isn't perpetually due (which would re-fire on every cron spawn). */
		$timestamp = max( $timestamp, time() + HOUR_IN_SECONDS );

		wp_schedule_single_event( $timestamp + 60, 'gfpdf_network_update_check' );
	}

	/**
	 * Do API call to GravityPDF.com to deactivate add-on license
	 *
	 * @param Helper_Abstract_Addon $addon
	 * @param string                $license_key
	 *
	 * @return bool
	 *
	 * @since 4.2
	 * @deprecated 6.16.0 Moved to Addon framework
	 */
	public function deactivate_license_key( Helper_Abstract_Addon $addon, $license_key = '' ) {
		return $addon->deactivate_license();
	}

	/**
	 * Removes the current font's TTF files from our font directory
	 *
	 * @param array $fonts The font config
	 *
	 * @since  4.0
	 *
	 * @deprecated Removed in 6.0. Use GPDFAPI::delete_pdf_font()
	 */
	public function remove_font_file( $fonts ) {}

	/**
	 * Check that the font name passed conforms to our expected naming convention
	 *
	 * @param string $name The font name to check
	 *
	 * @since 4.0
	 *
	 * @deprecated Moved in 6.0. Use Model_Custom_Fonts::check_font_name_valid()
	 */
	public function is_font_name_valid( $name ) {}

	/**
	 * Query our custom fonts options table and check if the font name already exists
	 *
	 * @param string     $name The font name to check
	 * @param int|string $id   The configuration ID (if any)
	 *
	 * @since 4.0
	 *
	 * @deprecated Removed in 6.0. Font names no longer need to be unique
	 */
	public function is_font_name_unique( $name, $id = '' ) {}

	/**
	 * Handles the database updates required to save a new font
	 *
	 * @param array $fonts
	 *
	 * @since 4.0
	 *
	 * @deprecated Moved in 6.0 to Model_Custom_Fonts::add_font()
	 */
	public function install_fonts( $fonts ) {}

	/**
	 * AJAX Endpoint for saving the custom font
	 *
	 * @since 4.0
	 *
	 * @deprecated Moved in 6.0. Use GPDFAPI::add_pdf_font()
	 */
	public function save_font() {}

	/**
	 * AJAX Endpoint for deleting a custom font
	 *
	 * @since 4.0
	 *
	 * @deprecated Moved in 6.0. Use GPDFAPI::delete_pdf_font()
	 */
	public function delete_font() {}

	/**
	 * Validate user input and save as new font
	 *
	 * @param array $font The four font fields to be processed
	 *
	 * @since 4.0
	 *
	 * @deprecated Removed in 6.0. Use GPDFAPI::add_pdf_font()
	 */
	public function process_font( $font ) {}

	/**
	 * Find the font unique ID from the font name
	 *
	 * @param string $font_name
	 *
	 * @since 4.1
	 *
	 * @deprecated Removed in 6.0. Font names no longer linked to IDs.
	 */
	public function get_font_id_by_name( $font_name ) {}

	/**
	 * Create a file in our tmp directory and check if it is publicly accessible (i.e no .htaccess protection)
	 *
	 * @since 4.0
	 *
	 * @deprecated Functionality removed in 6.0
	 */
	public function check_tmp_pdf_security() {}

	/**
	 * Create a file in our tmp directory and verify if it's protected from the public
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 *
	 * @deprecated Moved in 6.0. Use Model_System_Report::test_public_tmp_directory_access()
	 */
	public function test_public_tmp_directory_access() {
		/** @var Model_System_Report $model_system_report */
		$model_system_report = \GPDFAPI::get_mvc_class( 'Model_System_Report' );

		return $model_system_report->test_public_tmp_directory_access();
	}
}
