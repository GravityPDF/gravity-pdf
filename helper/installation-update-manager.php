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
	
	/**
	 * Install everything required
	 */
	public function pdf_extended_activate()
	{			
	    /*
		 * Initialise the Wordpress Filesystem API
		 */
		if(PDF_Common::initialise_WP_filesystem_API(array('gfpdf_deploy'), 'pdf-extended-filesystem') === false)
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
		$directory = PDF_PLUGIN_DIR;
		$template_directory = PDF_TEMPLATE_LOCATION;
		$template_save_directory = PDF_SAVE_LOCATION;
		$template_font_directory = PDF_FONT_LOCATION;
		
		
		/*
		 * If using FTP we need to make modifications to the file paths
		 * Unlike the direct method, the root of the FTP directory isn't the ABSPATH
		 * Usually FTP is restricted to the public_html directory, or just above it.
		 */
		if($wp_filesystem->method === 'ftpext' || $wp_filesystem->method === 'ftpsockets' || $wp_filesystem->method === 'ssh2')
		{
			/*
			 * Get the base directory
			 */ 			 
			 $base_directory = self::get_base_directory();
			 
			 $directory = str_replace(ABSPATH, $base_directory, $directory);
			 $template_directory = str_replace(ABSPATH, $base_directory, $template_directory);			 		
			 $template_save_directory = str_replace(ABSPATH, $base_directory, $template_save_directory);			 		
			 $template_font_directory = str_replace(ABSPATH, $base_directory, $template_font_directory);			 					 

		}

		/**
		 * If PDF_TEMPLATE_LOCATION already exists then we will remove the old template files so we can redeploy the new ones
		 */

		 if($wp_filesystem->exists($template_directory))
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
			 * The only function that requires the input to be the full path and the export to be the directory used in $wp_filesystem
			 */
			$results = unzip_file( PDF_PLUGIN_DIR . 'mPDF.zip', $directory );
		
			if($results !== true)
			{						
				add_action('gfpdfe_notices', array('GFPDF_InstallUpdater', 'gf_pdf_unzip_mpdf_err')); 	
				return 'fail';				
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
				add_action('gfpdfe_notices', array('GFPDF_InstallUpdater', 'gf_pdf_template_dir_err')); 	
				return 'fail';
			}
		}
	
		if(!$wp_filesystem->is_dir($template_save_directory))
		{
			/* create new directory in active themes folder*/	
			if($wp_filesystem->mkdir($template_save_directory) === false)
			{
				add_action('gfpdfe_notices', array('GFPDF_InstallUpdater', 'gf_pdf_template_dir_err')); 	
				return 'fail';
			}
		}
		
		if(!$wp_filesystem->is_dir($template_font_directory))
		{
			/* create new directory in active themes folder*/	
			if($wp_filesystem->mkdir($template_font_directory) === false)
			{
				add_action('gfpdfe_notices', array('GFPDF_InstallUpdater', 'gf_pdf_template_dir_err')); 	
				return 'fail';
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
				add_action('gfpdfe_notices', array('GFPDF_InstallUpdater', 'gf_pdf_template_dir_err')); 	
				return 'fail';
			}
		}
		
		if(!$wp_filesystem->exists($template_save_directory.'.htaccess'))
		{		
			if(!$wp_filesystem->put_contents($template_save_directory.'.htaccess', 'deny from all'))
			{
				add_action('gfpdfe_notices', array('GFPDF_InstallUpdater', 'gf_pdf_template_dir_err')); 	
				return 'fail';
			}	
		}	

		if(self::install_fonts($directory, $template_directory, $template_font_directory) !== true)
		{
			return 'fail';
		}				 
		
		/* 
		 * Update system to ensure everything is installed correctly.
		 */

		update_option('gf_pdf_extended_installed', 'installed');			
		update_option('gf_pdf_extended_deploy', 'yes');
		update_option('gf_pdf_extended_version', PDF_EXTENDED_VERSION);
		delete_option('gfpdfe_switch_theme');
		
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
		 * We need to set up some filesystem compatibility checkes to work with the different server file management types
		 * Most notably is the FTP options, but SSH may be effected too
		 */
		$directory = PDF_PLUGIN_DIR;
		$template_directory = PDF_TEMPLATE_LOCATION;
		$template_font_directory = PDF_FONT_LOCATION;
		
		
		if($wp_filesystem->method === 'ftpext' || $wp_filesystem->method === 'ftpsockets' || $wp_filesystem->method === 'ssh2')
		{
			/*
			 * Assume FTP is rooted to the Wordpress install
			 */ 			 
			 $base_directory = self::get_base_directory();
			 
			 $directory = str_replace(ABSPATH, $base_directory, $directory);
			 $template_directory = str_replace(ABSPATH, $base_directory, $template_directory);			 				 		
			 $template_font_directory = str_replace(ABSPATH, $base_directory, $template_font_directory);			 					 

		}
		
		if(self::install_fonts($directory, $template_directory, $template_font_directory) === true)
		{
			add_action('gfpdfe_notices', array('GFPDF_InstallUpdater', 'gf_pdf_font_install_success')); 
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
						add_action('gfpdfe_notices', array('GFPDF_InstallUpdater', 'gf_pdf_font_err')); 	
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
			  	add_action('gfpdfe_notices', array('GFPDF_InstallUpdater', 'gf_pdf_font_config_err')); 	
				return false;  
		  }			
		 
		 return true;
	}
	
	public function gf_pdf_font_install_success()
	{
		echo '<div id="message" class="updated"><p>';
		echo __('The font files have been successfully installed. A font can be used by adding it\'s file name (without .ttf and in lower case) in a CSS font-family declaration.', 'pdfextended');
		echo '</p></div>';
	}	

	public function gf_pdf_font_err()
	{
		echo '<div id="message" class="error"><p>';
		echo __('There was a problem installing the font files. Manually copy your fonts to the mPDF/ttfonts/ folder.', 'pdfextended');
		echo '</p></div>';
	}	
	
	public function gf_pdf_font_config_err()
	{
		echo '<div id="message" class="error"><p>';
		echo __('Could not create font configuration file. Try initialise again.', 'pdfextended');
		echo '</p></div>';
	}		
	
	/**
	 * Gravity Forms hasn't been installed so throw error.
	 * We make sure the user hasn't already dismissed the error
	 */
	public function gf_pdf_not_installed()
	{
		echo '<div id="message" class="error"><p>';
		echo sprintf(__('You need to install/update %sGravity Forms%s to use the Gravity Forms PDF Extended Plugin.', 'pdfextended'), '<a href="https://www.e-junkie.com/ecom/gb.php?cl=54585&c=ib&aff=235154" target="ejejcsingle">', '</a>');
		echo '</p></div>';
	}
	
	/**
	 * PDF Extended has been updated but the new template files haven't been deployed yet
	 */
	public function gf_pdf_not_deployed()
	{		
		if( !rgpost('update') )
		{
			if(rgget("page") == 'gf_settings' && rgget('addon') == 'PDF')
			{
				echo '<div id="message" class="error"><p>';
				echo __('You\'ve updated Gravity Forms PDF Extended but are yet to re-initialise the plugin. After initialising, please review the latest updates to ensure your custom templates remain compatible with the latest version.', 'pdfextended');
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
	public function gf_pdf_problem_detected()
	{
		if( !rgpost('update') )
		{
			if(rgget("page") == 'gf_settings' && rgget('addon') == 'PDF')
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
	public function gf_pdf_not_deployed_fresh()
	{		
		if( !rgpost('update') )
		{
			if(rgget("page") == 'gf_settings' && rgget('addon') == 'PDF')
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
	public function gf_pdf_not_supported()
	{
			echo '<div id="message" class="error"><p>';
			echo sprintf(__('Gravity Forms PDF Extended only works with Gravity Forms version '.GF_PDF_EXTENDED_SUPPORTED_VERSION.' and higher. Please %supgrade your copy of Gravity Forms%s to use this plugin.', 'pdfextended'), '<a href="https://www.e-junkie.com/ecom/gb.php?cl=54585&c=ib&aff=235154" target="ejejcsingle">', '</a>');
			echo '</p></div>';	
	}
								
	
	/**
	 * Cannot create new template folder in active theme directory
	 */
	public function gf_pdf_template_dir_err()
	{
			echo '<div id="message" class="error"><p>';
			echo __('We could not create a template folder in your active theme\'s directory. Please created a folder called <strong>\''. PDF_SAVE_FOLDER .'\'</strong> in '.get_stylesheet_directory().'/. Then copy the contents of '.PDF_PLUGIN_DIR.'templates/ to your newly-created PDF_EXTENDED_TEMPLATES folder, as well as styles/template.css. You should also make this directory writable.', 'pdfextended');
			echo '</p></div>';
			
	}
	
	public static function gf_pdf_unzip_mpdf_err()
	{
			echo '<div id="message" class="error"><p>';
			echo __('Could not unzip mPDF.zip (located in the plugin folder). Unzip the file manually, place the extracted mPDF folder in the plugin folder and run the initialisation again.', 'pdfextended');
			echo '</p></div>';		
	}
	
	/**
	 * Cannot remove old default template files
	 */
	public function gf_pdf_deployment_unlink_error()
	{
			echo '<div id="message" class="error"><p>';
			echo __('We could not remove the default template files from the Gravity Forms PDF Extended folder in your active theme\'s directory. Please manually remove all files starting with \'default-\' and the template.css file.', 'pdfextended');
			echo '</p></div>';
	
	}		
	
	/**
	 * Cannot create new template folder in active theme directory
	 */
	public function gf_pdf_template_move_err()
	{
			echo '<div id="message" class="error"><p>';
			echo __('We could not copy the contents of '.PDF_PLUGIN_DIR.'templates/ to your newly-created PDF_EXTENDED_TEMPLATES folder. Please manually copy the files to the aforementioned directory.', 'pdfextended');
			echo '</p></div>';
	
	}
	
	/*
	 * When switching themes copy over current active theme's PDF_EXTENDED_TEMPLATES (if it exists) to new theme folder
	 */
	public function gf_pdf_on_switch_theme($old_theme_name, $old_theme_object) {
		
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
			 add_action('admin_notices', array("GFPDF_InstallUpdater", "do_theme_switch_notice")); 	
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
			echo sprintf(__('Gravity Forms PDF Extended needs to keep your configuration and template folder in sync with your current active theme. %sSync Now%s', 'pdfextended'), '<a href="'. wp_nonce_url(PDF_SETTINGS_URL, 'gfpdfe_sync_now') . '" class="button">', '</a>');
			echo '</p></div>';		
		 
	}
	
	public static function gf_pdf_theme_sync_success()
	{
			echo '<div id="message" class="updated"><p>';
			echo __('Your configuration and template folder was successfully synced.', 'pdfextended');
			echo '</p></div>';			
	}
	
	/*
	 * The after_switch_theme hook is too early in the initialisation to use request_filesystem_credentials()
	 * so we have to call this function at a later inteval
	 */
	public function do_theme_switch($previous_pdf_path, $current_pdf_path)
	{
		/*
		 * Prepare for calling the WP Filesystem
		 * It only allows post data to be added so we have to manually assign them
		 */
		$_POST['previous_pdf_path'] = $previous_pdf_path;
		$_POST['current_pdf_path'] = $current_pdf_path;
		
	    /*
		 * Initialise the Wordpress Filesystem API
		 */
		if(PDF_Common::initialise_WP_filesystem_API(array('previous_pdf_path', 'current_pdf_path'), 'gfpdfe_sync_now') === false)
		{
			return false;	
		}				
		
		/*
		 * If we got here we should have $wp_filesystem available
		 */
		global $wp_filesystem;	
		
		/*
		 * We need to set up some filesystem compatibility checkes to work with the different server file management types
		 * Most notably is the FTP options, but SSH may be effected too
		 */
		
		if($wp_filesystem->method === 'ftpext' || $wp_filesystem->method === 'ftpsockets' || $wp_filesystem->method === 'ssh2')
		{
			/*
			 * Assume FTP is rooted to the Wordpress install
			 */ 			 
			 $base_directory = self::get_base_directory();
			 
			 $previous_pdf_path = str_replace(ABSPATH, $base_directory, $previous_pdf_path);
			 $current_pdf_path = str_replace(ABSPATH, $base_directory, $current_pdf_path);			 			 					 

		}						 
		 
		 if($wp_filesystem->is_dir($previous_pdf_path))
		 {
			 self::pdf_extended_copy_directory( $previous_pdf_path, $current_pdf_path, true, true );
		 }		
		 
		/*
		 * Remove the options key that triggers the switch theme function
		 */ 
		 delete_option('gfpdfe_switch_theme');
		 add_action('gfpdfe_notices', array('GFPDF_InstallUpdater', 'gf_pdf_theme_sync_success')); 	
		 
		 /*
		  * Show success message to user
		  */
		 return true;
	}
	
	/*
	 * Allows you to copy entire folder structures to new location
	 */
	
	public function pdf_extended_copy_directory( $source, $destination, $copy_base = true, $delete_destination = false ) 
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
	
	private static function check_access_path($directory, $file_path, $directory_list)
	{
		global $wp_filesystem;	

			foreach($directory_list as $name => $data)
			{
				/*
				 * Check if one of the file/folder names matches what is in $file_path, make sure it is a directory and 
				 * the name has a value
				 */

				$match = array_search($name, $file_path);
				
				if((strlen($name) > 0) && ($match !== false) && ((int) $data['isdir'] === 1 || $data['type'] === 'd') )
				{

					/* 
					 * We have a match but it could be fake
					 * Look inside the target folder and see if the next folder in $file_path can be found
					 * If it can we will assume it is the correct path
					 */
					 if(isset($file_path[$match+1]))
					 {

						$next_match = $file_path[$match+1];
						$directory_list2 = $wp_filesystem->dirlist('/'.$name.'/');

						if(isset($directory_list2[$next_match]) && ((int) $directory_list2[$next_match]['isdir'] === 1 || $directory_list2[$next_match]['type'] === 'd'))
						{
							 return self::merge_path($file_path, $match);				 
						}
						
					 }
					 else
					 {
							 return self::merge_path($file_path, $match);					 
					 }
				}
			}	
			
			return $directory;	
	}
	
	/*
	 * Merge the path array back together from the matched key
	 */	
	private static function merge_path($file_path, $key)
	{
		return '/' .  implode('/', array_slice($file_path, $key)) . '/';
	}
	
	/*
	 * Get the base directory for the current filemanagement type
	 * In this case it is FTP but may be SSH
	 */
	 private static function get_base_directory()
	 {
		global $wp_filesystem; 
		
		/*
		 * Assume FTP is rooted to the Wordpress install
		 */ 
		 $directory = '/';
		 
		 /*
		  * Test if the FTP is below the Wordpress base by sniffing the base directory 
		  */
		$directory_list = $wp_filesystem->dirlist('/');		
		
		/*
		 * Use the ABSPATH to compare the directory structure
		 */
		$file_path = array_filter(explode('/', ABSPATH ));

		/*
		 * Rekey the array
		 */
		$file_path = array_values($file_path);	
		
		return self::check_access_path($directory, $file_path, $directory_list); 
			 		
	 }	

}
