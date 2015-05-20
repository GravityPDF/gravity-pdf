<?php

namespace GFPDF\Controller;
use GFPDF\Helper\Helper_Controller;
use GFPDF\Helper\Helper_Model;
use GFPDF\Helper\Helper_View;
use GFPDF\Helper\Helper_Int_Actions;
use GFPDF\Helper\Helper_Int_Filters;
use \RGForms;

/**
 * Settings Controller
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
 * Controller_Settings
 * A general class for the global PDF settings
 *
 * @since 4.0
 */
class Controller_Settings extends Helper_Controller implements Helper_Int_Actions, Helper_Int_Filters
{
    /**
     * Load our model and view and required actions
     */
    public function __construct(Helper_Model $model, Helper_View $view)
    {
        /* load our model and view */
        $this->model = $model;
        $this->model->setController($this);   

        $this->view  = $view;        
    }

    /**
     * Initialise our class defaults 
     * @since 4.0
     * @return void
     */
    public function init() {
        global $gfpdf;
        
        /* 
         * Tell Gravity Forms to initiate our settings page
         * Using the following Class/Model
         */ 
         RGForms::add_settings_page($gfpdf->data->short_title, array($this, 'displayPage'));            

         $this->add_actions();
         $this->add_filters();       
    }

    /**
     * Apply any actions needed for the settings page
     * @since 4.0
     * @return void
     */
    public function add_actions() {
        /* Load our settings meta boxes */
        add_action('current_screen', array($this->model, 'add_meta_boxes')); 

        /* Display our system status on general and tools pages */
        add_action('pdf-settings-general', array($this->view, 'system_status'));        
        add_action('pdf-settings-tools', array($this->view, 'system_status'));
        add_action('pdf-settings-tools', array($this->view, 'uninstaller'), 20);

    }

    /**
     * Apply any filters needed for the settings page
     * @since 4.0
     * @return void
     */
    public function add_filters() {
        /* Add tooltips */
        add_action('gform_tooltips', array($this->view, 'add_tooltips'));

        /* If trying to save apply any errors passed back from options.php page */
        if($this->is_settings_page()) {
            add_filter('gfpdf_registered_settings', array('GFPDF\Stat\Stat_Options_API', 'highlight_errors'));            
        }        
    }

    /**
     * Display the settings page for Gravity PDF 
     * @since 4.0
     * @return void
     */
    public function displayPage() {

        /**
         * Determine which settings page to load
         */
        $page = (isset($_GET['tab'])) ? $_GET['tab'] : 'general';

        switch($page) {
          case 'general':
            $this->view->general();      
          break;

          case 'tools':
            $this->view->tools();
          break;

          case 'help':
            $this->view->help();      
          break;
        }      
    } 

    /**
     * Check if we are on the Gravity Form settings page
     * @param  string  $type Do we want to find a specific tab?
     * @return boolean       
     * @since  4.0
     */
    public function is_settings_page($type = '') {

        /* check we are on the PDF settings page */
        if(rgget('page') != 'gf_settings' || rgget('subview') != 'PDF') {
            return false;
        }

        /* if a type is passed in, check if we have a match */
        if(!empty($type)) {
            $page = (isset($_GET['tab'])) ? $_GET['tab'] : 'general';

            if($page != $type) {
                return false;
            }
        } 

        /* we are successful */
        return true;
    }
}
