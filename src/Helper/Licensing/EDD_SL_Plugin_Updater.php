<?php

namespace GFPDF\Helper\Licensing;

/**
 * @package     Gravity PDF
 * @author      Easy Digital Downloads
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.2
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allows plugins to use their own update API.
 *
 * @author  Easy Digital Downloads
 * @version 1.9.4
 * @since   6.16.0 Modified to make this class more useful for our plugin suite
 */
class EDD_SL_Plugin_Updater {

	protected $api_url     = '';
	protected $api_data    = [];
	protected $plugin_file = '';
	protected $name        = '';
	protected $slug        = '';
	protected $version     = '';
	protected $wp_override = false;
	protected $beta        = false;
	protected $failed_request_cache_key;

	/* Local gate for sharing a package across a Multisite; kept off api_data so it never rides an outbound API request */
	protected $license_status = '';

	/* The network-shared package outlives the per-site 3h check cache so a quiet licensed site can't let it lapse */
	protected const NETWORK_CACHE_TTL = 3 * DAY_IN_SECONDS;

	/*
	 * Drawn from Helper_Data::addon_license_responses(). `error` and `rate_limit` are deliberately absent — a failed or
	 * throttled request is not a verdict on the license, so it must not strip a package other sites depend on.
	 */
	protected const UNENTITLED_LICENSE_STATUSES = [
		'expired',
		'revoked',
		'disabled',
		'missing',
		'invalid',
		'site_inactive',
		'item_name_mismatch',
		'invalid_item_id',
		'no_activations_left',
	];

	/**
	 * Class constructor.
	 *
	 * @param string $_api_url     The URL pointing to the custom API endpoint.
	 * @param string $_plugin_file Path to the plugin file.
	 * @param array  $_api_data    Optional data to send with API calls.
	 */
	public function __construct( $_api_url, $_plugin_file, $_api_data = null ) {
		$this->api_url                  = trailingslashit( $_api_url );
		$this->api_data                 = $_api_data;
		$this->plugin_file              = $_plugin_file;
		$this->name                     = plugin_basename( $_plugin_file );
		$this->slug                     = basename( dirname( $_plugin_file ) );
		$this->version                  = $_api_data['version'] ?? 0;
		$this->wp_override              = isset( $_api_data['wp_override'] ) && (bool) $_api_data['wp_override'];
		$this->beta                     = ! empty( $this->api_data['beta'] );
		$this->failed_request_cache_key = 'gpdf_sl_failed_http_' . md5( $this->api_url );
	}

	/**
	 * Set up WordPress filters to hook into WP's update process.
	 */
	public function init() {
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
		add_filter( 'plugins_api', [ $this, 'plugins_api_filter' ], 10, 3 );
		add_action( 'after_plugin_row', [ $this, 'show_update_notification' ], 10, 2 );
		add_action( 'admin_init', [ $this, 'show_changelog' ] );
	}

	/**
	 * Check for Updates at the defined API endpoint and modify the update array.
	 *
	 * This function dives into the update API just when WordPress creates its update array,
	 * then adds a custom API call and injects the custom plugin data retrieved from the API.
	 * It is reassembled from parts of the native WordPress plugin update code.
	 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
	 *
	 * @param object $_transient_data Update array build by WordPress.
	 *
	 * @return object Modified update array with custom plugin data.
	 */
	public function check_update( $_transient_data ) {

		if ( ! is_object( $_transient_data ) ) {
			$_transient_data = new \stdClass();
		}

		if ( ! empty( $_transient_data->response ) && ! empty( $_transient_data->response[ $this->name ] ) ) {
			/* Do nothing, transient data already has product info */
			if ( ! $this->wp_override ) {
				return $_transient_data;
			}

			/* Overriding cache. Remove existing info */
			unset( $_transient_data->response[ $this->name ] );
		}

		/* Overriding cache, delete secondary DB cache */
		if ( $this->wp_override ) {
			$this->delete_version_info_cache();

			/* This filter runs multiple times in a single request. Disable override on the first run */
			$this->wp_override = false;
		}

		$current = $this->get_update_transient_data();
		if ( false !== $current && is_object( $current ) && isset( $current->new_version ) ) {
			if ( version_compare( $this->version, $current->new_version, '<' ) ) {
				$_transient_data->response[ $this->name ] = $current;
			} else {
				// Populating the no_update information is required to support auto-updates in WordPress 5.5.
				$_transient_data->no_update[ $this->name ] = $current;
			}
		}
		$_transient_data->last_checked           = time();
		$_transient_data->checked[ $this->name ] = $this->version;

		return $_transient_data;
	}

