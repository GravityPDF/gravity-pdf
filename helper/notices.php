<?php

/**
 * Plugin: Gravity Forms PDF Extended
 * File: notices.php
 * 
 * This file handles the output of all notices to the admin area 
 */

class GFPDF_Notices 
{
	/*
	 * Admin notice template 
	 */
	private static function message($text, $type = 'updated')
	{
		?>
		<div id="message" class="<?php echo $type; ?>">
			<p>
				<?php echo $text; ?>
			</p>
		</div>
		<?php
	}

	/*
	 * Output the update notice 
	 */
	private static function notice($text)
	{
		self::message($text);
	}	

	/*
	 * Output the error notice
	 */
	private static function error($text)
	{
		self::message($text, 'error');
	}	

	private static function display_plugin_message($message, $is_error = false){

        $style = $is_error ? 'style="background-color: #ffebe8;"' : "";

        echo '</tr><tr class="plugin-update-tr"><td colspan="5" class="plugin-update"><div class="update-message" ' . $style . '>' . $message . '</div></td>';
    }	

	private static function autoprefix()
	{
		global $gfpdfe_data;
		if($gfpdfe_data->automated === true && !rgpost('upgrade') && !rgpost('font-initialise'))
		{
			return sprintf(__('%sGravity Forms PDF Extended Automated Installer%s: ', 'pdfextended'), '<strong>', '</strong>');
		}
		return '';
	}

	private static function autosuffix()
	{
		global $gfpdfe_data;
		if($gfpdfe_data->automated === true && !rgpost('upgrade') && !rgpost('font-initialise'))
		{
			return sprintf(__(' %sGo to installer%s.', 'pdfextended'), '<a href="'. PDF_SETTINGS_URL .'">', '</a>');
		}
		return '';
	}

	public static function gf_pdf_font_install_success()
	{
		$prefix = self::autoprefix();
		$suffix = self::autosuffix();
		
		$msg = $prefix . __('The font files have been successfully installed. A font can be used by adding its file name (without .ttf and in lower case) in a CSS font-family declaration.', 'pdfextended') . $suffix;

		self::notice($msg);
		
	}	

	public static function gf_pdf_font_err()
	{
		$prefix = self::autoprefix();
		$suffix = self::autosuffix();
	
		$msg =  $prefix . __('There was a problem installing the font files. Check the file permissions in the plugin folder and try again.', 'pdfextended') . $suffix;

		self::error($msg);
		
	}	
	
	public static function gf_pdf_font_config_err()
	{
		$prefix = self::autoprefix();
		$suffix = self::autosuffix();
		
		$msg =  $prefix . __('Could not create font configuration file. Try initialise again.', 'pdfextended') . $suffix;

		self::error($msg);
		
	}		

	/**
	 * The software has detected a problem (no configuration.php file or no PDF_EXTENDED_TEMPLATE folder
	 * The user will need to reinitialise
	 */
	public static function gf_pdf_problem_detected()
	{
		if( !rgpost('update') )
		{
			if(rgget("page") == 'gf_settings' && rgget('subview') == 'PDF')
			{	
				$msg =  __('Gravity Forms PDF Extended detected a configuration problem. Please reinitialise the plugin.', 'pdfextended');
			}
			else
			{	
				$msg =  sprintf(__('Gravity Forms PDF Extended detected a configuration problem. Please go to the %splugin\'s settings page%s to reinitialise.', 'pdfextended'), '<a href="'.PDF_SETTINGS_URL.'">', '</a>');	
			}

			self::error($msg);
		}
	}

