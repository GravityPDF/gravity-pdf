<?php

/**
 * Plugin: Gravity Forms PDF Extended
 * File: install-update-manager.php
 * 
 * This file handles the installation and update code that ensures the plugin will be supported.
 */

/**
 * Check to see if Gravity Forms version is supported
 */
 
class GFPDF_InstallUpdater
{

	/*
	 * Will control if the error notices need to reflect an automated install, or a static 'initialise' install
	 */
	public static $automated = false;

	public static function check_filesystem_api()
	{
		$access_type = get_filesystem_method();

		if($access_type === 'direct')
		{
			self::$automated = true;
		}		
	}
	
	/*
	 * Check if we can automatically deploy the software 
	 * We use WP Filesystem API to initialise. 
	 * Check if we have direct write control to the filesystem. If so, automatically deploy 
	 * without asking the user. This will make upgrades much simplier.	 
	 */
	public static function maybe_deploy()
	{
		/*
		 * Check if we have a 'direct' method, that the software isn't fully installed and we aren't trying to manually initialise
		 */
		if(self::$automated === true && GFPDF_Core_Model::is_fully_installed() === false && !rgpost('upgrade') && get_option('gfpdfe_automated_install') != 'installing')
		{
			/*
			 * Initialise all multisites if a super admin is logged in
			 */
			if(is_multisite() && is_super_admin())
			{
				self::run_multisite_deployment();
			}
			else
			{
				if(self::do_deploy())
				{
					/*
					 * Output successfull automated installation message 
					 */
					$notice_type = (PDF_Common::is_settings()) ? 'gfpdfe_notices' : 'admin_notices';
					add_action($notice_type, array('GFPDF_InstallUpdater', 'gf_pdf_auto_deploy_success'));					
				}
			}
		}
	}

	public static function run_multisite_deployment()
	{
		global $gfpdfe_data;

		/* add additional check incase someone doesn't call this correctly */
		if(!is_multisite())
			return false;

		/*
		 * Don't do anything if over 10,000 sites 
		 */
		if(!wp_is_large_network())
		{
			/*
			 * Get multisites which aren't deleted 
			 */
			$sites = wp_get_sites(array('deleted' => 0));

			$success = true;
			$problem = array();
			foreach($sites as $site)
			{
				 switch_to_blog( (int) $site['blog_id'] );

				 /*
				  * Test if the blog has gravity forms and PDF Extended active
				  * If so, we can initialise 
				  */				 
				 $gravityforms = 'gravityforms/gravityforms.php'; /* have to hardcode the folder name is they don't set it in a constant or variable */
				 $pdfextended = GF_PDF_EXTENDED_PLUGIN_BASENAME; /* no need to hardcode the basename here */

				 if( (is_plugin_active_for_network($gravityforms) && is_plugin_active_for_network($pdfextended)) ||
				 	 (is_plugin_active($gravityforms) && is_plugin_active($pdfextended))
				 	)
				 {
				 	/* run our deployment and output any problems */
				 	if(!self::do_deploy())
				 	{
				 		$success = false;
				 		$problem[] = $site;
				 	}
				 }
				 restore_current_blog();
			}

			if(!$success)
			{	
					$gfpdfe_data->network_error = $problem;
					$notice_type = (PDF_Common::is_settings()) ? 'gfpdfe_notices' : (is_network_admin()) ? 'network_admin_notices' : 'admin_notices';
					add_action($notice_type, array('GFPDF_InstallUpdater', 'gf_pdf_auto_deploy_network_failure'));		
			}
			else
			{
					$notice_type = (PDF_Common::is_settings()) ? 'gfpdfe_notices' : (is_network_admin()) ? 'network_admin_notices' : 'admin_notices';
					add_action($notice_type, array('GFPDF_InstallUpdater', 'gf_pdf_auto_deploy_success'));			
			}

		}
	}

	private static function do_deploy()
	{
		update_option('gfpdfe_automated_install', 'installing');
		if(self::pdf_extended_activate())
		{
			return true;
		}		
		return false;
	}

	private static function get_base_dir($path)
	{
		global $wp_filesystem;
		return str_replace(ABSPATH, $wp_filesystem->abspath(), $path);
	}