	/**
	 * Get repo API data from store.
	 * Save to cache.
	 *
	 * @return \stdClass|false
	 */
	public function get_repo_api_data() {
		$version_info = $this->get_cached_version_info();
		if ( false === $version_info ) {
			$version_info = $this->get_version_info();
		}

		return $this->maybe_apply_network_package( $version_info );
	}

	/**
	 * Borrow a licensed download package cached by another site on the network
	 *
	 * On a Multisite where the plugin is activated per-site (not network-wide) each site checks for updates using its
	 * own license. A site without a valid license still receives version info but an empty `package`, which would
	 * otherwise overwrite the shared update_plugins transient and strip the download URL for every site. When any site
	 * does hold a valid license it promotes its package to a network option (see set_version_info_cache()); here we fall
	 * back to that package so the whole network can install the update.
	 *
	 * @param \stdClass|false $version_info
	 *
	 * @return \stdClass|false
	 *
	 * @since 6.16.0
	 */
	protected function maybe_apply_network_package( $version_info ) {
		if ( ! is_multisite() || ! is_object( $version_info ) || ! empty( $version_info->package ) ) {
			return $version_info;
		}

		$network = $this->get_network_cached_version_info();
		if (
			is_object( $network )
			&& ! empty( $network->package )
			&& ! empty( $version_info->new_version )
			&& version_compare( $network->new_version ?? '', $version_info->new_version, '>=' )
		) {
			$version_info->package = $network->package;
		}

		return $version_info;
	}

	/**
	 * Always return the full data array, and not a subset of the data
	 *
	 * This is required so subsites in a Network can correctly display the changelog info
	 *
	 * @return \stdClass|false
	 *
	 * @since 3.8.12
	 */
	public function get_update_transient_data() {
		return $this->get_repo_api_data();
	}

	/**
	 * Gets the plugin's tested version.
	 *
	 * @param object $version_info
	 *
	 * @return null|string
	 *
	 * @since 1.9.2
	 */
	public function get_tested_version( $version_info ) {

		// There is no tested version.
		if ( empty( $version_info->tested ) ) {
			return null;
		}

		// Strip off extra version data so the result is x.y or x.y.z.
		[ $current_wp_version ] = explode( '-', get_bloginfo( 'version' ) );

		// The tested version is greater than or equal to the current WP version, no need to do anything.
		if ( version_compare( $version_info->tested, $current_wp_version, '>=' ) ) {
			return $version_info->tested;
		}
		$current_version_parts = explode( '.', $current_wp_version );
		$tested_parts          = explode( '.', $version_info->tested );

		// The current WordPress version is x.y.z, so update the tested version to match it.
		if ( isset( $current_version_parts[2] ) && $current_version_parts[0] === $tested_parts[0] && $current_version_parts[1] === $tested_parts[1] ) {
			$tested_parts[2] = $current_version_parts[2];
		}

		return implode( '.', $tested_parts );
	}

	/**
	 * Show the update notification on multisite subsites.
	 *
	 * @param string $file
	 * @param array  $plugin
	 *
	 * @return void
	 */
	public function show_update_notification( $file, $plugin ) {

		// Return early if in the network admin, or if this is not a multisite install.
		if ( is_network_admin() || ! is_multisite() ) {
			return;
		}

		// Allow single site admins to see that an update is available.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( $this->name !== $file ) {
			return;
		}

		// Do not print any message if update does not exist.
		$update_cache = get_site_transient( 'update_plugins' );

		if ( ! isset( $update_cache->response[ $this->name ] ) ) {
			if ( ! is_object( $update_cache ) ) {
				$update_cache = new \stdClass();
			}
			$update_cache->response[ $this->name ] = $this->get_repo_api_data();
		}

		// Return early if this plugin isn't in the transient->response or if the site is running the current or newer version of the plugin.
		if ( empty( $update_cache->response[ $this->name ] ) || version_compare( $this->version, $update_cache->response[ $this->name ]->new_version, '>=' ) ) {
			return;
		}

		$plugin_update = $update_cache->response[ $this->name ];

