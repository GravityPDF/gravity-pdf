<?php

namespace GFPDF\Controller;
use GFPDF\Helper\Helper_Controller;
use GFPDF\Helper\Helper_Model;
use GFPDF\Helper\Helper_View;
use GFPDF\Helper\Helper_Int_Actions;
use GFPDF\Helper\Helper_Int_Filters;

/**
 * Form Settings (PDF Configuration) Controller
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
 * Controller_Form_Settings
 * Controls the individual form PDF settings pages
 *
 * @since 4.0
 */
class Controller_Form_Settings extends Helper_Controller implements Helper_Int_Actions, Helper_Int_Filters
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
        /* 
         * Tell Gravity Forms to add our form PDF settings pages         
         */ 
         $this->add_actions();
         $this->add_filters();       
    }

    /**
     * Apply any actions needed for the settings page
     * @since 4.0
     * @return void
     */
    public function add_actions() {
        global $gfpdf;

        /* Tell Gravity Forms to add our form PDF settings pages */
        add_action( 'gform_form_settings_menu', array( $this->model, 'add_form_settings_menu' ), 10, 2 );
        add_action( 'gform_form_settings_page_' . $gfpdf->data->slug, array( $this, 'displayPage' ) );

        /* Add AJAX endpoints */
        add_action('wp_ajax_gfpdf_list_delete', array( $this->model, 'delete_gf_pdf_setting'));
        add_action('wp_ajax_gfpdf_list_duplicate', array( $this->model, 'duplicate_gf_pdf_setting'));    
        add_action('wp_ajax_gfpdf_change_state', array( $this->model, 'change_state_pdf_setting'));            
    }

    /**
     * Apply any filters needed for the settings page
     * @since 4.0
     * @return void
     */
    public function add_filters() {

        /* Add Validation Errors */
        add_filter( 'gfpdf_form_settings', array($this->model, 'validation_error'));

        /* Enhance sanitize functionality */
        add_filter( 'gfpdf_settings_sanitize_text', 'wp_strip_all_tags');
    }

    /**
     * Processes / Setup the form settings page.
     * @since 4.0
     * @return void
     */
    public function displayPage() {        
        global $gfpdf;
        
        /* Determine whether to load the add/edit page, or the list view */
        $form_id = rgget( 'id' );
        $pdf_id  = rgget( 'pid' );

        /* Load the add/edit page */
        if ( ! rgblank( $pdf_id ) ) {
            if( rgpost('gfpdf_save_pdf')) {
                $this->model->process_submission($form_id, $pdf_id);
            }

            $this->model->show_edit_view($form_id, $pdf_id);
            return;
        }

        /* process list view */
        $this->model->process_list_view($form_id);                     
    }    
}
