<?php

namespace GFPDF;

use GFPDF\Controller;
use GFPDF\Model;
use GFPDF\View;
use GFPDF\Helper;
use GFPDF\Stat;
use GFCommon;
use GFPDF_Core;

/**
 * Bootstrap / Router Class
 * The bootstrap is loaded on WordPress 'plugins_loaded' functionality
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

/*
 * Load dependancies
 */
require_once(PDF_PLUGIN_DIR . 'src/autoload.php');

/**
 * @since 4.0
 */
class Router implements Helper\Helper_Int_Actions, Helper\Helper_Int_Filters {
    
    /**
     * Holds our Helper_Notices object
     * which we can use to queue up admin messages for the user
     * @var Object
     * @since 4.0
     */
    public $notices;

    /**
     * Holds our Helper_Data object
     * which we can autoload with any data needed
     * @var Object
     * @since 4.0
     */
    public $data;

    /**
     * Holds our Helper_Options object
     * Makes it easy to access global PDF settings and individual form PDF settings
     * @var Object
     * @since 4.0
     */
    public $options;

    /**
     * Add user depreciation notice for any methods not included in current object
     * @since  4.0
     */
    public function __call($name, $arguments) {
        trigger_error(sprintf(__('"%s" has been depreciated as of Gravity PDF 4.0', 'gravitypdf'), $name), E_USER_DEPRECATED);
    }

    /**
     * Add user depreciation notice for any methods not included in current object
     * @since  4.0
     */
    public static function __callStatic($name, $arguments) {
        trigger_error(sprintf(__('"%s" has been depreciated as of Gravity PDF 4.0', 'gravitypdf'), $name), E_USER_DEPRECATED);
    }

    /**
     * Setup our plugin functionality
     * Note: Fires on WordPress' init hook
     * @since 4.0
     */
    public function init() {
        /* Set up our notices */
        $this->notices = new Helper\Helper_Notices();
        $this->notices->init();

        /* Set up our data access layer */
        $this->data = new Helper\Helper_Data();
        $this->data->init();

        /* set up our options object - this is initialised on admin_init but other classes need to access its methods before this */
        $this->options = new Helper\Helper_Options();

        /**
         * Run generic actions and filters needed to get the plugin functional
         * The controllers will set more specific actions / filters as needed
         */
        $this->add_actions();
        $this->add_filters();

        /* load modules */
        $this->installer();
        $this->welcome_screen();
        $this->gf_settings();
        $this->gf_form_settings();
        $this->pdf();
        $this->shortcodes();

        /* Add localisation support */
        load_plugin_textdomain('gravitypdf', false,  dirname( plugin_basename( __FILE__ ) ) . '/assets/languages/' );

    }

    /**
     * Add required plugin actions
     * @since 4.0
     * @return void
     */
    public function add_actions() {
        add_action('init', array($this, 'register_assets'), 500);
        add_action('init', array($this, 'load_assets'), 600);

        /* load our modules */
        add_action('init', array($this, 'init_settings_api'));
        add_action('admin_init', array($this, 'setup_settings_fields'));
    }

    /**
     * Add required plugin filters
     * @since 4.0
     * @return void
     */
    public function add_filters() {
        /* automatically handle GF noconflict mode */
        add_filter('gform_noconflict_scripts', array($this, 'auto_noconflict_scripts'));
        add_filter('gform_noconflict_styles', array($this, 'auto_noconflict_styles'));
    }

    /**
     * Register all css and js which can be enqueued when needed
     * @since 4.0
     * @return void
     */
    public function register_assets() {
        $this->register_styles();
        $this->register_scripts();
    }

