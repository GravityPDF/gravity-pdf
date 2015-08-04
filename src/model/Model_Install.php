<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Model;
use GFPDF\Stat\Stat_Functions;

use GFAPI;
use GFCommon;

/**
 * Welcome Screen Model
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

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

/**
 * Model_Install
 *
 * Handles the grunt work of our installer / uninstaller
 *
 * @since 4.0
 */
class Model_Install extends Helper_Model {

    /**
     * The Gravity PDF Installer
     * @return void
     * @since 4.0
     */
    public function install_plugin() {
            update_option('gfpdf_is_installed', true);
    }

    /**
     * Get our permalink regex structure
     * @return  String
     * @since  4.0
     */
    public function get_permalink_regex() {
        return '^pdf\/([A-Za-z0-9]+)\/([0-9]+)\/?(download)?\/?';
    }

    /**
     * Get the plugin working directory name
     * @return String
     * @since  4.0
     */
    public function get_working_directory() {
        return apply_filters('gfpdf_working_folder_name', 'PDF_EXTENDED_TEMPLATES');
    }

    /**
     * Get a link to the plugin's settings page URL
     * @return String
     * @since  4.0
     */
    public function get_settings_url() {
        return admin_url('admin.php?page=gf_settings&subview=PDF');
    }

    /**
     * Get our current installation status
     * @return  String
     * @since  4.0
     */
    public function is_installed() {
        return get_option('gfpdf_is_installed');
    }

    /**
     * Used to set up our PDF template folder, tmp folder and font folder
     * @since 4.0
     */
    public function setup_template_location() {
        global $gfpdf;

        $gfpdf->data->template_location      = apply_filters('gfpdfe_template_location', $gfpdf->data->upload_dir . '/' . $gfpdf->data->working_folder . '/', $gfpdf->data->upload_dir, $gfpdf->data->working_folder);
        $gfpdf->data->template_location_url  = apply_filters('gfpdfe_template_location_url', $gfpdf->data->upload_dir_url . '/' . $gfpdf->data->working_folder . '/', $gfpdf->data->upload_dir_url, $gfpdf->data->working_folder);
        $gfpdf->data->template_font_location = $gfpdf->data->template_location . 'fonts/';
        $gfpdf->data->template_tmp_location  = $gfpdf->data->template_location . 'tmp/';
    }

    /**
     * If running a multisite we'll setup the path to the current multisite folder
     * @since 4.0
     * @return void
     */
    public function setup_multisite_template_location() {
        global $gfpdf;

        if(is_multisite()) {
            $blog_id = get_current_blog_id();
            $gfpdf->data->multisite_template_location     = apply_filters('gfpdfe_multisite_template_location', $gfpdf->data->upload_dir . '/' . $gfpdf->data->working_folder . '/', $gfpdf->data->upload_dir, $gfpdf->data->working_folder);
            $gfpdf->data->multisite_template_location_url = apply_filters('gfpdfe_multisite_template_location_url', $gfpdf->data->upload_dir_url . '/' . $gfpdf->data->working_folder . '/', $gfpdf->data->upload_dir_url, $gfpdf->data->working_folder);
        }
    }

    /**
     * Create the appropriate folder structure automatically
     * The upload directory should have all appropriate permissions to allow this kind of maniupulation
     * but devs who tap into the gfpdfe_template_location filter will need to ensure we can write to the appropraite folder
     * @since 4.0
     * @return void
     */
    public function create_folder_structures() {
        global $gfpdf;

        /* don't create the folder structure on our welcome page or through AJAX as an errors on the first page they see will confuse users */
        if( is_admin() &&
            (rgget('page') == 'gfpdf-getting-started') || (defined( 'DOING_AJAX' ) && DOING_AJAX))  {
            return false;
        }

        /* add folders that need to be checked */
        $folders = array(
            $gfpdf->data->template_font_location,
            $gfpdf->data->template_tmp_location,
        );

        if(is_multisite()) {
            $folders[] = $gfpdf->data->multisite_template_location;
        }

        /* allow other plugins to add their own folders which should be checked */
        $folders = apply_filters('gfpdf_installer_create_folders', $folders);

        /* create the required folder structure, or throw error */
        foreach($folders as $dir) {
            if(!is_dir($dir)) {
                if(! wp_mkdir_p($dir)) {
                    $gfpdf->notices->add_error(sprintf(__('There was a problem creating the %s directory. Ensure you have write permissions to your upload directory.', 'gravitypdf'), '<code>' . Stat_Functions::relative_path($dir) . '</code>'));
                }
            } else {
                /* test the directory is currently writable by the web server, otherwise throw and error */
                if(! Stat_Functions::is_directory_writable($dir)) {
                    $gfpdf->notices->add_error(sprintf(__('Gravity PDF does not have write permissions to the %s directory. Contact your web hosting provider to fix the issue.', 'gravitypdf'), '<code>' . Stat_Functions::relative_path($dir) . '</code>'));
                }
            }
        }

        /* create blank index file in all folders to prevent web servers listing the entire directory */
        if(is_dir($gfpdf->data->template_location) && !is_file($gfpdf->data->template_location . 'index.html')) {
            GFCommon::recursive_add_index_file($gfpdf->data->template_location);
        }

        /* create deny htaccess file to prevent direct access to files */
        if(is_dir($gfpdf->data->template_tmp_location) && !is_file($gfpdf->data->template_tmp_location . '.htaccess')) {
            file_put_contents($gfpdf->data->template_tmp_location . '.htaccess', 'deny from all');
        }
    }

