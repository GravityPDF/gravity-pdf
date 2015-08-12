<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Model;

use GFCommon;

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
class Model_Settings extends Helper_Model {

    /**
     * Errors with the global form submission process are stored here
     * @var Array
     * @since 4.0
     */
    public $form_settings_errors;

    /**
     * Get the form setting error and remove any duplicates
     * @since 4.0
     * @return  void
     */
    public function setup_form_settings_errors() {

        /* set up a place to access form setting validation errors */
        $this->form_settings_errors = get_transient( 'settings_errors' );

        /* remove multiple errors for a single form */
        if($this->form_settings_errors) {
            $set                    = false;
            $updated_settings_error = array();

            /* loop through current errors */
            foreach($this->form_settings_errors as $error) {
                if($error['setting'] != 'gfpdf-notices' || !$set) {
                    $updated_settings_error[] = $error;
                }

                if($error['setting'] == 'gfpdf-notices') {
                    $set = true;
                }
            }
            /* update transient */
            set_transient( 'settings_errors', $updated_settings_error, 30 );
        }
    }

    /**
     * If any errors have been passed back from the options.php page we will highlight them
     * @param  Array $settings The get_registered_settings() array
     * @return Array
     * @since 4.0
     */
    public function highlight_errors($settings) {
        
        /* we fire too late to tap into get_settings_error() so our data storage holds the details */
        $errors = $this->form_settings_errors;

        /* loop through errors if any and highlight the appropriate settings */
        if(is_array($errors) && sizeof($errors) > 0) {
            foreach($errors as $error) {
                /* exit if not an error */
                if($error['type'] !== 'error') {
                    continue;
                }

                /* loop through our data until we find a match */
                $found = false;
                foreach($settings as $key => &$group) {
                    foreach($group as $id => &$item) {
                        if($item['id'] === $error['code']) {
                            $item['class'] = (isset($item['class'])) ? $item['class'] . ' gfield_error' : 'gfield_error';
                            $found = true;
                            break;
                        }
                    }

                    /* exit outer loop */
                    if($found) {
                        break;
                    }
                }
            }
        }

        return $settings;
    }

    /**
     * Install the files stored in /initialisation/template/ to the user's template directory
     * @return Boolean
     * @since 4.0
     */
    public function install_templates() {
        global $gfpdf;

        if( ! $gfpdf->misc->copyr(PDF_PLUGIN_DIR . 'initialisation/templates/', $gfpdf->data->template_location) ) {
            $gfpdf->notices->add_error(sprintf(__('There was a problem copying all PDF templates to %s. Please try again.', 'gravitypdf'), '<code>' . $gfpdf->misc->relative_path($gfpdf->data->template_location) . '</code>'));
            return false;
        }

        $gfpdf->notices->add_notice(sprintf(__('Gravity PDF Custom Templates successfully installed to %s.', 'gravitypdf'), '<code>' . $gfpdf->misc->relative_path($gfpdf->data->template_location) . '</code>'));
        $gfpdf->options->update_option('custom_pdf_template_files_installed', true);
        return true;
    }

    /**
     * AJAX Endpoint for saving the custom font
     * @return void
     * @since 4.0
     */
    public function save_font() {

        /* prevent unauthorized access */
        $this->ajax_font_validation();

        /* Handle the validation and saving of the font */
        $results = $this->process_font($_POST['payload']);

        /* If we reached this point the results were successful so return the new object */
        echo json_encode($results);
        wp_die();
    }

    /**
     * AJAX Endpoint for deleting a custom font
     * @return void
     * @since 4.0
     */
    public function delete_font() {
        global $gfpdf;
       
        /* prevent unauthorized access */
        $this->ajax_font_validation();

        /* Get the required details for deleting fonts */
        $id    = $_POST['id'];
        $fonts = $gfpdf->options->get_option('custom_fonts');

        /* Check font actually exists and remove */
        if(isset($fonts[$id])) {

            if($this->remove_font_file($fonts[$id])) {
                unset($fonts[$id]);

                if($gfpdf->options->update_option('custom_fonts', $fonts)) {
                    /* Success */
                    echo json_encode(array('success' => true));
                    wp_die();
                }
            }
        }

        header('HTTP/1.1 400 Bad Request');

        $return = array(
            'error' => __('Could not delete Gravity PDF font correctly. Please try again.', 'gravitypdf')
        );

        echo json_encode($return);
        wp_die();
    }

    /**
     * Check a user is authorized to make modifications via this endpoint and
     * that there is a valid nonce
     * @return void
     * @since  4.0
     */
    private function ajax_font_validation() {
        /* prevent unauthorized access */
        if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_settings' ) ) {
            /* fail */
            header('HTTP/1.1 401 Unauthorized');
            wp_die('401');
        }

        /*
         * Validate Endpoint
         */
        $nonce    = $_POST['nonce'];
        $nonce_id = 'gfpdf_font_nonce';