	/**
	 * PDF Extended has been freshly installed
	 */
	public static function gf_pdf_not_deployed_fresh()
	{		
		if( !rgpost('update') )
		{
			if(rgget("page") == 'gf_settings' && rgget('subview') == 'PDF')
			{	
				$msg =  __('Welcome to Gravity Forms PDF Extended. Before you can use the plugin correctly you need to initilise it.', 'pdfextended');
			}
			else
			{
				$msg =  sprintf(__('Welcome to Gravity Forms PDF Extended. Before you can use the plugin correctly you need to initilise it. Please go to the %splugin\'s settings page%s to initialise.', 'pdfextended'), '<a href="'.PDF_SETTINGS_URL.'">', '</a>');
			}

			self::notice($msg);
		}
	}	
	
	/**
	 * The Gravity Forms version isn't compatible. Prompt user to upgrade
	 */
	public static function gf_pdf_not_supported()
	{
		$msg =  sprintf(__('Gravity Forms PDF Extended only works with Gravity Forms version %s and higher. Please %supgrade your copy of Gravity Forms%s to use this plugin.', 'pdfextended'), GF_PDF_EXTENDED_SUPPORTED_VERSION, '<a href="https://www.e-junkie.com/ecom/gb.php?cl=54585&c=ib&aff=235154" target="ejejcsingle">', '</a>');		

		self::error($msg);
	}
								
	
	/**
	 * Cannot create new template folder in active theme directory
	 */
	public static function gf_pdf_template_dir_err()
	{
		$prefix = self::autoprefix();
		$suffix = self::autosuffix();

		$msg = $prefix . __('We could not create a template folder in your active theme\'s directory. Please ensure your active theme directory is writable by your web server and try again.', 'pdfextended')  . $suffix;

		self::error($msg);
			
			
	}
	
	/**
	 * Cannot remove old default template files
	 */
	public static function gf_pdf_deployment_unlink_error()
	{
		$prefix = self::autoprefix();
		$suffix = self::autosuffix();
			
		$msg = $prefix . sprintf(__('We could not remove the default template files from the Gravity Forms PDF Extended folder in your active theme\'s directory. Please ensure %s is wriable by your web server and try again.', 'pdfextended'), PDF_SAVE_LOCATION) . $suffix;			
		
		self::error($msg);			
	}		
	
	/**
	 * Cannot create new template folder in active theme directory
	 */
	public static function gf_pdf_template_move_err()
	{
		$prefix = self::autoprefix();
		$suffix = self::autosuffix();
	
		$msg = $prefix . sprintf(__('We could not move the template files to the PDF_EXTENDED_TEMPLATES folder.  Please ensure %s is wriable by your web server and try again.', 'pdfextended'), PDF_SAVE_LOCATION) . $suffix;
			
		self::error($msg);
	}

