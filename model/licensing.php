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
 *
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

        if( !current_user_can('gravityforms_edit_settings') ) {
            /* TODO - display error */
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
         * TODO - add renewal link to license page and notice on the plugin page
         */
    }

    /**
     * User has saved the add ons license page
     * Update all license keys in database if needed, then verify the new key's validity
     * @since 3.8
     * TODO - refractor
     */
    private function update_license_keys()
    {
        /* check if nonce is valid */
        if (! wp_verify_nonce(PDF_Common::post('gfpdfe_license_nonce_field'), 'gfpdfe_license_nonce')) {
            /* TODO: show error message */
            return;
        }

        /*
         * Loop through all active addons and process the licenses
         */
        global $gfpdf, $gfpdfe_data;

        foreach ($gfpdfe_data->addon as &$addon) {
            $license = '';
            if ($addon['license'] === true) {
                /*
                 * Check for a $_POST key with the addon ID
                 */
                if (isset($_POST[$addon['id']])) {
                    $license = trim(PDF_Common::post($addon['id']));

                    if (strlen($license) > 0 && $this->is_new_license($license, $addon)) {
                        /* update our license state */
                        $addon['license_key'] = $license;
                        update_option('gfpdfe_addon_'.$addon['id'].'_license', $license);

                        /* check if the license is valid */
                        $this->check_license($license, $addon);
                    }
                }
            }
        }
    }

    /**
     * Determine if license key in input box is new, if so remove old one from database
     * @param  String  $new    License key
     * @param  Array   &$addon The addon details, passed by reference
     * @return boolean Whether license is new or not
     * @since 3.8
     * TODO - refractor
     */
    public function is_new_license($new, &$addon)
    {
        $expires = get_option('gfpdfe_addon_'.$addon['id'].'_license_expires');
        $old     = get_option('gfpdfe_addon_'.$addon['id'].'_license');

        if (!$expires) {
            return true;
        } elseif (!$old || $old != $new || $addon['license_status'] == 'inactive') {
            $this->remove_license_from_db($addon, false);
            $addon['valid_license'] = false;

            return true;
        }

        return false;
    }

    /**
     * Remove the addon's license key from database
     * @param Array   &$addon  The addon details, passed by reference
     * @param boolean $license Whether to delete the licese expiry from the database or the expiry and the actual license key
     * @since 3.8
     * TODO - refractor
     */
    public function remove_license_from_db(&$addon, $license = true)
    {
        if ($license) {
            delete_option('gfpdfe_addon_'.$addon['id'].'_license'); // new license has been entered, so must reactivate
            $addon['license_key'] = '';
        }

        delete_option('gfpdfe_addon_'.$addon['id'].'_license_expires');
        $addon['license_expires'] = false;
        $addon['valid_license']   = false;
    }

    /**
     * Call remote API and update license key accordingly
     * @param  String       $license The addon license key
     * @param  Array        &$addon  The addon details, passed by reference
     * @return Boolean/Null
     * @since 3.8
     */
    public function check_license($license, &$addon)
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
        $license_data = self::call_api($api_params);

        print_r($license_data);

        $status = ($license_data->license === 'valid') ? $license_data->license : $license_data->error;

        /* prepare license details */
        $license = array(
            'expires' => $license_data->expires,
            'status'  => $license_data->license,
        );

        /* update license details */
        self::update_license_information($license, $addon);
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
        $addon = false;

        if (!wp_verify_nonce(PDF_Common::get('nonce'), 'gfpdfe_deactive_license')) {
            /*
             * Show error message
             */
            return false;
        }

        /* check if user wasn't to disable an active add on */
        foreach ($gfpdfe_data->addon as &$plugin) {
            if ($plugin['id'] == $addon_id) {
                $addon = $plugin;
                break;
            }
        }

        if ($addon) {
            /* deactivate license key */
            $this->do_deactive_license_key($addon);
        }
    }

    /**
     * Contact API endpoint and deactivate license
     * @param  Array   &$addon The addon details, passed by reference
     * @return Boolean
     */
    public function do_deactive_license_key(&$addon)
    {
        global $gfpdfe_data;

        if (strlen(trim($addon['license_key'])) === 0) {
            /* display error message here */
            return false;
        }

        /* data to send in our API request */
        $api_params = array(
            'edd_action' => 'deactivate_license',
            'license'    => $addon['license_key'],
            'item_name'  => urlencode($addon['name']), // the name of our product in EDD
        );

        print_r($license_data);

        $license_data = self::call_api($api_params);

        if ($license_data !== false && $license_data->license == 'deactivated') {
            /* completely remove license details from database */
            $this->remove_license_from_db($addon);

            $license = array(
                'status' => $license_data->license,
            );

            /* update license details */
            self::update_license_information($license, $addon);
        }

        return true;
    }

    /**
     * Check our current license status
     */
    public static function check_license_key_status()
    {
        global $gfpdfe_data;
        foreach ($gfpdfe_data->addon as &$plugin) {
            self::do_license_key_status_check($plugin);
        }
    }

    /**
     * [do_license_key_status_check description]
     * @param  [type] &$addon [description]
     * @return [type] [description]
     */
    public static function do_license_key_status_check(&$addon)
    {
        global $gfpdfe_data;

        if (strlen(trim($addon['license_key'])) === 0) {
            return;
        }

        /* data to send in our API request */
        $api_params = array(
            'edd_action' => 'check_license',
            'license'    => $addon['license_key'],
            'item_name'  => urlencode($addon['name']), // the name of our product in EDD
        );

        /* Call the custom API. */
        $response = wp_remote_get(add_query_arg($api_params, $gfpdfe_data->store_url), array( 'timeout' => 15, 'sslverify' => false ));

        if (is_wp_error($response)) {
            continue;
        }

        /* decode the license data */
        $license_data = json_decode(wp_remote_retrieve_body($response));

        $valid_license_terms = array('valid', 'inactive');

        $license_status = (isset($license_data->license) && in_array($valid_license_terms)) ? $license_data->license : $license_data->error;

        /* prepare license details */
        $license = array(
            'expires' => $license_data->expires,
            'status'  => $license_status,
        );

        /* update license details */
        self::update_license_information($license, $addon);
    }

    private static function call_api($api_params)
    {
        global $gfpdfe_data;

        /* Call the custom API */
        $response = wp_remote_get(add_query_arg($api_params, $gfpdfe_data->store_url), array( 'timeout' => 15, 'sslverify' => false ));

        /* make sure the response came back okay */
        if (is_wp_error($response)) {
            /* TODO - custom error message */
            return false;
        }

        /* decode the license data */
        return json_decode(wp_remote_retrieve_body($response));
    }

    private static function update_license_information($license, &$addon)
    {

        /* update license in the database and locally */
        if (isset($license['key'])) {
            update_option(sprintf('gfpdfe_addon_%s_license', $addon['id']), $license['key']);
            $addon['license_key'] = $license['key'];
        }

        /* update license status in the database and locally */
        if (isset($license['status'])) {
            update_option(sprintf('gfpdfe_addon_%s_license_expires', $addon['id']), $license['status']);
            $addon['license_status'] = $license['status'];
        }

        /* update license expiry in the database and locally */
        if (isset($license['expires'])) {
            update_option(sprintf('gfpdfe_addon_%s_license_expires', $addon['id']), $license['expires']);
            $addon['license_expires'] = $license['expires'];
        }
    }
}