        if(! wp_verify_nonce( $nonce, $nonce_id )) {
            /* fail */
            header('HTTP/1.1 401 Unauthorized');
            wp_die('401');
        }
    }

    /**
     * Removes the current font's TTF or OTF files from our font directory
     * @param  Array $fonts The font config
     * @return Boolean        True on success, false on failure
     * @since  4.0
     */
    public function remove_font_file($fonts) {
        global $gfpdf;

        $fonts = array_filter($fonts);
        $types = array('regular', 'bold', 'italics', 'bolditalics');

        foreach($types as $type) {
            if(isset($fonts[$type])) {
                $filename = basename($fonts[$type]);

                if(is_file($gfpdf->data->template_font_location . $filename) && !unlink($gfpdf->data->template_font_location . $filename)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function process_font($font) {
        global $gfpdf;
        
        /* remove any empty fields */
        $font = array_filter($font);

        /* Check we have the required data */
        if(!isset($font['font_name']) || !isset($font['regular']) ||
           strlen($font['font_name']) === 0 || strlen($font['regular']) === 0)  {
           
            header('HTTP/1.1 400 Bad Request');

            $return = array(
                'error' => __('Required fields have not been included.', 'gravitypdf')
            );

            echo json_encode($return);
            wp_die();
        }

        /* Check we have a valid font name */
        $name = $font['font_name'];

        if(! $this->is_font_name_valid($name)) {

            header('HTTP/1.1 400 Bad Request');
            
            $return = array(
                'error' => __('Font name is not valid. Only alphanumeric characters and spaces are accepted.', 'gravitypdf')
            );

            echo json_encode($return);
            wp_die();
        }

        /* Check the font name is unique */
        $shortname = $gfpdf->options->get_font_short_name($name);
        $id = (isset($font['id'])) ? $font['id'] : '';

        if( ! $this->is_font_name_unique($shortname, $id)) {
            
            header('HTTP/1.1 400 Bad Request');
            
            $return = array(
                'error' => __('A font with the same name already exists. Try a different name.', 'gravitypdf')
            );

            echo json_encode($return);
            wp_die();
        }

        /* Move fonts to our Gravity PDF font folder */
        $installation = $this->install_fonts($font);

        /* Check if any errors occured installing the fonts */
        if(isset($installation['errors'])) {
            
            header('HTTP/1.1 400 Bad Request');
            
            $return = array(
                'error' => $installation
            );

            echo json_encode($return);
            wp_die();
        }

        /* If we got here the installation was successful so return the data */
        return $installation;
    }

    /**
     * Check that the font name passed conforms to our expected nameing convesion
     * @param  String  $name The font name to check
     * @return boolean       True on valid, false on failure
     * @since 4.0
     */
    public function is_font_name_valid($name) {

        $regex = '^[A-Za-z0-9 ]+$';

        if(preg_match("/$regex/", $name)) {
            return true;
        }

        return false;
    }

    /**
     * Query our custom fonts options table and check if the font name already exists
     * @param  String $name The font name to check
     * @param  Integer $id The configuration ID (if any)
     * @return boolean True if valid, false on failure
     */
    public function is_font_name_unique($name, $id = '') {
        global $gfpdf;

        /* Loop through default fonts and check for duplicate */
        $default_fonts = $gfpdf->options->get_installed_fonts();

        foreach($default_fonts as $group) {
            if(isset($group[$name])) {
                return false;
            }
        }

        /* Loop through custom fonts and check for duplicate */
        $custom_fonts  = $gfpdf->options->get_option('custom_fonts');

        if(is_array($custom_fonts)) {
            foreach($custom_fonts as $font) {
                /* skip over itself */
                if(! empty($id) && $font['id'] == $id) {
                    continue;
                }

                if($gfpdf->options->get_font_short_name($font['font_name']) == $name) {
                    return false;
                }
            }
        }

        return true;
    }

    public function install_fonts($fonts) {
        global $gfpdf;

        $types = array('regular', 'bold', 'italics', 'bolditalics');
        $errors = array();

        foreach($types as $type) {

            /* Check if a key exists for this type and process */
            if( isset($fonts[$type])) {
                $path = $gfpdf->misc->convert_url_to_path( $fonts[$type] );

                /* Couldn't find file so throw error */
                if(is_wp_error($path)) {
                    $errors[] = sprintf(__('Could not locate font on web server: %s', 'gravitypdf'), $fonts[$type]);
                }

                /* Copy font to our fonts folder */
                $filename = basename($path);
                if( !is_file($gfpdf->data->template_font_location . $filename) && ! copy($path, $gfpdf->data->template_font_location . $filename)) {
                    $errors[] = sprintf(__('There was a problem installing the font %s. Please try again.', 'gravitypdf'), $filename);
                }
            }
        }

        /* If errors were found then return */
        if(sizeof($errors) > 0) {
            return array('errors' => $errors);
        } else {
            /* Insert our font into the database */
            $custom_fonts  = $gfpdf->options->get_option('custom_fonts');

            /* Prepare our font data and give it a unique id */
            if(empty($fonts['id'])) {
                $id                = uniqid();
                $fonts['id']       = $id;
            }

            $custom_fonts[$fonts['id']] = $fonts;

            /* Update our font database */
            $gfpdf->options->update_option('custom_fonts', $custom_fonts);
        }

        /* Fonts sucessfully installed so return font data */
        return $fonts;
    }

    /**
     * Add an image of the current selected template (if any)
     * @param Array $settings Any existing settings loaded
     */
    public function add_template_image($settings) {
        global $gfpdf;

        if( isset( $settings['default_template'] ) ) {
            $current_template = $gfpdf->options->get_form_value( $settings['default_template'] );
            $template_image   = $gfpdf->misc->get_template_image( $current_template );

            if( ! empty($template_image) ) {
                $img                                  = '<img src="'. esc_url($template_image) . '" alt="' . __('Template Example') . '" id="gfpdf-template-example" />';
                $settings['default_template']['desc'] = $settings['default_template']['desc'] . $img;
            }
        }
        return $settings;
    }





    /**
     * Load Recent forum articles meta box
     * @param Object $object The metabox object
     * @return void
     * @since 4.0
     */
    public function process_meta_pdf_recent_forum_articles($object) {
        $controller = $this->getController();

        /* get our list of recent forum topics */
        $latest     = $this->get_latest_forum_topics();

        /* call view to render topics */
        $controller->view->add_meta_pdf_recent_forum_articles($object, $latest);
    }

    /**
     * Call forum endpoint and get the latest topic information
     * @param Object $object The metabox object
     * @return void
     * @since 4.0
     */
    public function get_latest_forum_topics() {

        /* check if we have a transient set up with cached response */
        if ( false !== ( $topics = get_transient( 'gfpdf_latest_forum_topics' ) ) ) {
            return $topics;
        }

        /* set up the api endpoint details */
        $url = 'https://support.gravitypdf.com/latest.json';

        $args = array(
            'timeout' => 10
        );

        /* do query */
        $response = wp_remote_get($url, $args);

        /* check for errors */
        if(is_wp_error($response)) {
            return false;
        }

        /* decode json response */
        $json = json_decode($response['body'], true);

        /* check we have the correct keys */
        if(!isset($json['topic_list']['topics'])) {
            return false;
        }

        /* cannot filter number of topics requested from endpoint so slice the data */
        $topics = array_slice($json['topic_list']['topics'], 2, 5);

        /* set a transient cache */
        set_transient('gfpdf_latest_forum_topics', $topics, 86400); /* cache for a day */

        return $topics;
    }

    /**
     * Turn capabilities into more friendly strings
     * @param  String $cap The wordpress-style capability
     * @return String
     * @since 4.0
     */
    public function style_capabilities($cap) {
        $cap = str_replace('gravityforms', 'gravity_forms', $cap);
        $cap = str_replace('_', ' ', $cap);
        $cap = ucwords($cap);
        return $cap;
    }

    /**
     * Add meta boxes used in the settings "help" tab
     * @since  4.0
     * @return  void
     */
    public function add_meta_boxes() {

        $controller = $this->getController();

        /* set the meta box id */
        $id = 'pdf_knowledgebase';
        add_meta_box(
            $id,
            __( 'Documentation', 'gravitypdf' ),
            array($controller->view, 'add_meta_' . $id),
            'pdf-help-and-support',
            'row-1'
        );

        /* set the meta box id */
        $id = 'pdf_support_forum';
        add_meta_box(
            $id,
            __( 'Support Forum', 'gravitypdf' ),
            array($controller->view, 'add_meta_' . $id),
            'pdf-help-and-support',
            'row-1'
        );

        /* set the meta box id */
        $id = 'pdf_direct';
        add_meta_box(
            $id,
            __( 'Contact Us', 'gravitypdf' ),
            array($controller->view, 'add_meta_' . $id),
            'pdf-help-and-support',
            'row-1'
        );

        /* set the meta box id */
        $id = 'pdf_popular_articles';
        add_meta_box(
            $id,
            __( 'Popular Documentation', 'gravitypdf' ),
            array($controller->view, 'add_meta_' . $id),
            'pdf-help-and-support',
            'row-2'
        );

        /* set the meta box id */
        $id = 'pdf_recent_forum_articles';
        add_meta_box(
            $id,
            __( 'Recent Forum Activity', 'gravitypdf' ),
            array($this, 'process_meta_' . $id),
            'pdf-help-and-support',
            'row-2'
        );

        /* set the meta box id */
        $id = 'pdf_support_hours';
        add_meta_box(
            $id,
            __( 'Support Hours', 'gravitypdf' ),
            array($controller->view, 'add_meta_' . $id),
            'pdf-help-and-support',
            'row-2'
        );
    }
}