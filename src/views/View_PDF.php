<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Model;
use GFPDF\Helper\Helper_View;
use GFPDF\Helper\Helper_Fields;
use GFPDF\Helper\Helper_Field_Container;
use GFPDF\Helper\Helper_PDF;

use GFPDF\Helper\Fields\Field_Products;

use GFPDF\Stat\Stat_Functions;

use GFFormsModel;
use GFCommon;
use GF_Field;

use mPDF;
use Exception;

/**
 * PDF View
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
 * View_PDF
 *
 * A general class for PDF display
 *
 * @since 4.0
 */
class View_PDF extends Helper_View
{

    /**
     * Set the view's name
     * @var string
     * @since 4.0
     */
    protected $ViewType = 'PDF';

    public function __construct($data = array()) {
        $this->data = $data;
    }

    /**
     * Our PDF Generator
     * @param  Array $entry    The Gravity Forms Entry to process
     * @param  Array $settings The Gravity Form PDF Settings
     * @return void
     * @since 4.0
     */
    public function generate_pdf($entry, $settings) {

        /**
         * Load our arguments that should be accessed by our PDF template
         * @var array
         */
        $args = Stat_Functions::get_template_args($entry, $settings);

        /**
         * Set out our PDF abstraction class
         */
        $pdf = new Helper_PDF($entry, $settings);

        try {
            $pdf->init();
            $pdf->renderHtml($args);

            /* set display type */
            if(rgget('download')) {
                $pdf->setOutputType('download');
            }

            /* Generate PDF */
            $pdf->generate();
        } catch(Exception $e) {
            /* Log Error */
        }
    }

    /**
     * Save the PDF to our tmp directory
     * @param  String $pdf      The generated PDF to be saved
     * @param  String $filename The PDF filename
     * @param  Array $settings The Gravity PDF Settings
     * @return Mixed           The full path to the file or false if failed
     * @since  4.0
     */
    public function savePDF($pdf, $filename, $entry) {
        global $gfpdf;

        $path = $gfpdf->data->template_tmp_location . '/' . $entry['form_id'] . $entry['id'] . '/';

        /* create our path */
        if(wp_mkdir_p($path)) {
            /* save our PDF */
            if(file_put_contents($path . $filename, $pdf)) {
                return $path . $filename;
            }
        }

        return false;
    }



    /**
     * Ensure a PHP extension is added to the end of the template name
     * @param  String $name The PHP template
     * @return String
     * @since  4.0
     */
    public function get_template_filename($name) {
        if(substr($name, -4) !== '.php') {
            $name = $name . '.php';
        }

        return $name;
    }

    /**
     * Start the PDF HTML Generation Process
     * @param  Array $entry  The Gravity Forms Entry Array
     * @param  Array  $config Any configuration data passed in
     * @return String         The generated HTML
     * @since 4.0
     */
    public function process_html_structure($entry, Helper_Model $model, $config = array()) {
        /* Determine whether we should output or return the results */
        $config['meta'] = (isset($config['meta'])) ? $config['meta'] : array();
        $echo           = (rgar($config, 'echo')) ? rgar($config, 'echo') : true; /* whether to output or return the generated markup. Default is echo */

        if(!$echo) {
            ob_start();
        }

        /* Generate the markup */
        ?>

        <div id="container">
            <?php $this->generate_html_structure($entry, $model, $config); ?>
        </div>

        <?php

        if(!$echo) {
            return ob_get_clean();
        }
    }

    /**
     * Build our HTML structure
     * @param  Array $entry  The Gravity Forms Entry Array
     * @param  Array  $config Any configuration data passed in
     * @return String         The generated HTML
     * @since 4.0
     */
    public function generate_html_structure($entry, Helper_Model $model, $config = array()) {

        /* Set up required variables */
        $form                           = GFFormsModel::get_form_meta($entry['form_id']);
        $products                       = new Field_Products($entry);
        $has_products                   = false;
        $page_number                    = 0;
        $container                      = new Helper_Field_Container();
        
        /* Allow the config to be changed through a filter */
        $config['meta']                 = (isset($config['meta'])) ? $config['meta'] : array();
        $config                         = apply_filters('gfpdf_pdf_configuration', $config, $entry, $form);
        
        /* Get the user configuration values */
        $skip_marked_fields             = (rgar($config['meta'], 'exclude')) ? rgar($config['meta'], 'exclude') : true; /* whether we should exclude fields with a CSS value of 'exclude'. Default to true */
        $skip_hidden_fields             = (rgar($config['meta'], 'hidden')) ? rgar($config['meta'], 'hidden') : true; /* whether we should skip fields hidden with conditional logic. Default to true. */
        $show_title                     = (rgar($config['meta'], 'show_title')) ? rgar($config['meta'], 'show_title') : true; /* whether we should show the form title. Default to true */
        $show_page_names                = (rgar($config['meta'], 'page_names')) ? rgar($config['meta'], 'page_names') : false; /* whether we should show the form's page names. Default to false */
        $show_html_fields               = (rgar($config['meta'], 'html_field')) ? rgar($config['meta'], 'html_field') : false; /* whether we should show the form's html fields. Default to false */
        $show_individual_product_fields = (rgar($config['meta'], 'individual_products')) ? rgar($config['meta'], 'individual_products') : false; /* Whether to show individual fields in the entry. Default to false - they are grouped together at the end of the form */

        /* Display the form title, if needed */
        $this->show_form_title($show_title, $form);

        /* Loop through the fields and output or skip if needed */
        foreach($form['fields'] as $key => $field) {

            /* Load our page name, if needed */
            if($show_page_names === true && $field->pageNumber !== $page_number) {
                $this->display_page_name($page_number, $form);
                $page_number++;
            }

            /* Skip any fields with the css class 'exclude', if needed */
            if($skip_marked_fields !== false && strpos($field->cssClass, 'exclude')) {
                continue;
            }

            /* Skip over any hidden fields (usually by conditional logic), if needed */
            if($skip_hidden_fields === true && GFFormsModel::is_field_hidden($form, $field, array(), $entry )) {
                continue;
            }

            /* Skip over any product fields, if needed */
            if( $show_individual_product_fields === false && GFCommon::is_product_field($field->type) ) {
                $has_products = true;
                continue;
            }

            /* Skip HTML fields, if needed */
            if($show_html_fields === false && $field->type == 'html') {
                continue;
            }

            /**
             * Let's output our field
             */
            $this->process_field($field, $entry, $form, $config, $products, $container, $model);
        }

        /* correctly close / cleanup the HTML container if needed */
        $container->close();

        /* Output product table, if needed */
        if($has_products && !$products->is_empty()) {
            echo $products->html();
        }

    }

