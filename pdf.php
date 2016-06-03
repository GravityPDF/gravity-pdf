<?php

/**
 * Plugin Name: Gravity PDF
 * Plugin URI: https://gravitypdf.com
 * Description: Gravity PDF allows you to save/view/download a PDF from the front- and back-end, and automate PDF creation on form submission. Our Business Plus package also allows you to overlay field onto an existing PDF.
 * Version: 3.7.8
 * Author: Blue Liquid Designs
 * Author URI: http://www.blueliquiddesigns.com.au
 */

/*
    This file is part of Gravity PDF.

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
 * As PDFs can't be generated if notices are displaying, turn off error reporting to the screen if not in debug mode.
 * Production servers should already have this done.
 */
 if(WP_DEBUG !== true)
 {
 	error_reporting(0);
 }

/*
 * Define our constants
 */
define('PDF_EXTENDED_VERSION', '3.7.8');
define('GF_PDF_EXTENDED_SUPPORTED_VERSION', '1.8');
define('GF_PDF_EXTENDED_WP_SUPPORTED_VERSION', '3.9');
define('GF_PDF_EXTENDED_PHP_SUPPORTED_VERSION', '5');

define('PDF_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
define('PDF_PLUGIN_URL', plugin_dir_url( __FILE__ ));
define("PDF_SETTINGS_URL", site_url() .'/wp-admin/admin.php?page=gf_settings&subview=PDF');
define('PDF_SAVE_FOLDER', 'PDF_EXTENDED_TEMPLATES');
define('GF_PDF_EXTENDED_PLUGIN_BASENAME', plugin_basename(__FILE__));

/*
 * Include the core files
 */
 include PDF_PLUGIN_DIR . 'helper/data.php';
 include PDF_PLUGIN_DIR . 'helper/notices.php';
 include PDF_PLUGIN_DIR . 'helper/pdf-configuration-indexer.php';
 include PDF_PLUGIN_DIR . 'helper/installation-update-manager.php';
 include PDF_PLUGIN_DIR . 'helper/pdf-common.php';
 include PDF_PLUGIN_DIR . 'helper/pdf-render.php';

 /*
  * Initiate the class after Gravity Forms has been loaded using the init hook.
  */
   add_action('init', array('GFPDF_Core', 'pdf_init'));
   add_action('wp_ajax_support_request', array('GFPDF_Settings_Model', 'gfpdf_support_request'));


class GFPDF_Core extends PDFGenerator
{
	public $render;

	/*
	 * Main Controller
	 * First function fired when plugin is loaded
	 * Determines if the plugin can run or not
	 */
	public static function pdf_init()
	{
		 /*
		  * Initialise our data helper class
		  */
		 global $gfpdfe_data;
		 $gfpdfe_data = new GFPDFE_DATA();

		  /* set our PDF folder storage */
		 $gfpdfe_data->set_directory_structure();

		/*
		 * Include any dependancy-based files
		 */
		 include_once PDF_PLUGIN_DIR . 'pdf-settings.php';
		 include_once PDF_PLUGIN_DIR . 'depreciated.php';
		 include_once PDF_PLUGIN_DIR . 'helper/pdf-entry-detail.php';
		 include_once PDF_PLUGIN_DIR . 'major-upgrade-checker.php';

		/*
		 * Set the notice type
		 */
		self::set_notice_type();

	   /*
	    * Add localisation support
	    */
	    load_plugin_textdomain('pdfextended', false,  dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		/*
		 * Call our Settings class which will do our compatibility processing
		 */
		$gfpdfe_data->settingsClass = new GFPDF_Settings();

		/*
		 * Only run settings page if Gravity Forms version is installed and compatible
		 * Needs to be run before major compatibility checks so it can prompt user
		 * about issues with WP version or PHP
		 */

		if($gfpdfe_data->gf_is_compatible === true && is_admin())
		{
			/*
			 * Run our settings page
			 */
			GFPDF_Settings::settings_page();

			/*
			 * Only load our scripts if on a Gravity Forms admin page
			 */
			if( isset($_GET['page']) && (substr($_GET['page'], 0, 3) === 'gf_') )
			{
				/*
				* Run our scripts and add the settings page to the admin area
				*/
				add_action('admin_init',  array('GFPDF_Core', 'gfe_admin_init'), 9);
			}
		}

		/*
		 * We'll initialise our model which will do any function checks ect
		 */
		 include PDF_PLUGIN_DIR . 'model/pdf.php';

		/*
		* Check for any major compatibility issues early
		*/
		if(GFPDF_Core_Model::check_major_compatibility() === false)
		{
			/*
			 * Major compatibility errors (WP version, Gravity Forms or PHP errors)
			 * Exit to prevent conflicts
			 */
			return;
		}

		/*
		* Some functions are required to monitor changes in the admin area
		* and ensure the plugin functions smoothly
		*/
		add_action('admin_init', array('GFPDF_Core', 'fully_loaded_admin'), 9999); /* run later than usual to give our auto initialiser a chance to fire */

		/*
		 * Only load the plugin if the following requirements are met:
		 *  - Load on Gravity Forms Admin pages
		 *  - Load if on any front-end admin page
		 *  - Load if doing AJAX request (which natively is called from the /wp-admin/ backend)
		 */
		 if( (is_admin() && isset($_GET['page']) && (substr($_GET['page'], 0, 3) === 'gf_')) ||
		 	  !is_admin() ||
		 	  defined( 'DOING_AJAX' ) && DOING_AJAX )
		 {
			/*
			 * Initialise the core class which will load the __construct() function
			 */
			global $gfpdf;
			$gfpdf = new GFPDF_Core();
		 }

		 return;

   }

	public function __construct()
	{

		/*
		 * Ensure the system is fully installed
		 * We run this after the 'settings' page has been set up (above)
		 */
		if(GFPDF_Core_Model::is_fully_installed() === false)
		{
			return;
		}

		global $gfpdfe_data;

		/*
		* Set up the PDF configuration and indexer
		* Accessed through $this->configuration and $this->index.
		*/
		parent::__construct();

		/*
		* Add our main hooks
		*/
		add_action('gform_entries_first_column_actions', array('GFPDF_Core_Model', 'pdf_link'), 10, 4);
		add_action("gform_entry_info", array('GFPDF_Core_Model', 'detail_pdf_link'), 10, 2);
		add_action('wp', array('GFPDF_Core_Model', 'process_exterior_pages'));

		/*
		* Apply default filters
		*/
		add_filter('gfpdfe_pdf_template', array('PDF_Common', 'do_mergetags'), 10, 3); /* convert mergetags in PDF template automatically */
		add_filter('gfpdfe_pdf_template', 'do_shortcode', 10, 1); /* convert shortcodes in PDF template automatically */

		/* Check if on the entries page and output javascript */
		if(is_admin() && rgget('page') == 'gf_entries')
		{
			wp_enqueue_script( 'gfpdfeentries', PDF_PLUGIN_URL . 'resources/javascript/entries-admin.min.js', array('jquery') );
		}

		/*
		* Register render class
		*/
		$this->render = new PDFRender();

		/*
		* Run the notifications filter / save action hook if the web server can write to the output folder
		*/
		if($gfpdfe_data->can_write_output_dir === true)
		{
			add_action('gform_after_submission', array('GFPDF_Core_Model', 'gfpdfe_save_pdf'), 10, 2);
			add_filter('gform_notification', array('GFPDF_Core_Model', 'gfpdfe_create_and_attach_pdf'), 100, 3);  /* ensure it's called later than standard so the attachment array isn't overridden */
		}

	}

	/*
	 * Do processes that require Wordpress Admin to be fully loaded
	 */
	 public static function fully_loaded_admin()
	 {

	 	/*
	 	 * Check user has the correct permissions to deploy the software
	 	 */
	 	if(!current_user_can( 'manage_options' ))
	 	{
	 		return;
	 	}

	 	global $gfpdfe_data;

	 	/*
	 	 * Don't run initialiser if we cannot...
	 	 */
		if($gfpdfe_data->allow_initilisation === false)
		{
			/*
			 * Prompt user about a server configuration problem
			 */
			add_action($gfpdfe_data->notice_type, array("GFPDF_Notices", "gf_pdf_server_problem_detected"));
			return false;
		}

		/*
		* Check if we have direct write access to the server
		*/
		GFPDF_InstallUpdater::check_filesystem_api();

		/*
		* Check if we can automatically deploy the software.
		* 90% of sites should be able to do this as they will have 'direct' write abilities
		* to their server files.
		*/
		GFPDF_InstallUpdater::maybe_deploy();

		/*
		* Check if we need to deploy the software
		*/
		self::check_deployment();

		/*
		* Check if the template folder location needs to be migrated
		*/
		if(!rgpost('upgrade'))
		{
			GFPDF_InstallUpdater::check_template_migration();
		}
	 }

	 /*
	  * Depending on what page we are on, we need to fire different notices
	  * We've added our own custom notice to the settings page as some functions fire later than the normal 'admin_notices' action
	  */
	 private static function set_notice_type()
	 {
	 	global $gfpdfe_data;

	 	if(PDF_Common::is_settings())
	 	{
	 		$gfpdfe_data->notice_type = 'gfpdfe_notices';
	 	}
	 	else if (is_multisite() && is_network_admin())
	 	{
	 		$gfpdfe_data->notice_type = 'network_admin_notices';
	 	}
	 	else
	 	{
	 		$gfpdfe_data->notice_type = 'admin_notices';
	 	}
	 }

	 /*
	  * Check if the software needs to be deployed/redeployed
	  */
	  public static function check_deployment()
	  {

	  		global $gfpdfe_data;

	  		/*
	  		 * Check if client is using the automated installer
	  		 * If installer has issues or client cannot use auto installer (using FTP/SSH ect) then run the usual
	  		 * initialisation messages.
	  		 */
	  		if($gfpdfe_data->automated === true && $gfpdfe_data->fresh_install === true & get_option('gfpdfe_automated_install') != 'installing')
	  		{
	  			return;
	  		}

			/*
			 * Check if GF PDF Extended is correctly installed. If not we'll run the installer.
			 */
			$theme_switch = get_option('gfpdfe_switch_theme');

			if( get_option('gf_pdf_extended_installed') != 'installed' && !rgpost('upgrade') )
			{
				/*
				 * Prompt user to initialise plugin
				 */
				 add_action($gfpdfe_data->notice_type, array("GFPDF_Notices", "gf_pdf_not_deployed_fresh"));
			}
			elseif( (
						( !is_dir($gfpdfe_data->template_site_location))  ||
						( !file_exists($gfpdfe_data->template_site_location . 'configuration.php') ) ||
						( !is_dir($gfpdfe_data->template_save_location) )
					)
					&& (!rgpost('upgrade'))
					&& (!is_dir($gfpdfe_data->old_template_location)
					&& (!is_dir($gfpdfe_data->old_3_6_template_site_location)) ) /* add in 3.6 directory change */
				  )
			{

				/*
				 * Prompt user that a problem was detected and they need to redeploy
				 */
				add_action($gfpdfe_data->notice_type, array("GFPDF_Notices", "gf_pdf_problem_detected"));
			}
	  }

	/**
	 * Add our scripts and settings page to the admin area
	 */
	public static function gfe_admin_init()
	{
		/*
		 * Configure the settings page
		 */
		  wp_enqueue_style( 'pdfextended-admin-styles', PDF_PLUGIN_URL . 'resources/css/admin-styles.min.css', array(), '1.3' );
		  wp_enqueue_script( 'pdfextended-settings-script', PDF_PLUGIN_URL . 'resources/javascript/admin.min.js', array(), '1.3' );

		  /*
		   * Localise admin script
		   */
		  $localise_script = array(
		  	'GFbaseUrl' => GFCommon::get_base_url(),
		  );

		  wp_localize_script( 'pdfextended-settings-script', 'GFPDF', $localise_script );

		 /*
		  * Register our scripts/styles with Gravity Forms to prevent them being removed in no conflict mode
		  */
		  add_filter('gform_noconflict_scripts', array('GFPDF_Core', 'register_gravityform_scripts'));
		  add_filter('gform_noconflict_styles', array('GFPDF_Core', 'register_gravityform_styles'));

		  add_filter('gform_tooltips', array('GFPDF_Notices', 'add_tooltips'));

	}

	/*
	 * Register our scripts with Gravity Forms so they aren't removed when no conflict mode is active
	 */
	public static function register_gravityform_scripts($scripts)
	{
		$scripts[] = 'pdfextended-settings-script';
		$scripts[] = 'gfpdfeentries';

		return $scripts;
	}

	/*
	 * Register our styles with Gravity Forms so they aren't removed when no conflict mode is active
	 */
	public static function register_gravityform_styles($styles)
	{
		$styles[] = 'pdfextended-admin-styles';

		return $styles;
	}

}

/*
 * array_replace_recursive was added in PHP5.3
 * Add fallback support for those with a version lower than this
 * as Wordpress still supports PHP5.0 to PHP5.2
 */
if (!function_exists('array_replace_recursive'))
{
    function array_replace_recursive($base, $replacements)
    {
        foreach (array_slice(func_get_args(), 1) as $replacements) {
            $bref_stack = array(&$base);
            $head_stack = array($replacements);

            do {
                end($bref_stack);

                $bref = &$bref_stack[key($bref_stack)];
                $head = array_pop($head_stack);

                unset($bref_stack[key($bref_stack)]);

                foreach (array_keys($head) as $key) {
                    if (isset($key, $bref) && isset($bref[$key]) && is_array($bref[$key]) && is_array($head[$key])) {
                        $bref_stack[] = &$bref[$key];
                        $head_stack[] = $head[$key];
                    } else {
                        $bref[$key] = $head[$key];
                    }
                }
            } while(count($head_stack));
        }

        return $base;
    }
}
