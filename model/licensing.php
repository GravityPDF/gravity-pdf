<?php

/**
 * Plugin: Gravity PDF
 * File: licensing.php
 *
 * This class brings add on functionality to the software
 */

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
 * This class handles all our user-interacting licensing functionality
 * Including all the licensing views
 * The core licensing integrating happens in /helper/addons/framework.php && /helper/addons/licensing.php
 * @since 3.8
 */
class GFPDF_License_Model
{
    /**
     * Add license actions and filters
     */
    public function __construct()
    {
        add_action('gfpdfe_addons', array($this, 'init'));
    }

    /**
     * Add event listeners and run appropriate actions
     * @since  3.8
     */
    public function init()
    {
        if (!current_user_can('gravityforms_edit_settings')) {
            $error = __('Access Denied. You require the Gravity Forms Edit Settings capability to view this page.', 'pdfextended');
            PDF_Common::add_message($error, 'error');

            return false;
        }

        /*
         * Check if we are updating the license keys
         */
        if (PDF_Common::post('gfpdfe_license_nonce_field')) {
            $this->update_license_keys();
        }

        /*
         * Check if we are deactivating the license
         */
        if (PDF_Common::get('deactivate')) {
            $this->deactivate_license_key();
        }

        /*
         * add renewal link to license page and notice on the plugin page
         */
        $this->show_renewal_notice_on_plugin_page();
    }

    /**
     * User has saved the add ons license page
     * Update all license keys in database if needed, then verify the new key's validity
     * @since 3.8
     */
    private function update_license_keys()
    {
        /* check if nonce is valid */
        if (! wp_verify_nonce(PDF_Common::post('gfpdfe_license_nonce_field'), 'gfpdfe_license_nonce')) {
            $error = __('There was a problem processing your request. Please try again.', 'pdfextended');
            PDF_Common::add_message($error, 'error');

            return;
        }

        /*
         * Loop through all active addons and process the licenses
         */
        global $gfpdfe_data;
        foreach ($gfpdfe_data->addon as &$addon) {
            /* if the addon marks itself as requiring a license key... */
            if ($addon['license'] === true) {
                $this->do_update_license_key($addon);
            }
        }
    }

    /**
     * Update the addon's license key if needed. License key can either be passed directly or passed via _POST.
     * @param Array          &$addon      The addon details, passed by reference
     * @param boolean/string $license_key License key passed in
     * @since 3.8
     */
    public function do_update_license_key(&$addon, $license_key = false)
    {
        $license = ($license_key === false) ? trim(PDF_Common::post($addon['id'])) : trim($license_key);

        if ($this->is_new_license($license, $addon)) {

            /* prepare license details */
            $new_license_details = array(
                'key' => $license,
            );

            /* update our license details */
            $this->update_license_information($new_license_details, $addon);

            /* check if the license is valid */
            $this->activate_license($license, $addon);
        }
    }

    /**
     * Determine if license key in input box is new, if so remove old one from database
     * @param  String  $new    License key
     * @param  Array   &$addon The addon details, passed by reference
     * @return boolean Whether license is new or not
     * @since 3.8
     */
    public function is_new_license($updated_license_key, &$addon)
    {
        /* get license details for add on */
        $current_license_key = $addon['license_key'];
        $license_status      = $addon['license_status'];

        /* check if there is no current license key */
        if (!$current_license_key) {
            return true;
        }

        /* check if use is removing a license key completely (but isn't current activated) */
        if (strlen($updated_license_key) == 0) {
            if ($current_license_key !== $updated_license_key && $license_status != 'active') {
                $this->remove_license_information($addon);
            }

            return true;
        }

        /* check if $updated_license_key differs from $current_license_key */
        if ($license_status != 'active') {
            $this->remove_license_information($addon, false);

            return true;
        }

        return false;
    }

    /**
     * Remove the addon's license key from database
     * @param Array   &$addon  The addon details, passed by reference
     * @param boolean $license Whether to delete the licese expiry from the database or the expiry and the actual license key
     * @since 3.8
     */
    public function remove_license_information(&$addon, $license = true)
    {
        /* prepare license details */
        $new_license_details = array();

        if ($license) {
            $new_license_details['key'] = '';
        }

        $new_license_details['expires'] = '';
        $new_license_details['status']  = '';

        /* update our license details */
        $this->update_license_information($new_license_details, $addon);
    }