    /**
     * Handle our field loader and display the generated HTML
     * @param  GF_Field $field    The field to process
     * @param  Array $entry    The Gravity Form Entry
     * @param  Array $form     The Gravity Form Field
     * @param  Array $config   The user-passed configuration data
     * @param  Object $products A Field_Products Object
     * @param  Helper_Field_Container $container
     * @return void
     * @since 4.0
     */
    public function process_field($field, $entry, $form, $config, Field_Products $products, Helper_Field_Container $container, Helper_Model $model) {

       /*
        * Set up our configuration variables
        */
        $config['meta']           = (isset($config['meta'])) ? $config['meta'] : array();
        $show_empty_fields        = (rgar($config['meta'], 'empty')) ? rgar($config['meta'], 'empty') : false; /* whether to show empty fields or not. Default is false */
        $load_legacy_css          = (rgar($config['meta'], 'legacy_css')) ? rgar($config['meta'], 'legacy_css') : false; /* whether we should add our legacy field class names (v3.x.x) to our fields. Default to false */
        $show_section_description = (rgar($config['meta'], 'section_content')) ? rgar($config['meta'], 'section_content') : false; /* whether we should include a section breaks content. Default to false */

        $class = $model->get_field_class($field, $form, $entry, $products);

        /* Try and display our HTML */
        try {
            /* Only load our HTML if the field is NOT empty, or the $empty config option is true */
            if(!$class->is_empty() || $show_empty_fields === true) {
                /* Load our legacy CSS class names */
                if($load_legacy_css === true) {
                    $this->load_legacy_css($field);
                }

                /**
                 * Add CSS Ready Class Float Support to mPDF
                 * Open a HTML container if needed
                 */
                $container->generate($field);

                echo ($field->type !== 'section') ? $class->html() : $class->html($show_section_description);
            } else {
                /**
                 * Close our CSS Ready Class Row, if open
                 */
                $container->close();
            }

        } catch(Exception $e) {
            /**
             * TODO, would log this information
             */
            var_dump($e);
        }
    }

    /**
     * If enabled, we'll show the Gravity Form Title in the document
     * @param  Boolean $show_title Whether or not to show the title
     * @param  Array $form       The Gravity Form array
     * @return void
     * @since 4.0
     */
    public function show_form_title($show_title, $form) {
        /* Show the form title, if needed */
        if($show_title !== false): ob_start(); ?>
            <h3 id="form_title"><?php echo $form['title']?></h3>
        <?php endif;

        echo apply_filters('gfpdf_pdf_form_title_html', ob_get_clean(), $form);
    }


    /**
     * Our default template used a number of legacy classes.
     * To keep backwards compatible, we will manually assign when needed.
     * @param  GF_Field $field The Gravity Form Fields
     * @return void (classes are passed by reference)
     * @since 4.0
     */
    public function load_legacy_css(GF_Field $field) {
        static $counter = 1;

        /* Add odd / even rows */
        $field->cssClass = ($counter++%2) ? $field->cssClass . ' odd' : ' even';

        switch($field->type) {
            case 'html':
                $field->cssClass = $field->cssClass . ' entry-view-html-value';
            break;

            case 'section':
                $field->cssClass = $field->cssClass . ' entry-view-section-break-content';
            break;

            default:
                $field->cssClass = $field->cssClass . ' entry-view-field-value';
            break;
        }
    }

    /**
     * Output the current page name HTML
     * @param  Integer   $page  The current page number
     * @param  Array     $form  The form array
     * @return String           The page HTML output
     */
    public function display_page_name($page, $form) {
        /* Only display the current page name if it has changed (and it exists) */
        if(isset($form['pagination']['pages'][$page]) && strlen(trim($form['pagination']['pages'][$page])) > 0) {
            ob_start();
            ?>
                <h3 id="field-<?php echo $field->id; ?>" class="gfpdf-<?php echo $field->inputType; ?> gfpdf-field <?php echo $field->cssClass; ?>">
                    <?php echo $form['pagination']['pages'][$page]; ?>
                </h3>
            <?php
            echo apply_filters('gfpdf_field_page_name_html', ob_get_clean(), $page, $field, $form);
        }
    }

}
