<?php

namespace GFPDF\Model;
use GFPDF\Model\Model_Form_Settings;
use GFPDF\Helper\Helper_Model;
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
     * Check if we need to force the rewrite rules to be flushed
     * @param  $rule The rule to check
     * @since 4.0
     * @return void
     */
    public function maybe_flush_rewrite_rules($rule) {
        $rules = get_option( 'rewrite_rules' );

        if ( ! isset( $rules[ $rule ] ) ) {
            flush_rewrite_rules();
        }
    }

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
         * Default middleware includes 'middle_logged_out', 'middle_logged_out_timeout', 'middle_user_capability', 'middle_current_pdf_owner'
         * If WP_Error is returned the PDF won't be parsed
         */
        $middleware = apply_filters( 'gfpdf_pdf_middleware', true, $entry, $settings);

        /* Throw error */
        if(is_wp_error($middleware)) {
            return $middleware;
        }
    }

    /**
     * Check the "Restrict Logged Out User" global setting and validate it against the current user
     * @param  Boolean / Object $action
     * @param  Array $entry    The Gravity Forms Entry
     * @param  Array $settings The Gravity Form PDF Settings
     * @return Boolean / Object
     * @since 4.0
     */
    public function middle_logged_out($action, $entry, $settings) {
        global $gfpdf;

        /* ensure another middleware filter hasn't already done validation */
        if(!is_wp_error($action)) {
            /* get the setting */
            $logged_out_restriction = $gfpdf->options->get_option('limit_to_admin', 'No');

            if($logged_out_restriction === 'Yes' && is_user_logged_in() === false) {
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
            $logged_out_restriction = $gfpdf->options->get_option('limit_to_admin', 'No');

            /* only check if PDF timed out if our logged out restriction is not 'Yes' and the user is not logged in */
            if($logged_out_restriction !== 'Yes' && is_user_logged_in() === false) {
                $timeout = (int) $gfpdf->options->get_option('logged_out_timeout', '30');

                /* if '0' there is no timeout */
                if($timeout !== 0) {
                    $timeout_stamp   = 60 * $timeout; /* 60 seconds multiplied by number of minutes */
                    $entry_created   = strtotime( $entry['date_created'] ); /* get entry timestamp */
                    $timeout_expires = $entry_created + $timeout_stamp; /* get the timeout expiry based on the entry created time */

                    /* compare our two timestamps and throw error is outside the timeout */
                    if(time() > $timeout_expires) {
                        return new WP_Error('timeout_expired', __('Your PDF is no longer accessible.', 'gravitypdf'));
                    }
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

        /* ensure another middleware filter hasn't already done validation */
        if(!is_wp_error($action)) {
            if(is_user_logged_in()) {
                /* TODO - check if user has no access */
            }
        }

        return $action;
    }

    public function middle_current_pdf_owner($action, $entry, $settings) {
        /* TODO */
    }
}