		printf(
			'<tr class="plugin-update-tr %3$s" id="%1$s-update" data-slug="%1$s" data-plugin="%2$s">',
			esc_attr( $this->slug ),
			esc_attr( $file ),
			in_array( $this->name, $this->get_active_plugins(), true ) ? 'active' : 'inactive'
		);

		echo '<td colspan="3" class="plugin-update colspanchange">';
		echo '<div class="update-message notice inline notice-warning notice-alt"><p>';

		$changelog_link = '';

		$has_package   = ! empty( $plugin_update->package );
		$has_changelog = ! empty( $plugin_update->changelog );

		if ( $has_changelog ) {
			$changelog_link = add_query_arg(
				[
					'gpdf_sl_action' => 'view_plugin_changelog',
					'gpdf_sl_nonce'  => wp_create_nonce( 'install-plugin_' . $this->slug ),
					'plugin'         => rawurlencode( $this->slug ),
					'section'        => 'changelog',
					'TB_iframe'      => 'true',
					'width'          => 77,
					'height'         => 911,
				],
				self_admin_url( 'index.php' )
			);
		}

		$update_link = add_query_arg(
			[
				'action' => 'upgrade-plugin',
				'plugin' => rawurlencode( $this->name ),
			],
			self_admin_url( 'update.php' )
		);

		printf(
		/* translators: the plugin name. */
			esc_html__( 'There is a new version of %1$s available.', 'gravity-pdf' ),
			esc_html( $plugin['Name'] )
		);

		if ( ! current_user_can( 'update_plugins' ) ) {
			echo ' ';
			esc_html_e( 'Contact your network administrator to install the update.', 'gravity-pdf' );
		} elseif ( ! $has_package && $has_changelog ) {
			echo ' ';
			echo wp_kses(
				sprintf(
				/* translators: 1. opening anchor tag, do not translate 2. the new plugin version 3. closing anchor tag, do not translate. */
					__( '%1$sView version %2$s details%3$s.', 'gravity-pdf' ),
					'<a target="_blank" class="thickbox open-plugin-details-modal" href="' . esc_url( $changelog_link ) . '">',
					esc_html( $plugin_update->new_version ),
					'</a>'
				),
				[
					'a' => [
						'href'   => [],
						'target' => [],
						'class'  => [],
					],
				]
			);
		} elseif ( $has_package && $has_changelog ) {
			echo ' ';
			echo wp_kses(
				sprintf(
					/* translators: 1: Opening <a> tag, 2: Version number, 3: Closing </a> tag, 4: Opening <a> tag, 5: Closing </a> tag */
					__( '%1$sView version %2$s details%3$s or %4$supdate now%5$s.', 'gravity-pdf' ),
					'<a target="_blank" class="thickbox open-plugin-details-modal" href="' . esc_url( $changelog_link ) . '">',
					esc_html( $plugin_update->new_version ),
					'</a>',
					'<a target="_blank" class="update-link" href="' . esc_url( wp_nonce_url( $update_link, 'upgrade-plugin_' . $file ) ) . '">',
					'</a>'
				),
				[
					'a' => [
						'href'   => [],
						'target' => [],
						'class'  => [],
					],
				]
			);
		} elseif ( $has_package && ! $has_changelog ) {
			echo wp_kses(
				sprintf(
					' %1$s%2$s%3$s',
					'<a target="_blank" class="update-link" href="' . esc_url( wp_nonce_url( $update_link, 'upgrade-plugin_' . $file ) ) . '">',
					esc_html__( 'Update now.', 'gravity-pdf' ),
					'</a>'
				),
				[
					'a' => [
						'href'   => [],
						'target' => [],
						'class'  => [],
					],
				]
			);
		}

		do_action( "in_plugin_update_message-{$file}", $plugin, $plugin ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

		echo '</p></div></td></tr>';
	}

	/**
	 * Gets the plugins active in a multisite network.
	 *
	 * @return array
	 */
	public function get_active_plugins() {
		$active_plugins         = (array) get_option( 'active_plugins' );
		$active_network_plugins = (array) get_site_option( 'active_sitewide_plugins' );

		return array_merge( $active_plugins, array_keys( $active_network_plugins ) );
	}

