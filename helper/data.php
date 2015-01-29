<?php

/**
 * Plugin: Gravity PDF 
 * File: data.php
 * 
 * This is a data overloading class which holds important variables shared throughout the plugin
 * In some circumstances it will also provide functions to get data from itself
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
       
        $this->template_location              = $upload_dir['basedir'] . '/' . PDF_SAVE_FOLDER . '/';
        $this->template_site_location         = $this->template_location;
        $this->template_save_location         = $this->template_location . 'output/';
        $this->template_font_location         = $this->template_location . 'fonts/';
        
        $this->template_location_url          = $upload_dir['baseurl'] . '/' . PDF_SAVE_FOLDER . '/';
        $this->template_site_location_url     = $this->template_location_url;
        $this->template_save_location_url     = $this->template_location_url . 'output/';
        $this->template_font_location_url     = $this->template_location_url . 'fonts/';        
        
        $this->old_3_6_template_site_location = $this->template_location . $site_name . '/';

        /*
         * Use the network ID for multisite installs 
         */
        if(is_multisite())
        {
            $blog_id = get_current_blog_id();
            
            $this->template_site_location     = $this->template_location . $blog_id . '/';
            $this->template_save_location     = $this->template_site_location . 'output/';
            $this->template_font_location     = $this->template_site_location . 'fonts/';
            
            $this->template_site_location_url = $this->template_location_url . $blog_id . '/';
            $this->template_save_location_url = $this->template_site_location_url . 'output/';
            $this->template_font_location_url = $this->template_site_location_url . 'fonts/';   

            $this->old_3_6_template_site_location = $this->template_location . $site_name . '/';
        }    

        $this->old_template_location      = get_stylesheet_directory().'/'.PDF_SAVE_FOLDER.'/';
        $this->upload_dir                 = $upload_dir['basedir'];
    }
}