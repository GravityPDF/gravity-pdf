<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_View;
use GFPDF\Helper\Fields\Field_Default;
use GFPDF\Helper\Fields\Field_Product;
use GFPDF\Helper\Fields\Field_Products;
use GFPDF\Stat\Stat_functions;
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
        global $gfpdf;

        $paper_size = 'A4';
        $orientation = '';

        $mpdf = new mPDF('', $paper_size, 0, '', 15, 15, 16, 16, 9, 9, $orientation);

        /* set up contstants for gravity forms to use so we can override the security on the printed version */
        $template = (isset($_GET['template'])) ? $_GET['template'] : '';
        $html     = '';

        /**
         * Load our arguments that should be accessed by our view
         * @var array
         */
        $args = array(
            'form_id'   => $entry['form_id'], /* backwards compat */
            'lead_ids'  => array($entry['id']), /* backwards compat */
            'lead_id'   => $entry['id'], /* backwards compat */
            
            'form'      => GFFormsModel::get_form_meta($entry['form_id']),
            'entry'     => $entry,
            'lead'      => $entry,
            'form_data' => '',
        );

        if(file_exists( $gfpdf->data->template_site_location . $template)) {
            $html = $this->load($template, $args, false, $gfpdf->data->template_site_location);
        }

        if(isset($_GET['html'])) {
            echo $html; exit;
        }

        $mpdf->WriteHTML($html);

        $mpdf->Output('test.pdf', 'I');
        exit;
    }

    /**
     * Build our HTML structure
     * @param  Array $entry  The Gravity Forms Entry Array
     * @param  Array  $config Any configuration data passed in
     * @return String         The generated HTML
     */
    public function generate_html_structure($entry, $config = array()) {

        /* Set up required variables */
        $form         = GFFormsModel::get_form_meta($entry['form_id']);
        $products     = new Field_Products($entry);
        $has_products = false;
        
        /* get the user configuration values */
        $echo                           = (rgar($config, 'echo')) ? rgar($config, 'echo') : true;
        $show_empty_fields              = (rgar($config, 'empty')) ? rgar($config, 'empty') : false;
        $skip_marked_fields             = (rgar($config, 'exclude')) ? rgar($config, 'exclude') : true;
        $skip_hidden_fields             = (rgar($config, 'hidden')) ? rgar($config, 'hidden') : true;
        $show_individual_product_fields = (rgar($config, 'individual_products')) ? rgar($config, 'individual_products') : false;
        $load_legacy_css                = (rgar($config, 'legacy_css')) ? rgar($config, 'legacy_css') : false;
        $show_title                     = (rgar($config, 'show_title')) ? rgar($config, 'show_title') : true;
        $show_section_description       = (rgar($config, 'section_content')) ? rgar($config, 'section_content') : false;

        /* check if we should return the HTML, or echo it */
        if($echo === false) {
            ob_start();
        }

        ?>

        <div id="container">

            <?php if($show_title !== false): /* Show the form title, if needed */ ?>
                    <h3 id="form_title"><?php echo $form['title']?></h3>
            <?php endif; ?>

        <?php

        foreach($form['fields'] as $field) {

            /* Skip any fields with the css class 'exclude' */
            if($css_exclude !== false && strpos($field->cssClass, 'exclude')) {
                continue;
            }

            /* Skip over any hidden fields (usually by conditional logic) */
            if($skip_hidden_fields === true && GFFormsModel::is_field_hidden($form, $field, array(), $entry )) {
                continue;
            }

            /* Skip over any product fields */
            if( $show_individual_product_fields === false && GFCommon::is_product_field($field->type) ) {
                $has_products = true;
                continue;
            }

            /* Load our legacy CSS class names */
            if($load_legacy_css === true) {
                $this->load_legacy_css($field);
            }

            ?>
            
            

                <?php

                    /* Try and load a class based on the field type */
                    $class_name = Stat_functions::get_field_class($field->type);
                   
                    try {
                        /* check load our class */
                        if(class_exists($class_name)) {

                            $is_product = $this->is_product_field($field);

                            /* Product fields are handled through a single function */
                            if($show_individual_product_fields && $is_product) {
                                $class = new Field_Product($field, $entry, $products);
                            } else if (! $is_product) {
                                $class = new $class_name($field, $entry);
                            }

                        } else {
                            throw new Exception('Class not found');
                        }
                    } catch(Exception $e) {
                        /* Exception thrown. Load generic field loader */
                        $class = new Field_Default($field, $entry);
                    }

                    /* Try and display our HTML */
                    try {

                        /* Only load our HTML if the field is NOT empty, or the $empty config option is true */
                        if(!$class->is_empty() || $show_empty_fields === true) {
                            echo ($field->type !== 'section') ? $class->html() : $class->html($show_section_description);
                        }

                    } catch(Exception $e) {
                        var_dump($e);
                    }

                ?>
            <?php
        }

        /* Output Product table if needed */
        if($has_products && !$products->is_empty()) {
            echo $products->html();
        }

        ?>

        </div><!-- close #container -->

        <?php

        /* return the output, if needed */
        if($echo === false) {
            return ob_get_clean();
        }
    }

    /**
     * Check if the field being passed is a product field
     * @param  GF_Field $field The Gravity Form Fields
     * @return Boolean
     * @since 4.0
     */
    public function is_product_field(GF_Field $field) {

        $products = array(
            'product',
            'option',
            'quantity',
            'shipping',
            'total',
        );

        if(in_array($field->type, $products)) {
            return true;
        }

        return false;
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
}
