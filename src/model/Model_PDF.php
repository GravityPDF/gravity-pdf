<?php

namespace GFPDF\Model;

use GFPDF\Model\Model_Form_Settings;
use GFPDF\Helper\Helper_Model;
use GFPDF\Helper\Helper_PDF;
use GFPDF\Stat\Stat_Functions;

use GFFormsModel;
use GFCommon;
use GFAPI;

use WP_Error;

use Exception;

/**
 * PDF Display Model
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
 * Model_PDF
 *
 * Handles all the PDF display logic
 *
 * @since 4.0
 */
class Model_PDF extends Helper_Model {

    /**
     * Our Middleware used to handle the authentication process
     * @param  $pid The Gravity Form PDF Settings ID
     * @param  $lid The Gravity Form Entry ID
     * @since 4.0
     * @return void
     */
    public function process_pdf($pid, $lid) {
        
        /**
         * Check if we have a valid Gravity Form Entry and PDF Settings ID
         */
        $entry = GFAPI::get_entry($lid);

        /* not a valid entry */
        if(is_wp_error($entry)) {
            return $entry; /* return error */
        }

        $settingsAPI = new Model_Form_Settings();
        $settings = $settingsAPI->get_pdf( $entry['form_id'], $pid);

        /* Not valid settings */
        if(is_wp_error($settings)) {
            return $settings; /* return error */
        }

        /**
         * Our middleware authenticator
         * Allow users to tap into our middleware and add additional or remove additional authentication layers
         *
         * Default middleware includes 'middle_logged_out_restriction', 'middle_logged_out_timeout', 'middle_auth_logged_out_user', 'middle_user_capability'
         * If WP_Error is returned the PDF won't be parsed
         */
        $middleware = apply_filters( 'gfpdf_pdf_middleware', false, $entry, $settings);

        /* Throw error */
        if(is_wp_error($middleware)) {
            return $middleware;
        }

        /**
         * If we are here we can generate our PDF
         */
        $controller = $this->getController();
        $controller->view->generate_pdf($entry, $settings);
    }

    /**
     * Check if the current user attempting to access is the PDF owner
     * @param  Array $entry    The Gravity Forms Entry
     * @param  String $type    The authentication type we should use
     * @return Boolean
     * @since 4.0
     */
    public function is_current_pdf_owner($entry, $type = 'all') {
        $owner = false;
        /* check if the user is logged in and the entry is assigned to them */
        if($type === 'all' || $type === 'logged_in') {
            if(is_user_logged_in() && (int) $entry['created_by'] === get_current_user_id()) {
                $owner = true;
            }
        }

        if($type === 'all' || $type === 'logged_out') {
            $user_ip = trim(GFFormsModel::get_ip());
            if($entry['ip'] == $user_ip && $entry['ip'] !== '127.0.0.1' && strlen($user_ip) !== 0) { /* check if the user IP matches the entry IP */
                $owner = true;
            }
        }

        return $owner;
    }

    /**
     * Check the "Restrict Logged Out User" global setting and validate it against the current user
     * @param  Boolean / Object $action
     * @param  Array $entry    The Gravity Forms Entry
     * @param  Array $settings The Gravity Form PDF Settings
     * @return Boolean / Object
     * @since 4.0
     */
    public function middle_logged_out_restriction($action, $entry, $settings) {
        global $gfpdf;

        /* ensure another middleware filter hasn't already done validation */
        if(!is_wp_error($action)) {
            /* get the setting */
            $logged_out_restriction = $gfpdf->options->get_option('limit_to_admin', 'No');

            if($logged_out_restriction === 'Yes' && !is_user_logged_in()) {
                /* prompt user to login */
                auth_redirect();
            }
        }

        return $action;
    }