	/**
	 * Install everything required
	 */
	public static function pdf_extended_activate()
	{			
	    /*
		 * Initialise the Wordpress Filesystem API
		 */
		if(PDF_Common::initialise_WP_filesystem_API(array('gfpdf_deploy'), 'pdf-extended-filesystem') === false)
		{
			return 'false';	
		}	

		$notice_type = (PDF_Common::is_settings()) ? 'gfpdfe_notices' : 'admin_notices';
		
		/*
		 * If we got here we should have $wp_filesystem available
		 */
		global $wp_filesystem, $gfpdfe_data;	
		
		/*
		 * Set the correct paths 
		 * FTP and SSH could be rooted to the wordpress base directory 
		 * use $wp_filesystem->abspath(); function to fix any issues
		 */
		$directory               = self::get_base_dir(PDF_PLUGIN_DIR);
		$template_directory      = self::get_base_dir(PDF_TEMPLATE_LOCATION);
		$template_save_directory = self::get_base_dir(PDF_SAVE_LOCATION);
		$template_font_directory = self::get_base_dir(PDF_FONT_LOCATION);		

		/**
		 * If PDF_TEMPLATE_LOCATION already exists then we will remove the old template files so we can redeploy the new ones
		 */
		 if($wp_filesystem->exists($template_directory) && PDF_DEPLOY === true)
		 {
			 /*
			  * Create a backup folder and move all the files there
			  */
			  $backup_folder = 'INIT_BACKUP_' . date('Y-m-d_G-i') . '/';
			  $do_backup = false;
			  if($wp_filesystem->mkdir($template_directory . $backup_folder ))
			  {
					$do_backup = true;  
			  }
			  
			 
			 /* read all file names into array and unlink from active theme template folder */
			 foreach(glob($directory.'initialisation/templates/*') as $file) {
				 	$path_parts = pathinfo($file);					
						if($wp_filesystem->exists($template_directory.$path_parts['basename']))
						{
							if(!$do_backup)
							{
								$wp_filesystem->delete($template_directory.$path_parts['basename']);
								continue;		
							}
							$wp_filesystem->move($template_directory.$path_parts['basename'], $template_directory . $backup_folder . $path_parts['basename']);
						}
			 }			
		 }
		 

		/* unzip the mPDF file */
		if($wp_filesystem->exists($directory . 'mPDF.zip'))
		{
			/*
			 *	unzip_file() is only function in the file-manipulators that requires the absolute 'direct' path and 
			 *	the 'save' directory to be relative to the method in the $wp_filesystem
			 */
			$results = unzip_file( PDF_PLUGIN_DIR . 'mPDF.zip', $directory );
		
			if($results !== true)
			{						
				add_action($notice_type, array('GFPDF_InstallUpdater', 'gf_pdf_unzip_mpdf_err')); 	
				return false;				
			}			

			/*
			 * Remove the original archive
			 */
			 $wp_filesystem->delete($directory . 'mPDF.zip');
		}	

		/* create new directory in active themes folder*/	
		if(!$wp_filesystem->is_dir($template_directory))
		{
			if($wp_filesystem->mkdir($template_directory) === false)
			{
				add_action($notice_type, array('GFPDF_InstallUpdater', 'gf_pdf_template_dir_err')); 	
				return false;
			}
		}
	
		if(!$wp_filesystem->is_dir($template_save_directory))
		{
			/* create new directory in active themes folder*/	
			if($wp_filesystem->mkdir($template_save_directory) === false)
			{
				add_action($notice_type, array('GFPDF_InstallUpdater', 'gf_pdf_template_dir_err')); 	
				return false;
			}
		}
		
		if(!$wp_filesystem->is_dir($template_font_directory))
		{
			/* create new directory in active themes folder*/	
			if($wp_filesystem->mkdir($template_font_directory) === false)
			{
				add_action($notice_type, array('GFPDF_InstallUpdater', 'gf_pdf_template_dir_err')); 	
				return false;
			}
		}	
		
		/*
		 * Copy entire template folder over to PDF_TEMPLATE_LOCATION
		 */
		 self::pdf_extended_copy_directory( $directory . 'initialisation/templates', $template_directory, false );

		if(!$wp_filesystem->exists($template_directory .'configuration.php'))
		{ 
			/* copy template files to new directory */
			if(!$wp_filesystem->copy($directory .'initialisation/configuration.php.example', $template_directory.'configuration.php'))
			{ 
				add_action($notice_type, array('GFPDF_InstallUpdater', 'gf_pdf_template_dir_err')); 	
				return false;
			}
		}
		
		if(!$wp_filesystem->exists($template_save_directory.'.htaccess'))
		{		
			if(!$wp_filesystem->put_contents($template_save_directory.'.htaccess', 'deny from all'))
			{
				add_action($notice_type, array('GFPDF_InstallUpdater', 'gf_pdf_template_dir_err')); 	
				return false;
			}	
		}	

		if(self::install_fonts($directory, $template_directory, $template_font_directory) !== true)
		{
			return false;
		}				 
		
		/* 
		 * Update system to ensure everything is installed correctly.
		 */

		update_option('gf_pdf_extended_installed', 'installed');			
		update_option('gf_pdf_extended_deploy', 'yes');
		update_option('gf_pdf_extended_version', PDF_EXTENDED_VERSION);
		delete_option('gfpdfe_switch_theme');
		delete_option('gfpdfe_automated_install');
		
		return true;	
	}
	