	/**
	 * Updates information on the "View version x.x details" page with custom data.
	 *
	 * @param mixed  $_data
	 * @param string $_action
	 * @param object $_args
	 *
	 * @return object $_data
	 */
	public function plugins_api_filter( $_data, $_action = '', $_args = null ) {

		if ( 'plugin_information' !== $_action ) {
			return $_data;
		}

		if ( ! isset( $_args->slug ) || ( $_args->slug !== $this->slug ) ) {
			return $_data;
		}

		// Get the transient where we store the api request for this plugin for 24 hours
		$edd_api_request_transient = $this->get_cached_version_info();

		//If we have no transient-saved value, run the API (which caches a successful response internally) and return it too right now.
		if ( empty( $edd_api_request_transient ) ) {
			$api_response = $this->get_version_info();

			if ( false !== $api_response ) {
				$_data = $api_response;
			}
		} else {
			$_data = $edd_api_request_transient;
		}

		// $_data stays false when get_version_info() bails (API down, backoff, secondary site) — assigning to a bool fatals on PHP 8.
		if ( is_object( $_data ) ) {
			if ( ! isset( $_data->plugin ) ) {
				$_data->plugin = $this->name;
			}

			if ( ! isset( $_data->version ) && ! empty( $_data->new_version ) ) {
				$_data->version = $_data->new_version;
			}
		}

		return $_data;
	}

	/**
	 * Convert some objects to arrays when injecting data into the update API
	 *
	 * Some data like sections, banners, and icons are expected to be an associative array, however due to the JSON
	 * decoding, they are objects. This method allows us to pass in the object and return an associative array.
	 *
	 * @param \stdClass|array $data
	 *
	 * @return array
	 *
	 * @since 3.6.5
	 */
	public function convert_object_to_array( $data ) {
		if ( ! is_array( $data ) && ! is_object( $data ) ) {
			return [];
		}
		$new_data = [];
		foreach ( $data as $key => $value ) {
			$new_data[ $key ] = is_object( $value ) ? $this->convert_object_to_array( $value ) : $value;
		}

		return $new_data;
	}

