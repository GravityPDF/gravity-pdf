<?php
/*
Plugin Name: Gravity PDF
Version: 4.0.0
Description: A PHP toolkit to automatically overlay Gravity Forms data onto existing PDF documents with ease. It's the perfect tool for autocompleting government and legal documents that are legally required to be completed in the provided layout. Or for complex PDFs that would be difficult to reproduce using HTML / CSS using our free software. 
Author: Gravity PDF
Author URI: https://gravitypdf.com
Text Domain: pdfextended
Domain Path: /src/languages
*/

/* Exit if accessed directly */
if (! defined('ABSPATH')) {
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
 * Change the name when creating a new add on
 */

define('PDF_EXTENDED_VERSION', '4.0.0'); /* the current plugin version */
define('PDF_PLUGIN_DIR', plugin_dir_path( __FILE__ )); /* plugin directory path */
define('PDF_PLUGIN_URL', plugin_dir_url( __FILE__ )); /* plugin directory url */
define('GF_PDF_EXTENDED_PLUGIN_BASENAME', plugin_basename(__FILE__)); /* the plugin basename */

/*
 * Add our activation hook
 */
require_once(PDF_PLUGIN_DIR . 'src/controller/Controller_Update.php');
register_activation_hook(   __FILE__, array('Controller_Update', 'activation') );

/**
 *
 * Our initialisation class 
 * Check all the dependancy requirements are met, otherwise fallback and show appropriate user error 
 * 
 * Note: Remember to change the class name when creating a new add on
 */
class GFPDF_Major_Compatibility_Checks
{
	/**
	 * The plugin's basename 
	 * @var String	 
	 * @since 4.0
	 */
	private $basename;

	/**
	 * The path to the plugin 
	 * @var String
	 * @since 4.0
	 */
	private $path;

	/**
	 * The plugin's required Gravity Forms version 
	 * @var String
	 * @since 4.0 
	 */
	private $required_gf_version = '1.8';

	/**
	 * The plugin's required WordPress version 
	 * @var String
	 * @since 4.0
	 */
	private $required_wp_version = '3.9';

	/**
	 * The plugin's required PHP version 
	 * We've made the call to drop PHP5.2 support
	 * @var String
	 * @since 4.0
	 */
	private $required_php_version = '5.3.2';

	/**
	 * Set our required variables for a fallback and attempt to initialise 
	 * @param String $basename         Plugin basename
	 * @param String $path 		       The plugin path
	 * @param String $required_version Required Gravity PDF version 
	 */
	public function __construct($basename, $path) {

		/*
		 * Set our class variables 
		 */
		$this->basename         = $basename;
		$this->path             = $path;

		/* load the plugin */
		add_action('plugins_loaded', array($this, 'plugins_loaded'));
	}

	/**
	 * Check if dependancies are met and load plugin, otherwise display errors 
	 * @return void
	 */
	public function plugins_loaded()
	{
		global $wp_version;

		/**
		 * Check Gravity Forms and WordPress meet the version requirements 
		 * TODO: php requirements 
		 *       error message display 
		 * 
		 */
		if(!version_compare($wp_version, $this->required_wp_version, '>='))
		{
			add_action('after_plugin_row_' . $this->basename, array($this, 'wp_outdated_version')); 		
			return false;				
		} elseif( !class_exists('GFCommon') || !version_compare(GFCommon::$version, $this->required_gf_version, '>=')) {
			add_action('after_plugin_row_' . $this->basename, array($this, 'gf_outdated_version')); 		
			return false;				
		} elseif( !function_exists('spl_autoload_register')) { 
			add_action('after_plugin_row_' . $this->basename, array($this, 'php_autoload_dependancy_issue')); 		
			return false;							
		}
		
		require_once($this->path . 'src/bootstrap.php');
	}

	/**
	 * Display WP outdated version error and prompt to upgrade	 
	 * @return void
	 */
	public function wp_outdated_version() {
		$message = sprintf(__('WordPress version %s is required to use Gravity PDF. Please upgrade to a compatible version.', 'pdfextended'), $this->required_wp_version);
		self::display_plugin_message($message, true);		
	}

	/**
	 * Display GF outdated version error and prompt to upgrade	 
	 * @return void
	 */
	public function gf_outdated_version() {
		$message = sprintf(__('Gravity PDF %s is required to use this extension. Please upgrade to a compatible version.', 'pdfextended'), $this->required_gf_version);
		self::display_plugin_message($message, true);		
	}

	/**
	 * Display error when PHP doesn't have the SPL library enabled (only PHP5.2 effected)
	 * @return void
	 */
	public function php_autoload_dependancy_issue() {
		$message = sprintf(__('You are running an %soutdated version of PHP%s. Contact your web host to update to a newer version.', 'pdfextended'), '<a href="http://www.wpupdatephp.com/update/">', '</a>');
		self::display_plugin_message($message, true);		
	}
	

	/**
	 * Helper function to easily display messages below the plugin screen
	 * @param  string  $message  The error to output
	 * @param  boolean $is_error Whether it is a message or an error that should be displayed
	 * @return void
	 */
	private function display_plugin_message($message, $is_error = false){
	    $style = $is_error ? 'style="background-color: #ffebe8;"' : "";
	    echo '</tr><tr class="plugin-update-tr"><td colspan="5" class="plugin-update"><div class="update-message" ' . $style . '>' . $message . '</div></td>';
	}
}	

/*
 * Initialise the software 
 */
new GFPDF_Major_Compatibility_Checks(
	GF_PDF_EXTENDED_PLUGIN_BASENAME,
	PDF_PLUGIN_DIR
);