<?php

namespace GFPDF\Helper;

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
 * An abstract class to assist with addon licensing
 */
abstract class Helper_Abstract_Addon {

	/**
	 * @var string The add-on slug (usually the name with the spaces substituted for hyphens)
	 *
	 * @since 4.2
	 */
	private $slug;

	/**
	 * @var string The add-on name (should match the name/title used in EDD)
	 *
	 * @since 4.2
	 */
	private $name;

	/**
	 * @var string The add-on author
	 *
	 * @since 4.2
	 */
	private $author;

	/**
	 * @var string The add-on version
	 *
	 * @since 4.2
	 */
	private $version;

	/**
	 * @var string The add-on mail file path
	 *
	 * @since 4.2
	 */
	private $addon_path_main_plugin_file;

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
	 * Setup the add-on licensing and initialise any classes
	 *
	 * @param array $classes
	 *
	 * @since 4.2
	 */
	public function init( $classes = [] ) {

		/*
		 * Register our plugin updater on the admin initialisation action
		 *
		 * @Internal Due to WordPress.org rules we cannot initialisation the updater code in the core plugin
		 *           Add-ons have to initialise this functionality via GFPDF\Helper\Licensing\EDD_SL_Plugin_Updater
		 */
		add_action( 'init', [ $this, 'plugin_updater' ] );

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

		/*
		 * Automatically schedule license checks weekly
		 */
		add_action( 'admin_init', [ $this, 'maybe_schedule_license_check' ] );
		add_action( 'gfpdf_' . $this->get_slug() . '_license_check', [ $this, 'schedule_license_check' ] );

		/*
		 * Include info on plugin listing
		 */
		add_action(
			'after_plugin_row_' . plugin_basename( $this->get_main_plugin_file() ),
			[
				$this,
				'license_registration',
			]
		);
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
	 * Due to WordPress.org rules we cannot initialisation the updater code in the core plugin so add-ons that utilise
	 * this class need to handle that code themselves.
	 *
	 * Official Gravity PDF add-ons should initialise the GFPDF\Helper\Licensing\EDD_SL_Plugin_Updater class
	 * when the add-on license status is set to "active". You can check the status of the plugin
	 * using the following:
	 *
	 * $license_info = $this->get_license_info();
	 * if ( $license_info['status'] !== 'active' ) {
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
	 *
	 */
	abstract public function plugin_updater();

	/**
	 * Register the add-on with Gravity PDF
	 *
	 * @Internal If you don't want the add-on licensing handled automatically in the UI override this method
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
	 * Get the add-on license information stored in the database (if any)
	 *
	 * @Internal If you don't want the add-on licensing handled automatically in the UI override this method
	 *
	 * @since    4.2
	 */
	public function get_license_info() {
		$settings = $this->options->get_settings();

		$slug    = $this->get_slug();
		$license = ( isset( $settings[ "license_$slug" ] ) ) ? $settings[ "license_$slug" ] : '';
		$status  = ( isset( $settings[ "license_{$slug}_status" ] ) ) ? $settings[ "license_{$slug}_status" ] : '';
		$message = ( isset( $settings[ "license_{$slug}_message" ] ) ) ? $settings[ "license_{$slug}_message" ] : '';

		$license_details = [
			'license' => $license,
			'status'  => $status,
			'message' => $message,
		];

		$this->log->notice( 'Get plugin license details', $license_details );

		return $license_details;
	}

	/**
	 * Update the add-on license information stored in the database
	 *
	 * @param array $license_info
	 *
	 * @Internal If you don't want the add-on licensing handled automatically in the UI override this method
	 *
	 * @since    4.2
	 */
	public function update_license_info( $license_info ) {
		$settings = $this->options->get_settings();
		$slug     = $this->get_slug();

		$settings[ "license_$slug" ]           = $license_info['license'];
		$settings[ "license_{$slug}_status" ]  = $license_info['status'];
		$settings[ "license_{$slug}_message" ] = $license_info['message'];

		$this->log->notice( 'Update plugin license details', $license_info );

		$this->options->update_settings( $settings );
	}

	/**
	 * Remove the license info and keys from the settings
	 *
	 * @since 4.2
	 */
	public function delete_license_info() {
		$settings = $this->options->get_settings();
		$slug     = $this->get_slug();

		unset( $settings[ "license_$slug" ] );
		unset( $settings[ "license_{$slug}_status" ] );
		unset( $settings[ "license_{$slug}_message" ] );

		$this->log->notice( 'Delete plugin license details' );

		$this->options->update_settings( $settings );
	}

	/**
	 * @return string Returns the current add-on license key
	 *
	 * @since 4.2
	 */
	final public function get_license_key() {
		return $this->get_license_info()['license'];
	}

	/**
	 * @return string Returns the current add-on license status
	 *
	 * @since 4.2
	 */
	final public function get_license_status() {
		return $this->get_license_info()['status'];
	}

	/**
	 * @return string Returns the current add-on license message
	 *
	 * @since 4.2
	 */
	final public function get_license_message() {
		return $this->get_license_info()['message'];
	}

	/**
	 * Register our license check event one week into the future.
	 *
	 * @Internal Using wp_schedule_single_event() means we don't need to 1. Add a weekly interval to wp_schedule_event()
	 *           and 2. Need to clear the scheduled hook when the plugin is deactivated
	 *
	 * @since    4.2
	 */
	final public function maybe_schedule_license_check() {
		if ( ! wp_next_scheduled( 'gfpdf_' . $this->get_slug() . '_license_check' ) ) {
			wp_schedule_single_event( strtotime( '+ 1 week' ), 'gfpdf_' . $this->get_slug() . '_license_check' );
		}
	}

	/**
	 * Makes an API call to check the status of the license and updates the license settings
	 *
	 * @Internal If you don't want the add-on licensing handled automatically in the UI override this method
	 *
	 * @since    4.2
	 */
	public function schedule_license_check() {
		$license_info = $this->get_license_info();

		/* If the license info is empty disable check */
		if ( empty( array_filter( $license_info ) ) ) {
			return false;
		}

		$response = wp_remote_post(
			$this->data->store_url,
			[
				'timeout' => 15,
				'body'    => [
					'edd_action'  => 'check_license',
					'license'     => $license_info['license'],
					'item_id'     => $this->get_edd_download_id(),
					'item_name'   => rawurlencode( $this->get_short_name() ),
					'url'         => home_url(),
					'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
				],
			]
		);

		/* If there was a problem with the request we'll try again in an hour */
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			$this->log->error( 'Failed to contact remote API for license status check. Rescheduling.' );
			wp_schedule_single_event( strtotime( '+ 1 hour' ), 'gfpdf_' . $this->get_slug() . '_license_check' );

			return false;
		}

		$license_check = json_decode( wp_remote_retrieve_body( $response ) );

		/* License still valid, no need to do anything */
		if ( isset( $license_check->license ) && $license_check->license === 'valid' ) {
			$this->log->notice( 'License key still valid.' );

			return false;
		}

		/* Error occurred. Update status and message in the license settings */
		$possible_responses = $this->data->addon_license_responses( $this->get_name() );

		/* Ensure we have a known error */
		if ( ! isset( $license_check->license ) || ! isset( $possible_responses[ $license_check->license ] ) ) {
			$this->log->error( 'Unknown license status returned from remote API' );

			return false;
		}

		$license_info['status']  = $license_check->license;
		$license_info['message'] = $possible_responses[ $license_check->license ];

		switch ( $license_check->license ) {
			case 'expired':
				$date_format = get_option( 'date_format' );
				$dt          = new \DateTimeImmutable( $license_check->expires, wp_timezone() );
				$date        = $dt === false ? gmdate( $date_format, false ) : $dt->format( $date_format );

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
						'edd_options[price_id]' => $license_check->price_id,
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
						'license_id' => $license_check->license_id,
						'payment_id' => $license_check->payment_id,
					],
					'https://gravitypdf.com/account/'
				);

				$license_info['message'] = sprintf( $license_info['message'], $url );
				break;
		}

		$this->log->notice( 'License key no longer valid', $license_info );
		$this->update_license_info( $license_info );

		return true;
	}

	/**
	 * Include a license key prompt
	 *
	 * @since 4.3
	 */
	public function license_registration() {

		$license_info = $this->get_license_info();
		$edd_id       = $this->get_edd_download_id();

		if ( $license_info['status'] === 'active' || empty( $edd_id ) ) {
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
				$row_meta['docs'] = '<a href="' . esc_url( 'https://docs.gravitypdf.com/v6/extensions/' . str_replace( 'shop-plugin-', '', $doc_slug ) . '/' ) . '" title="' . esc_attr__( 'View plugin Documentation', 'gravity-pdf' ) . '">' . esc_html__( 'Docs', 'gravity-pdf' ) . '</a>';
			}

			$row_meta['support'] = '<a href="' . esc_url( 'https://gravitypdf.com/support/#contact-support' ) . '" title="' . esc_attr__( 'Get Help and Support', 'gravity-pdf' ) . '">' . esc_html__( 'Support', 'gravity-pdf' ) . '</a>';

			return apply_filters( 'gfpdf_addon_row_meta', array_merge( $links, $row_meta ), $file, $this );
		}

		return (array) $links;
	}
}