    /**
     * Check the "Logged Out Timeout" global setting and validate it against the current user
     * @param  Boolean / Object $action
     * @param  Array $entry    The Gravity Forms Entry
     * @param  Array $settings The Gravity Form PDF Settings
     * @return Boolean / Object
     * @since 4.0
     */
    public function middle_logged_out_timeout($action, $entry, $settings) {
        global $gfpdf;

        /* ensure another middleware filter hasn't already done validation */
        if(!is_wp_error($action)) {

            /* only check if PDF timed out if our logged out restriction is not 'Yes' and the user is not logged in */
            if(!is_user_logged_in() && $this->is_current_pdf_owner($entry, 'logged_out') === true) {
                /* get the global PDF settings */
                $timeout                = (int) $gfpdf->options->get_option('logged_out_timeout', '30');

                /* if '0' there is no timeout, or if the logged out restrictions are enabled we'll ignore this */
                if($timeout !== 0) {

                    $timeout_stamp   = 60 * $timeout; /* 60 seconds multiplied by number of minutes */
                    $entry_created   = strtotime( $entry['date_created'] ); /* get entry timestamp */
                    $timeout_expires = $entry_created + $timeout_stamp; /* get the timeout expiry based on the entry created time */

                    /* compare our two timestamps and throw error if outside the timeout */
                    if(time() > $timeout_expires) {

                        /* if there is no user account assigned to this entry throw error */
                        if(empty($entry['created_by'])) {
                            return new WP_Error('timeout_expired', __('Your PDF is no longer accessible.', 'gravitypdf'));
                        } else {
                            /* prompt to login */
                            auth_redirect();
                        }
                    }
                }
            }
        }
        return $action;
    }

    /**
     * Check if the user is logged out and authenticate as needed
     * @param  Boolean / Object $action
     * @param  Array $entry    The Gravity Forms Entry
     * @param  Array $settings The Gravity Form PDF Settings
     * @return Boolean / Object
     * @since 4.0
     */
    public function middle_auth_logged_out_user($action, $entry, $settings) {
        if(!is_wp_error($action)) {

            /* check if the user is not the current entry owner */
            if(!is_user_logged_in() && $this->is_current_pdf_owner($entry, 'logged_out') === false) {
                /* check if there is actually a user who owns entry */
                if(!empty($entry['created_by'])) {
                    /* prompt user to login to get access */
                    auth_redirect();
                } else {
                    /* there's no returning, throw generic error */
                    return new WP_Error('error');
                }
            }
        }
        return $action;
    }

    /**
     * Check the "User Restriction" global setting and validate it against the current user
     * @param  Boolean / Object $action
     * @param  Array $entry    The Gravity Forms Entry
     * @param  Array $settings The Gravity Form PDF Settings
     * @return Boolean / Object
     * @since 4.0
     */
    public function middle_user_capability($action, $entry, $settings) {
        global $gfpdf;

        if(!is_wp_error($action)) {
            /* check if the user is logged in but is not the current owner */
            if(is_user_logged_in() &&
                ( ($gfpdf->options->get_option('limit_to_admin', 'No') == 'Yes') || ($this->is_current_pdf_owner($entry, 'logged_in') === false)) ) {
                /* Handle permissions checks */
                 $admin_permissions = $gfpdf->options->get_option('admin_capabilities', array('gravityforms_view_entries'));

                 /* loop through permissions and check if the current user has any of those capabilities */
                 $access = false;
                 foreach($admin_permissions as $permission) {
                    if(GFCommon::current_user_can_any( $permission )) {
                        $access = true;
                    }
                }

                /* throw error if no access granted */
                if(!$access) {
                    return new WP_Error('access_denied', __('You do not have access to view this PDF.', 'gravitypdf'));
                }
            }
        }
        return $action;
    }

    /**
     * Display PDF on Gravity Form entry list page
     * @param  Integer $form_id  Gravity Form ID
     * @param  Integer $field_id Current field ID
     * @param  Mixed $value    Current value of field
     * @param  Array $entry     Entry Information
     * @return void
     * @since 4.0
     */
    public function view_pdf_entry_list($form_id, $field_id, $value, $entry) {
        global $gfpdf;

        $controller = $this->getController();

        /* Check if we have any PDFs */
        $form = GFAPI::get_form($entry['form_id']);
        $pdfs = (isset($form['gfpdf_form_settings'])) ? $this->get_active_pdfs($form['gfpdf_form_settings'], $entry) : array();


        if(!empty($pdfs)) {

            $download = ($gfpdf->options->get_option('default_action') == 'Download') ? '?download=1' : '';

            if(sizeof($pdfs) > 1) {
                $args = array('pdfs' => array());

                foreach($pdfs as $pdf) {
                    $args['pdfs'][] = array(
                        'name' => $this->get_pdf_name($pdf, $entry),
                        'url' => $this->get_pdf_url($pdf['id'], $entry['id']) . $download,
                    );
                }

                $controller->view->entry_list_pdf_multiple($args);
            } else {
                /* Only one PDF for this form so display a simple 'View PDF' link */
                $pdf = array_shift($pdfs);

                $args = array(
                    'url' => $this->get_pdf_url($pdf['id'], $entry['id']) . $download,
                );

                $controller->view->entry_list_pdf_single($args);
            }
        }
    }

