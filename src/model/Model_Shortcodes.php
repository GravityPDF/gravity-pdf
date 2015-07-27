<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Model;
use GFPDF\Model\Model_Form_Settings;
use GFPDF\Model\Model_PDF;

use GFAPI;

/**
 * PDF Shortcode Model
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
 *
 * Handles all the PDF Shortcode logic
 * @since 4.0
 */
class Model_Shortcodes extends Helper_Model {

    /**
     * Generates a direct link to the PDF that should be generated
     * If placed in a confirmation the appropriate entry will be displayed.
     * A user also has the option to pass in an "entry" parameter to define the entry ID
     * @param Array $attributes The shortcode attributes specified
     * @return void
     * @since 4.0
     */
    public function gravitypdf($attributes) {

        $controller = $this->getController();

        /* merge in any missing defaults */
        $attributes = shortcode_atts(array(
            'id'      => '',
            'text'    => 'Download PDF',
            'type'    => 'download',
            'classes' => 'gravitypdf-download-link',
            'entry'   => '',
        ), $attributes, 'gravitypdf');

        $attributes = apply_filters('gfpdf_gravityforms_shortcode_attributes', $attributes);

        /* Add Shortcake preview support */
        if ( isset($_POST['shortcode']) ) {
            $attributes['url'] = '#';
            return $controller->view->display_gravitypdf_shortcode($attributes);
        }
        
        /* Check if we have an entry ID, otherwise check the GET and POST data */
        if(empty($attributes['entry'])) {
            if(isset($_GET['lid']) || isset($_GET['entry'])) {
                $attributes['entry'] = (isset($_GET['lid'])) ? (int) $_GET['lid'] : (int) $_GET['entry'];
            } else {
                return $controller->view->no_entry_id();
            }
        }

        /* Check if we have a valid PDF configuration */
        $settings    = new Model_Form_Settings();
        $entry  = GFAPI::get_entry($attributes['entry']);
        $config = (!is_wp_error($entry)) ? $settings->get_pdf($entry['form_id'], $attributes['id']) : $entry; /* if invalid entry a WP_Error will be thrown */

        if(is_wp_error($config)) {
            return $controller->view->invalid_pdf_config();
        }

        /* everything looks valid so let's get the URL */
        $pdf = new Model_PDF();
        $pdf_url = $pdf->get_pdf_url($attributes['id'], $attributes['entry']);
        $attributes['url'] = ($attributes['type'] == 'download') ? $pdf_url . '?download=1' : $pdf_url;

        /* generate the markup and return */
        return $controller->view->display_gravitypdf_shortcode($attributes);
    }

    /**
     * Update our Gravity Forms "Text" Confirmation Shortcode to include the current entry ID
     * @param  String $confirmation The confirmation text
     * @param  Array $form         The Gravity Form array
     * @param  Array $lead         The Gravity Form entry information
     * @return Array               The confirmation text
     * @since 4.0
     */
    public function gravitypdf_confirmation($confirmation, $form, $lead) {
        
        /* check if confirmation is text-based and our shortcode exists */
        if ( ! is_array( $confirmation ) ) {
            /* check if our shortcode exists and add the entry ID if needed */
            $gravitypdf = $this->get_shortcode_information( 'gravitypdf', $confirmation );

            if(sizeof($gravitypdf) > 0) {
                foreach($gravitypdf as $shortcode) {
                    /* if the user hasn't explicitely defined an entry to display... */
                    if( !isset($shortcode['attr']['entry'])) {
                        /* get the new shortcode information */
                        $new_shortcode = $this->add_shortcode_attr($shortcode, 'entry', $lead['id']);

                        /* update our confirmation message */
                        $confirmation = str_replace($shortcode['shortcode'], $new_shortcode['shortcode'], $confirmation);
                    }
                }
            }
        }

        return $confirmation;
    }


    /**
     * Update a shortcode attributes
     * @param Array $code  In individual shortcode array pulled in from the $this->get_shortcode_information() function
     * @param String $attr  The attribute to add / replace
     * @param String $value The new attribute value
     */
    public function add_shortcode_attr($code, $attr, $value) {
        /* if the attribute doesn't already exist... */
        if( !isset($code['attr'][$attr])) {
            $code['shortcode'] = str_ireplace($code['attr_raw'], "{$code['attr_raw']} {$attr}=\"{$value}\"", $code['shortcode']);
        } else { /* replace the current attribute */
            $pattern = $attr . '="(.+?)"';
            $code['shortcode'] = preg_replace("/$pattern/si", $attr . '="' . $value . '"', $code['shortcode']);
        }

        return $code;
    }

    /**
     * Check if a currently submitting a new confirmation redirect URL in the admin area,
     * if so replace any shortcodes with a direct link to the PDF (as Gravity Forms correctly validates the URL)
     * @param  Array $form Gravity Form Array
     * @return Array
     * @since 4.0
     */
    public function gravitypdf_redirect_confirmation($form) {
        
        /* check if the confirmation is currently being saved */
        if(isset($_POST['form_confirmation_url'])) {
            $url = stripslashes_deep($_POST['form_confirmation_url']);

             /* check if our shortcode exists and convert it to a URL */
            $gravitypdf = $this->get_shortcode_information( 'gravitypdf', $url );

            if(sizeof($gravitypdf) > 0) {
                foreach($gravitypdf as $code) {
                    /* get the PDF Settings ID */
                    $pid = (isset($code['attr']['id'])) ? $code['attr']['id'] : '';

                    if(! empty($pid)) {

                        /* generate the PDF URL */
                        $pdf = new Model_PDF();
                        $pdf_url = $pdf->get_pdf_url($pid, '{entry_id}', false);

                        /* check if the PDF should be auto-prompted to download, or whether the user should view it in the browser */
                        if(! isset($code['attr']['attr']) || $code['attr']['attr'] == 'download') {
                            $pdf_url .= '?download=1';
                        }

                        /* override the confirmation URL submitted */
                        $_POST['form_confirmation_url'] = str_replace($code['shortcode'], $pdf_url, $url);
                    }
                }
            }
        }
        
        /* it's a filter so return the $form array */
        return $form;
    }

    /**
     * Search for any shortcodes in the text and return any matches
     * @param  String $shortcode The shortcode to search for
     * @param  String $text      The text to search in
     * @return Array             The shortcode information
     * @since 4.0
     */
    public function get_shortcode_information( $shortcode, $text ) {
        $shortcodes = array();

        if( has_shortcode( $text, $shortcode )) {

            /* out shortcode exists, so check if "entry" exists and override, otherwise add it */
            $pattern = get_shortcode_regex();
            preg_match_all("/$pattern/s", $text, $matches);

            if(!empty($matches) && isset($matches[2])) {
                foreach($matches[2] as $key => $code) {
                    if($code == $shortcode) {
                        $shortcodes[] = array(
                            'shortcode' => $matches[0][$key],
                            'attr_raw'  => $matches[3][$key],
                            'attr'      => shortcode_parse_atts($matches[3][$key]),
                        );
                    }
                }
            }
        }
        return $shortcodes;
    }
}