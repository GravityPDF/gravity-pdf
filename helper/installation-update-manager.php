<?php

/**
 * Plugin: Gravity PDF
 * File: install-update-manager.php
 *
 * This file handles the installation and update code that ensures the plugin will be supported.
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

class GFPDF_InstallUpdater
{

    public static function check_filesystem_api()
    {
        global $gfpdfe_data;
        $access_type = get_filesystem_method();

        $gfpdfe_data->automated = false;
        if ($access_type === 'direct') {
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

        if ($gfpdfe_data->automated === true && $gfpdfe_data->is_initialised === false && !rgpost('upgrade') && get_option('gfpdfe_automated_install') != 'installing') {
            /*
             * Initialise all multisites if a super admin is logged in
             */
            if (is_multisite() && is_super_admin()) {
                $results = GFPDF_InstallUpdater::run_multisite_deployment(array('GFPDF_InstallUpdater', 'do_deploy'));

                if ($results === true) {
                    add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_network_deploy_success'));
                } elseif ($results === false) {
                    add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_auto_deploy_network_failure'));
                }

                return $results;
            } else {
                if (self::do_deploy()) {                   
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
    public static function run_multisite_deployment($action)
    {
        global $gfpdfe_data;

        /* add additional check incase someone doesn't call this correctly */
        if (!is_multisite() || !is_super_admin()) {
            return false;
        }

            /*
             * Get multisites which aren't deleted
             */
            $sites = wp_get_sites(array('deleted' => 0));

        if (sizeof($sites) > 0) {
            $problem = array();
            foreach ($sites as $site) {
                switch_to_blog((int) $site['blog_id']);

                     /*
                      * Reset the directory structure
                      */
                     $gfpdfe_data->set_directory_structure();

                     /*
                      * Test if the blog has gravity forms and PDF Extended active
                      * If so, we can initialise
                      */
                     $gravityforms = 'gravityforms/gravityforms.php'; /* have to hardcode the folder name as they don't set it in a constant or variable */
                     $pdfextended = GF_PDF_EXTENDED_PLUGIN_BASENAME; /* no need to hardcode the basename here */

                     if ((is_plugin_active_for_network($gravityforms) && is_plugin_active_for_network($pdfextended)) ||
                         (is_plugin_active($gravityforms) && is_plugin_active($pdfextended))
                        ) {
                         /* run our deployment and output any problems */
                        $deploy = call_user_func($action);
                         if ($deploy === false) {
                             $problem[] = $site;
                         } elseif ($deploy === 'false') {
                             /*
                              * Asking for the access details so we can write to the server
                              * Exit early
                              */
                            return $deploy;
                         }
                     }
                restore_current_blog();

                     /*
                      * Reset the directory structure
                      */
                     $gfpdfe_data->set_directory_structure();
            }

            if (sizeof($problem) > 0) {
                $gfpdfe_data->network_error = $problem;

                return false;
            }

            return true;
        }
    }

    /*
     * Used to automatically deploy the software
     * Regular initialisation (via the settings page) will call pdf_extended_activate() directly.
     */
    public static function do_deploy()
    {
        update_option('gfpdfe_automated_install', 'installing');

        return self::pdf_extended_activate();
    }

    /*
     * Different filesystems (FTP/SSH) might have a different ABSPATH than the 'direct' method
     * due to being rooted to a specific folder.
     * The $wp_filesystem->abspath() corrects this behaviour.
     */
    private static function get_basedir($path)
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
        if (PDF_Common::initialise_WP_filesystem_API(array('gfpdf_deploy', 'overwrite'), 'pdf-extended-filesystem') === false) {
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
        $directory               = self::get_basedir(PDF_PLUGIN_DIR);
        $base_template_directory = self::get_basedir($gfpdfe_data->template_location);
        $template_directory      = self::get_basedir($gfpdfe_data->template_site_location);
        $template_save_directory = self::get_basedir($gfpdfe_data->template_save_location);
        $template_font_directory = self::get_basedir($gfpdfe_data->template_font_location);

        /**
         * If template directory already exists then we will remove the old template files so we can redeploy the new ones
         */
        self::reinitialise_templates($template_directory);

        /* create new directory in uploads folder*/        
        if (self::create_base_template_dir($base_template_directory) === false) {            
            return false;
        }

        /* create site directory in base template directory */
        if (self::create_site_template_dir($template_directory) === false) {            
            return false;
        }

        /* create 'save' output folder */
        if (self::create_save_dir($template_save_directory) === false) {            
            return false;
        }

        /* create 'font' folder */
        if (self::create_font_dir($template_font_directory) === false) {
            return false;
        }

        /* copy entire template folder over to the template directory */
        if(!self::pdf_extended_copy_directory($directory . 'initialisation/templates/', $template_directory, false)) {            
            return false;
        }

        /* copy configuration file over to new directory */
        if (self::create_configuration_file($directory, $template_directory) === false) {            
            return false;
        }

        /* create .htaccess file */
        if (self::create_htaccess_file($template_save_directory) === false) {
            return false;
        }

        /* initialise font directory */
        if (self::install_fonts($directory, $template_directory, $template_font_directory) !== true) {
            return false;
        }

        /* update db to ensure everything is installed correctly. */
        self::db_init();

        return true;
    }

    public static function reinitialise_templates($template_directory)
    {
        global $wp_filesystem, $gfpdfe_data;

        if ($wp_filesystem->exists($template_directory) && isset($_POST['overwrite'])) {
            /*
              * Create a backup folder and move all the files there
              */
              $backup_folder = 'INIT_BACKUP_'.date('Y-m-d_G-i').'/';
            $do_backup = false;
            if ($wp_filesystem->mkdir($template_directory.$backup_folder)) {
                $do_backup = true;
            }

             /* read all file names into array and unlink from active theme template folder */
             foreach (glob(PDF_PLUGIN_DIR.'initialisation/templates/*') as $file) {
                 $path_parts = pathinfo($file);
                 if ($wp_filesystem->exists($template_directory.$path_parts['basename'])) {
                     if (!$do_backup) {
                         $wp_filesystem->delete($template_directory.$path_parts['basename']);
                         continue;
                     }
                     $wp_filesystem->move($template_directory.$path_parts['basename'], $template_directory.$backup_folder.$path_parts['basename']);
                 }
             }
        }
    }

    public static function create_base_template_dir($base_template_directory)
    {
        global $wp_filesystem, $gfpdfe_data;

        /* create new directory in uploads folder*/
        if (!$wp_filesystem->is_dir($base_template_directory)) {
            if ($wp_filesystem->mkdir($base_template_directory) === false) {
                /*
                 * TODO: add correct notices
                 */
                add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_template_dir_err'));

                return false;
            }
        }

        return true;
    }

    public static function create_site_template_dir($template_directory)
    {
        global $wp_filesystem, $gfpdfe_data;

        if (!$wp_filesystem->is_dir($template_directory)) {
            if ($wp_filesystem->mkdir($template_directory) === false) {
                add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_template_site_dir_err'));

                return false;
            }
        }

        return true;
    }

    public static function create_save_dir($template_save_directory)
    {
        global $wp_filesystem, $gfpdfe_data;

        if (!$wp_filesystem->is_dir($template_save_directory)) {
            /* create new directory in active themes folder*/
            if ($wp_filesystem->mkdir($template_save_directory) === false) {
                add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_template_dir_err'));

                return false;
            }
        }

        return true;
    }

    public static function create_font_dir($template_font_directory)
    {
        global $wp_filesystem, $gfpdfe_data;

        if (!$wp_filesystem->is_dir($template_font_directory)) {
            /* create new directory in active themes folder*/
            if ($wp_filesystem->mkdir($template_font_directory) === false) {
                add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_template_dir_err'));

                return false;
            }
        }

        return true;
    }

    /**
     * Copy configuration file to template folder
     * @param  String  $directory          The $wp_filesystem path to the plugin folder
     * @param  String  $template_directory The $wp_filesystem path to the template folder
     * @return Boolean Success on true (or not run at all). false on fail
     */
    public static function create_configuration_file($directory, $template_directory)
    {
        global $wp_filesystem, $gfpdfe_data;

        if (!$wp_filesystem->exists($template_directory.'configuration.php')) {
            /* copy template files to new directory */
            if (!$wp_filesystem->copy($directory.'initialisation/configuration.php.example', $template_directory.'configuration.php')) {
                add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_template_dir_err'));

                return false;
            }
        }

        return true;
    }

    /**
     * Create htaccess file to prevent direct access to PDFs
     * @param  String  $template_save_directory The $wp_filesystem path to the save directory
     * @return Boolean success on true (or not run at all). false on fail
     */
    public static function create_htaccess_file($template_save_directory)
    {
        global $wp_filesystem, $gfpdfe_data;

        if (!$wp_filesystem->exists($template_save_directory.'.htaccess')) {
            if (!$wp_filesystem->put_contents($template_save_directory.'.htaccess', 'deny from all')) {
                add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_template_dir_err'));

                return false;
            }
        }

        return true;
    }

    /*
     * Normalize the database options related to initialisation
     */
    public static function db_init()
    {
        global $gfpdfe_data;

        update_option('gf_pdf_extended_installed', 'installed');
        delete_option('gfpdfe_automated_install');
        GFPDF_Settings::$model->check_compatibility();
    }

    public static function initialise_fonts()
    {
        /*
         * Initialise the Wordpress Filesystem API
         */
        if (PDF_Common::initialise_WP_filesystem_API(array('gfpdf_deploy', 'font-initialise', 'gfpdf_deploy_nonce'), 'pdf-extended-fonts') === false) {
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
        $directory               = self::get_basedir(PDF_PLUGIN_DIR);
        $template_directory      = self::get_basedir($gfpdfe_data->template_site_location);
        $template_font_directory = self::get_basedir($gfpdfe_data->template_font_location);

        if (self::install_fonts($directory, $template_directory, $template_font_directory) === true) {
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
         foreach (glob($gfpdfe_data->template_font_location.'/*.[tT][tT][fF]') as $file) {
             $path_parts = pathinfo($file);

                /*
                 * Generate configuration information in preparation to write to file
                 */
                $write_to_file .= '
					$this->fontdata[\''.mb_strtolower(str_replace(' ', '', $path_parts['filename']), 'UTF-8').'\'] = array(
								\'R\' => \''.$path_parts['basename'].'\'
					);';
         }

         /*
          * Remove the old configuration file and put the contents of $write_to_file in a font configuration file
          */
          $wp_filesystem->delete($template_directory.'fonts/config.php');
        if ($wp_filesystem->put_contents($template_directory.'fonts/config.php', $write_to_file) === false) {
            add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_font_config_err'));

            return false;
        }

        return true;
    }

    public static function maybe_automigrate()
    {
        global $gfpdfe_data;
        self::check_filesystem_api();

        if ($gfpdfe_data->automated === true) {
            update_option('gfpdfe_automated_install', 'installing');
            self::run_template_migration();

            return true;
        }

        return false;
    }

    /*
     * Check if the new PDF_TEMPLATE_LOCATION (v.3.6 mod) has been made switched
     * If it has then prompt the user to move the files
     */
    public static function check_template_migration()
    {
        global $gfpdfe_data;

        if (is_dir($gfpdfe_data->old_template_location) || is_dir($gfpdfe_data->old_3_6_template_site_location)) {/* add in 3.6 directory change */
            if (!self::maybe_automigrate()) {
                /*
                 * Add admin notification hook to move the files
                 */
                 add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'do_template_switch_notice'));

                return true;
            }
        }

        return false;
    }

    public static function run_template_migration()
    {
        global $gfpdfe_data;

        if (is_multisite() && is_super_admin()) {
            $return = self::run_multisite_deployment(array('GFPDF_InstallUpdater', 'do_template_migration'));
            GFPDF_Settings::$model->check_compatibility();

            /* multisite mode */
            if ($return === true) {
                add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_migration_success'));
            } elseif ($return === false) {
                add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_merge_network_failure'));
            }

            return $return;
        } else {
            $return = self::do_template_migration();
            GFPDF_Settings::$model->check_compatibility();

            /* single site mode */
            if ($return === true) {
                add_action($gfpdfe_data->notice_type, array('GFPDF_Notices', 'gf_pdf_migration_success'));

                return $return;
            }

            return $return;
        }
    }

    /*
     * The after_switch_theme hook is too early in the initialisation to use request_filesystem_credentials()
     * so we have to call this function at a later inteval
     */
    public static function do_template_migration()
    {
        /*
         * Initialise the Wordpress Filesystem API
         */
        if (PDF_Common::initialise_WP_filesystem_API(array(), 'gfpdfe_migrate') === false) {
            return 'false';
        }

        /*
         * If we got here we should have $wp_filesystem available
         */
        global $wp_filesystem, $gfpdfe_data;

        /*
         * Convert paths for SSH/FTP users who are rooted to a directory along the server absolute path
         */
        $base_template_directory = self::get_basedir($gfpdfe_data->template_location);
        $current_pdf_path        = self::get_basedir($gfpdfe_data->template_site_location);

        if (is_dir($gfpdfe_data->old_template_location)) {
            $previous_pdf_path = self::get_basedir($gfpdfe_data->old_template_location);
        } elseif (is_dir($gfpdfe_data->old_3_6_template_site_location)) {
            $previous_pdf_path = self::get_basedir($gfpdfe_data->old_3_6_template_site_location);
        }

        /* create the base template directory */
        if (self::create_base_template_dir($base_template_directory) === false) {
            return false;
        }

        /* create the site template directory */
        if (self::create_site_template_dir($current_pdf_path) === false) {
            return false;
        }

        if ($wp_filesystem->is_dir($previous_pdf_path)) {
            if (is_dir($gfpdfe_data->old_template_location)) {         
                /* swap back to TRUE to delete the theme folder */       
                if(!self::pdf_extended_copy_directory($previous_pdf_path, $current_pdf_path, true, true, true)) {
                    return false;
                }

            } elseif (is_dir($gfpdfe_data->old_3_6_template_site_location)) {
                /* swap back to TRUE to delete the theme folder */
                if(!self::pdf_extended_copy_directory($previous_pdf_path, $current_pdf_path, true, false, true)) {
                    return false;
                }
            }

             /*
              * Clean up the DB
              */
             delete_option('gfpdfe_automated_install');
        }

        return true;
    }

    /**
     * Allows you to copy entire folder structures to new location
     * @param  String  $source             The source path that should be copied
     * @param  String  $destination        The destination path where the files should be copied to
     * @param  boolean $copy_base          Whether to create the base directory at the destination
     * @param  boolean $delete_destination Whether to want to remove the destination before copying the files
     * @param  boolean $delete_source      Once finished copying should we remove the source folder
     */
    public static function pdf_extended_copy_directory($source, $destination, $copy_base = true, $delete_destination = false, $delete_source = false)
    {
        global $wp_filesystem;

        /* ensure source and destination end in a forward slash */
        if(substr($source, -1) != '/') {
            $source .= '/';
        }

        if(substr($destination, -1) != '/') {
            $destination .= '/';
        }

        if($wp_filesystem->is_dir($destination) && $wp_filesystem->is_writable($destination) === false)
        {
            return false;
        }

        if ($wp_filesystem->is_dir($source)) {
            if ($delete_destination === true && $wp_filesystem->exists($destination) === true) {
                /*
                 * To ensure everything stays in sync we will remove the destination file structure
                 */
                 $wp_filesystem->delete($destination, true);
            }

            if ($copy_base === true && $wp_filesystem->exists($destination) === false) {
                if(!$wp_filesystem->mkdir($destination)) {
                    return false;
                }
            }
            $directory = $wp_filesystem->dirlist($source);

            foreach ($directory as $name => $data) {
                $PathDir = $source.$name;

                if ($wp_filesystem->is_dir($PathDir) === true) {
                    if(!self::pdf_extended_copy_directory($PathDir, $destination.$name)) {
                        return false;
                    }
                    continue;
                }
                $wp_filesystem->copy($PathDir, $destination.$name);
                
                /* verify the file copied correctly */
                if($wp_filesystem->is_file($destination.$name) === false || $wp_filesystem->size($PathDir) != $wp_filesystem->size($destination.$name)) {
                    return false;
                }                
            }
        } else {
            $wp_filesystem->copy($source, $destination);
            /* verify the file copied correctly */
            if($wp_filesystem->is_file($destination) === false || $wp_filesystem->size($source) != $wp_filesystem->size($destination)) {
                return false;
            }
        }

        if ($delete_source) {
            if($wp_filesystem->delete($source, true) === false) {
                return false;
            }
        }

        return true;
    }
}