    /**
     * Display the PDF links on the entry detailed section of the admin area
     * @param  Integer $form_id Gravity Form ID
     * @param  Array $entry    The entry information
     * @return void
     * @since  4.0
     */
    public function view_pdf_entry_detail($form_id, $entry) {
        $controller = $this->getController();

        /* Check if we have any PDFs */
        $form = GFAPI::get_form($entry['form_id']);
        $pdfs = (isset($form['gfpdf_form_settings'])) ? $this->get_active_pdfs($form['gfpdf_form_settings'], $entry) : array();

        if(!empty($pdfs)) {
            $args = array('pdfs' => array());

            foreach($pdfs as $pdf) {
                $args['pdfs'][] = array(
                    'name' => $this->get_pdf_name($pdf, $entry),
                    'url' => $this->get_pdf_url($pdf['id'], $entry['id']),
                );
            }

            $controller->view->entry_detailed_pdf($args);
        }
    }

    /**
     * Generate the PDF Name
     * @param  Array $pdf  The PDF Form Settings
     * @param  Array $entry The Gravity Form entry details
     * @return String      The PDF Name
     * @since  4.0
     */
    public function get_pdf_name($pdf, $entry) {
        $form = GFAPI::get_form($entry['form_id']);
        $name = GFCommon::replace_variables($pdf['filename'], $form, $entry);

        /* add filter to modify PDF name */
        $name = apply_filters('gfpdf_pdf_filename', $name, $form, $entry, $pdf);
        $name = apply_filters('gfpdfe_pdf_filename', $name, $form, $entry, $pdf); /* backwards compat */

        return $name;
    }

    /**
     * Create a PDF Link based on the current PDF settings and entry
     * @param  Integer $pid  The PDF Form Settings ID
     * @param  Integer $id The Gravity Form entry ID
     * @return String       Direct link to the PDF
     * @since  4.0
     */
    public function get_pdf_url($pid, $id, $esc = true) {
        $url = home_url() . '/pdf/' . $pid . '/' . $id . '/';

        if($esc) {
            $url = esc_url($url);
        }

        return $url;
    }

    /**
     * Filter out inactive PDFs and those who don't meet the conditional logic
     * @param  Array $pdfs The PDF settings array
     * @param  Array $entry The current entry information
     * @return Array       The filtered PDFs
     * @since 4.0
     */
    public function get_active_pdfs($pdfs, $entry) {
        $filtered = array();
        $form     = GFAPI::get_form($entry['form_id']);

        foreach($pdfs as $pdf) {
            if($pdf['active'] && GFCommon::evaluate_conditional_logic( $pdf['conditionalLogic'], $form, $entry)) {
                $filtered[$pdf['id']] = $pdf;
            }
        }

        return $filtered;
    }

    /**
     * Generate and save PDF to disk
     * @param  Object $pdf The Helper_PDF object
     * @return Boolean
     * @since 4.0
     */
    public function process_and_save_pdf($pdf) {
        /* Check that the PDF hasn't already been created this session */
        if($this->does_pdf_exist($pdf)) {
            try {
                $pdf->init();
                $pdf->renderHtml(Stat_Functions::get_template_args($pdf->getEntry(), $pdf->getSettings()));
                $pdf->setOutputType('save');

                /* Generate PDF */
                $raw_pdf  = $pdf->generate();
                $pdf->savePdf($raw_pdf);

                return true;
            } catch(Exception $e) {
                /* Log Error */
                return false;
            }
        }
    }

    /**
     * Check if the form has any PDFs, generate them and attach to the notification
     * @param  Array $notifications Gravity Forms Notification Array
     * @param  Array $form
     * @param  Array $entry
     * @return Array
     * @since 4.0
     */
    public function notifications($notifications, $form, $entry) {
        $pdfs       = (isset($form['gfpdf_form_settings'])) ? $this->get_active_pdfs($form['gfpdf_form_settings'], $entry) : array();

        if(sizeof($pdfs) > 0) {
            /* set up classes */
            $controller  = $this->getController();
            $settingsAPI = new Model_Form_Settings();

            /* loop through each PDF config and generate */
            foreach($pdfs as $pdf) {
                $settings = $settingsAPI->get_pdf( $entry['form_id'], $pdf['id']);

                if(! is_wp_error($settings) && $this->maybe_attach_to_notification($notifications, $settings)) {
                    $pdf = new Helper_PDF($entry, $settings);
                    
                    if($this->process_and_save_pdf($pdf)) {
                        $pdf_path = $pdf->getPath() . $pdf->getFilename();

                        if(is_file($pdf_path)) {
                            $notifications['attachments'][] = $pdf_path;
                        }
                    }
                }
            }
        }
        return $notifications;
    }

