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

	public static function check_filesystem_api()
	{
		global $gfpdfe_data;
		$access_type = get_filesystem_method();

		$gfpdfe_data->automated = false;
		if($access_type === 'direct')
		{
			$gfpdfe_data->automated = true;
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
		global $gfpdfe_data;
		/*
		 * Check if we have a 'direct' method, that the software isn't fully installed and we aren't trying to manually initialise
		 */
		
		if($gfpdfe_data->automated === true && $gfpdfe_data->is_initialised === false && !rgpost('upgrade') && get_option('gfpdfe_automated_install') != 'installing')
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
					add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_auto_deploy_success'));					
				}
			}
		}
	}

	/*
	 * Initialise all multsites in one fowl swoop 
	 */
	public static function run_multisite_deployment()
	{
		global $gfpdfe_data;

		/* add additional check incase someone doesn't call this correctly */
		if(!is_multisite())
			return false;

			/*
			 * Get multisites which aren't deleted 
			 */
			$sites = wp_get_sites(array('deleted' => 0));

			if(sizeof($sites) > 0)
			{

				$success = true;
				$problem = array();
				foreach($sites as $site)
				{
					 switch_to_blog( (int) $site['blog_id'] );

					 /*
					  * Test if the blog has gravity forms and PDF Extended active
					  * If so, we can initialise 
					  */				 
					 $gravityforms = 'gravityforms/gravityforms.php'; /* have to hardcode the folder name as they don't set it in a constant or variable */
					 $pdfextended = GF_PDF_EXTENDED_PLUGIN_BASENAME; /* no need to hardcode the basename here */

					 if( (is_plugin_active_for_network($gravityforms) && is_plugin_active_for_network($pdfextended)) ||
					 	 (is_plugin_active($gravityforms) && is_plugin_active($pdfextended))
					 	)
					 {
					 	/* run our deployment and output any problems */
					 	$deploy = self::do_deploy();
					 	if($deploy === false)
					 	{
					 		$success = false;
					 		$problem[] = $site;
					 	}
					 	else if ($deploy === 'false')
					 	{
					 		/* 
					 		 * Asking for the access details so we can write to the server 
					 		 * Exit early
					 		 */
					 		return $deploy;
					 	}
					 }
					 restore_current_blog();
				}

				if(!$success)
				{	
						$gfpdfe_data->network_error = $problem;
						add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_auto_deploy_network_failure'));		
				}
				else
				{
						add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_network_deploy_success'));			
				}
			}
	}

	/*
	 * Used to automatically deploy the software 
	 * Regular initialisation (via the settings page) will call pdf_extended_activate() directly.
	 */
	private static function do_deploy()
	{
		update_option('gfpdfe_automated_install', 'installing');
		return self::pdf_extended_activate();
	}

	/*
	 * Different filesystems (FTP/SSH) might have a different ABSPATH than the 'direct' method 
	 * due to being rooted to a specific folder. 
	 * The $wp_filesystem->abspath() corrects this behaviour. 
	 */
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
		if(PDF_Common::initialise_WP_filesystem_API(array('gfpdf_deploy', 'overwrite'), 'pdf-extended-filesystem') === false)
		{
			return 'false';	
		}	
		
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
		 if($wp_filesystem->exists($template_directory) && isset($_POST['overwrite']))
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

		/* create new directory in active themes folder*/	
		if(!$wp_filesystem->is_dir($template_directory))
		{
			if($wp_filesystem->mkdir($template_directory) === false)
			{
				add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_template_dir_err')); 	
				return false;
			}
		}
	
		if(!$wp_filesystem->is_dir($template_save_directory))
		{
			/* create new directory in active themes folder*/	
			if($wp_filesystem->mkdir($template_save_directory) === false)
			{
				add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_template_dir_err')); 	
				return false;
			}
		}
		
		if(!$wp_filesystem->is_dir($template_font_directory))
		{
			/* create new directory in active themes folder*/	
			if($wp_filesystem->mkdir($template_font_directory) === false)
			{
				add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_template_dir_err')); 	
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
				add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_template_dir_err')); 	
				return false;
			}
		}
		
		if(!$wp_filesystem->exists($template_save_directory.'.htaccess'))
		{		
			if(!$wp_filesystem->put_contents($template_save_directory.'.htaccess', 'deny from all'))
			{
				add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_template_dir_err')); 	
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
		self::db_init();
		
		return true;	
	}

	/*
	 * Normalize the database options related to initialisation
	 */
	public static function db_init()
	{
		global $gfpdfe_data;

		update_option('gf_pdf_extended_installed', 'installed');			
		delete_option('gfpdfe_switch_theme');
		delete_option('gfpdfe_automated_install');	
		GFPDF_Settings::$model->check_compatibility();
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
		global $wp_filesystem, $gfpdfe_data;			
		
		/*
		 * We need to set up some filesystem compatibility checkes to work with the different server file management types
		 * Most notably is the FTP options, but SSH may be effected too
		 */
		$directory               = self::get_base_dir(PDF_PLUGIN_DIR);
		$template_directory      = self::get_base_dir(PDF_TEMPLATE_LOCATION);
		$template_font_directory = self::get_base_dir(PDF_FONT_LOCATION);
		
		if(self::install_fonts($directory, $template_directory, $template_font_directory) === true)
		{
			add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_font_install_success')); 
		}		
		return true;
	}
	
	private static function install_fonts($directory, $template_directory, $fonts_location)
	{

		global $wp_filesystem, $gfpdfe_data;	
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
			  	add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_font_config_err')); 	
				return false;  
		  }			
		 
		 return true;
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
		global $gfpdfe_data;
		self::check_filesystem_api();

		if($gfpdfe_data->automated === true)
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
		global $gfpdfe_data;

		$theme_switch = get_option('gfpdfe_switch_theme');

		if(isset($theme_switch['old']) && isset($theme_switch['new']))
		{
			/*
			 * Add admin notification hook to move the files
			 */	
			 add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'do_theme_switch_notice')); 	
			 return true;
		}
		return false;		
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
		global $wp_filesystem, $gfpdfe_data;	
			 
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
			 add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_theme_sync_success')); 	
			 
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