	/*
	 * Prompt user to keep the plugin working
	 */
	public static function do_theme_switch_notice()
	{		
		/*
		 * Check we aren't in the middle of doing the sync
		 */
		 if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'gfpdfe_sync_now'))
		 {
			return; 
		 } 
			
		$msg = sprintf(__('Gravity Forms PDF Extended needs to keep your configuration and templates folder in sync with your current active theme. %sSync Now%s', 'pdfextended'), '<a href="'. wp_nonce_url(PDF_SETTINGS_URL, 'gfpdfe_sync_now') . '" class="button">', '</a>');

		self::notice($msg);
					 
	}
	
	public static function gf_pdf_theme_sync_success()
	{
		global $gfpdfe_data;
		$prefix = ($gfpdfe_data->automated === true && !rgpost('upgrade')) ? sprintf(__('%sGravity Forms PDF Extended Automated Theme Sync%s: ', 'pdfextended'), '<strong>', '</strong>') : '';		
		
		$msg = $prefix . __('Your PDF configuration and template folder was successfully synced to your new theme.', 'pdfextended');

		self::notice($msg);
						
	}

	public static function gf_pdf_network_deploy_success()
	{		
		global $gfpdfe_data;

		if($gfpdfe_data->automated === true && !rgpost('upgrade') && !rgpost('font-initialise'))
		{
			$msg = __('Gravity Forms PDF Extended Auto Initialisation Complete across the entire network.', 'pdfextended');
		}
		else
		{
			$msg = __('Gravity Forms PDF Extended Initialisation Complete across the entire network.', 'pdfextended');
		}

		if(get_option('gf_pdf_extended_installed') != 'installed')
		{
			$msg .= ' ' . sprintf( __('%sLearn how to configuring the plugin%s.', 'pdfextended'), '<a href="'. PDF_SETTINGS_URL .'">', '</a>');
		}
		self::notice($msg);				
	}	

	public static function gf_pdf_auto_deploy_success()
	{		
		global $gfpdfe_data;
		$msg = __('Gravity Forms PDF Extended Auto Initialisation Complete.', 'pdfextended');

		if(get_option('gf_pdf_extended_installed') != 'installed')
		{
			$msg .= ' ' . sprintf( __('%sLearn how to configuring the plugin%s.', 'pdfextended'), '<a href="'. PDF_SETTINGS_URL .'">', '</a>');
		}
		self::notice($msg);				
	}

	public static function gf_pdf_auto_deploy_network_failure()
	{		
		global $gfpdfe_data;

		$prefix = self::autoprefix();		
		$errors = (array) $gfpdfe_data->network_error;
		
		if(sizeof($errors) > 0)
		{
			$msg = $prefix . __('There was a network initialisation issue on the following sites;', 'pdfextended');
			$msg .= '<ul>';

			$base_site_url = site_url();
			foreach($errors as $site)
			{
				switch_to_blog( (int) $site['blog_id'] );
				$url = str_replace($base_site_url, site_url(), PDF_SETTINGS_URL );
				$msg .= "<li><a href='$url'>{$site['domain']}{$site['path']}</a></li>";
				restore_current_blog();
			}
			$msg .= '</ul>';

			$msg .= __('Please try manually initialise the software', 'pdfextended');
		}
		else
		{
			$msg = $prefix . __('An unknown network initialisation error occured. Please try initialise again.', 'pdfextended');
		}	

		self::error($msg);				
	}

	public static function gf_pdf_deploy_success() {		
		$msg = __('You\'ve successfully initialised Gravity Forms PDF Extended.', 'pdfextended');

		self::notice($msg);		
	}

	public static function display_compatibility_error()
	{
		 $message = sprintf(__("Gravity Forms " . GF_PDF_EXTENDED_SUPPORTED_VERSION . " is required to use this plugin. Activate it now or %spurchase it today!%s", 'pdfextended'), "<a href='https://www.e-junkie.com/ecom/gb.php?cl=54585&c=ib&aff=235154'>", "</a>"); 
		 self::display_plugin_message($message, true);			
	}
	
	public static function display_wp_compatibility_error()
	{
		 $message = __("Wordpress " . GF_PDF_EXTENDED_WP_SUPPORTED_VERSION . " or higher is required to use this plugin.", 'pdfextended'); 
		 self::display_plugin_message($message, true);			
	}	
	
	/*public static function display_documentation_details()
	{
		 $message = sprintf(__("Please review the %sGravity Forms PDF Extended documentation%s for comprehensive installation instructions.%s", 'pdfextended'), "<a href='http://gravityformspdfextended.com/documentation-v3-x-x/installation-and-configuration/'>", "</a>", '</span>'); 
		 PDF_Common::display_plugin_message($message);						
	}*/	
	
	public static function display_pdf_compatibility_error()
	{
		 $message = __("PHP " . GF_PDF_EXTENDED_PHP_SUPPORTED_VERSION . " or higher is required to use this plugin.", 'pdfextended'); 
		 self::display_plugin_message($message, true);			
	}	

	public static function add_tooltips($tooltips)
	{
		$tooltips['pdf_overwrite'] = sprintf(__('If you reinitialise and enable this option %syou will overwrite%s the default and example template files in your active theme directory.', 'pdfextended'), '<strong>', '</strong>');

		return $tooltips;
	}					
}