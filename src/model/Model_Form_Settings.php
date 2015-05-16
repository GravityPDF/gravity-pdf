<?php

namespace GFPDF\Model;
use GFPDF\Helper\Helper_Model;
use GFPDF\Helper\Helper_PDF_List_Table;
use RGFormsModel;

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
    public function process_edit_view($form_id, $pdf_id) {    
        global $gfpdf;

        $controller = $this->getController();

        /* parse input and get required information */
        $pdf_id = (int) $pdf_id;

        /* prepare our data */
        $label = $pdf_id ? __( 'Update PDF', 'pdfextended' ) : __( 'Save PDF', 'pdfextended' );

        /* pass to view */
        $controller->view->add_edit(array(
            'pdf_id'       => $gfpdf->data->title,
            'title'        => $label, 
            'button_label' => $label,          
        ));         
    }
}