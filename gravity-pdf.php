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
define('PDF_PLUGIN_DIR', plugin_dir_path(__FILE__)); /* plugin directory path */
define('PDF_PLUGIN_URL', plugin_dir_url(__FILE__)); /* plugin directory url */
define('GF_PDF_EXTENDED_PLUGIN_BASENAME', plugin_basename(__FILE__)); /* the plugin basename */

/*
 * Add our activation hook
 */
require_once PDF_PLUGIN_DIR.'src/controller/Controller_Update.php';
register_activation_hook(__FILE__, array('Controller_Update', 'activation'));

/**
 *
 * Our initialisation class
 * Check all the dependancy requirements are met, otherwise fallback and show appropriate user error
 *
 * @since 4.0
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
     * Holds any blocker error messages stopping plugin running 
     * @var array
     * @since 4.0
     */
    private $notices = array();

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
     * @param String $path             The plugin path
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
    public function plugins_loaded() {
        global $wp_version;

        /**
         * Check minimum requirements are met
         */             
        
        /* WordPress version not compatible */
        if (! version_compare($wp_version, $this->required_wp_version, '>=')) {            
            $this->notices[] = sprintf(__('WordPress Version %s is required.', 'pdfextended'), $this->required_wp_version);
        } 

        /* Gravity Forms version not compatible */
        if (! class_exists('GFCommon') || ! version_compare(GFCommon::$version, $this->required_gf_version, '>=')) {            
            $this->notices[] = sprintf(__('Gravity Forms Version %s is required.', 'pdfextended'), $this->required_wp_version);
        } 

        /* Check PHP version is compatible */
        if (! version_compare(phpversion(), $this->required_php_version, '>=')) {
            $this->notices[] = sprintf(__('You are running an %soutdated version of PHP%s. Contact your web hosting provider to update.', 'pdfextended'), '<a href="http://www.wpupdatephp.com/update/">', '</a>');
        }

        /* Check MB String is installed */
        if (! extension_loaded('mbstring')) {
            $this->notices[] = __("The PHP Extension MB String (with mb-regex enabled) could not be detected. Contact your web hosting provider to fix.", 'pdfextended');
        }

        /* Check MB String is compiled with regex capabilities */
        if ( extension_loaded('mbstring') && ! extension_loaded('mbstring')) {
            $this->notices[] = __("The PHP Extension MB String does not have MB Regex enabled. Contact your web hosting provider to fix.", 'pdfextended');
        }        

        /* Check GD Image Library is installed */
        if (! extension_loaded('gd')) {
            $this->notices[] = __("The PHP Extension GD Image Library could not be detected. Contact your web hosting provider to fix.", 'pdfextended');
        }

        /* Check Minimum RAM requirements */
        $ram = $this->get_ram();
        if ($ram < 64) {
            $this->notices[] = sprintf(__("You need %s128MB%s of WP Memory (RAM) but we only found %s available. Contact your web hosting provider to fix (you need to increase your PHP 'memory_limit' setting).", 'pdfextended'), '<strong>', '</strong>', $ram . 'MB');
        }

        /* check if any errors were thrown, enqueue them and exit early */
        if (sizeof($this->notices) > 0) {
            add_action('admin_notices', array($this, 'display_notices'));
            return false;
        }

        require_once $this->path.'src/bootstrap.php';
    }
     /**
      * Get the available system memory
      * @return integer The calculated RAM
      * @since 4.0
      */
     public function get_ram() {
     	 /* get memory in standardised bytes format */
         $memory_limit = $this->convert_ini_memory(ini_get('memory_limit'));

         /* convert to megabytes, or set to -1 if unlimited */
         return ($memory_limit === '-1') ? -1 : floor($memory_limit / 1024 / 1024); 
     }    

     /**
      * Convert .ini file memory to bytes
      * @param  String The .ini memory limit
      * @return Integer The calculated memory limit in bytes
      */
     public function convert_ini_memory($memory) {
		$convert = array('mb' => 'm', 'kb' => 'k', 'gb' => 'g');

		/* standardise format */
		foreach ($convert as $k => $v) {
		 $memory = str_ireplace($k, $v, $memory);
		}

		/* check if memory allocation is in mb, kb or gb */
		switch (strtolower(substr($memory, -1))) {            
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
     * @return void
     * @since 4.0
     */
    public function display_notices() {
    	?>
		    <div class="error">
		        <p><?php _e('The minimum requirements for Gravity PDF have not been met. Please fix the issues below to continue:', 'pdfextended'); ?></p>		        
				<ul style="padding-bottom: 0.5em">
	        		<?php foreach($this->notices as $notice): ?>
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
new GFPDF_Major_Compatibility_Checks(
    GF_PDF_EXTENDED_PLUGIN_BASENAME,
    PDF_PLUGIN_DIR
);
