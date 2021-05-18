<?php
/*
Plugin Name: Gravity PDF
Version: 6.0.2
Description: Automatically generate highly-customisable PDF documents using Gravity Forms.
Author: Gravity PDF
Author URI: https://gravitypdf.com
Plugin URI: https://wordpress.org/plugins/gravity-forms-pdf-extended/
Text Domain: gravity-forms-pdf-extended
Domain Path: /src/assets/languages
Requires at least: 5.3
Requires PHP: 7.3
*/

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Set base constants we'll use throughout the plugin
 */
define( 'PDF_EXTENDED_VERSION', '6.0.2' ); /* the current plugin version */
define( 'PDF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ); /* plugin directory path */
define( 'PDF_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); /* plugin directory url */
define( 'PDF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); /* the plugin basename */

/*
 * Add our activation hook and deactivation hooks
 */
require_once PDF_PLUGIN_DIR . 'src/Controller/Controller_Activation.php';
register_deactivation_hook( __FILE__, array( 'Controller_Activation', 'deactivation' ) );

/**
 *
 * Our initialisation class
 * Check all the dependancy requirements are met, otherwise fallback and show appropriate user error
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
	public $required_gf_version = '2.5.0';

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
	 * Whether to offer a downgrade notice or not
	 *
	 * @var bool
	 *
	 * @since 6.0
	 */
	protected $offer_downgrade = false;

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

		/* Check minimum requirements are met */
		$this->is_compatible_wordpress_version();
		$this->check_gravity_forms();
		$this->check_php();
		$this->check_mb_string();
		$this->check_mb_string_regex();
		$this->check_gd();
		$this->check_dom();
		$this->check_ram( ini_get( 'memory_limit' ) );

		/* Check if any errors were thrown, enqueue them and exit early */
		if ( count( $this->notices ) > 0 ) {
			if ( $this->offer_downgrade ) {
				add_action( 'admin_menu', array( $this, 'admin_rollback_menu' ), 20 );

				/* don't display the notice on the rollback page */
				if ( is_admin() && rgget( 'page' ) === 'gpdf-downgrade' ) {
					return;
				}
			}

			if ( class_exists( 'GFForms' ) && GFForms::is_gravity_page() ) {
				ob_start();
				$this->notice_body_content();
				GFCommon::add_error_message( ob_get_clean() );

				return;
			}

			add_action( 'admin_notices', array( $this, 'display_notices' ) );

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
			$this->notices[] = sprintf( esc_html__( 'WordPress version %1$s is required: upgrade to the latest version. %2$sGet more info%3$s.', 'gravity-forms-pdf-extended' ), $this->required_wp_version, '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#wordpress-version-x-is-required">', '</a>' );

			/* Offer downgrade prompt if WP version is compatible with v5 */
			if ( version_compare( $wp_version, '4.8', '>=' ) ) {
				$this->offer_downgrade = true;
			}

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
			$this->notices[] = sprintf( esc_html__( '%1$sGravity Forms%2$s is required to use Gravity PDF. %4$sGet more info%5$s.', 'gravity-forms-pdf-extended' ), '<a href="https://rocketgenius.pxf.io/c/1211356/445235/7938">', '</a>', $this->required_gf_version, '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#gravity-forms-is-required">', '</a>' );

			return false;
		}

		if ( ! version_compare( GFCommon::$version, $this->required_gf_version, '>=' ) ) {
			$this->notices[] = sprintf( esc_html__( '%1$sGravity Forms%2$s version %3$s or higher is required. %4$sGet more info%5$s.', 'gravity-forms-pdf-extended' ), '<a href="https://rocketgenius.pxf.io/c/1211356/445235/7938">', '</a>', $this->required_gf_version, '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#gravity-forms-version-x-is-required">', '</a>' );

			/* Offer downgrade prompt if GF version is compatible with v5 */
			if ( version_compare( GFCommon::$version, '2.3.1', '>=' ) ) {
				$this->offer_downgrade = true;
			}

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
			$this->notices[] = sprintf( esc_html__( 'You are running an %1$soutdated version of PHP%2$s. Contact your web hosting provider to update. %3$sGet more info%4$s.', 'gravity-forms-pdf-extended' ), '<a href="https://wordpress.org/support/update-php/">', '</a>', '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#you-are-running-an-outdated-version-of-php">', '</a>' );

			/* Offer downgrade prompt if PHP version is compatible with v5 */
			if ( version_compare( phpversion(), '5.6', '>=' ) ) {
				$this->offer_downgrade = true;
			}

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
			$this->notices[] = sprintf( esc_html__( 'The PHP Extension MB String could not be detected. Contact your web hosting provider to fix. %1$sGet more info%2$s.', 'gravity-forms-pdf-extended' ), '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#the-php-extension-mb-string-could-not-be-detected">', '</a>' );

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
			$this->notices[] = sprintf( esc_html__( 'The PHP Extension MB String does not have MB Regex enabled. Contact your web hosting provider to fix. %1$sGet more info%2$s.', 'gravity-forms-pdf-extended' ), '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#the-php-extension-mb-string-does-not-have-mb-regex-enabled">', '</a>' );

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
			$this->notices[] = sprintf( esc_html__( 'The PHP Extension GD Image Library could not be detected. Contact your web hosting provider to fix. %1$sGet more info%2$s.', 'gravity-forms-pdf-extended' ), '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#the-php-extension-gd-image-library-could-not-be-detected">', '</a>' );

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
			$this->notices[] = sprintf( esc_html__( 'The PHP DOM Extension was not found. Contact your web hosting provider to fix. %1$sGet more info%2$s.', 'gravity-forms-pdf-extended' ), '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#the-php-dom-extension-was-not-found">', '</a>' );

			return false;
		}

		/* Check libxml is loaded */
		if ( ! extension_loaded( 'libxml' ) ) {
			$this->notices[] = sprintf( esc_html__( 'The PHP Extension libxml could not be detected. Contact your web hosting provider to fix. %1$sGet more info%2$s.', 'gravity-forms-pdf-extended' ), '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#the-php-extension-libxml-could-not-be-detected">', '</a>' );

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
			$this->notices[] = sprintf( esc_html__( 'You need %1$s128MB%2$s of WP Memory (RAM) but we only found %3$s available. %4$sTry these methods to increase your memory limit%5$s, otherwise contact your web hosting provider to fix.', 'gravity-forms-pdf-extended' ), '<strong>', '</strong>', $ram . 'MB', '<a href="https://docs.gravitypdf.com/v6/users/activation-errors#you-need-128mb-of-wp-memory-ram-but-we-only-found-x-available">', '</a>' );

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
		?>
		<div class="error">
			<?php $this->notice_body_content(); ?>
		</div>
		<?php
	}

	/**
	 * @since 6.0
	 */
	public function notice_body_content() {
		?>
		<p><strong><?php esc_html_e( 'Gravity PDF Installation Problem', 'gravity-forms-pdf-extended' ); ?></strong></p>

		<p><?php esc_html_e( 'The minimum requirements for Gravity PDF have not been met. Please fix the issue(s) below to use the plugin:', 'gravity-forms-pdf-extended' ); ?></p>
		<ul style="padding-bottom: 0">
			<?php foreach ( $this->notices as $notice ): ?>
				<li style="padding-left: 20px;list-style: inside"><?php echo $notice; ?></li>
			<?php endforeach; ?>
		</ul>

		<?php if ( $this->offer_downgrade && PDF_PLUGIN_BASENAME === 'gravity-forms-pdf-extended/pdf.php' ): ?>
			<form method="post" action="<?= admin_url( 'index.php?page=gpdf-downgrade' ) ?>">
				<?php wp_nonce_field( 'gpdf-downgrade' ); ?>
				<p>
					<?php esc_html_e( 'Not ready to upgrade? Try an earlier version of Gravity PDF', 'gravity-forms-pdf-extended' ); ?>
					<button class="button primary"><?php esc_html_e( 'Downgrade Now', 'gravity-forms-pdf-extended' ); ?></button>
				</p>
			</form>
		<?php endif; ?>
		<?php
	}

	/**
	 * Adds a 'hidden' menu item that is activated when the user elects to rollback
	 *
	 * @since 6.0
	 */
	public function admin_rollback_menu() {
		if ( rgget( 'page' ) !== 'gpdf-downgrade' ) {
			return;
		}

		$title = esc_html__( 'Downgrade', 'gravity-forms-pdf-extended' );

		add_dashboard_page( $title, $title, 'update_plugins', 'gpdf-downgrade', array( $this, 'rollback' ) );
	}

	/**
	 * Roll Gravity PDF back to the latest v5 release
	 *
	 * @since 6.0
	 */
	public function rollback() {
		if ( ! check_admin_referer( 'gpdf-downgrade' ) || ! current_user_can( 'update_plugins' ) ) {
			die( __( 'The link you followed has expired.', 'default' ) );
		}

		$plugin   = 'gravity-forms-pdf-extended';
		$response = wp_remote_get( 'https://api.wordpress.org/plugins/info/1.0/' . $plugin . '.json' );
		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			die( __( 'Plugin downgrade failed.', 'default' ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['versions'] ) ) {
			die( __( 'Plugin downgrade failed.', 'default' ) );
		}

		/* Get the first matching v5 tag and url (the latest) */
		foreach ( array_reverse( $body['versions'] ) as $version => $download_url ) {
			if ( $version[0] === '5' && $version[1] === '.' ) {
				break;
			}
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$nonce     = 'gpdf-downgrade';
		$url       = 'index.php?page=' . $nonce;
		$overwrite = 'downgrade-plugin';

		$upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( compact( 'nonce', 'url', 'plugin', 'version', 'overwrite' ) ) );
		$upgrader->install( $download_url, [ 'overwrite_package' => true ] );
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
