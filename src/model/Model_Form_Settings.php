<?php

namespace GFPDF\Model;
use GFPDF\Helper\Helper_Model;
use GFPDF\Helper\Helper_PDF_List_Table;
use GFPDF\Stat\Stat_Options_API;
use RGFormsModel;
use GFFormsModel;
use GFCommon;
use WP_Error;

/**
 * Settings Model
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
 * Model_Welcome_Screen
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class Model_Form_Settings extends Helper_Model {

    /**
     * Add the form settings tab.
     *
     * Override this function to add the tab conditionally.
     * @param $tabs array The list of existing tags
     * @param $form_id integer The current form ID
     * @return Array modified list of $tabs
     * @since 4.0
     */
    public function add_form_settings_menu( $tabs, $form_id ) {
        global $gfpdf;
        $tabs[] = array( 'name' => $gfpdf->data->slug, 'label' => $gfpdf->data->short_title, 'query' => array( 'pid' => null ) );
        return $tabs;
    }  

    /**
     * Setup the PDF Settings List View Logic
     * @param  Integer $form_id The Gravity Form ID
     * @return void 
     * @since 4.0
     */
    public function process_list_view($form_id) {
        global $gfpdf;
        $controller = $this->getController();

        /* get the form object */
        $form = RGFormsModel::get_form_meta( $form_id );

        /* load our list table */
        $pdf_table = new Helper_PDF_List_Table( $form );
        $pdf_table->prepare_items();        

        /* pass to view */
        $controller->view->list(array(
            'title'       => $gfpdf->data->title,
            'add_new_url' => $add_new_url = add_query_arg( array( 'pid' => 0 ) ),
            'list_items'  => $pdf_table,
        ));                
    }  

    /**
     * Setup the PDF Settings Add/Edit View Logic
     * @param  Integer $form_id The Gravity Form ID
     * @param  Integer $pdf_id The PDF configuration ID
     * @return void 
     * @since 4.0
     */
    public function show_edit_view($form_id, $pdf_id) {    
        global $gfpdf;

        $controller = $this->getController();

        /* get the form object */
        $form = RGFormsModel::get_form_meta( $form_id );     

        /* parse input and get required information */
        if(!$pdf_id) {
            if(rgpost('gform_pdf_id')) {
                $pdf_id = rgpost('gform_pdf_id');
            } else {
                $pdf_id = uniqid();
            }
        }      

        /* prepare our data */
        $label = $pdf_id ? __( 'Update PDF', 'pdfextended' ) : __( 'Save PDF', 'pdfextended' );

        /* pass to view */
        $controller->view->add_edit(array(
            'pdf_id'       => $pdf_id,
            'title'        => $label, 
            'button_label' => $label, 
            'form'         => $form,    
        ));         
    }

    /**
     * Get Form Settings
     *
     * Retrieves all form PDF settings
     *
     * @since 4.0
     * @return array GFPDF settings
     */
    public function get_settings($form_id) {
        global $gfpdf;

        $form_id = (int) $form_id;
        if( (int) $form_id === 0) {
            return new WP_Error('invalid_id', __('You must pass in a valid form ID', 'pdfextended'));
        }
        
        /* If we haven't pulled the form meta data from the database do so now */
        if(!isset($gfpdf->data->form_settings[$form_id])) {
            $form     = GFFormsModel::get_form_meta( $form_id );          
            $settings = (isset($form['gfpdf_form_settings'])) ? $form['gfpdf_form_settings'] : array();
            $gfpdf->data->form_settings[$form_id] = apply_filters( 'gfpdf_get_form_settings', $settings );          
        }

        /* return the form meta data */
        return $gfpdf->data->form_settings[$form_id];
    }    

    /**
     * Get pdf config
     *
     * Looks to see if the specified setting exists, returns default if not
     *
     * @since 4.0
     * @return mixed
     */
    public function get_pdf( $form_id, $pdf_id ) {
        $gfpdf_options = $this->get_settings($form_id);
        $value         = ! empty( $gfpdf_options[ $pdf_id ] ) ? $gfpdf_options[ $pdf_id ] : array();
        return apply_filters( 'gfpdf_pdf_config', $value, $key, $default );

    }

    /**
     * Update an pdf config
     *
     * Updates a Gravity PDF setting value in both the db and the global variable.
     * Warning: Passing in an empty, false or null string value will remove
     *          the key from the gfpdf_options array.
     *
     * @since 3.8
     * @param string $key The Key to update
     * @param string|bool|int $value The value to set the key to
     * @return boolean True if updated, false if not.
     */
    public function update_pdf( $form_id, $pdf_id, $value = '', $update_db = true ) {

        if ( empty( $value ) || ! is_array($value) || sizeof($value) == 0 ) {
            $remove_option = $this->delete_pdf( $key );
            return $remove_option;
        }

        /* First let's grab the current settings */
        $options = $this->get_settings($form_id);

        if(! is_wp_error($options)) {
            /* Let's let devs alter that value coming in */
            $value = apply_filters( 'gfpdf_form_update_pdf', $value, $key );

            if(!is_array($options[ $pdf_id ])) {
                $options[ $pdf_id ] = array();
            }

            /* Next let's try to update the value */
            $options[ $pdf_id ] = $value;

            /* get the up-to-date form object and merge in the results */
            $form = GFFormsModel::get_form_meta($form_id);

            /* Update our GFPDF settings */
            $form['gfpdf_form_settings'] = $options;
            
            if($update_db) {
                /* update the database, if able */
                $did_update = GFFormsModel::update_form_meta($form_id, $form);
            }
            
            /* If it updated, let's update the global variable */
            if ( $did_update !== false || !$update_db ){
                global $gfpdf;     
                $gfpdf->data->form_settings[$form_id] = $options;

            }

            return $did_update;
        }

        return false;
    }

    /**
     * Remove an option
     *
     * Removes an Gravity PDF setting value in both the db and the global variable.
     *
     * @since 4.0
     * @param string $key The Key to delete
     * @return boolean True if updated, false if not.
     */
    public function delete_pdf( $form_id, $pdf_id ) {

        /* First let's grab the current settings */
        $options = $this->get_settings($form_id);

        if(! is_wp_error($options)) {

            /* Next let's try to update the value */
            if( isset( $options[ $pdf_id ] ) ) {
                unset( $options[ $pdf_id ] );
            }

            /* get the form and merge in the results */
            $form = GFFormsModel::get_form_meta($form_id);

            /* Update our GFPDF settings */
            $form['gfpdf_form_settings'] = $options;            

            /* update the database, if able */
            $did_update = GFFormsModel::update_form_meta($form_id, $form);

            /* If it updated, let's update the global variable */
            if ( $did_update !== false ) {
                global $gfpdf;        
                $gfpdf->data->form_settings[$form_id] = $options;
            }

            return $did_update;
        }
    }    

    /**
     * Validate, Sanatize and Update PDF settings
     * @param  Integer $form_id The Gravity Form ID
     * @param  Integer $pdf_id The PDF configuration ID
     * @return void 
     * @since 4.0
     */
    public function process_submission($form_id, $pdf_id) {
        global $gfpdf;

        /* Check Nonce is valid */
        if( ! wp_verify_nonce( $_POST['gfpdf_save_pdf'], 'gfpdf_save_pdf' ) ) {
             GFCommon::add_error_message( __( 'There was a problem saving your PDF settings. Please try again.', 'pdfextneded' ) );
             return false;    
        }           

        /* Check if we have a new PDF ID */
        if(empty($pdf_id)) {
            $pdf_id = (rgpost('gform_pdf_id')) ? rgpost('gform_pdf_id') : false;
        }

        $input = rgpost('gfpdf_settings');

        /* check appropriate settings */
        if(!is_array($input) || !$pdf_id) {
             GFCommon::add_error_message( __( 'There was a problem saving your PDF settings. Please try again.', 'pdfextneded' ) );
             return false;           
        }   

        $sanitized = $this->settings_sanitize($input);

        
        /* Update our GFPDF settings */
        $sanitized['id']     = $pdf_id;
        $sanitized['active'] = true;

        $this->update_pdf($form_id, $pdf_id, $sanitized, false);

        /* Do validation */
        if(empty($sanitized['name']) || empty($sanitized['filename'])) {

            GFCommon::add_error_message( __( 'PDF could not be saved. Please enter all required information below.', 'pdfextended' ) );
            return false;
        }

        /* get the form and merge in the results */
        $form = GFFormsModel::get_form_meta($form_id);

        /* Update our GFPDF settings */
        $form['gfpdf_form_settings'][$pdf_id] = $sanitized;        

        /* Update database */
        $did_update = GFFormsModel::update_form_meta($form_id, $form);

        /* If it updated, let's update the global variable */
        if ( $did_update !== false ){                                
            GFCommon::add_message( sprintf( __( 'PDF saved successfully. %sBack to PDF list.%s', 'pdfextended' ), '<a href="' . remove_query_arg( 'pid' ) . '">', '</a>' ) );
            return true;
        }

        GFCommon::add_error_message( __( 'There was a problem saving your PDF settings. Please try again.', 'pdfextneded' ) );
        return false;
    }

    /**
     * Apply gfield_error class when validation fails, highlighting field blocks with problems
     * @param  array $fields Array of fields to process
     * @return array         Modified list of fields     
     * @since 4.0
     */
    public function validation_error($fields) {
        /* Check Nonce is valid */
        if( empty($_POST['gfpdf_save_pdf']) || ! wp_verify_nonce( $_POST['gfpdf_save_pdf'], 'gfpdf_save_pdf' ) ) {            
            return $fields;
        }       

        $input = rgpost('gfpdf_settings');

        /* throw errors on required fields */
       foreach($fields as $key => &$field) {
            if($field['required']) {
                $value = (isset($input[$field['id']])) ? apply_filters( 'gfpdf_settings_sanitize_text', $input[$field['id']], $key)  : '';
                if(empty($value)) {
                    $field['class'] .= $field['class'] . ' gfield_error' ;
                }
            }
        }

        return $fields;
    }

    /**
     * Similar to GFPDF\Stat\Stat_Options_API::settings_sanitize() except we are storing/processing values 
     * in Gravity Forms meta table  
     * @param  array  $input Fields to process
     * @return array         Sanitized fields
     * @return void 
     * @since 4.0
     */
    private function settings_sanitize($input = array()) {

        $settings = Stat_Options_API::get_registered_settings();
        $sections = array('form_settings', 'form_settings_appearance', 'form_settings_advanced');

        foreach($sections as $s) {
            $input = apply_filters( 'gfpdf_settings_'. $s .'_sanitize', $input );    
        }                

        /* Loop through each setting being saved and pass it through a sanitization filter */
        foreach ( $input as $key => $value ) {

            foreach($sections as $s) {
                $type = isset( $settings[$s][$key]['type'] ) ? $settings[$s][$key]['type'] : false;    

                if ( $type ) {
                    /* Field type specific filter */
                    $input[$key] = apply_filters( 'gfpdf_settings_sanitize_' . $type, $value, $key );
                }                
            }        

            /* General filter */
            $input[$key] = apply_filters( 'gfpdf_settings_sanitize', $input[$key], $key );
        }

        return $input;        
    }

    /**
     * AJAX Endpoint for deleting PDF Settings
     * @param $_POST['nonce'] a valid nonce 
     * @param $_POST['fid'] a valid form ID
     * @param $_POST['pid'] a valid PDF ID
     * @return JSON 
     * @since 4.0
     */
    public function delete_gf_pdf_setting() {
        /*
         * Validate Endpoint 
         */
        $nonce = $_POST['nonce'];
        $fid   = (int) $_POST['fid'];
        $pid   = $_POST['pid'];

        $nonce_id = "gfpdf_delete_nonce_{$fid}_{$pid}";

        if(! wp_verify_nonce( $nonce, $nonce_id )) {
            /* fail */
            header('HTTP/1.1 401 Unauthorized');
            exit;
        }

        $results = $this->delete_pdf($fid, $pid);

        if($results) {
            $return = array(
                'msg' => __('PDF successfully deleted.', 'pdfextended'),
            );

            echo json_encode($return);
            exit;
        }

        header('HTTP/1.1 500 Internal Server Error');
        exit;
    }

    /**
     * AJAX Endpoint for duplicating PDF Settings
     * @param $_POST['nonce'] a valid nonce 
     * @param $_POST['fid'] a valid form ID
     * @param $_POST['pid'] a valid PDF ID
     * @return JSON 
     * @since 4.0
     */
    public function duplicate_gf_pdf_setting() {
        /*
         * Validate Endpoint 
         */
        $nonce = $_POST['nonce'];
        $fid   = (int) $_POST['fid'];
        $pid   = $_POST['pid'];

        $nonce_id = "gfpdf_duplicate_nonce_{$fid}_{$pid}";

        if(! wp_verify_nonce( $nonce, $nonce_id )) {
            /* fail */
            header('HTTP/1.1 401 Unauthorized');
            exit;
        }

        $config = $this->get_pdf($fid, $pid);
        $config['id']   = uniqid();
        $config['name'] = $config['name'] . ' (copy)';

        $results = $this->update_pdf($fid, $config['id'], $config);

        if($results) {
            $dup_nonce   = wp_create_nonce("gfpdf_duplicate_nonce_{$fid}_{$config['id']}");
            $del_nonce   = wp_create_nonce("gfpdf_delete_nonce_{$fid}_{$config['id']}");
            $state_nonce = wp_create_nonce("gfpdf_state_nonce_{$fid}_{$config['id']}");

            $return = array(
                'msg'         => __('PDF successfully duplicated.', 'pdfextended'),
                'pid'         => $config['id'],
                'name'        => $config['name'],
                'dup_nonce'   => $dup_nonce,
                'del_nonce'   => $del_nonce,
                'state_nonce' => $state_nonce,
            );

            echo json_encode($return);
            exit;
        }

        header('HTTP/1.1 500 Internal Server Error');
        exit;
    }    

    /**
     * AJAX Endpoint for changing the PDF Settings state
     * @param $_POST['nonce'] a valid nonce 
     * @param $_POST['fid'] a valid form ID
     * @param $_POST['pid'] a valid PDF ID     
     * @return JSON 
     * @since 4.0
     */
    public function change_state_pdf_setting() {
        /*
         * Validate Endpoint 
         */
        $nonce    = $_POST['nonce'];
        $fid      = (int) $_POST['fid'];
        $pid      = $_POST['pid'];
        $nonce_id = "gfpdf_state_nonce_{$fid}_{$pid}";

        if(! wp_verify_nonce( $nonce, $nonce_id )) {
            /* fail */
            header('HTTP/1.1 401 Unauthorized');
            exit;
        }

        $config = $this->get_pdf($fid, $pid);

        /* toggle state */
        $config['active'] = ($config['active'] === true) ? false : true;
        $state            = ($config['active']) ? __( 'Active', 'pdfextended' ) : __( 'Inactive', 'pdfextended' );
        $src              = GFCommon::get_base_url() . '/images/active' . intval( $config['active'] ) . '.png';

        $results = $this->update_pdf($fid, $config['id'], $config);

        if($results) {
            $return = array(
                'state' => $state,
                'src'   => $src,
            );

            echo json_encode($return);
            exit;
        }

        header('HTTP/1.1 500 Internal Server Error');
        exit;
    }    
}