    /**
     * Register requrired CSS
     * @since 4.0
     * @return void
     */
    private function register_styles() {
        $version = PDF_EXTENDED_VERSION;
        $suffix = '.min.';
        if(defined('WP_DEBUG') && WP_DEBUG === true) {
            $suffix  = '';
        }

        wp_register_style( 'gfpdf_css_styles', PDF_PLUGIN_URL . 'src/assets/css/gfpdf-styles'. $suffix .'.css', array('wp-color-picker'), $version);
        wp_register_style( 'gfpdf_css_chosen_style', PDF_PLUGIN_URL . 'bower_components/chosen/chosen.min.css', array('wp-jquery-ui-dialog'), $version );
    }

    /**
     * Register requrired JS
     * @since 4.0
     * @return void
     * @todo Limit js dependancies on particular pages (eg. form pdf settings vs global settings)
     */
    private function register_scripts() {

        $version = PDF_EXTENDED_VERSION;
        $suffix  = '.min.';
        if(defined('WP_DEBUG') && WP_DEBUG === true) {
            $suffix = '';
        }

        wp_register_script( 'gfpdf_js_settings', PDF_PLUGIN_URL . 'src/assets/js/gfpdf-settings'. $suffix .'.js', array('wpdialogs', 'jquery-ui-tooltip', 'gform_forms', 'gform_form_admin', 'jquery-color', 'wp-color-picker'), $version );
        wp_register_script( 'gfpdf_js_backbone', PDF_PLUGIN_URL . 'src/assets/js/gfpdf-backbone'. $suffix .'.js', array('gfpdf_js_settings', 'backbone', 'underscore', 'gfpdf_js_backbone_model_binder', 'wpdialogs'), $version );
        wp_register_script( 'gfpdf_js_chosen', PDF_PLUGIN_URL . 'bower_components/chosen/chosen.jquery.min.js', array('jquery'), $version );
        wp_register_script( 'gfpdf_js_backbone_model_binder', PDF_PLUGIN_URL . 'bower_components/backbone.modelbinder/Backbone.ModelBinder.js', array('backbone', 'underscore'), $version );
        wp_register_script( 'gfpdf_js_entries', PDF_PLUGIN_URL . 'src/assets/js/gfpdf-entries' . $suffix . '.js', array('jquery'), $version );
        
        /*
        * Localise admin script
        */
        wp_localize_script( 'gfpdf_js_settings', 'GFPDF', $this->data->get_localised_script_data() );
    }


    /**
     * Load any assets that are needed
     * @since 4.0
     * @return void
     */
    public function load_assets() {
        if(Stat\Stat_Functions::is_gfpdf_page()) {
            /* load styles */
            wp_enqueue_style('gfpdf_css_styles');
            wp_enqueue_style('gfpdf_css_chosen_style');

            /* load scripts */
            wp_enqueue_script('gfpdf_js_settings');
            wp_enqueue_script('gfpdf_js_chosen');

            /* add media uploader */
            wp_enqueue_media();
        }

        if(Stat\Stat_Functions::is_gfpdf_settings_tab('help') || Stat\Stat_Functions::is_gfpdf_settings_tab('tools')) {
             wp_enqueue_script('gfpdf_js_backbone');
        }

        if(is_admin() && rgget('page') == 'gf_entries') {
            wp_enqueue_script('gfpdf_js_entries');
            wp_enqueue_style('gfpdf_css_styles');
        }
    }

     /**
     * Auto no-conflict any preloaded scripts that begin with 'gfpdf_'
     * @since 4.0
     * @return void
     */
    public function auto_noconflict_scripts($items) {
        $wp_scripts = wp_scripts();

        /* set defaults we will allow to load on GF pages */
        $default_scripts = array(
            'editor',
            'word-count',
            'quicktags',
            'wpdialogs-popup',
            'media-upload',
            'wplink',
            'backbone',
            'underscore',
            'media-editor',
            'media-models',
            'media-views',
            'plupload',
            'plupload-flash',
            'plupload-html4',
            'plupload-html5',
            'plupload-silverlight',
            'wp-plupload',
            'gform_placeholder',
            'jquery-ui-autocomplete',
            'thickbox',
        );

        foreach($wp_scripts->queue as $object) {
            if(substr($object, 0, 8) === 'gfpdf_js') {
                $items[] = $object;
            }
        }

        if(Stat\Stat_Functions::is_gfpdf_page()) {
            $items = array_merge($default_scripts, $items);
        }

        return apply_filters('gfpdf_autoload_gf_scripts', $items);
    }