    /**
     * Call remote API to activate the license key
     * @param  String       $license The addon license key
     * @param  Array        &$addon  The addon details, passed by reference
     * @return Boolean/Null
     * @since 3.8
     */
    public function activate_license($license, &$addon)
    {
        global $gfpdfe_data;

        if (strlen(trim($license)) === 0) {
            return false;
        }

        // data to send in our API request
        $api_params = array(
            'edd_action' => 'activate_license',
            'license'    => $license,
            'item_name'  => urlencode($addon['name']), // the name of our product in EDD
        );

        /* decode the license data */
        $license_data = $this->call_api($api_params);

        if(!is_wp_error($license_data)) {
            $status = ($license_data->license === 'valid') ? $license_data->license : $license_data->error;

            /* prepare license details */
            $license = array(
                'expires' => $license_data->expires,
                'status'  => $status,
            );

            /* update license details */
            $this->update_license_information($license, $addon);

            /* show update or error messages */
            $results = $this->show_api_message($license_data);            

            if(is_wp_error($results)) {
                return $results;
            }
            /* success */
            return true;
            
        }

        /* throw error */
        return $license_data;
    }

    /**
     * Deactivate user's license key
     * @return Boolean
     * @since 3.8
     */
    public function deactivate_license_key()
    {
        global $gfpdfe_data;

        $addon_id = PDF_Common::get('deactivate');
        $addon    = false;

        if (!wp_verify_nonce(PDF_Common::get('nonce'), 'gfpdfe_deactivate_license')) {
            $error = __('There was a problem processing your request. Please try again.', 'pdfextended');
            PDF_Common::add_message($error, 'error');

            return false;
        }

        /* check if user wasn't to disable an active add on */
        foreach ($gfpdfe_data->addon as &$plugin) {
            if ($plugin['id'] == $addon_id) {
                $addon = & $plugin;
                break;
            }
        }

        if ($addon) {
            /* deactivate license key */
            $this->do_deactivate_license_key($addon);
        }
    }

    /**
     * Contact API endpoint and deactivate license
     * @param  Array   &$addon The addon details, passed by reference
     * @return Boolean
     * @since 3.8
     */
    public function do_deactivate_license_key(&$addon)
    {
        global $gfpdfe_data;

        if (!isset($addon['license_key']) || strlen(trim($addon['license_key'])) === 0) {
            /* display error message here */
            return false;
        }

        /* data to send in our API request */
        $api_params = array(
            'edd_action' => 'deactivate_license',
            'license'    => $addon['license_key'],
            'item_name'  => urlencode($addon['name']), // the name of our product in EDD
        );

        $license_data = $this->call_api($api_params);


        if(!is_wp_error($license_data)) {
            /* ensure license data was correctly deactivated */
            if($license_data->license != 'deactivated') {
                return new WP_Error('edd_api_deactivation_error', __('There was a problem deactivating your license key. Please try again.', 'pdfextended'));
            }

            /* completely remove license details from database */
            $this->remove_license_information($addon);

            /* prepare new license details */
            $license = array(
                'status' => $license_data->license,
            );

            /* update license status */
            $this->update_license_information($license, $addon);

            /* show update or error messages */
            $results = $this->show_api_message($license_data);

            if(is_wp_error($results)) {
                return $results;
            } 
            
            /* success */
            return true;                        
        }

        /* return error */
        return $license_data;
    }

    /**
     * Check if the license will expire in less than a month and mark plugin to prompt renewal notice
     * @since  3.8
     */
    private function show_renewal_notice_on_plugin_page()
    {
        global $gfpdfe_data;
        foreach ($gfpdfe_data->addon as $addon) {
            /* check if license is about to expire, or will expire soon */
            if (strtotime('+1 Month') > strtotime($addon['license_expires']) ) {
                add_action('after_plugin_row_'.$addon['basename'], array('GFPDF_Notices', 'display_plugin_renewal_notice'), 10, 1);
            }
        }
    }

    /**
     * Check our current license status
     * This is called via a scheduled task on a daily basis
     * @since 3.8
     */
    public function check_license_key_status()
    {
        global $gfpdfe_data;
        foreach ($gfpdfe_data->addon as &$plugin) {
            $this->do_license_key_status_check($plugin);
        }
    }

    /**
     * Poll API and update the status of the addon's license
     * @param Array &$addon The addon details, passed by reference
     * @since 3.8
     */
    public function do_license_key_status_check(&$addon)
    {
        global $gfpdfe_data;

        if (!isset($addon['license_key']) || strlen(trim($addon['license_key'])) === 0) {
            return false;
        }

        /* data to send in our API request */
        $api_params = array(
            'edd_action' => 'check_license',
            'license'    => $addon['license_key'],
            'item_name'  => urlencode($addon['name']), // the name of our product in EDD
        );

        /* Call the custom API. */
        $license_data = $this->call_api($api_params);

        if(!is_wp_error($license_data)) {        

            $status = (isset($license_data->license)) ? $license_data->license : $license_data->error;

            /* prepare license details */
            $license = array(
                'expires' => $license_data->expires,
                'status'  => $status,
            );

            /* update license details */
            $this->update_license_information($license, $addon);  

            /* check for any errors */
            /* show update or error messages */
            $results = $this->show_api_message($license_data, true);

            if(is_wp_error($results)) {
                return $results;
            }  

            /* no issues */
            return true;                      
        }

        /* return error */
        return $license_data;
    }