	public static function initialise_fonts()
	{
	    /*
		 * Initialise the Wordpress Filesystem API
		 */
		if(PDF_Common::initialise_WP_filesystem_API(array('gfpdf_deploy'), 'pdf-extended-fonts') === false)
		{
			return false;	
		}	
		
		/*
		 * If we got here we should have $wp_filesystem available
		 */
		global $wp_filesystem;

		/*
		 * Set out notice type 
		 */
		$notice_type = (PDF_Common::is_settings()) ? 'gfpdfe_notices' : 'admin_notices';			
		
		/*
		 * We need to set up some filesystem compatibility checkes to work with the different server file management types
		 * Most notably is the FTP options, but SSH may be effected too
		 */
		$directory               = self::get_base_dir(PDF_PLUGIN_DIR);
		$template_directory      = self::get_base_dir(PDF_TEMPLATE_LOCATION);
		$template_font_directory = self::get_base_dir(PDF_FONT_LOCATION);
		
		if(self::install_fonts($directory, $template_directory, $template_font_directory) === true)
		{
			add_action($notice_type, array('GFPDF_InstallUpdater', 'gf_pdf_font_install_success')); 
		}		
		return true;
	}
	
	private static function install_fonts($directory, $template_directory, $fonts_location)
	{

		global $wp_filesystem;	
		$write_to_file = '<?php 
		
			if(!defined("PDF_EXTENDED_VERSION"))
			{
				return;	
			}
		
		';
		
		/*
		 * Search the font folder for .ttf files. If found, move them to the mPDF font folder 
		 * and write the configuration file
		 */

		 /* read all file names into array and unlink from active theme template folder */
		 foreach(glob($fonts_location.'/*.[tT][tT][fF]') as $file) {

			 	$path_parts = pathinfo($file);	
				
				/*
				 * Check if the files already exist in the mPDF font folder
				 */					
				 if(!$wp_filesystem->exists($directory . 'mPDF/ttfonts/' . $path_parts['basename']))
				 {
					/*
					 * copy ttf file to the mPDF font folder
					 */
					if($wp_filesystem->copy($file, $directory . 'mPDF/ttfonts/' . $path_parts['basename']) === false)
					{ 
						add_action($notice_type, array('GFPDF_InstallUpdater', 'gf_pdf_font_err')); 	
						return false;
					}	
				 }
				
				/*
				 * Generate configuration information in preparation to write to file
				 */ 							
				$write_to_file .= '
					$this->fontdata[\''.strtolower($path_parts['filename']).'\'] = array(
								\'R\' => \''.$path_parts['basename'].'\'
					);';
					
		 }					 

		 /*
		  * Remove the old configuration file and put the contents of $write_to_file in a font configuration file
		  */
		  $wp_filesystem->delete($template_directory.'fonts/config.php');
		  if($wp_filesystem->put_contents($template_directory.'fonts/config.php', $write_to_file) === false)
		  {
			  	add_action($notice_type, array('GFPDF_InstallUpdater', 'gf_pdf_font_config_err')); 	
				return false;  
		  }			
		 
		 return true;
	}
	
