<?php
/*
Plugin Name: Gravity PDF
Version: 4.0-RC1
Description: Automatically generate highly-customisable PDF documents using Gravity Forms.
Author: Gravity PDF
Author URI: https://gravitypdf.com
Text Domain: gravity-forms-pdf-extended
Domain Path: /src/languages
*/

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/*
 * Set base constants we'll use throughout the plugin
 */

define( 'PDF_EXTENDED_VERSION', '4.0-RC1' ); /* the current plugin version */
define( 'PDF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ); /* plugin directory path */
define( 'PDF_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); /* plugin directory url */
define( 'PDF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); /* the plugin basename */

/*
 * Add our activation hook and deactivation hooks
 */
require_once PDF_PLUGIN_DIR . 'src/controller/Controller_Activation.php';

register_activation_hook( __FILE__, array( 'Controller_Activation', 'activation' ) );
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
	public $required_gf_version = '1.8';

	/**
	 * The plugin's required WordPress version
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	public $required_wp_version = '4.0';

	/**
	 * The plugin's required PHP version
	 * We've made the call to drop PHP5.2 support
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	public $required_php_version = '5.3';

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
	 * @return void
	 *
	 * @since 4.0
	 */
	public function init() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	/**
	 * Check if dependancies are met and load plugin, otherwise display errors
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function plugins_loaded() {

		/* Check minimum requirements are met */
		$this->check_wordpress();
		$this->check_gravity_forms();
		$this->check_php();
		$this->check_mb_string();
		$this->check_mb_string_regex();
		$this->check_gd();
		$this->check_dom();
		$this->check_ram( ini_get( 'memory_limit' ) );

		/* Check if any errors were thrown, enqueue them and exit early */
		if ( sizeof( $this->notices ) > 0 ) {
			add_action( 'admin_notices', array( $this, 'display_notices' ) );

			return null;
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
	public function check_wordpress() {
		global $wp_version;

		/* WordPress version not compatible */
		if ( ! version_compare( $wp_version, $this->required_wp_version, '>=' ) ) {
			$this->notices[] = sprintf( __( 'WordPress Version %s is required.', 'gravity-forms-pdf-extended' ), $this->required_wp_version );

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
		if ( ! class_exists( 'GFCommon' ) || ! version_compare( GFCommon::$version, $this->required_gf_version, '>=' ) ) {
			$this->notices[] = sprintf( __( '%sGravity Forms%s Version %s is required.', 'gravity-forms-pdf-extended' ), '<a href="https://www.e-junkie.com/ecom/gb.php?cl=54585&c=ib&aff=235154">', '</a>', $this->required_gf_version );

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
			$this->notices[] = sprintf( __( 'You are running an %soutdated version of PHP%s. Contact your web hosting provider to update.', 'gravity-forms-pdf-extended' ), '<a href="http://www.wpupdatephp.com/update/">', '</a>' );

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
			$this->notices[] = __( 'The PHP Extension MB String could not be detected. Contact your web hosting provider to fix.', 'gravity-forms-pdf-extended' );

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
			$this->notices[] = __( 'The PHP Extension MB String does not have MB Regex enabled. Contact your web hosting provider to fix.', 'gravity-forms-pdf-extended' );

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
			$this->notices[] = __( 'The PHP Extension GD Image Library could not be detected. Contact your web hosting provider to fix.', 'gravity-forms-pdf-extended' );

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
			$this->notices[] = __( 'The PHP DOM Extension was not found. Contact your web hosting provider to fix.', 'gravity-forms-pdf-extended' );

			return false;
		}

		/* Check libxml is loaded */
		if ( ! extension_loaded( 'libxml' ) ) {
			$this->notices[] = __( 'The PHP Extension libxml could not be detected. Contact your web hosting provider to fix.', 'gravity-forms-pdf-extended' );

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
			$this->notices[] = sprintf( __( "You need %s128MB%s of WP Memory (RAM) but we only found %s available. %sTry these methods%s to increase your memory limit, otherwise contact your web hosting provider to fix.", 'gravity-forms-pdf-extended' ), '<a href="#">', '</a>', '<strong>', '</strong>', $ram . 'MB' );

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

		$convert = array( 'mb' => 'm', 'kb' => 'k', 'gb' => 'g' );

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
	 * @return void
	 *
	 * @since 4.0
	 */
	public function display_notices() {
		?>
		<div class="error">
			<p><strong><?php _e( 'Gravity PDF Installation Problem', 'gravity-forms-pdf-extended' ); ?></strong></p>

			<p><?php _e( 'The minimum requirements for Gravity PDF have not been met. Please fix the issue(s) below to continue:', 'gravity-forms-pdf-extended' ); ?></p>
			<ul style="padding-bottom: 0.5em">
				<?php foreach ( $this->notices as $notice ) : ?>
					<li style="padding-left: 20px;list-style: inside"><?php echo $notice; ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
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
