<?php

/**
 * Plugin: Gravity PDF
 * File: helper/system-status.php
 *
 * The model that does all the processing and interacts with our controller and view
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

/**
 * Class to set up the system status getters, setters and display
 * @since 3.8
 */
class GFPDF_System_Status
{
    /**
     * Used to check if this is a fresh installation or an upgrade
     */
    public function fresh_install()
    {
        global $gfpdfe_data;

        if (get_option('gf_pdf_extended_installed') !== 'installed') {
            $gfpdfe_data->fresh_install = true;
        } else {
            $gfpdfe_data->fresh_install = false;
        }
    }

     /**
      * Check if the software has been initialised
      */
     public function is_initialised()
     {
         global $gfpdfe_data;

         /*
          * Sniff the options to see if it exists
          */
          $gfpdfe_data->is_initialised = false;
         if ($gfpdfe_data->fresh_install === false) {
             $gfpdfe_data->is_initialised = true;
         }
         $gfpdfe_data->allow_initilisation = true;
     }

     /**
      * [check_wp_compatibility description]
      * @return [type] [description]
      */
     public function check_wp_compatibility()
     {
         global $wp_version, $gfpdfe_data;
         $gfpdfe_data->wp_version = $wp_version;

         if (version_compare($gfpdfe_data->wp_version, GF_PDF_EXTENDED_WP_SUPPORTED_VERSION, ">=") === true) {
             $gfpdfe_data->wp_is_compatible = true;

             return;
         }
         $gfpdfe_data->wp_is_compatible = false;
         $gfpdfe_data->allow_initilisation = false;
     }

     /**
      * [check_gf_compatibility description]
      * @return [type] [description]
      */
     public function check_gf_compatibility()
     {
         global $gfpdfe_data;

         if (class_exists('GFCommon')) {
             $gfpdfe_data->gf_installed = true;
             $gfpdfe_data->gf_version = GFCommon::$version;

             if (version_compare($gfpdfe_data->gf_version, GF_PDF_EXTENDED_SUPPORTED_VERSION, '>=') === true) {
                 $gfpdfe_data->gf_is_compatible = true;

                 return;
             }
         }
         $gfpdfe_data->gf_installed = false;
         $gfpdfe_data->gf_is_compatible = false;
         $gfpdfe_data->allow_initilisation = false;
     }

     /**
      * [check_php_compatibility description]
      * @return [type] [description]
      */
     public function check_php_compatibility()
     {
         global $gfpdfe_data;
         $gfpdfe_data->php_version = (float) phpversion();

         if (version_compare($gfpdfe_data->php_version, GF_PDF_EXTENDED_PHP_SUPPORTED_VERSION, '>=') === true) {
             $gfpdfe_data->php_version_compatible = true;

             return;
         }
         $gfpdfe_data->php_version_compatible = false;
         $gfpdfe_data->allow_initilisation = false;
     }

     /**
      * [mb_string_installed description]
      * @return [type] [description]
      */
     public function mb_string_installed()
     {
         global $gfpdfe_data;

         if (extension_loaded('mbstring')) {
             if (function_exists('mb_regex_encoding')) {
                 $gfpdfe_data->mb_string_installed = true;

                 return;
             }
         }
         $gfpdfe_data->mb_string_installed = false;
         $gfpdfe_data->allow_initilisation = false;
     }

     /**
      * [gd_installed description]
      * @return [type] [description]
      */
     public function gd_installed()
     {
         global $gfpdfe_data;

         if (extension_loaded('gd')) {
             $gfpdfe_data->gd_installed = true;

             return;
         }
         $gfpdfe_data->gd_installed = false;
         $gfpdfe_data->allow_initilisation = false;
     }

     /**
      * convert ini memory limit to bytes
      * @param  [type] $size_str [description]
      * @return [type]           [description]
      */
     public function convert_ini_memory($size_str)
     {
         $convert = array('mb' => 'm', 'kb' => 'k', 'gb' => 'g');

         foreach ($convert as $k => $v) {
             $size_str = str_ireplace($k, $v, $size_str);
         }

         switch (substr($size_str, -1)) {
            case 'M': case 'm': return (int) $size_str * 1048576;
            case 'K': case 'k': return (int) $size_str * 1024;
            case 'G': case 'g': return (int) $size_str * 1073741824;
            default: return $size_str;
        }
     }

