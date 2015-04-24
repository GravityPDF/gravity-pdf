<?php

/**
 * Plugin: Gravity PDF
 * File: mode/settings.php
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

   

class GFPDF_Settings_Model extends GFPDF_Settings
{
    public $navigation = array();

    /*
     * Construct
     */
     public function __construct()
     {
         /*
         * Let's check if the web server is going to be compatible
         */
         $this->check_compatibility();        
     }

     /**
      * Set up our settings navigation
      * Note: ID 40/50 are taken by "Extensions" and "License" tabs
      */
     public function support_navigation()
     {

            /**
             * Store the setting navigation
             * The array key is the settings order
             * @var array
             */
            $this->navigation = array(
                5 => array(
                    'name'     => __('General', 'pdfextended'),
                    'id'       => 'general',
                    'template' => PDF_PLUGIN_DIR.'view/templates/settings/general.php',
                ),

                100 => array(
                    'name'     => __('Tools', 'pdfextended'),
                    'id'       => 'tools',
                    'template' => PDF_PLUGIN_DIR.'view/templates/settings/tools.php' ,
                ),

                120 => array(
                    'name' => __('Help', 'pdfextended'),
                    'id' => 'help',
                    'template' => PDF_PLUGIN_DIR.'view/templates/settings/help.php' ,
                ),

                150 => array(
                    'name' => __('DEP_Initialisation', 'pdfextended'),
                    'id' => 'initialisation',
                    'template' => PDF_PLUGIN_DIR.'view/templates/settings/initialisation-tab.php',
                ),

            );

            /**
             * Allow additional navigation to be added to the settings page
             * @since 3.8
             */
            $this->navigation = apply_filters('pdf_extended_settings_navigation', $this->navigation);                        
     }

    public function check_compatibility()
    {
        $status = new GFPDF_System_Status();

        $status->fresh_install();
        $status->is_initialised();

        $status->check_wp_compatibility();
        $status->check_gf_compatibility();
        $status->check_php_compatibility();

        $status->mb_string_installed();
        $status->gd_installed();
        $status->check_available_ram();

        $status->check_write_permissions();
    }

    /*
     * Shows the GF PDF Extended settings page
     */
    public function gfpdf_settings_page()
    {
        global $gfpdfe_data;
        /*
         * Run the page's configuration/routing options
         */
        if ($this->run_setting_routing() === true) {
            return;
        }

        $this->support_navigation();

        include PDF_PLUGIN_DIR.'view/settings.php';

		/*
		* Pass any additional variables to the view templates
		*/
        $status = new GFPDF_System_Status();

        $gfpdfe_data->active_plugins           = $status->get_active_plugins();        
        $gfpdfe_data->configuration_file       = $status->get_configuration_file();

        new settingsView($this);
    }
}
