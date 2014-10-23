<?php

/**
 * Plugin: Gravity Forms PDF Extended
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
        $upload_dir = wp_upload_dir();
        $site_name = PDF_Common::get_site_name();
        

        $this->template_location      = $upload_dir['basedir'] . '/' . PDF_SAVE_FOLDER . '/';
        $this->template_site_location = $upload_dir['basedir'] . '/' . PDF_SAVE_FOLDER . '/' . $site_name . '/';
        $this->template_save_location = $upload_dir['basedir'] . '/' . PDF_SAVE_FOLDER . '/' . $site_name . '/output/';
        $this->template_font_location = $upload_dir['basedir'] . '/' . PDF_SAVE_FOLDER . '/' . $site_name . '/fonts/';
        
        $this->old_template_location  = get_stylesheet_directory().'/'.PDF_SAVE_FOLDER.'/';
        $this->upload_dir             = $upload_dir['basedir'];
    }
}