	/**
	 * Normalize a sections/banners/icons value into an associative array.
	 *
	 * The store may hand these back as a JSON-encoded string, a legacy PHP-serialized array (a:N:{...}), or an
	 * already-decoded object (some EDD endpoints). JSON is attempted first since it can't trigger object injection; a
	 * serialized value is only unserialized when it is an array and with `allowed_classes => false`, so any nested
	 * object is neutralized and a bare serialized-object payload (O:...) — the object-injection vector the earlier
	 * hardening guarded against — is refused and collapses to an empty array.
	 *
	 * @param string|array|\stdClass $data
	 *
	 * @return array
	 *
	 * @since 6.16.0
	 */
	public function normalize_api_collection( $data ) {
		if ( is_string( $data ) ) {
			$json = json_decode( $data );

			if ( json_last_error() === JSON_ERROR_NONE && ( is_array( $json ) || is_object( $json ) ) ) {
				$data = $json;
			} elseif ( preg_match( '/^a:\d+:\{/', $data ) ) {
				$data = unserialize( $data, [ 'allowed_classes' => false ] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- object injection is guarded by allowed_classes => false
			}
		}

		// A string we couldn't decode (or a failed unserialize) falls through here; convert_object_to_array() maps any
		// non-array/object to [], so an unrecognized payload safely collapses to an empty collection.
		return $this->convert_object_to_array( $data );
	}

	/**
	 * Calls the API and, if successful, returns the object delivered by the API.
	 *
	 * @return \stdClass|false
	 */
	public function get_version_info() {
		/* Don't allow a plugin to ping itself */
		if ( trailingslashit( home_url() ) === $this->api_url ) {
			return false;
		}

		/* The primary site checks for updates on behalf of network-activated installs */
		if ( \GPDFAPI::get_misc_class()->is_secondary_network_site( $this->name ) ) {
			return false;
		}

		if ( $this->request_recently_failed() ) {
			return false;
		}

		return $this->get_version_from_remote();
	}

	/**
	 * Determines if a request has recently failed.
	 *
	 * @return bool
	 *
	 * @since 1.9.1
	 */
	public function request_recently_failed() {
		$failed_request_details = get_option( $this->failed_request_cache_key );

		/* Request has never failed. */
		if ( empty( $failed_request_details ) || ! is_numeric( $failed_request_details ) ) {
			return false;
		}

		/*
		 * Request previously failed, but the timeout has expired.
		 * This means we're allowed to try again.
		 */
		if ( time() > $failed_request_details ) {
			delete_option( $this->failed_request_cache_key );

			return false;
		}

		return true;
	}

	/**
	 * Logs a failed HTTP request for this API URL.
	 * We set a timestamp for 1 hour from now. This prevents future API requests from being
	 * made to this domain for 1 hour. Once the timestamp is in the past, API requests
	 * will be allowed again. This way if the site is down for some reason we don't bombard
	 * it with failed API requests.
	 *
	 * @return void
	 *
	 * @since 1.9.1
	 */
	public function log_failed_request() {
		update_option( $this->failed_request_cache_key, strtotime( '+1 hour' ), false );
	}

	/**
	 * If available, show the changelog for sites in a multisite install.
	 *
	 * @return void
	 */
	public function show_changelog() {

		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_REQUEST['gpdf_sl_action'] ) || 'view_plugin_changelog' !== $_REQUEST['gpdf_sl_action'] ) {
			return;
		}

		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_REQUEST['plugin'] ) || $this->slug !== $_REQUEST['plugin'] ) {
			return;
		}

		check_admin_referer( 'install-plugin_' . $this->slug, 'gpdf_sl_nonce' );

		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to install plugin updates', 'gravity-pdf' ), esc_html__( 'Error', 'gravity-pdf' ), [ 'response' => 403 ] );
		}

		/* Masquerade as the plugin install screen */
		global $hook_suffix, $body_id, $tab, $pagenow;

		/* install_plugin_information() calls `exit;` so there isn't a conflict/compat risk */
		$pagenow     = 'plugin-install.php'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$hook_suffix = $pagenow; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$body_id     = 'plugin-information'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$tab         = $body_id; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		set_current_screen( 'plugin-install' );

		wp_enqueue_script( 'plugin-install' );
		wp_enqueue_script( 'updates' );

		/* Let WP output the changelog info */
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		install_plugin_information();
	}

	/**
	 * Get the arguments required for the version API check
	 *
	 * @return array
	 *
	 * @since 6.16.0
	 */
	public function get_version_api_params() {
		return [
			'license'     => $this->api_data['license'] ?? '',
			'item_name'   => $this->api_data['item_name'] ?? false,
			'item_id'     => $this->api_data['item_id'] ?? false,
			'version'     => $this->api_data['version'] ?? false,
			'slug'        => $this->slug,
			'author'      => $this->api_data['author'] ?? '',
			'url'         => home_url(),
			'beta'        => $this->beta,
			'php_version' => phpversion(),
			'wp_version'  => get_bloginfo( 'version' ),
		];
	}

	/**
	 * Gets the current version information from the remote site.
	 *
	 * @return \stdClass|false
	 */
	public function get_version_from_remote() {
		$api_params = array_merge(
			[ 'edd_action' => 'get_version' ],
			$this->get_version_api_params()
		);

		/**
		 * Filters the parameters sent in the API request.
		 *
		 * @param array  $api_params The array of data sent in the request.
		 * @param array  $this       ->api_data    The array of data set up in the class constructor.
		 * @param string $this       ->plugin_file The full path and filename of the file.
		 */
		$api_params = apply_filters( 'gpdf_sl_plugin_updater_api_params', $api_params, $this->api_data, $this->plugin_file );

		$request = wp_remote_post(
			$this->api_url,
			[
				'timeout'   => 15,
				'sslverify' => $this->verify_ssl(),
				'body'      => $api_params,
			]
		);

		if ( is_wp_error( $request ) || ( 200 !== wp_remote_retrieve_response_code( $request ) ) ) {
			$this->log_failed_request();

			return false;
		}

		$response = json_decode( wp_remote_retrieve_body( $request ) );
		$response = apply_filters( 'gpdf_sl_plugin_updater_api_response', $response, $this->api_data, $this->plugin_file );
		$response = $this->standardize_api_response( $response );

		// A 200 with an empty/unparseable body yields false — back off like any other failure so we don't re-POST every call.
		if ( false === $response ) {
			$this->log_failed_request();

			return false;
		}

		$this->set_version_info_cache( $response );

		return $response;
	}

	/**
	 * Get the version info from the cache, if it exists.
	 *
	 * @param string $cache_key
	 *
	 * @return object|false
	 */
	public function get_cached_version_info( $cache_key = '' ) {
		if ( empty( $cache_key ) ) {
			$cache_key = $this->get_cache_key();
		}

		return $this->read_timed_cache( get_option( $cache_key ) );
	}

	/**
	 * Get the licensed version info promoted to the network cache by any site on a Multisite, if it exists
	 *
	 * @return \stdClass|false
	 *
	 * @since 6.16.0
	 */
	public function get_network_cached_version_info() {
		return $this->read_timed_cache( get_site_option( $this->get_network_cache_key() ) );
	}

	/**
	 * Decode a timed version-info cache payload, shared by the per-site and network cache readers
	 *
	 * @param mixed $cache The stored [ 'timeout' => int, 'value' => string ] payload
	 *
	 * @return \stdClass|false
	 *
	 * @since 6.16.0
	 */
	protected function read_timed_cache( $cache ) {
		/* Guard against a corrupted scalar-string option: array access on a string throws a TypeError on PHP 8 */
		if ( ! is_array( $cache ) ) {
			return false;
		}

		if ( empty( $cache['timeout'] ) || time() > $cache['timeout'] ) {
			return false;
		}

		return $this->standardize_api_response( json_decode( $cache['value'] ) );
	}

	/**
	 * Adds the plugin version information to the database.
	 *
	 * @param string $value
	 * @param string $cache_key
	 *
	 * @return void
	 */
	public function set_version_info_cache( $value = '', $cache_key = '' ) {

		/* Let cache be skipped when plugin not active on current multisite */
		if ( $this->is_non_active_multisite() ) {
			return;
		}

		if ( empty( $cache_key ) ) {
			$cache_key = $this->get_cache_key();
		}

		$data = [
			'timeout' => strtotime( '+3 hours', time() ),
			'value'   => wp_json_encode( $value ),
		];

		update_option( $cache_key, $data, false );

		/*
		 * Promote the package to a network option so any site on the Multisite can install the update. The store can
		 * return a package URL even when the license is inactive (that URL errors on access), so only an active license
		 * is allowed to share its package. The promoting site is recorded so it alone can withdraw the package later.
		 */
		if ( is_multisite() && $this->is_license_active() && is_object( $value ) && ! empty( $value->package ) ) {
			$network_data            = $data;
			$network_data['timeout'] = time() + self::NETWORK_CACHE_TTL;
			$network_data['blog_id'] = get_current_blog_id();
			update_site_option( $this->get_network_cache_key(), $network_data );
		}
	}

	/**
	 * Delete the cached version info
	 *
	 * @param string $cache_key
	 *
	 * @return bool
	 *
	 * @since 6.16.0
	 */
	public function delete_version_info_cache( $cache_key = '' ) {
		if ( empty( $cache_key ) ) {
			$cache_key = $this->get_cache_key();
		}

		return delete_option( $cache_key );
	}

	/**
	 * Withdraw the package this site shared with the network, so a removed license stops backing network-wide downloads
	 *
	 * Ownership counts as much as the license state: a package promoted by another site belongs to a site that is still
	 * licensed, and must outlive this one losing its own.
	 *
	 * @return bool
	 *
	 * @since 6.16.0
	 */
	public function delete_network_version_info_cache() {
		if ( ! is_multisite() ) {
			return false;
		}

		$lost_license = empty( $this->api_data['license'] ) || in_array( $this->license_status, self::UNENTITLED_LICENSE_STATUSES, true );
		if ( ! $lost_license ) {
			return false;
		}

		$cache_key = $this->get_network_cache_key();
		$cache     = get_site_option( $cache_key );
		if ( ! is_array( $cache ) || (int) ( $cache['blog_id'] ?? 0 ) !== get_current_blog_id() ) {
			return false;
		}

		return delete_site_option( $cache_key );
	}

	/**
	 * Delete the cached update info without removing the entire update plugin data
	 *
	 * @return bool
	 *
	 * @since 6.16.0
	 */
	public function delete_transient_plugin_info() {
		$plugin_update = get_site_transient( 'update_plugins' );

		if ( ! isset( $plugin_update->response[ $this->name ] ) ) {
			return true;
		}

		unset(
			$plugin_update->response[ $this->name ],
			$plugin_update->no_update[ $this->name ],
			$plugin_update->checked[ $this->name ],
		);

		/* Prevent a version check API call right after the plugin data is removed */
		remove_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );

		$results = set_site_transient( 'update_plugins', $plugin_update );

		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );

		return $results;
	}

	/**
	 * Returns if the SSL of the store should be verified.
	 *
	 * @return bool
	 *
	 * @since  1.6.13
	 */
	public function verify_ssl() {
		return (bool) apply_filters( 'gpdf_sl_api_request_verify_ssl', true, $this );
	}

	/**
	 * Gets the unique key (option name) for a plugin.
	 *
	 * @return string
	 *
	 * @since 1.9.0
	 */
	public function get_cache_key() {
		return 'gpdf_sl_' . md5( $this->slug );
	}

	/**
	 * The network option name used to share a licensed package across a Multisite
	 *
	 * @return string
	 *
	 * @since 6.16.0
	 */
	public function get_network_cache_key() {
		return 'gpdf_sl_net_' . md5( $this->slug );
	}

	/**
	 * Allow the license key to be set mid-flight
	 *
	 * @param string $license_key
	 *
	 * @return void
	 *
	 * @since 6.16.0
	 */
	public function set_license_key( $license_key ) {
		$this->api_data['license'] = $license_key;
	}

	/**
	 * Allow the license status to be set mid-flight
	 *
	 * @param string $license_status
	 *
	 * @return void
	 *
	 * @since 6.16.0
	 */
	public function set_license_status( $license_status ) {
		$this->license_status = $license_status;
	}

	/**
	 * Whether the current site holds an active/valid license for this add-on
	 *
	 * @return bool
	 *
	 * @since 6.16.0
	 */
	protected function is_license_active() {
		return in_array( $this->license_status, [ 'active', 'valid' ], true );
	}

	/**
	 * @param \StdClass|false|null $response
	 *
	 * @return false|\StdClass
	 */
	public function standardize_api_response( $response ) {
		/* A non-object (false/null/scalar/array — from a filter or a malformed 200 body) would fatal on the property writes below (PHP 8) */
		if ( ! is_object( $response ) ) {
			return false;
		}

		// sections/banners/icons may arrive as a JSON string, a legacy serialized array, or an already-decoded object;
		// normalize_api_collection() turns each into an array (see it for the object-injection guard on strings).
		if ( isset( $response->sections ) ) {
			$response->sections = $this->normalize_api_collection( $response->sections );
		}

		if ( isset( $response->banners ) ) {
			$response->banners = $this->normalize_api_collection( $response->banners );
		}

		if ( isset( $response->icons ) ) {
			$response->icons = $this->normalize_api_collection( $response->icons );
		}

		if ( ! empty( $response->sections ) ) {
			foreach ( $response->sections as $key => $section ) {
				$response->$key = (array) $section;
			}
		}

		/* This is required for your plugin to support auto-updates in WordPress 5.5. */
		$response->plugin = $this->name;
		$response->id     = $this->name;
		$response->tested = $this->get_tested_version( $response );

		if ( ! isset( $response->requires ) ) {
			$response->requires = '';
		}

		if ( ! isset( $response->requires_php ) ) {
			$response->requires_php = '';
		}

		return $response;
	}

	/**
	 * @return string
	 *
	 * @since 6.16.0
	 */
	public function get_plugin_file() {
		return $this->plugin_file;
	}

	/**
	 * The plugin-folder basename sent as `slug` in the version API request and echoed back in the response.
	 *
	 * @return string
	 *
	 * @since 6.16.0
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Detect if Multisite and the plugin not active on current site
	 *
	 * This might happen if `switch_to_blog()` is used on a site where the plugin was originally active
	 * and the 'update_plugins' transient is set.
	 *
	 * @internal Controller_Settings::maybe_schedule_network_update_check() uses this feature to display a notice on the network admin about an update.
	 *
	 * @return bool
	 *
	 * @since 6.16.0
	 */
	protected function is_non_active_multisite() {
		if ( ! is_multisite() ) {
			return false;
		}

		// is_plugin_active() is in wp-admin/includes/plugin.php, unloaded on the frontend and during WP-Cron.
		$active_plugins  = (array) get_option( 'active_plugins', [] );
		$network_plugins = (array) get_site_option( 'active_sitewide_plugins', [] );

		if ( in_array( $this->name, $active_plugins, true ) || isset( $network_plugins[ $this->name ] ) ) {
			return false;
		}

		return true;
	}
}