	public static function gf_pdf_font_install_success()
	{
		$preface = (self::$automated === true && !rgpost('upgrade') && !rgpost('font-initialise')) ? sprintf(__('%sGravity Forms PDF Extended Automated Installer%s: ', 'pdfextended'), '<strong>', '</strong>') : '';
		$suffix = (self::$automated === true && !rgpost('upgrade') && !rgpost('font-initialise')) ? sprintf(__(' %sGo to installer%s.', 'pdfextended'), '<a href="'. PDF_SETTINGS_URL .'">', '</a>') : '';

		echo '<div id="message" class="updated"><p>';
		echo $preface . __('The font files have been successfully installed. A font can be used by adding its file name (without .ttf and in lower case) in a CSS font-family declaration.', 'pdfextended') . $suffix;
		echo '</p></div>';
	}	

	public static function gf_pdf_font_err()
	{
		$preface = (self::$automated === true && !rgpost('upgrade') && !rgpost('font-initialise')) ? sprintf(__('%sGravity Forms PDF Extended Automated Installer%s: ', 'pdfextended'), '<strong>', '</strong>') : '';
		$suffix = (self::$automated === true && !rgpost('upgrade') && !rgpost('font-initialise')) ? sprintf(__(' %sGo to installer%s.', 'pdfextended'), '<a href="'. PDF_SETTINGS_URL .'">', '</a>') : '';

		echo '<div id="message" class="error"><p>';
		echo $preface . __('There was a problem installing the font files. Check the file permissions in the plugin folder and try again.', 'pdfextended') . $suffix;
		echo '</p></div>';
	}	
	
	public static function gf_pdf_font_config_err()
	{
		$preface = (self::$automated === true && !rgpost('upgrade') && !rgpost('font-initialise')) ? sprintf(__('%sGravity Forms PDF Extended Automated Installer%s: ', 'pdfextended'), '<strong>', '</strong>') : '';
		$suffix = (self::$automated === true && !rgpost('upgrade') && !rgpost('font-initialise')) ? sprintf(__(' %sGo to installer%s.', 'pdfextended'), '<a href="'. PDF_SETTINGS_URL .'">', '</a>') : '';

		echo '<div id="message" class="error"><p>';
		echo $preface . __('Could not create font configuration file. Try initialise again.', 'pdfextended') . $suffix;
		echo '</p></div>';
	}		
	
	/**
	 * Gravity Forms hasn't been installed so throw error.
	 * We make sure the user hasn't already dismissed the error
	 */
	public static function gf_pdf_not_installed()
	{
		echo '<div id="message" class="error"><p>';
		echo sprintf(__('You need to install/update %sGravity Forms%s to use the Gravity Forms PDF Extended Plugin.', 'pdfextended'), '<a href="https://www.e-junkie.com/ecom/gb.php?cl=54585&c=ib&aff=235154" target="ejejcsingle">', '</a>');
		echo '</p></div>';
	}
	
