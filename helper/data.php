<?php

/**
 * Plugin: Gravity PDF 
 * File: data.php
 * 
 * This is a data overloading class which holds important variables shared throughout the plugin
 * In some circumstances it will also provide functions to get data from itself
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

class GFPDFE_DATA
{
    /**  Location for overloaded data.  */
    private $data = array();

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    /**  As of PHP 5.1.0  */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**  As of PHP 5.1.0  */
    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    /**
     * Used to set up our PDF template folder, 
     * save folder and font folder
     */
    public function set_directory_structure()
    {
        $upload_dir = PDF_Common::get_upload_dir();
        $site_name  = PDF_Common::get_site_name();
        
        /*
         * As of Gravity PDF 3.7 we'll be dropping the 'site_name' folder for single installs 
         * And changing multisite installs to their site ID         
         */
       
        $this->template_location              = apply_filters('gfpdfe_template_location', $upload_dir['basedir'] . '/' . PDF_SAVE_FOLDER . '/', $upload_dir['basedir'], PDF_SAVE_FOLDER);
        $this->template_site_location         = $this->template_location;
        $this->template_save_location         = $this->template_location . 'output/';
        $this->template_font_location         = $this->template_location . 'fonts/';
        
        $this->template_location_url          = apply_filters('gfpdfe_template_location_uri', $upload_dir['baseurl'] . '/' . PDF_SAVE_FOLDER . '/', $upload_dir['baseurl'], PDF_SAVE_FOLDER);
        $this->template_site_location_url     = $this->template_location_url;
        $this->template_save_location_url     = $this->template_location_url . 'output/';
        $this->template_font_location_url     = $this->template_location_url . 'fonts/';        
        
        $this->old_3_6_template_site_location = $this->template_location . $site_name . '/';

        /*
         * Use the network ID for multisite installs 
         */
        if(is_multisite())
        {
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
        $this->old_template_location      = get_stylesheet_directory().'/'.PDF_SAVE_FOLDER.'/';
        $this->upload_dir                 = $upload_dir['basedir'];

        /*
         * Include relative paths for display on the support pages 
         */
        $this->relative_output_location = str_replace(ABSPATH, '/', $this->template_save_location);
        $this->relative_font_location   = str_replace(ABSPATH, '/', $this->template_font_location);
        $this->relative_mpdf_tmp        = str_replace(ABSPATH, '/', PDF_PLUGIN_DIR) . 'mPDF/tmp/';        
    }
}