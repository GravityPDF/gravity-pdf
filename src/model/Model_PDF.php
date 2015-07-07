<?php

namespace GFPDF\Model;

use GFPDF\Model\Model_Form_Settings;
use GFPDF\Helper\Helper_Model;

use GFFormsModel;
use GFCommon;
use GFAPI;

use WP_Error;

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
            if(is_user_logged_in() && $this->is_current_pdf_owner($entry, 'logged_in') === false) {
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
}