     /**
     * Auto no-conflict any preloaded styles that begin with 'gfpdf_'
     * @since 4.0
     * @return void
     */
    public function auto_noconflict_styles($items) {
        $wp_styles  = wp_styles();

        $default_styles = array(
            'editor-buttons',
            'wp-jquery-ui-dialog',
            'media-views',
            'buttons',
            'thickbox',
        );

        foreach($wp_styles->queue as $object) {
            if(substr($object, 0, 9) === 'gfpdf_css') {
                $items[] = $object;
            }
        }

        if(Stat\Stat_Functions::is_gfpdf_page()) {
            $items = array_merge($default_styles, $items);
        }

        return apply_filters('gfpdf_autoload_gf_styles', $items);
    }

    /**
     * Bootstrap our settings API for use
     * @return void
     * @return 4.0
     */
    public function init_settings_api() {
        /* load our options API */
        $this->options->init();
    }

    /**
     * Register our admin settings
     * @return void
     * @return 4.0
     */
    public function setup_settings_fields() {
        /* register our options settings */
        $this->options->register_settings();
    }

    public function installer() {
        $model = new Model\Model_Install();
        $class = new Controller\Controller_Install($model);
        $class->init();

        /* set up required data */
        $class->setup_defaults();
    }

    /**
     * Include Welcome Screen functionality for installation / upgrades
     * @since 4.0
     * @return void
     */
    public function welcome_screen() {

        $model = new Model\Model_Welcome_Screen();
        $view  = new View\View_Welcome_Screen(array(
            'display_version' => PDF_EXTENDED_VERSION
        ));

        $class = new Controller\Controller_Welcome_Screen($model, $view);
        $class->init();
    }

    /**
     * Include Settings Page functionality
     * @since 4.0
     * @return void
     */
    public function gf_settings() {
        
        $model = new Model\Model_Settings();
        $view  = new View\View_Settings(array(
        
        ));

        $class = new Controller\Controller_Settings($model, $view);
        $class->init();
    }

    /**
     * Include Form Settings (PDF) functionality
     * @since 4.0
     * @return void
     */
    public function gf_form_settings() {
        
        $model = new Model\Model_Form_Settings();
        $view  = new View\View_Form_Settings(array(
        
        ));

        $class = new Controller\Controller_Form_Settings($model, $view);
        $class->init();
    }

    /**
     * Include PDF Display functionality
     * @since 4.0
     * @return void
     */
    public function pdf() {
        
        $model = new Model\Model_PDF();
        $view  = new View\View_PDF(array(
        
        ));

        $class = new Controller\Controller_PDF($model, $view);
        $class->init();
    }

    /**
     * Include PDF Shortcodes functionality
     * @since 4.0
     * @return void
     */
    public function shortcodes() {
        
        $model = new Model\Model_Shortcodes();
        $view  = new View\View_Shortcodes(array(
        
        ));

        $class = new Controller\Controller_Shortcodes($model, $view);
        $class->init();
    }

    /**
     * Add backwards compatbility with v3.x.x default PDF template files
     * This function will now pull the PDF configuration details from the database and return them
     * @param  Integer $form_id  The Gravity Form ID
     * @return  Array The matched configuration being requested
     * @since 4.0
     */
    public function get_default_config_data($form_id) {
        $pid = $GLOBALS['wp']->query_vars['pid'];
        $lid = (int) $GLOBALS['wp']->query_vars['lid'];
    }
}


/**
 * Execute our bootstrap class
 */
new GFPDF_Core();