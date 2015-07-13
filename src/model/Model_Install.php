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

    public function remove_plugin_options() {
        delete_option('gfpdf_is_installed');
        delete_option('gfpdf_settings');
    }

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

    public function remove_folder_structure() {
        global $gfpdf;

        $paths = apply_filters('gfpdf_uninstall_path', array(
            $gfpdf->data->template_location,
        ));

        foreach($paths as $dir) {
            if(is_dir($dir)) {
                $results = Stat_Functions::rmdir($dir);

                if(is_wp_error($results) || !$results) {
                    $gfpdf->notices->add_error(sprintf(__('There was a problem removing the "%s" directory. Clean up manually via (S)FTP.', 'gravitypdf'), str_replace(ABSPATH, '', $dir)));
                }
            }
        }
    }

    public function deactivate_plugin() {
        deactivate_plugins(PDF_PLUGIN_BASENAME);
    }

    public function redirect_to_plugins_page() {
        /* check if user can view the plugins page */
        if(current_user_can('activate_plugins') ) {
            wp_safe_redirect( admin_url('plugins.php'));
        } else { /* otherwise redirect to dashboard */
            wp_safe_redirect( admin_url('index.php'));
        }
        exit;
    }

    /**
     * Get our permalink regex structure
     * @return  String
     * @since  4.0
     */
    public function get_permalink_regex() {
        return '^pdf/([A-Za-z0-9]+)/([0-9]+)/?';
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
            'index.php?gf_pdf=1&pid=$matches[1]&lid=$matches[2]',
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
}