	/**
	 * PDF Extended has been updated but the new template files haven't been deployed yet
	 */
	public static function gf_pdf_not_deployed()
	{		
		if( !rgpost('update') )
		{
			if(rgget("page") == 'gf_settings' && rgget('subview') == 'PDF')
			{
				echo '<div id="message" class="error"><p>';
				echo __('You\'ve updated Gravity Forms PDF Extended but are yet to re-initialise the plugin. Please use the "Initialise Plugin" button below to complete the upgrade.', 'pdfextended');
				echo '</p></div>';
				
			}
			else
			{
				echo '<div id="message" class="error"><p>';
				echo sprintf(__('You\'ve updated Gravity Forms PDF Extended but are yet to re-initialise the plugin. Please go to the %splugin\'s settings page%s to initialise.', 'pdfextended'), '<a href="'.PDF_SETTINGS_URL.'">', '</a>');
				echo '</p></div>';
			}
		}
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
				echo '<div id="message" class="error"><p>';
				echo __('Gravity Forms PDF Extended detected a configuration problem. Please re-initialise the plugin.', 'pdfextended');
				echo '</p></div>';

			}
			else
			{
				echo '<div id="message" class="error"><p>';
				echo sprintf(__('Gravity Forms PDF Extended detected a configuration problem. Please go to the %splugin\'s settings page%s to re-initialise.', 'pdfextended'), '<a href="'.PDF_SETTINGS_URL.'">', '</a>');
				echo '</p></div>';
			}
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
				echo '<div id="message" class="updated"><p>';
				echo __('Welcome to Gravity Forms PDF Extended. Before you can use the plugin correctly you need to initilise it.', 'pdfextended');
				echo '</p></div>';
				
			}
			else
			{
				echo '<div id="message" class="updated"><p>';
				echo sprintf(__('Welcome to Gravity Forms PDF Extended. Before you can use the plugin correctly you need to initilise it. Please go to the %splugin\'s settings page%s to initialise.', 'pdfextended'), '<a href="'.PDF_SETTINGS_URL.'">', '</a>');
				echo '</p></div>';
			}
		}
	}	
	
	/**
	 * The Gravity Forms version isn't compatible. Prompt user to upgrade
	 */
	public static function gf_pdf_not_supported()
	{
			echo '<div id="message" class="error"><p>';
			echo sprintf(__('Gravity Forms PDF Extended only works with Gravity Forms version %s and higher. Please %supgrade your copy of Gravity Forms%s to use this plugin.', 'pdfextended'), GF_PDF_EXTENDED_SUPPORTED_VERSION, '<a href="https://www.e-junkie.com/ecom/gb.php?cl=54585&c=ib&aff=235154" target="ejejcsingle">', '</a>');
			echo '</p></div>';	
	}
								
	
	/**
	 * Cannot create new template folder in active theme directory
	 */
	public static function gf_pdf_template_dir_err()
	{
			$preface = (self::$automated === true && !rgpost('upgrade')) ? sprintf(__('%sGravity Forms PDF Extended Automated Installer%s: ', 'pdfextended'), '<strong>', '</strong>') : '';
			$suffix = (self::$automated === true && !rgpost('upgrade')) ? sprintf(__(' %sGo to installer%s.', 'pdfextended'), '<a href="'. PDF_SETTINGS_URL .'">', '</a>') : '';

			echo '<div id="message" class="error"><p>';
			echo $preface . __('We could not create a template folder in your active theme\'s directory. Please ensure your active theme directory is writable by your web server and try again.', 'pdfextended')  . $suffix;
			echo '</p></div>';
			
	}
	
	public static function gf_pdf_unzip_mpdf_err()
	{
			$preface = (self::$automated === true && !rgpost('upgrade')) ? sprintf(__('%sGravity Forms PDF Extended Automated Installer%s: ', 'pdfextended'), '<strong>', '</strong>') : '';
			$suffix = (self::$automated === true && !rgpost('upgrade')) ? sprintf(__(' %sGo to installer%s.', 'pdfextended'), '<a href="'. PDF_SETTINGS_URL .'">', '</a>') : '';

			echo '<div id="message" class="error"><p>';
			echo $preface . __('Could not unzip mPDF.zip (located in the plugin folder). Try initialise the plugin again.', 'pdfextended')  . $suffix;
			echo '</p></div>';		
	}
	
	/**
	 * Cannot remove old default template files
	 */
	public static function gf_pdf_deployment_unlink_error()
	{
			$preface = (self::$automated === true && !rgpost('upgrade')) ? sprintf(__('%sGravity Forms PDF Extended Automated Installer%s: ', 'pdfextended'), '<strong>', '</strong>') : '';
			$suffix = (self::$automated === true && !rgpost('upgrade')) ? sprintf(__(' %sGo to installer%s.', 'pdfextended'), '<a href="'. PDF_SETTINGS_URL .'">', '</a>') : '';

			echo '<div id="message" class="error"><p>';
			echo $preface . sprintf(__('We could not remove the default template files from the Gravity Forms PDF Extended folder in your active theme\'s directory. Please ensure %s is wriable by your web server and try again.', 'pdfextended'), PDF_SAVE_LOCATION) . $suffix;			
			echo '</p></div>';
	
	}		
	
	/**
	 * Cannot create new template folder in active theme directory
	 */
	public static function gf_pdf_template_move_err()
	{
			$preface = (self::$automated === true && !rgpost('upgrade')) ? sprintf(__('%sGravity Forms PDF Extended Automated Installer%s: ', 'pdfextended'), '<strong>', '</strong>') : '';
			$suffix = (self::$automated === true && !rgpost('upgrade')) ? sprintf(__(' %sGo to installer%s.', 'pdfextended'), '<a href="'. PDF_SETTINGS_URL .'">', '</a>') : '';

			echo '<div id="message" class="error"><p>';
			echo $preface . sprintf(__('We could not move the template files to the PDF_EXTENDED_TEMPLATES folder.  Please ensure %s is wriable by your web server and try again.', 'pdfextended'), PDF_SAVE_LOCATION) . $suffix;
			echo '</p></div>';
	
	}
	
	/*
	 * When switching themes copy over current active theme's PDF_EXTENDED_TEMPLATES (if it exists) to new theme folder
	 */
	public static function gf_pdf_on_switch_theme($old_theme_name, $old_theme_object) {
		
		/*
		 * We will store the old pdf dir and new pdf directory and prompt the user to copy the PDF_EXTENDED_TEMPLATES folder
		 */		
		 	 $previous_theme_directory = $old_theme_object->get_stylesheet_directory();
		 			 			
			 $current_theme_array = wp_get_theme(); 
			 $current_theme_directory = $current_theme_array->get_stylesheet_directory();

			 /*
			  * Add the save folder name to the end of the paths
			  */ 
			 $old_pdf_path = $previous_theme_directory . '/' . PDF_SAVE_FOLDER;
			 $new_pdf_path = $current_theme_directory . '/' . PDF_SAVE_FOLDER;		 
		 	
			 update_option('gfpdfe_switch_theme', array('old' => $old_pdf_path, 'new' => $new_pdf_path));

			 /* add action to check if we can auto sync */
			 /* filesystem API isn't avaliable during this action (too early) */
			 add_action('admin_init', array('GFPDF_InstallUpdater', 'maybe_autosync'));
	}

	public static function maybe_autosync()
	{
		self::check_filesystem_api();

		if(self::$automated === true)
		{	
			$theme_switch = get_option('gfpdfe_switch_theme');
			self::do_theme_switch($theme_switch['old'], $theme_switch['new']);
		}		
	}
	
	/*
	 * Check if a theme switch has been made recently 
	 * If it has then prompt the user to move the files
	 */
	public static function check_theme_switch()
	{
		$theme_switch = get_option('gfpdfe_switch_theme');

		if(isset($theme_switch['old']) && isset($theme_switch['new']))
		{
			/*
			 * Add admin notification hook to move the files
			 */	
			 add_action('admin_notices', array('GFPDF_InstallUpdater', 'do_theme_switch_notice')); 	
			 return true;
		}
		return false;		
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
		 
			echo '<div id="message" class="error"><p>';
			echo sprintf(__('Gravity Forms PDF Extended needs to keep your configuration and templates folder in sync with your current active theme. %sSync Now%s', 'pdfextended'), '<a href="'. wp_nonce_url(PDF_SETTINGS_URL, 'gfpdfe_sync_now') . '" class="button">', '</a>');
			echo '</p></div>';		
		 
	}
	
	public static function gf_pdf_theme_sync_success()
	{
			$preface = (self::$automated === true && !rgpost('upgrade')) ? sprintf(__('%sGravity Forms PDF Extended Automated Theme Sync%s: ', 'pdfextended'), '<strong>', '</strong>') : '';		
			echo '<div id="message" class="updated"><p>';
			echo $preface . __('Your PDF configuration and template folder was successfully synced to your new theme.', 'pdfextended');
			echo '</p></div>';			
	}

	public static function gf_pdf_auto_deploy_success()
	{		
			$multisite_msg = (is_multisite() && self::$automated === true && !rgpost('upgrade') && !rgpost('font-initialise')) ? __(' across entire network', 'pdfextended') : '';
			$msg = sprintf(__('Gravity Forms PDF Extended Auto Initialisation Complete%s.', 'pdfextended'), $multisite_msg);
			if(get_option('gf_pdf_extended_installed') != 'installed')
			{
				$msg .= ' ' . sprintf( __('%sLearn how to configuring the plugin%s.', 'pdfextended'), '<a href="'. PDF_SETTINGS_URL .'">', '</a>');
			}
			echo '<div id="message" class="updated"><p>';
			echo $msg;
			echo '</p></div>';			
	}

	public static function gf_pdf_auto_deploy_network_failure()
	{		
			global $gfpdfe_data;

			$prefix = (self::$automated === true && !rgpost('upgrade') && !rgpost('font-initialise')) ? sprintf(__('%sGravity Forms PDF Extended Automated Installer%s: ', 'pdfextended'), '<strong>', '</strong>') : '';
			$errors = (array) $gfpdfe_data->network_error;
			echo '<div id="message" class="error"><p>';
			if(sizeof($errors) > 0)
			{
				echo $prefix . __('There was a network initialisation issue on the following sites;', 'pdfextended');
				echo '<ul>';

				$base_site_url = site_url();
				foreach($errors as $site)
				{
					switch_to_blog( (int) $site['blog_id'] );
					$url = str_replace($base_site_url, site_url(), PDF_SETTINGS_URL );
					echo "<li><a href='$url'>{$site['domain']}{$site['path']}</a></li>";
					restore_current_blog();
				}
				echo '</ul>';

				echo __('Please try manually initialise the software', 'pdfextended');
			}
			else
			{
				echo $prefix . __('An unknown network initialisation error occured. Please try initialise again.', 'pdfextended');
			}

			echo '</p></div>';				
	}	
	
	/*
	 * The after_switch_theme hook is too early in the initialisation to use request_filesystem_credentials()
	 * so we have to call this function at a later inteval
	 */
	public static function do_theme_switch($previous_pdf_path, $current_pdf_path)
	{
		/*
		 * Prepare for calling the WP Filesystem
		 * It only allows post data to be added so we have to manually assign them
		 */
		$_POST['previous_pdf_path'] = $previous_pdf_path;
		$_POST['current_pdf_path']  = $current_pdf_path;
		
	    /*
		 * Initialise the Wordpress Filesystem API
		 */
		if(PDF_Common::initialise_WP_filesystem_API(array('previous_pdf_path', 'current_pdf_path'), 'gfpdfe_sync_now') === false)
		{
			return 'false';	
		}				
		
		/*
		 * If we got here we should have $wp_filesystem available
		 */
		global $wp_filesystem;	

		$notice_type = (PDF_Common::is_settings()) ? 'gfpdfe_notices' : 'admin_notices';
			 
		/*
		 * Convert paths for SSH/FTP users who are rooted to a directory along the server absolute path 
		 */	 
		$previous_pdf_path = self::get_base_dir($previous_pdf_path);
		$current_pdf_path  = self::get_base_dir($current_pdf_path);			 			 					 

		if($wp_filesystem->is_dir($previous_pdf_path))
		{
			self::pdf_extended_copy_directory( $previous_pdf_path, $current_pdf_path, true, true );

			/*
			 * Remove the options key that triggers the switch theme function
			 */ 
			 delete_option('gfpdfe_switch_theme');
			 add_action($notice_type, array('GFPDF_InstallUpdater', 'gf_pdf_theme_sync_success')); 	
			 
			 /*
			  * Show success message to user
			  */
			 return true;			
		}		
		return false; 
	}
	
	/*
	 * Allows you to copy entire folder structures to new location
	 */
	public static function pdf_extended_copy_directory( $source, $destination, $copy_base = true, $delete_destination = false ) 
	{
		global $wp_filesystem;		
		
		if ( $wp_filesystem->is_dir( $source ) ) 
		{			
			if($delete_destination === true)
			{
				/*
				 * To ensure everything stays in sync we will remove the destination file structure
				 */
				 $wp_filesystem->delete($destination, true);
			}
			 
			if($copy_base === true)
			{
				$wp_filesystem->mkdir( $destination );
			}
			$directory = $wp_filesystem->dirlist( $source );

			foreach($directory as $name => $data)
			{
							
				$PathDir = $source . '/' . $name; 
				
				if ( $wp_filesystem->is_dir( $PathDir ) ) 
				{
					self::pdf_extended_copy_directory( $PathDir, $destination . '/' . $name );
					continue;
				}
				$wp_filesystem->copy( $PathDir, $destination . '/' . $name );
			}

		}
		else 
		{
			$wp_filesystem->copy( $source, $destination );
		}	
	}
}
