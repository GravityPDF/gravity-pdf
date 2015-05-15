<?php

namespace GFPDF\Helper;
use GFPDF\PDF_Common;
use GFPDF\Stat\Stat_Options_API;

/**
 * Data overloaded Helper Class 
 * Cache shared data across the plugin
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
 * @since 4.0
 */
class Helper_Data {
    /**
     * Location for the overloaded data         
     * @var array
     * @since 4.0
     */
    private $data = array();

    /**
     * PHP Magic Method __set() 
     * Run when writing data to inaccessible properties
     * @param string $name  Name of the peroperty being interacted with 
     * @param mixed $value  Data to assign to the $name property
     * @since 4.0
     */
    public function __set($name, $value) {
        $this->data[$name] = $value;
    }

    /**
     * PHP Magic Method __get()
     * Run when reading data from inaccessible properties
     * @param string $name  Name of the property being interacted with 
     * @return mixed        The data assigned to the $name property is returned
     * @since 4.0
     */
    public function &__get($name) {
        /* Check if we actually have a key matching what was requested */
        if (array_key_exists($name, $this->data)) {
            /* key exists, so return */
            return $this->data[$name];
        }

        /* Not found so generate error */
        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);

        /* because we are returning by reference we need return something that can be referenced */
        $value = null;
        return $value;
    }

    /**
     * PHP Magic Method __isset()
     * Triggered when isset() or empty() is called on inaccessible properties 
     * @param  string  $name Name of the property being interacted with 
     * @return boolean       Whether property exists
     * @since 4.0
     */
    public function __isset($name) {
        return isset($this->data[$name]);
    }

    /**
     * PHP Magic Method __isset()
     * Triggered when unset() is called on inaccessible properties 
     * @param  string  $name Name of the property being interacted with 
     * @return void
     * @since 4.0
     */
    public function __unset($name) {
        unset($this->data[$name]);
    }

    /**
     * Set up addon array for use tracking active addons 
     * @since  3.8
     */
    public function set_addon_details() {
        $this->addon = array();
    }

    /**
     * Set up any default data that should be stored 
     * @return void
     * @since 3.8
     */
    public function init() {
        $this->set_working_folder();
        $this->set_directory_structure();
        $this->set_plugin_settings();
        $this->set_licensing();
    }

    /**
     * Set the folder name we'll be using to hold custom templates / PDFs
     * @return  void 
     * @since  4.0
     */
    public function set_working_folder() {
        $this->working_folder = 'PDF_EXTENDED_TEMPLATES';
    }

    /**
     * Set up our license model for later use
     * @return  void 
     * @since  4.0
     */    
    public function set_licensing() {
         /* Set up our licensing */
         //$this->license = new License_Model();    
         $this->store_url = 'https://gravitypdf.com/';    
    }

    /**
     * Get the plugin's settings from the database 
     * @since 3.8
     */
    public function set_plugin_settings() {
        if ( false == get_option( 'gfpdf_settings' ) ) {
            add_option( 'gfpdf_settings' );
        }

        /* assign our settings */
        $this->settings = Stat_Options_API::get_settings();         
    }

    /**
     * Used to set up our PDF template folder, 
     * save folder and font folder
     * @since  3.6
     */
    public function set_directory_structure()
    {
        $upload_dir = PDF_Common::get_upload_dir();
        $site_name  = PDF_Common::get_site_name();
        
        /*
         * As of Gravity PDF 3.7 we'll be dropping the 'site_name' folder for single installs 
         * And changing multisite installs to their site ID         
         */
       
        $this->template_location              = apply_filters('gfpdfe_template_location', $upload_dir['basedir'] . '/' . $this->working_folder . '/', $upload_dir['basedir'], $this->working_folder);
        $this->template_site_location         = $this->template_location;
        $this->template_save_location         = $this->template_location . 'output/';
        $this->template_font_location         = $this->template_location . 'fonts/';

        $this->settings_url                   = admin_url('admin.php?page=gf_settings&subview=PDF'); 
        
        $this->template_location_url          = apply_filters('gfpdfe_template_location_uri', $upload_dir['baseurl'] . '/' . $this->working_folder . '/', $upload_dir['baseurl'], $this->working_folder);
        $this->template_site_location_url     = $this->template_location_url;
        $this->template_save_location_url     = $this->template_location_url . 'output/';
        $this->template_font_location_url     = $this->template_location_url . 'fonts/';        
        
        $this->old_3_6_template_site_location = $this->template_location . $site_name . '/';

        /*
         * Use the network ID for multisite installs 
         */
        if(is_multisite()) {
            $blog_id                              = get_current_blog_id();
            
            $this->template_site_location         = $this->template_location . $blog_id . '/';
            $this->template_save_location         = $this->template_site_location . 'output/';
            $this->template_font_location         = $this->template_site_location . 'fonts/';
            
            $this->template_site_location_url     = $this->template_location_url . $blog_id . '/';
            $this->template_save_location_url     = $this->template_site_location_url . 'output/';
            $this->template_font_location_url     = $this->template_site_location_url . 'fonts/';  
            
            $this->old_3_6_template_site_location = $this->template_location . $site_name . '/';
        }    

        /*
         * Include old template locations to help with migrations 
         */
        $this->old_template_location      = get_stylesheet_directory().'/'. $this->working_folder .'/';
        $this->upload_dir                 = $upload_dir['basedir'];

        /*
         * Include relative paths for display on the support pages 
         */
        $this->relative_output_location = str_replace(ABSPATH, '/', $this->template_save_location);
        $this->relative_font_location   = str_replace(ABSPATH, '/', $this->template_font_location);
        $this->relative_mpdf_tmp        = str_replace(ABSPATH, '/', PDF_PLUGIN_DIR) . 'mPDF/tmp/';        
    }
}