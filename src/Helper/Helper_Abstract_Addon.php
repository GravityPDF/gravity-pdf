<?php

namespace GFPDF\Helper;

use GFPDF\Helper\Licensing\EDD_SL_Plugin_Updater;
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
 * An abstract class to assist with addon licensing
 */
abstract class Helper_Abstract_Addon {

	/**
	 * @var string The add-on slug (usually the name with the spaces substituted for hyphens)
	 *
	 * @since 4.2
	 */
	protected $slug;

	/**
	 * @var string The add-on name (should match the name/title used in EDD)
	 *
	 * @since 4.2
	 */
	protected $name;

	/**
	 * @var string The add-on author
	 *
	 * @since 4.2
	 */
	protected $author;

	/**
	 * @var string The add-on version
	 *
	 * @since 4.2
	 */
	protected $version;

	/**
	 * @var string The add-on mail file path
	 *
	 * @since 4.2
	 */
	protected $addon_path_main_plugin_file;

	/**
	 * Holds our registered objects
	 *
	 * @var Helper_Singleton
	 *
	 * @since 4.2
	 */
	public $singleton;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var Helper_Data
	 *
	 * @since 4.2
	 */
	protected $data;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var Helper_Options_Fields
	 *
	 * @since 4.2
	 */
	protected $options;

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 4.2
	 */
	protected $log;

	/**
	 * Give easy access to our notice helper
	 *
	 * @var Helper_Notices
	 *
	 * @since 4.2
	 */
	protected $notices;

	/**
	 * Holds the Easy Digital Download add-on ID
	 *
	 * @since 4.3
	 */
	protected $edd_id = '';

	/**
	 * Holds the Plugin Documentation Slug
	 *
	 * @since 4.3
	 */
	protected $addon_documentation_slug = '';

	/**
	 * Determine whether we should use a prefix for this add-ons global settings
	 *
	 * @since 6.5
	 * @internal This has been added for backwards compatibility. Use self::enable_settings_prefix() after initialization to opt in
	 */
	protected $use_settings_prefix = false;

	/**
	 * @var EDD_SL_Plugin_Updater
	 * @since 6.14.0
	 */
	protected $plugin_updater;

	/**
	 * @var string The current license key for this addon
	 * @since 6.14.0
	 */
	protected $license_key = '';

	/**
	 * @var string The current license key status (retrieved from the API) for this addon
	 * @since 6.14.0
	 */
	protected $license_key_status = '';

	/**
	 * @var string The current license key message for this addon (based on the status)
	 * @since 6.14.0
	 */
	protected $license_key_message = '';

	/**
	 * @var bool Whether the addon activated the license based on another addon activation
	 * @since 6.14.0
	 */
	protected $license_auto_activated = false;

	/**
	 * @var bool Whether the addon deactivated the license based on another addon deactivation
	 * @since 6.14.0
	 */
	protected $license_auto_deactivated = false;

	/**
	 * Helper_Abstract_Addon constructor.
	 *
	 * @param string                $addon_slug
	 * @param string                $addon_name
	 * @param string                $author
	 * @param string                $version
	 * @param string                $path_to_main_plugin_file
	 * @param Helper_Data           $data
	 * @param Helper_Options_Fields $options
	 * @param Helper_Singleton      $singleton
	 * @param Helper_Logger         $log
	 * @param Helper_Notices        $notices
	 *
	 * @since 4.2
	 */
	public function __construct( $addon_slug, $addon_name, $author, $version, $path_to_main_plugin_file, Helper_Data $data, Helper_Options_Fields $options, Helper_Singleton $singleton, Helper_Logger $log, Helper_Notices $notices ) {
		$this->slug                        = $addon_slug;
		$this->name                        = $addon_name;
		$this->author                      = $author;
		$this->version                     = $version;
		$this->addon_path_main_plugin_file = $path_to_main_plugin_file;

		$this->data      = $data;
		$this->options   = $options;
		$this->singleton = $singleton;
		$this->log       = $log->get_logger();

		$this->notices = $notices;
		$this->notices->init();
	}

