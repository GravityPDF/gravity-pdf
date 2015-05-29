<?php

namespace GFPDF\Model;
use GFPDF\Helper\Helper_Model;

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