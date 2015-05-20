<?php

namespace GFPDF\View;
use GFPDF\Helper\Helper_View;
use GFPDF_Major_Compatibility_Checks;
use GFCommon;

/**
 * Settings View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if (! defined('ABSPATH')) {
    exit;
}

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
 * View_Welcome_Screen
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class View_Settings extends Helper_View
{

    /**
     * Set the view's name
     * @var string
     * @since 4.0
     */
    protected $ViewType = 'Settings';

    public function __construct($data = array()) {
        $this->data = $data;
    }   

    /**
     * Load the Welcome Tab tabs
     * @since 4.0
     * @return void
     */
    public function tabs() {
        global $gfpdf;

        /*
         * Set up any variables we need for the view and display 
         */        
        $vars = array(
            'selected' => isset( $_GET['tab'] ) ? $_GET['tab'] : 'general',
            'tabs'     => $this->get_avaliable_tabs(),
            'data'     => $gfpdf->data,
        );

        $vars = array_merge($vars, $this->data); 

        /* load the tabs view */
        $this->load('tabs', $vars);
    }

    /**
     * Set up our settings navigation 
     * @return array The navigation array 
     * @since 4.0
     */
    public function get_avaliable_tabs() {
            /**
             * Store the setting navigation
             * The array key is the settings order
             * @var array
             */
            $navigation = array(
                5 => array(
                    'name'     => __('General', 'pdfextended'),
                    'id'       => 'general',                    
                ),

                100 => array(
                    'name'     => __('Tools', 'pdfextended'),
                    'id'       => 'tools',                    
                ),

                120 => array(
                    'name' => __('Help', 'pdfextended'),
                    'id' => 'help',                    
                ),
            );

            /**
             * Allow additional navigation to be added to the settings page
             * @since 3.8
             */
            return apply_filters('pdf_extended_settings_navigation', $navigation);                      
    }

    /**
     * Pull the system status details and show
     * @return void 
     * @since 4.0
     */
    public function system_status() {
        global $wp_version;

        $status = new GFPDF_Major_Compatibility_Checks();

        $mb_string = false;
        if($this->get_mb_string() && $this->check_mb_string_regex()) {
            $mb_string = true;
        }

        $vars = array(
            'memory' => $status->get_ram(),
            'output' => true, /* TODO - write installer / uninstaller first */
            'output_path' => 'path/to/file', /* TODO */
            'wp'     => $wp_version,
            'php'    => phpversion(),
            'gf'     => GFCommon::$version,                    
        );

        $vars = array_merge($vars, $this->data); 

        /* load the system status view */
        $this->load('system_status', $vars);        
    }

    /**
     * Pull the tools details and show
     * @return void 
     * @since 4.0
     */
    public function tools() {
        global $gfpdf;

        $vars = array(
            'template_directory' => str_replace(ABSPATH, '/', $gfpdf->data->template_location),
        );

        $vars = array_merge($vars, $this->data); 

        /* load the system status view */
        $this->load('tools', $vars);        
    }

    /**
     * Add Gravity Forms Tooltips
     * @param Array The existing tooltips
     */
    public static function add_tooltips($tooltips)
    {
        global $gfpdf;        

        $tooltips['pdf_status_wp_memory']     = '<h6>' . __( 'WP Memory Available', 'pdfextended' ) . '</h6>' . sprintf(__( "Producing PDF documents is hard work and Gravity PDF requires more resources than most plugins. We strongly recommend you have at least 128MB, but you may need more.", 'pdfextended' )); 
        $tooltips['pdf_status_notifications'] = '<h6>' . __( 'PDF Notifications', 'pdfextended' ) . '</h6>' . sprintf(__( 'Sending PDFs automatically via Gravity Form notifications requires write access to our designated output directory: %s.', 'pdfextended' ), '<code>' . $gfpdf->data->relative_output_location . '</code>');       

        return $tooltips;
    }
}