	/**
	 * @return string Return the plugin slug
	 *
	 * @since 4.2
	 */
	final public function get_slug() {
		return $this->slug;
	}

	/**
	 * @return string Return the plugin name
	 *
	 * @since 4.2
	 */
	final public function get_name() {
		return $this->name;
	}

	/**
	 * @return string Return the short name for the plugin
	 *
	 * @since 4.2
	 */
	public function get_short_name() {
		return trim(
			str_replace(
				'Gravity PDF',
				'',
				$this->get_name()
			)
		);
	}

	/**
	 * @return string Return the plugin version
	 *
	 * @since 4.2
	 */
	final public function get_version() {
		return $this->version;
	}

	/**
	 * @return string Return the plugin author
	 *
	 * @since 4.2
	 */
	final public function get_author() {
		return $this->author;
	}

	/**
	 * @return string Return the plugin main file path
	 *
	 * @since 4.2
	 */
	final public function get_main_plugin_file() {
		return $this->addon_path_main_plugin_file;
	}

	/**
	 * @param string $id
	 *
	 * @since 4.3
	 */
	final public function set_edd_download_id( $id ) {
		$this->edd_id = $id;
	}

	/**
	 * @return string Return the EDD add-on ID
	 *
	 * @since 4.3
	 */
	final public function get_edd_download_id() {
		return $this->edd_id;
	}

	/**
	 * @param string $slug
	 *
	 * @since 4.3
	 */
	final public function set_addon_documentation_slug( $slug ) {
		$this->addon_documentation_slug = $slug;
	}

	/**
	 * @return string
	 *
	 * @since 4.3
	 */
	final public function get_addon_documentation_slug() {
		return $this->addon_documentation_slug;
	}

	/**
	 * @return EDD_SL_Plugin_Updater|null
	 * @since 6.14.0
	 */
	public function get_plugin_updater() {
		$updater = $this->plugin_updater;
		if ( ! $updater ) {
			_doing_it_wrong( __METHOD__, 'This method should not be called before the "init" hook (priority 1)', '6.14.0' );
		}

		return $updater;
	}

	/**
	 * Setup the add-on licensing and initialise any classes
	 *
	 * @param array $classes
	 *
	 * @since 4.2
	 */
	public function init( $classes = [] ) {

		/* Get and store the license information from the database */
		$this->get_license_info( true );

		/*
		 * Register our plugin updater
		 */
		$central_plugin_updater = function () {
			$this->central_plugin_updater();
		};

		add_action( 'init', $central_plugin_updater, 1 );

		/* Maybe auto-activate hardcoded license */
		$maybe_activate_hardcoded_license = function () {
			$hardcoded_license = $this->get_license_key_from_constant();
			if (
				$hardcoded_license && /* is constant defined */
				$hardcoded_license !== $this->license_key && /* is hardcoded license different to DB version */
				! $this->license_auto_activated && /* if addon hasn't already be auto-activated when another addon was activated */
				is_admin() /* is the admin area */
			) {
				$this->activate_license( $hardcoded_license, true );
			}
		};

		add_action( 'init', $maybe_activate_hardcoded_license, 2 );

		/*
		 * Automatically register our addon with the main plugin to enable license management in the UI
		 */
		$this->register_addon();

		/*
		 * Register add-on fields (if any) when class uses our extension interface
		 */
		if ( $this instanceof Helper_Interface_Extension_Settings ) {
			add_filter( 'gfpdf_settings_extensions', [ $this, 'register_addon_fields' ] );
		}

		/* Add listener for now-deprecated individual licence check (handled in bulk in Controller_Settings) */
		add_action( 'gfpdf_' . $this->get_slug() . '_license_check', [ $this, 'schedule_license_check' ] );

		/* Add listener for other license activation/deactivation */
		add_action( 'gfpdf_addon_post_license_activation', [ $this, 'maybe_auto_activate_license' ], 10, 3 );
		add_action( 'gfpdf_addon_post_license_deactivation', [ $this, 'maybe_auto_deactivate_license' ], 10, 2 );

		/*
		 * Include info on plugin listing
		 */
		add_action( 'after_plugin_row_' . plugin_basename( $this->get_main_plugin_file() ), [ $this, 'license_registration' ] );
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );

		/*
		 * Register Translation
		 */
		load_plugin_textdomain( $this->get_slug(), false, dirname( plugin_basename( $this->get_main_plugin_file() ) ) . '/languages' );

		/*
		 * Run the init() method (if it exists) for the add-on classes and register them with our internal singleton
		 */
		array_walk(
			$classes,
			function ( $class_object ) {

				/* Inject the logger class if using the trait Helper_Trait_Logger */
				$trait = class_uses( $class_object );
				if ( isset( $trait['GFPDF\Helper\Helper_Trait_Logger'] ) ) {
					$class_object->set_logger( $this->log );
				}

				if ( method_exists( $class_object, 'init' ) ) {
					$class_object->init();
				}

				$this->singleton->add_class( $class_object );
			}
		);
	}

	/**
	 * This method handles the add-on update code
	 *
	 * Official Gravity PDF add-ons should initialise the GFPDF\Helper\Licensing\EDD_SL_Plugin_Updater class
	 * when the add-on license status is set to "active". You can check the status of the plugin
	 * using the following:
	 *
	 * $license_info = $this->get_license_info();
	 * if ( in_array( $this->get_license_status(), [ 'active', 'valid' ], true ) ) {
	 *    return;
	 * }
	 *
	 * The EDD_SL_Plugin_Updater should be initialised as follows:
	 *
	 * new EDD_SL_Plugin_Updater(
	 *     $this->data->store_url,
	 *   $this->get_main_plugin_file(),
	 *   [
	 *      'version'   => $this->get_version(),
	 *      'license'   => $license_info['license'],
	 *      'item_name' => $this->get_addon_name(),
	 *      'author'    => $this->get_version(),
	 *      'beta'      => false,
	 *   ]
	 * );
	 *
	 * @return void
	 * @since 4.2
	 * @depecated 6.14.0 Use self::central_plugin_updater()
	 */
	public function plugin_updater() {}

	/**
	 * @return array
	 * @since 6.14.0
	 */
	public function get_default_api_params() {
		return [
			'version'   => $this->get_version(),
			'license'   => $this->get_license_key(),
			'item_name' => $this->get_short_name(),
			'item_id'   => $this->get_edd_download_id(),
			'author'    => $this->get_author(),
			'beta'      => false,
		];
	}

	/**
	 * The central add-on update initializer
	 *
	 * @return void
	 * @since 6.14
	 */
	protected function central_plugin_updater() {
		$this->plugin_updater = new EDD_SL_Plugin_Updater(
			$this->data->store_url,
			$this->get_main_plugin_file(),
			$this->get_default_api_params()
		);

		$this->plugin_updater->init();
	}

	/**
	 * Register the add-on with Gravity PDF
	 *
	 * @since    4.2
	 */
	protected function register_addon() {
		$this->data->add_addon( $this );
	}

	/**
	 * When Helper_Interface_Extension_Settings is used we'll auto-register any
	 * settings the add-on includes
	 *
	 * @param array $settings
	 *
	 * @return array
	 *
	 * @since 4.2
	 */
	final public function register_addon_fields( $settings ) {
		/*
		 * Because this method is called via a filter it needs to be public
		 * so we'll check the class implements the correct interface before
		 * doing anything.
		 */
		if ( ! $this instanceof Helper_Interface_Extension_Settings ) {
			return $settings;
		}

		/* Add our settings prefix automatically */
		$fields = [];
		foreach ( $this->get_global_addon_fields() as $field ) {
			$field['id']            = $this->get_addon_settings_key() . $field['id'];
			$fields[ $field['id'] ] = $field;
		}

		return array_merge( $settings, $fields );
	}

	/**
	 * Allows add-ons to opt into using a prefix on the settings.
	 *
	 * @return void
	 *
	 * @since 6.5
	 * @internal This was added for backwards compatibility in case user-land implemented global add-on settings
	 */
	public function enable_settings_prefix(): void {
		$this->use_settings_prefix = true;
	}

	/**
	 * Return the prefix to use for all add-on global settings
	 *
	 * @return string
	 * @since 6.5
	 */
	public function get_addon_settings_key(): string {
		return $this->use_settings_prefix ? 'addon_' . $this->get_slug() . '_' : '';
	}

	/**
	 * Get all the global setting default values which is useful as a fallback if the setting doesn't yet exist in the DB
	 *
	 * @return array
	 * @since 6.5
	 */
	public function get_addon_settings_defaults(): array {
		if ( ! $this instanceof Helper_Interface_Extension_Settings ) {
			return [];
		}

		$defaults = [];
		foreach ( $this->get_global_addon_fields() as $field ) {
			if ( ! isset( $field['std'] ) ) {
				continue;
			}

			$defaults[ $field['id'] ] = $field['std'];
		}

		return $defaults;
	}

	/**
	 * Return all registered settings IDs, with or without the prefix string included
	 *
	 * @param bool $include_prefix
	 *
	 * @return array
	 * @since 6.5
	 */
	final protected function get_addon_settings_ids( bool $include_prefix = true ): array {
		if ( ! $this instanceof Helper_Interface_Extension_Settings ) {
			return [];
		}

		$ids = array_keys( $this->get_global_addon_fields() );
		if ( $include_prefix ) {
			$ids = array_map(
				function ( $id ) {
					return $this->get_addon_settings_key() . $id;
				},
				$ids
			);
		}

		return $ids;
	}

	/**
	 * Get all available settings for this add-on that are stored in the DB
	 *
	 * @return array an ID => Value paid, where ID does NOT include the setting prefix
	 * @since 6.5
	 */
	final public function get_addon_settings_values(): array {
		if ( ! $this instanceof Helper_Interface_Extension_Settings ) {
			return [];
		}

		$setting_ids   = $this->get_addon_settings_ids();
		$prefix_length = strlen( $this->get_addon_settings_key() );

		/* Get only settings that apply to this add-on */
		$filters_settings = array_filter(
			$this->options->get_settings(),
			function ( $key ) use ( $setting_ids ) {
				return in_array( $key, $setting_ids, true );
			},
			ARRAY_FILTER_USE_KEY
		);

		$processed_settings = [];
		foreach ( $filters_settings as $key => $value ) {
			$processed_settings[ substr( $key, $prefix_length ) ] = $value;
		}

		return $processed_settings;
	}

	/**
	 * Get the add-on global setting from the DB, or return the fallback if it doesn't exist
	 *
	 * @param string $name The settings key name without the prefix
	 * @param mixed $fallback A fallback value if the setting doesn't exist
	 *
	 * @return mixed
	 * @since 6.5
	 */
	final public function get_addon_setting_value( string $name, $fallback = '' ) {
		if ( ! $this instanceof Helper_Interface_Extension_Settings ) {
			return $fallback;
		}

		return $this->options->get_settings()[ $this->get_addon_settings_key() . $name ] ?? $fallback;
	}

	/**
	 * Get the add-on license information (if any)
	 *
	 * @param bool $use_database Fetch license info from the database
	 *
	 * @since    4.2
	 * @since 6.14.0 Get license info stored in the object
	 */
	public function get_license_info( $use_database = false ) {
		if ( $use_database ) {
			$settings = $this->options->get_settings();

			$slug                      = $this->get_slug();
			$this->license_key         = $settings[ "license_$slug" ] ?? '';
			$this->license_key_status  = $settings[ "license_{$slug}_status" ] ?? '';
			$this->license_key_message = $settings[ "license_{$slug}_message" ] ?? '';
		}

		$license_details = [
			'license' => $this->get_license_key(),
			'status'  => $this->get_license_status(),
			'message' => $this->get_license_message(),
		];

		return $license_details;
	}

	/**
	 * Update the add-on license information stored in the database
	 *
	 * @param array $license_info
	 * @param bool $use_database Whether to update the database or not. A DB update will auto-call Model_Settings::maybe_active_licenses(), which may not be ideal
	 *
	 * @since    4.2
	 * @since 6.14.0 Added
	 */
	public function update_license_info( $license_info, $use_database = false ) {
		$this->license_key         = $license_info['license'] ?? '';
		$this->license_key_status  = $license_info['status'] ?? '';
		$this->license_key_message = $license_info['message'] ?? '';

		/* Check the update has been initialized before setting the license key */
		if ( isset( $this->plugin_updater ) ) {
			$this->plugin_updater->set_license_key( $this->license_key );
		}

		if ( ! $use_database ) {
			return;
		}

		$settings = $this->options->get_settings();
		$slug     = $this->get_slug();

		$settings[ "license_$slug" ]           = $this->get_license_key();
		$settings[ "license_{$slug}_status" ]  = $this->get_license_status();
		$settings[ "license_{$slug}_message" ] = $this->get_license_message();

		$this->log->notice( 'Update plugin license details', $license_info );

		$this->options->update_settings( $settings );
	}

	/**
	 * Remove the license info and keys from the settings
	 *
	 * @since 4.2
	 */
	public function delete_license_info() {
		$this->update_license_info( [] );

		/* Check the update has been initialized before setting the license key */
		if ( isset( $this->plugin_updater ) ) {
			$this->plugin_updater->set_license_key( '' );
		}

		$settings = $this->options->get_settings();
		$slug     = $this->get_slug();

		unset(
			$settings[ "license_$slug" ],
			$settings[ "license_{$slug}_status" ],
			$settings[ "license_{$slug}_message" ]
		);

		wp_clear_scheduled_hook( 'gfpdf_' . $slug . '_license_check' );

		$this->log->notice( 'Delete plugin license details' );

		$this->options->update_settings( $settings );
	}

	/**
	 * @return string Returns the current add-on license key
	 *
	 * @since 4.2
	 */
	final public function get_license_key() {
		$hardcoded_license = $this->get_license_key_from_constant();

		return $hardcoded_license ?: $this->license_key;
	}

	/**
	 * Get a Gravity PDF license key defined in the `GPDF_LICENSE_KEY` PHP constant
	 *
	 * @return false|string
	 *
	 * @since 6.14.0
	 */
	final public function get_license_key_from_constant() {
		$slug = $this->get_slug();

		/** @var string|array $license_key */
		$license_key = defined( 'GPDF_LICENSE_KEY' ) ? GPDF_LICENSE_KEY : null;
		$license_key = apply_filters( 'gfpdf_addon_hardcoded_license_key', $license_key, $slug, $this );

		if ( empty( $license_key ) ) {
			return false;
		}

		/* universal license */
		if ( is_string( $license_key ) ) {
			return $license_key;
		}

		/* extension-specific license */
		if ( is_array( $license_key ) && isset( $license_key[ $slug ] ) ) {
			return $license_key[ $slug ];
		}

		if ( is_array( $license_key ) && isset( $license_key['*'] ) ) {
			return $license_key['*'];
		}

		return false;
	}

	/**
	 * @return string Returns the current add-on license status
	 *
	 * @since 4.2
	 */
	final public function get_license_status() {
		return $this->license_key_status;
	}

	/**
	 * @return string Returns the current add-on license message
	 *
	 * @since 4.2
	 */
	final public function get_license_message() {
		return $this->license_key_message;
	}

	/**
	 * Whether the addon activated the license based on another addon activation
	 *
	 * @return bool
	 * @since 6.14.0
	 */
	final public function has_license_auto_activated() {
		return $this->license_auto_activated;
	}

	/**
	 * Whether the addon deactivated the license based on another addon deactivation.
	 *
	 * @return bool
	 * @since 6.14.0
	 */
	final public function has_license_auto_deactivated() {
		return $this->license_auto_deactivated;
	}

	/**
	 * Register our license check event one week into the future.
	 *
	 * @Internal Using wp_schedule_single_event() means we don't need to 1. Add a weekly interval to wp_schedule_event()
	 *           and 2. Need to clear the scheduled hook when the plugin is deactivated
	 *
	 * @since    4.2
	 *
	 * @depreacted 6.14.0 Handled in bulk via Model_Settings::licensing_bulk_license_check()
	 */
	final public function maybe_schedule_license_check() {
		if ( ! wp_next_scheduled( 'gfpdf_' . $this->get_slug() . '_license_check' ) ) {
			wp_schedule_single_event( strtotime( '+ 1 week' ), 'gfpdf_' . $this->get_slug() . '_license_check' );
		}
	}

	/**
	 * Makes an API call to check the status of the license and updates the license settings
	 *
	 * @since    4.2
	 */
	public function schedule_license_check() {
		/* If there's no license key disable the check */
		if ( empty( $this->get_license_key() ) ) {
			return false;
		}

		$response = wp_remote_post(
			$this->data->store_url,
			[
				'timeout' => 15,
				'body'    => array_merge(
					[ 'edd_action' => 'check_license' ],
					$this->get_default_api_params()
				),
			]
		);

		/* Check for problems contacting the licensing server */
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			$this->log->error(
				'Failed to contact remote API for license status check.',
				[
					'slug'  => $this->get_slug(),
					'error' => is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_response_code( $response ),
				]
			);

			wp_schedule_single_event( strtotime( '+3 hour' ), 'gfpdf_' . $this->get_slug() . '_license_check' );

			return false;
		}

		/* Check for a malformed response */
		$license_check = json_decode( wp_remote_retrieve_body( $response ) );
		if ( $license_check === null ) {
			$this->log->error(
				'Invalid response returned from license status check.',
				[
					'slug'     => $this->get_slug(),
					'response' => wp_remote_retrieve_body( $response ),
				]
			);

			wp_schedule_single_event( strtotime( '+3 hour' ), 'gfpdf_' . $this->get_slug() . '_license_check' );

			return false;
		}

		if ( isset( $license_check->license ) && $license_check->license === 'valid' ) {
			/* License is still valid, do nothing */

			return true;
		}

		/* License status has changed. Update database */
		return $this->update_license_status_from_response( $this->get_license_key(), $response, true );
	}

	/**
	 * Parse and extract the addon license status from the API response
	 *
	 * @param string $license_key Current license key
	 * @param array|\WP_Error $response The raw response from wp_remote_*())
	 * @param bool $use_database Whether to save the license info in the database
	 *
	 * @return bool
	 *
	 * @since 6.14.0
	 */
	public function update_license_status_from_response( $license_key, $response, $use_database = false ) {
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( is_wp_error( $response ) || $response_code !== 200 ) {
			$license_data = new \stdClass();

			/* handle rate limiting */
			if ( $response_code === 429 ) {
				$license_data->error = 'rate_limit';
			}
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		}

		$license_info       = $this->get_license_info();
		$possible_responses = $this->data->addon_license_responses( $this->get_name() );

		$status = 'error';
		if ( ! empty( $license_data->error ) ) {
			$status = $license_data->error;
		} elseif ( ! empty( $license_data->license ) ) {
			$status = $license_data->license;
		}

		$license_info['license'] = $license_key;
		$license_info['status']  = $status;
		$license_info['message'] = $possible_responses[ $license_info['status'] ] ?? $possible_responses['generic'];

		switch ( $license_info['status'] ) {
			case 'expired':
				$date_format = get_option( 'date_format' );
				try {
					$dt   = new \DateTimeImmutable( $license_data->expires, wp_timezone() );
					$date = $dt->format( $date_format );
				} catch ( \Exception $e ) {
					$date = gmdate( $date_format, false );
				}

				$url = add_query_arg(
					[
						'edd_license_key' => $license_info['license'],
						'download_id'     => $this->get_edd_download_id(),
					],
					'https://gravitypdf.com/checkout/'
				);

				$license_info['message'] = sprintf( $license_info['message'], $date, $url );
				break;

			case 'revoked':
			case 'disabled':
				$url = add_query_arg(
					[
						'edd_action'            => 'add_to_cart',
						'download_id'           => $this->get_edd_download_id(),
						'edd_options[price_id]' => $license_data->price_id,
					],
					'https://gravitypdf.com/checkout/'
				);

				$license_info['message'] = sprintf( $license_info['message'], $url );
				break;

			case 'no_activations_left':
				$url = add_query_arg(
					[
						'view'       => 'upgrades',
						'action'     => 'manage_licenses',
						'license_id' => $license_data->license_id,
						'payment_id' => $license_data->payment_id,
					],
					'https://gravitypdf.com/account/'
				);

				$license_info['message'] = sprintf( $license_info['message'], $url );
				break;
		}

		$this->log->notice( 'License key status', array_merge( $license_info, [ 'slug' => $this->get_slug() ] ) );

		$this->update_license_info( $license_info, $use_database );
		$this->flush_update_cache();

		return in_array( $license_info['status'], [ 'active', 'valid' ], true );
	}

	/**
	 * Include a license key prompt
	 *
	 * @since 4.3
	 */
	public function license_registration() {
		$edd_id = $this->get_edd_download_id();
		if ( in_array( $this->get_license_status(), [ 'active', 'valid' ], true ) || empty( $edd_id ) ) {
			return;
		}

		?>

		<tr class="plugin-update-tr">
			<td colspan="3" class="plugin-update colspanchange">
				<div class="update-message">
					<?php
					printf(
						esc_html__(
							'%1$sRegister your copy of %2$s%3$s to receive access to automatic upgrades and support. Need a license key? %4$sPurchase one now%5$s.',
							'gravity-pdf'
						),
						'<a href="' . esc_url( admin_url( 'admin.php?page=gf_settings&subview=PDF&tab=license' ) ) . '">',
						esc_html( $this->get_name() ),
						'</a>',
						'<a href="' . esc_url( 'https://gravitypdf.com/checkout/?edd_action=add_to_cart&download_id=' . $edd_id ) . '">',
						'</a>'
					)
					?>
				</div>
			</td>
		</tr>

		<?php
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param mixed $links Plugin Row Meta
	 * @param mixed $file  Plugin Base file
	 *
	 * @return    array
	 *
	 * @since  1.0
	 */
	public function plugin_row_meta( $links, $file ) {

		if ( $file === plugin_basename( $this->get_main_plugin_file() ) ) {
			$row_meta = [];

			$doc_slug = $this->get_addon_documentation_slug();
			if ( ! empty( $doc_slug ) ) {
				$row_meta['docs'] = '<a href="' . esc_url( 'https://docs.gravitypdf.com/extensions/' . str_replace( 'shop-plugin-', '', $doc_slug ) . '/' ) . '" title="' . esc_attr__( 'View plugin Documentation', 'gravity-pdf' ) . '">' . esc_html__( 'Docs', 'gravity-pdf' ) . '</a>';
			}

			$row_meta['support'] = '<a href="' . esc_url( 'https://gravitypdf.com/help/' ) . '" title="' . esc_attr__( 'Get Help and Support', 'gravity-pdf' ) . '">' . esc_html__( 'Support', 'gravity-pdf' ) . '</a>';

			return apply_filters( 'gfpdf_addon_row_meta', array_merge( $links, $row_meta ), $file, $this );
		}

		return (array) $links;
	}

	/**
	 * Do API call to GravityPDF.com to activate the current add-on license key
	 *
	 * @param string $license_key The current license key for this add-on
	 * @param bool $use_database Auto-update the database with the response
	 *
	 * @return array The API response and license status
	 *
	 * @since 6.14.0
	 */
	public function activate_license( $license_key = '', $use_database = false ) {

		if ( empty( $license_key ) ) {
			$license_key = $this->get_license_key();
		}

		$response = wp_remote_post(
			$this->data->store_url,
			[
				'timeout' => 15,
				'body'    => array_merge(
					$this->get_default_api_params(),
					[
						'edd_action' => 'activate_license',
						'license'    => $license_key,
					],
				),
			]
		);

		$this->update_license_status_from_response( $license_key, $response, $use_database );

		do_action( 'gfpdf_addon_post_license_activation', $response, $this, $use_database );

		return $this->get_license_info();
	}

	/**
	 * Listen for all license activations, and auto-activate addon if license supports it
	 *
	 * @param array $response
	 * @param Helper_Abstract_Addon $addon
	 *
	 * @return void
	 *
	 * @since 6.14.0
	 */
	public function maybe_auto_activate_license( $response, $addon, $use_database ) {
		/* skip if current addon doing licence activation */
		if ( $this->get_edd_download_id() === $addon->get_edd_download_id() ) {
			return;
		}

		/* skip if invalid response, or not an Access Pass license */
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! $license_data ) {
			return;
		}

		if ( ! isset( $license_data->products ) || ! is_array( $license_data->products ) ) {
			return;
		}

		/* skip if addon not available in Access Pass */
		if ( ! in_array( (int) $this->get_edd_download_id(), $license_data->products, true ) ) {
			return;
		}

		$this->update_license_info( $addon->get_license_info(), $use_database );

		$this->license_auto_activated = true;
	}

	/**
	 * Do API call to GravityPDF.com to deactivate add-on license
	 *
	 * @return bool
	 *
	 * @since 6.14.0
	 */
	public function deactivate_license() {
		$response = wp_remote_post(
			$this->data->store_url,
			[
				'timeout' => 15,
				'body'    => array_merge(
					[ 'edd_action' => 'deactivate_license' ],
					$this->get_default_api_params()
				),
			]
		);

		/* Remove license data from database, no matter if the API request fails */
		$this->delete_license_info();
		$this->flush_update_cache();

		/* If API error exit early */
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		/* Get API response and check license is now deactivated */
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! isset( $license_data->license ) || $license_data->license !== 'deactivated' ) {
			return false;
		}

		$this->log->notice( 'License successfully deactivated', [ 'slug' => $this->get_slug() ] );

		do_action( 'gfpdf_addon_post_license_deactivation', $response, $this );

		return true;
	}

	/**
	 * Listen for all license deactivations, and auto-deactivate addon if license supports it
	 *
	 * @param array $response
	 * @param Helper_Abstract_Addon $addon
	 *
	 * @return void
	 *
	 * @since 6.14.0
	 */
	public function maybe_auto_deactivate_license( $response, $addon ) {
		/* skip if current addon doing licence activation */
		if ( $this->get_edd_download_id() === $addon->get_edd_download_id() ) {
			return;
		}

		/* skip if invalid response, or not an Access Pass license */
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! $license_data ) {
			return;
		}

		if ( ! isset( $license_data->products ) || ! is_array( $license_data->products ) ) {
			return;
		}

		/* skip if addon not available in Access Pass */
		if ( ! in_array( (int) $this->get_edd_download_id(), $license_data->products, true ) ) {
			return;
		}

		$this->update_license_info( $addon->get_license_info(), true );

		$this->license_auto_deactivated = true;
	}

	/**
	 * Delete the add-on update information
	 *
	 * @since 6.14.0
	 * @return void
	 */
	public function flush_update_cache() {
		if ( ! $this->plugin_updater ) {
			return;
		}

		$this->plugin_updater->delete_version_info_cache();
		$this->plugin_updater->delete_transient_plugin_info();
	}
}