    /**
     * Register our PDF custom rewrite rules
     * @since 4.0
     * @return void
     */
    public function register_rewrite_rules() {
        global $gfpdf;

        /* store query */
        $query = $gfpdf->data->permalink;

        /* Add our main endpoint */
        add_rewrite_rule(
            $query,
            'index.php?gf_pdf=1&pid=$matches[1]&lid=$matches[2]&action=$matches[3]',
            'top');

        /* check to see if we need to flush the rewrite rules */
        $this->maybe_flush_rewrite_rules($query);
    }

    /**
     * Register our PDF custom rewrite rules
     * @since 4.0
     * @return void
     */
    public function register_rewrite_tags( $tags ) {
        $tags[] = 'gf_pdf';
        $tags[] = 'pid';
        $tags[] = 'lid';
        $tags[] = 'action';

        return $tags;
    }

    /**
     * Check if we need to force the rewrite rules to be flushed
     * @param  $rule The rule to check
     * @since 4.0
     * @return void
     */
    public function maybe_flush_rewrite_rules($rule) {
        $rules = get_option( 'rewrite_rules' );

        if ( ! isset( $rules[ $rule ] ) ) {
            flush_rewrite_rules(false);
        }
    }


    /**
     * The Gravity PDF Uninstaller
     * @return void
     * @since 4.0
     * @todo  Add Multisite Support (Network Activated)
     */
    public function uninstall_plugin() {
        $this->remove_plugin_options();
        $this->remove_plugin_form_settings();
        $this->remove_folder_structure();
        $this->deactivate_plugin();
        $this->redirect_to_plugins_page();
    }

    /**
     * Remove and options stored in the database
     * @return void
     * @since 4.0
     */
    public function remove_plugin_options() {
        delete_option('gfpdf_is_installed');
        delete_option('gfpdf_settings');
    }

    /**
     * Remove all form settings from each individual form.
     * Because we stored out PDF settings with each form and have no index we need to individually load and forms and check them for Gravity PDF settings
     * @return void
     * @since 4.0
     */
    public function remove_plugin_form_settings() {
        global $gfpdf;

        $forms = GFAPI::get_forms();

        foreach($forms as $form) {
            /* only update forms which have a PDF configuration */
            if(isset($form['gfpdf_form_settings'])) {
                unset($form['gfpdf_form_settings']);
                if(GFAPI::update_form($form) !== true) {
                    $gfpdf->notices->add_error(sprintf(__('There was a problem removing the Gravity Form "%s" PDF configuration. Try delete manually.', 'gravitypdf'), $form['ID'] . ': ' . $form['title']));
                }
            }
        }
    }

    /**
     * Remove our PDF directory structure
     * @return void
     * @since 4.0
     */
    public function remove_folder_structure() {
        global $gfpdf;

        $paths = apply_filters('gfpdf_uninstall_path', array(
            $gfpdf->data->template_location,
        ));

        foreach($paths as $dir) {
            if(is_dir($dir)) {
                $results = Stat_Functions::rmdir($dir);

                if(is_wp_error($results) || !$results) {
                    $gfpdf->notices->add_error(sprintf(__('There was a problem removing the %s directory. Clean up manually via (S)FTP.', 'gravitypdf'), '<code>' . Stat_Functions::relative_path($dir) . '</code>'));
                }
            }
        }
    }

    /**
     * Deactivate Gravity PDF
     * @return void
     * @since 4.0
     */
    public function deactivate_plugin() {
        deactivate_plugins(PDF_PLUGIN_BASENAME);
    }

    /**
     * Safe redirect after deactivation
     * @return void
     * @since 4.0
     */
    public function redirect_to_plugins_page() {
        /* check if user can view the plugins page */
        if(current_user_can('activate_plugins') ) {
            wp_safe_redirect( admin_url('plugins.php'));
        } else { /* otherwise redirect to dashboard */
            wp_safe_redirect( admin_url('index.php'));
        }
        exit;
    }
}