     /**
      * [check_available_ram description]
      * @return [type] [description]
      */
     public function check_available_ram()
     {
         global $gfpdfe_data;

        /*
         * Get ram available in bytes and convert it to megabytes
         */
         $memory_limit = $this->convert_ini_memory(ini_get('memory_limit'));
         $gfpdfe_data->ram_available = ($memory_limit === '-1') ? -1 : floor($memory_limit / 1024 / 1024); /* convert to MB */

         $gfpdfe_data->ram_compatible = true;

         if ($gfpdfe_data->ram_available < 128 && $gfpdfe_data->ram_available !== -1) {
             $gfpdfe_data->ram_compatible = false;
         }

         /*
          * If under 64MB of ram assigned to the server do not run the software
          */
         if ($gfpdfe_data->ram_available < 64 && $gfpdfe_data->ram_available !== -1) {
             $gfpdfe_data->allow_initilisation = false;
         }
     }

     /**
      * [check_write_permissions description]
      * @return [type] [description]
      */
     public function check_write_permissions()
     {
         global $gfpdfe_data;

         /*
          * Attempt to actually write a file and test if it works
          */

          /*
           * Check if the PDF_EXTENDED_FOLDER is already created
           */
          if ($gfpdfe_data->is_initialised === false) {

              /*
               * Default our values
               */
               $gfpdfe_data->can_write_upload_dir = false;

              /*
               * Test the upload folder where our templates are stored
               */
              if ($this->test_write_permissions($gfpdfe_data->upload_dir) === true) {
                  $gfpdfe_data->can_write_upload_dir = true;
              }
          } else {
              /*
               * Default our values
               */
              $gfpdfe_data->can_write_output_dir = false;
              $gfpdfe_data->can_write_font_dir = false;
              $gfpdfe_data->can_write_pdf_temp_dir = false;

              /*
               * The PDF_EXTENDED_TEMPLATE folder is created so lets check our permissions
               */
              if ($this->test_write_permissions($gfpdfe_data->template_save_location) === true) {
                  $gfpdfe_data->can_write_output_dir = true;
              }

              if ($this->test_write_permissions($gfpdfe_data->template_font_location) === true) {
                  $gfpdfe_data->can_write_font_dir = true;
              }

              if ($this->test_write_permissions(PDF_PLUGIN_DIR.'mPDF/tmp/') === true) {
                  $gfpdfe_data->can_write_pdf_temp_dir = true;
              }
          }
     }

     /**
      * [test_write_permissions description]
      * @param  [type] $path [description]
      * @return [type]       [description]
      */
     public function test_write_permissions($path)
     {
         global $gfpdfe_data;

         if (is_writable($path)) {
             file_put_contents($path.'pdf_extended_temp', '');
             if (file_exists($path.'pdf_extended_temp')) {
                 /* clean up */
                @unlink($path.'pdf_extended_temp');

                 return true;
             }
         }

         return false;
     }

     /**
      * [display_general_status description]
      * @return [type] [description]
      */
    public function display_site_status() {
      echo $this->get_system_status_html();
    }

    /**
     * [get_system_status_html description]
     * @return [type]  [description]
     */
    public function get_system_status_html()
    {
        global $gfpdfe_data;

        ob_start();
        include PDF_PLUGIN_DIR.'view/templates/settings/system-status.php';
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * [get_active_plugins description]
     * @return [type] [description]
     */
    public function get_active_plugins()
    {
        global $gfpdfe_data;

        if (isset($gfpdfe_data->active_plugins)) {
            return $gfpdfe_data->active_plugins;
        }
        $active_plugins = get_option('active_plugins');

        /*
         * Look up the name of the plugin
         */
         $user_plugins = array();
        foreach ($active_plugins as $plugin) {
            $data = get_plugin_data(WP_PLUGIN_DIR.'/'.$plugin);
            $user_plugins[] = $data['Name'].', '.$data['Version'].' - '.$data['PluginURI'];
        }
        $plugins = implode("\n", $user_plugins);

        return $plugins;
    }

    /**
     * [get_configuration_file description]
     * @return [type] [description]
     */
    public function get_configuration_file()
    {
        global $gfpdfe_data;

        if (isset($gfpdfe_data->configuration_file)) {
            return $gfpdfe_data->configuration_file;
        }

            /*
             * Include the current configuration, if available
             */
             if (file_exists($gfpdfe_data->template_site_location.'configuration.php')) {
                 return esc_html(file_get_contents($gfpdfe_data->template_site_location.'/configuration.php'));
             } else {
                 return __('Plugin not yet initialised', 'pdfextended');
             }
    }    
}