    /**
     * Determine if the PDF should be attached to the current notification
     * @param  Array $notification The Gravity Form Notification currently being processed
     * @param  Array $settings     The current Gravity PDF Settings
     * @return Boolean
     * @since 4.0
     */
    public function maybe_attach_to_notification($notification, $settings) {
        if(isset($settings['notification']) && is_array($settings['notification'])) {
            if(isset($notification['isActive']) && $notification['isActive'] && in_array($notification['name'], $settings['notification'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the PDF should be saved to disk
     * @param  Array $settings     The current Gravity PDF Settings
     * @return Boolean
     * @since 4.0
     */
    public function maybe_always_save_pdf($settings) {
        if(strtolower($settings['save']) == 'yes') {
            return true;
        }

        return false;
    }

    /**
     * Creates a PDF on every submission, except when the PDF is already created during the notification hook
     * @param  Array $entry The GF Entry Details
     * @param  Array $form  The Gravity Form
     * @return void
     * @since 4.0
     */
    public function maybe_save_pdf($entry, $form) {
        $pdfs = (isset($form['gfpdf_form_settings'])) ? $this->get_active_pdfs($form['gfpdf_form_settings'], $entry) : array();

        if(sizeof($pdfs) > 0) {
            /* set up classes */
            $controller  = $this->getController();
            $settingsAPI = new Model_Form_Settings();

            /* loop through each PDF config */
            foreach($pdfs as $pdf) {
                $settings = $settingsAPI->get_pdf( $entry['form_id'], $pdf['id']);

                /* Only generate if the PDF wasn't created during the notification process */
                if(! is_wp_error($settings)) {
                    $pdf = new Helper_PDF($entry, $settings);

                    /* Check that the PDF hasn't already been created this session */
                    if($this->maybe_always_save_pdf($settings)) {
                        $this->process_and_save_pdf($pdf);
                    }

                    /* Run an action users can tap into to manipulate the PDF */
                    if($this->does_pdf_exist($pdf)) {
                        do_action('gfpdf_post_pdf_save', $entry['form_id'], $entry['id'], $settings, $pdf->getPath() . $pdf->getFilename());
                    }
                }
            }
        }
    }

    /**
     * Check if the current PDF to be processed already exists on disk
     * @param  Object $pdf     The Helper_PDF Object
     * @return Boolean
     * @since  4.0
     */
    public function does_pdf_exist($pdf) {
        $pdf->setPath();
        $pdf->setFilename();

        if(is_file( $pdf->getPath() . $pdf->getFilename())) {
            return true;
        }

        return false;
    }

    /**
     * Remove the generated PDF from the server to save disk space
     * @internal  In future we may give the option to cache PDFs to save on processing power
     * @param  Array $entry The GF Entry Data
     * @param  Array $form  The Gravity Form
     * @return void
     * @since 4.0
     */
    public function cleanup_pdf($entry, $form) {
        $pdfs = (isset($form['gfpdf_form_settings'])) ? $this->get_active_pdfs($form['gfpdf_form_settings'], $entry) : array();

        if(sizeof($pdfs) > 0) {
            /* set up classes */
            $controller  = $this->getController();
            $settingsAPI = new Model_Form_Settings();

            /* loop through each PDF config */
            foreach($pdfs as $pdf) {
                $settings = $settingsAPI->get_pdf( $entry['form_id'], $pdf['id']);

                /* Only generate if the PDF wasn't during the notification process */
                if(! is_wp_error($settings)) {
                    $pdf = new Helper_PDF($entry, $settings);

                    if($this->does_pdf_exist($pdf)) {
                        try {
                            Stat_Functions::rmdir($pdf->getPath());
                        } catch(Exception $e) {
                            /* Log to file */
                        }
                    }
                }
            }
        }
    }
}