    /**
     * Class our EDD API
     * @param  array          $api_params The api parameters/arguments to pass
     * @return boolean/object The API response
     * @since  3.8
     */
    public function call_api($api_params)
    {
        global $gfpdfe_data;

        /* Call the custom API */
        $response = wp_remote_get(add_query_arg($api_params, $gfpdfe_data->store_url), array( 'timeout' => 15, 'sslverify' => false ));        
        /* make sure the response came back okay */
        if (is_wp_error($response)) {
            $error = __('There was a problem contacting the Gravity PDF server. Please try again shortly.', 'pdfextended');
            PDF_Common::add_message($error, 'error');

            return new WP_Error('edd_api_contact', $error);
        }

        /* decode the license data */
        return json_decode(wp_remote_retrieve_body($response));
    }

    /**
     * Update and kee our database and $addon object in sync
     * @param array $license The license information to update
     * @param array &$addon  The addon information to keep in sync
     * @since 3.8
     */
    public function update_license_information($license, &$addon)
    {
        /* update license in the database and locally */
        if (isset($license['key'])) {
            if (strlen($license['key']) > 0) {
                update_option(sprintf('gfpdfe_addon_%s_license', $addon['id']), $license['key']);
                $addon['license_key'] = $license['key'];
            } else {
                delete_option(sprintf('gfpdfe_addon_%s_license', $addon['id']));
                $addon['license_key'] = false;
            }
        }

        /* update license status in the database and locally */
        if (isset($license['status'])) {
            if (strlen($license['status']) > 0) {
                update_option(sprintf('gfpdfe_addon_%s_license_status', $addon['id']), $license['status']);
                $addon['license_status'] = $license['status'];
            } else {
                delete_option(sprintf('gfpdfe_addon_%s_license_status', $addon['id']));
                $addon['license_status'] = false;
            }
        }

        /* update license expiry in the database and locally */
        if (isset($license['expires'])) {
            if (strlen($license['expires']) > 0) {
                update_option(sprintf('gfpdfe_addon_%s_license_expires', $addon['id']), $license['expires']);
                $addon['license_expires'] = $license['expires'];
            } else {
                delete_option(sprintf('gfpdfe_addon_%s_license_expires', $addon['id']));
                $addon['license_expires'] = false;
            }
        }
    }

    /**
     * Display appropriate error / notice response when attempting to verify license type
     * @param Array $response The API response
     * @param Boolean $disable_message Disable any error messages 
     * @since  3.8
     * @todo  Add correct links to get in touch...
     */
    private function show_api_message($response, $disable_message = false)
    {
        /* set the add on name */
        $addon_name = $response->item_name;

        /* success message */
        if (isset($response->license) && !isset($response->error)) {
            $success = '';

            switch ($response->license) {
                case 'valid':
                    $success = sprintf(__('Your license key for %s has been activated.', 'pdfextended'), $addon_name);
                break;

                case 'inactive':
                    /* Not sure if/when this should be thrown. Leave blank for now */
                break;

                case 'deactivated':
                    $success = sprintf(__('Your license key for %s has been deactivated.', 'pdfextended'), $addon_name);
                break;
            }

            /* store sucess message so we can display a notice */
            if(!$disable_message) {
                PDF_Common::add_message($success);
            }
            return true;
        } else {
            /* display error message */
            $error = '';
            switch ($response->error) {
                case 'missing':
                    $error = sprintf(__('Your license key for %s is invalid.', 'pdfextended'), $addon_name);
                break;

                case 'revoked':
                    $error = sprintf(__('Your license key for %s has been revoked. If you feel this was done in error %splease contact us directly%s.', 'pdfextended'), $addon_name, '<a href="#">', '</a>');
                break;

                case 'no_activations_left':
                    $error = sprintf(__("You've reached the maximum activation limit for %s. Try deactivate your license on another website or %scontact us to upgrade your plan%s.", 'pdfextended'), $addon_name, '<a href="#">', '</a>');
                break;

                case 'expired':
                    $error = sprintf(__('Your license for %s has expired. %sRenew your license now%s and get a 20% discount off the standard price.', 'pdfextended'), $addon_name, '<a href="#">', '</a>');
                break;

                default:
                    $error = sprintf(__('There was a problem validating your license key for %s. Reload the page and try again %sor get in touch with our support team%s for further assistance.', 'pdfextended'), $addon_name, '<a href="#">', '</a>');
                break;
            }

            if(!$disable_message) {
                /* store error message so we can display a notice */
                PDF_Common::add_message($error, 'error');
            }

            return new WP_Error('license_validation_error', $error);
        }
    }
}
