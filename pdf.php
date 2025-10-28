<?php
/*
Plugin Name: Gravity PDF
Version: 6.14.0
Description: Automatically generate highly-customizable PDF documents using Gravity Forms and WordPress (canonical)
Author: Blue Liquid Designs
Author URI: https://blueliquiddesigns.com.au
Plugin URI: https://gravitypdf.com
Update URI: https://gravitypdf.com
Text Domain: gravity-pdf
Domain Path: /src/assets/languages
Requires at least: 5.3
Requires PHP: 7.3
License: GPL-2.0
License URI: https://opensource.org/licenses/gpl-2.0.php
*/

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* If a copy of the plugin has been activated already, deactivate the original */
if ( defined( 'PDF_PLUGIN_BASENAME' ) ) {
	deactivate_plugins( PDF_PLUGIN_BASENAME, true );

	return;
}

/*
 * Set base constants we'll use throughout the plugin
 */
define( 'PDF_EXTENDED_VERSION', '6.14.0' ); /* the current plugin version */
define( 'PDF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ); /* plugin directory path */
define( 'PDF_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); /* plugin directory url */
define( 'PDF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); /* the plugin basename */
define( 'GPDF_PLUGIN_FILE', __FILE__ );
define( 'GPDF_API_URL', 'https://gravitypdf.com?api=1' );

if ( ! class_exists( 'GFPDF_Major_Compatibility_Checks' ) ) {
	/*
	 * Add our activation hook and deactivation hooks
	 */
	require_once PDF_PLUGIN_DIR . 'src/Controller/Controller_Activation.php';
	register_deactivation_hook( __FILE__, array( 'Controller_Activation', 'deactivation' ) );

	/* If canonical plugin load the plugin updater */
	if ( is_file( __DIR__ . '/gravity-pdf-updater.php' ) ) {
		require_once __DIR__ . '/gravity-pdf-updater.php';
	}

	/**
	 * Plugin initialization class
	 * Check dependency requirements are met before loading. If not, inform user of the problem
	 *
	 * @since 4.0
	 */
	class GFPDF_Major_Compatibility_Checks {

		/**
		 * The plugin's basename
		 *
		 * @var string
		 *
		 * @since 4.0
		 */
		private $basename;

		/**
		 * The path to the plugin
		 *
		 * @var string
		 *
		 * @since 4.0
		 */
		private $path;

		/**
		 * Holds any blocker error messages stopping plugin running
		 *
		 * @var array
		 *
		 * @since 4.0
		 */
		private $notices = array();

		/**
		 * The plugin's required Gravity Forms version
		 *
		 * @var string
		 *
		 * @since 4.0
		 */
		public $required_gf_version = '2.5';

		/**
		 * The plugin's required WordPress version
		 *
		 * @var string
		 *
		 * @since 4.0
		 */
		public $required_wp_version = '5.3';

		/**
		 * The plugin's required PHP version
		 *
		 * @var string
		 *
		 * @since 4.0
		 */
		public $required_php_version = '7.3';

		/**
		 * Set our required variables for a fallback and attempt to initialise
		 *
		 * @param string $basename Plugin basename
		 * @param string $path     The plugin path
		 *
		 * @since    4.0
		 */
		public function __construct( $basename = '', $path = '' ) {

			/* Set our class variables */
			$this->basename = $basename;
			$this->path     = $path;
		}

		/**
		 * Load the plugin
		 *
		 * @since 4.0
		 */
		public function init() {
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		}

		/**
		 * Check if dependencies are met and load plugin, otherwise display errors
		 *
		 * @since 4.0
		 */
		public function plugins_loaded() {

			/* Register language files early so startup errors can be translated */
			load_plugin_textdomain( 'gravity-pdf', false, dirname( plugin_basename( __FILE__ ) ) . '/src/assets/languages/' );

			/* Notify administrator current version is not canonical */
			if ( ! is_file( __DIR__ . '/gravity-pdf-updater.php' ) ) {
				add_action( 'admin_init', [ $this, 'maybe_display_canonical_plugin_notice' ] );
				add_action( 'after_plugin_row', [ $this, 'maybe_display_canonical_plugin_notice_below_plugin' ], 10, 2 );
			}

			/* Check minimum requirements are met */
			$this->is_compatible_wordpress_version();
			$this->check_gravity_forms();
			$this->check_php();
			$this->check_mb_string();
			$this->check_mb_string_regex();
			$this->check_ctype();
			$this->check_gd();
			$this->check_dom();
			$this->check_ram( ini_get( 'memory_limit' ) );

			/* Check if any errors were thrown, enqueue them to display on specific admin pages and exit early */
			if ( count( $this->notices ) > 0 ) {
				global $pagenow;

				$is_admin_area       = is_admin();
				$is_specific_wp_page = in_array( $pagenow, [ 'index.php', 'plugins.php', 'options-general.php' ], true );
				$is_specific_gf_page = $pagenow === 'admin.php' && in_array( $_GET['page'] ?? '', [ 'gf_edit_forms', 'gf_entries', 'gf_settings' ], true ); /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */

				if ( $is_admin_area && ( $is_specific_wp_page || $is_specific_gf_page ) ) {
					add_action( 'admin_notices', [ $this, 'display_notices' ] );
				}

				return;
			}

			require_once $this->path . 'src/bootstrap.php';
		}

		/**
		 * Check if WordPress version is compatible
		 *
		 * @return boolean Whether compatible or not
		 *
		 * @since 4.0
		 */
		public function is_compatible_wordpress_version() {
			global $wp_version;

			/* WordPress version not compatible */
			if ( ! version_compare( $wp_version, $this->required_wp_version, '>=' ) ) {
				$this->notices[] = function () {
					/* translators: 1. WordPress version number 2. HTML Anchor Open Tag 3. Html Anchor Close Tag */
					return sprintf( esc_html__( 'WordPress version %1$s is required: upgrade to the latest version. %2$sGet more information%3$s.', 'gravity-pdf' ), $this->required_wp_version, '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#wordpress-version-x-is-required">', '</a>' );
				};

				return false;
			}

			return true;
		}

		/**
		 * Check if Gravity Forms version is compatible
		 *
		 * @return boolean Whether compatible or not
		 *
		 * @since 4.0
		 */
		public function check_gravity_forms() {

			/* Gravity Forms version not compatible */
			if ( ! class_exists( 'GFCommon' ) ) {
				$this->notices[] = static function () {
					/* translators: 1. HTML Anchor Open Tag 2. HTML Anchor Open Tag 3. Html Anchor Close Tag */
					return sprintf( esc_html__( '%1$sGravity Forms%3$s is required to use Gravity PDF. %2$sGet more information%3$s.', 'gravity-pdf' ), '<a href="https://rocketgenius.pxf.io/c/1211356/445235/7938">', '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#gravity-forms-is-required">', '</a>' );
				};

				return false;
			}

			if ( ! version_compare( GFCommon::$version, $this->required_gf_version, '>=' ) ) {
				$this->notices[] = function () {
					/* translators: 1. HTML Anchor Open Tag 2. HTML Anchor Close Tag 3. Plugin version number 4. Html Anchor Open Tag */
					return sprintf( esc_html__( '%1$sGravity Forms%2$s version %3$s or higher is required. %4$sGet more information%2$s.', 'gravity-pdf' ), '<a href="https://rocketgenius.pxf.io/c/1211356/445235/7938">', '</a>', $this->required_gf_version, '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#gravity-forms-version-x-is-required">' );
				};

				return false;
			}

			return true;
		}

		/**
		 * Check if PHP version is compatible
		 *
		 * @return boolean Whether compatible or not
		 *
		 * @since 4.0
		 */
		public function check_php() {

			/* Check PHP version is compatible */
			if ( ! version_compare( phpversion(), $this->required_php_version, '>=' ) ) {
				$this->notices[] = static function () {
					/* translators: 1. HTML Anchor Open Tag 2. HTML Anchor Close Tag 3. HTML Anchor Open Tag 4. HTML Anchor Close Tag */
					return sprintf( esc_html__( 'You are running an %1$soutdated version of PHP%2$s. Contact your web hosting provider to update. %3$sGet more information%4$s.', 'gravity-pdf' ), '<a href="https://wordpress.org/support/update-php/">', '</a>', '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#you-are-running-an-outdated-version-of-php">', '</a>' );
				};

				return false;
			}

			return true;
		}

		/**
		 * Check if PHP MB String enabled
		 *
		 * @return boolean Whether compatible or not
		 *
		 * @since 4.0
		 */
		public function check_mb_string() {

			/* Check MB String is installed */
			if ( ! extension_loaded( 'mbstring' ) ) {
				$this->notices[] = static function () {
					/* translators: 1. HTML Anchor Open Tag 2. HTML Anchor Close Tag 3. PHP Extension name */
					return sprintf(
						esc_html__( 'The PHP extension %3$s could not be detected. Contact your web hosting provider to fix. %1$sGet more information%2$s.', 'gravity-pdf' ),
						'<a href="https://docs.gravitypdf.com/v6/users/activation-errors#the-php-extension-mb-string-could-not-be-detected">',
						'</a>',
						'MB String'
					);
				};

				return false;
			}

			return true;
		}

		/**
		 * Check if MB String Regex enabled
		 *
		 * @return boolean Whether compatible or not
		 *
		 * @since 4.0
		 */
		public function check_mb_string_regex() {

			/* Check MB String is compiled with regex capabilities */
			if ( extension_loaded( 'mbstring' ) && ! function_exists( 'mb_regex_encoding' ) ) {
				$this->notices[] = static function () {
					/* translators: 1. HTML Anchor Open Tag 2. HTML Anchor Close Tag */
					return sprintf( esc_html__( 'The PHP extension MB String does not have MB Regex enabled. Contact your web hosting provider to fix. %1$sGet more information%2$s.', 'gravity-pdf' ), '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#the-php-extension-mb-string-does-not-have-mb-regex-enabled">', '</a>' );
				};

				return false;
			}

			return true;
		}

		/**
		 * Check if the Ctype PHP extension is enabled
		 *
		 * @return bool
		 *
		 * @since 6.11
		 */
		public function check_ctype() {
			if ( ! extension_loaded( 'ctype' ) ) {
				$this->notices[] = static function () {
					/* translators: 1. HTML Anchor Open Tag 2. HTML Anchor Close Tag 3. PHP Extension name */
					return sprintf(
						esc_html__( 'The PHP extension %3$s could not be detected. Contact your web hosting provider to fix. %1$sGet more information%2$s.', 'gravity-pdf' ),
						'<a href="https://docs.gravitypdf.com/v6/users/activation-errors#the-php-extension-ctype-could-not-be-detected">',
						'</a>',
						'Ctype'
					);
				};

				return false;
			}

			return true;
		}

		/**
		 * Check if PHP GD Library installed
		 *
		 * @return boolean Whether compatible or not
		 *
		 * @since 4.0
		 */
		public function check_gd() {

			/* Check GD Image Library is installed */
			if ( ! extension_loaded( 'gd' ) ) {
				$this->notices[] = static function () {
					/* translators: 1. HTML Anchor Open Tag 2. HTML Anchor Close Tag 3. PHP Extension name */
					return sprintf( esc_html__( 'The PHP extension %3$s could not be detected. Contact your web hosting provider to fix. %1$sGet more information%2$s.', 'gravity-pdf' ), '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#the-php-extension-gd-image-library-could-not-be-detected">', '</a>', 'GD Image Library' );
				};

				return false;
			}

			return true;
		}

		/**
		 * Check if PHP DOM / libxml installed
		 *
		 * @return boolean Whether compatible or not
		 *
		 * @since 4.0
		 */
		public function check_dom() {

			/* Check DOM Class is installed */
			if ( ! extension_loaded( 'dom' ) || ! class_exists( 'DOMDocument' ) ) {
				$this->notices[] = static function () {
					/* translators: 1. HTML Anchor Open Tag 2. HTML Anchor Close Tag 3. PHP Extension name */
					return sprintf( esc_html__( 'The PHP extension %3$s could not be detected. Contact your web hosting provider to fix. %1$sGet more information%2$s.', 'gravity-pdf' ), '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#the-php-dom-extension-was-not-found">', '</a>', 'DOM' );
				};

				return false;
			}

			/* Check libxml is loaded */
			if ( ! extension_loaded( 'libxml' ) ) {
				$this->notices[] = static function () {
					/* translators: 1. HTML Anchor Open Tag 2. HTML Anchor Close Tag 3. PHP Extension name */
					return sprintf( esc_html__( 'The PHP extension %3$s could not be detected. Contact your web hosting provider to fix. %1$sGet more information%2$s.', 'gravity-pdf' ), '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#the-php-extension-libxml-could-not-be-detected">', '</a>', 'libxml' );
				};

				return false;
			}

			return true;
		}

		/**
		 * Check if minimum RAM requirements met
		 *
		 * @param string $ram The PHP RAM setting
		 *
		 * @return boolean Whether compatible or not
		 *
		 * @since 4.0
		 */
		public function check_ram( $ram ) {

			/* Check Minimum RAM requirements */
			$ram = $this->get_ram( $ram );

			if ( $ram < 64 && $ram !== -1 ) {
				$this->notices[] = static function () use ( $ram ) {
					/* translators: 1. RAM value in MB 2. HTML Anchor Open Tag 3. HTML Anchor Close Tag */
					return sprintf( esc_html__( 'You need 128MB of WP Memory (RAM) but we only found %1$s available. %2$sTry these methods to increase your memory limit%3$s, otherwise contact your web hosting provider to fix.', 'gravity-pdf' ), esc_html( $ram . 'MB' ), '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#you-need-128mb-of-wp-memory-ram-but-we-only-found-x-available">', '</a>' );
				};

				return false;
			}

			return true;
		}


		/**
		 * Get the available system memory
		 *
		 * @param string $ram The PHP RAM setting
		 *
		 * @return integer The calculated RAM
		 *
		 * @since 4.0
		 */
		public function get_ram( $ram ) {

			/* Get memory in standardised bytes format */
			$memory_limit = $this->convert_ini_memory( $ram );

			/* Convert to megabytes, or set to -1 if unlimited */
			return ( $memory_limit === '-1' ) ? -1 : floor( $memory_limit / 1024 / 1024 );
		}

		/**
		 * Convert .ini file memory to bytes
		 *
		 * @param string $memory The .ini memory limit
		 *
		 * @return integer The calculated memory limit in bytes
		 */
		public function convert_ini_memory( $memory ) {

			$convert = array(
				'mb' => 'm',
				'kb' => 'k',
				'gb' => 'g',
			);

			/* Standardise format */
			foreach ( $convert as $k => $v ) {
				$memory = str_ireplace( $k, $v, $memory );
			}

			/* Check if memory allocation is in mb, kb or gb */
			switch ( strtolower( substr( $memory, -1 ) ) ) {
				case 'm':
					return (int) $memory * 1048576;
				case 'k':
					return (int) $memory * 1024;
				case 'g':
					return (int) $memory * 1073741824;
			}

			return $memory;
		}

		/**
		 * Helper function to easily display error messages
		 *
		 * @since 4.0
		 */
		public function display_notices() {
			$classes = class_exists( 'GFForms' ) && GFForms::is_gravity_page() ?
				'notice gf-notice notice-error' :
				'notice error';
			?>
			<div class="<?php echo esc_attr( $classes ); ?>">
				<?php $this->notice_body_content(); ?>
			</div>
			<?php
		}

		/**
		 * Detail detailed error message if user can activate plugins, otherwise show generic message
		 *
		 * @since 6.0
		 */
		public function notice_body_content() {
			?>
			<?php if ( current_user_can( 'activate_plugins' ) ): ?>
				<p><strong><?php esc_html_e( 'Gravity PDF Installation Problem', 'gravity-pdf' ); ?></strong></p>

				<p><?php esc_html_e( 'The minimum requirements for Gravity PDF have not been met. Please fix the issue(s) below to use the plugin:', 'gravity-pdf' ); ?></p>
				<ul style="padding-bottom: 0">
					<?php foreach ( $this->notices as $notice ): ?>
						<li style="padding-left: 20px;list-style: inside"><?php echo wp_kses_post( is_callable( $notice ) ? $notice() : $notice ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php else: ?>
				<p><?php esc_html_e( 'The minimum requirements for the Gravity PDF plugin have not been met. Please contact the site administrator for assistance.', 'gravity-pdf' ); ?></p>
			<?php endif; ?>
			<?php
		}

		/**
		 * Notify administrator they are not using the canonical version of Gravity PDF
		 *
		 * @return void
		 *
		 * @since 6.12
		 */
		public function maybe_display_canonical_plugin_notice() {
			if ( ! method_exists( '\GFCommon', 'add_dismissible_message' ) ) {
				return;
			}

			$message = wp_kses(
				sprintf(
					__( 'The Gravity PDF plugin has a new home! In order to get updates direct from GravityPDF.com %1$syou need to perform a one-time download of the plugin%2$s.', 'gravity-pdf' ),
					'<a href="https://gravitypdf.com/news/installing-and-upgrading-to-the-canonical-version-of-gravity-pdf/" target="_blank">',
					'</a>',
				),
				[
					'a' => [
						'href'   => true,
						'target' => true,
					],
				]
			);

			\GFCommon::add_dismissible_message(
				$message,
				'gravity-pdf-canonical-plugin-notice',
				'warning',
				'install_plugins',
				true
			);
		}

		/**
		 * Notify administrator they are not using the canonical version of Gravity PDF
		 *
		 * @return void
		 *
		 * @since 6.12
		 */
		public function maybe_display_canonical_plugin_notice_below_plugin( $plugin_file, $plugin_data ) {
			if ( ! isset( $plugin_data['TextDomain'] ) || $plugin_data['TextDomain'] !== 'gravity-forms-pdf-extended' ) {
				return;
			}

			printf(
				'<tr class="plugin-update-tr %3$s" id="%1$s-update" data-slug="%1$s" data-plugin="%2$s">',
				esc_attr( $plugin_data['slug'] ),
				esc_attr( $plugin_data['plugin'] ),
				'active'
			);

			echo '<td colspan="4" class="plugin-update colspanchange">';
			echo '<div class="notice inline notice-warning notice-alt"><p>';

			echo wp_kses(
				sprintf(
					__( 'The Gravity PDF plugin has a new home! In order to get updates direct from GravityPDF.com %1$syou need to perform a one-time download of the plugin%2$s.', 'gravity-pdf' ),
					'<a href="https://gravitypdf.com/news/installing-and-upgrading-to-the-canonical-version-of-gravity-pdf/" target="_blank">',
					'</a>',
				),
				[
					'a' => [
						'href'   => true,
						'target' => true,
					],
				]
			);

			echo '</p></div>';
			echo '</td>';
			echo '</tr>';
		}
	}
}

/*
 * Initialise the software
 */
$gravitypdf = new GFPDF_Major_Compatibility_Checks(
	PDF_PLUGIN_BASENAME,
	PDF_PLUGIN_DIR
);

$gravitypdf->init();
