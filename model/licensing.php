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
     * [__construct description]
     */
    public function __construct()
    {
        add_action('gfpdfe_addons', array($this, 'init'));
    }

    /**
     * [init description]
     * @return [type] [description]
     */
    public function init()
    {
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
     * [update_license_keys description]
     * @return [type] [description]
     */
    private function update_license_keys()
    {
        if (! wp_verify_nonce(PDF_Common::post('gfpdfe_license_nonce_field'), 'gfpdfe_license_nonce')) {
            /*
             * Show error message
             */
            return;
        }

        /*
         * Loop through all active add ons and process the licenses
         */
        global $gfpdf;

        foreach (GFPDF_Core::$addon as &$addon) {
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
     * [is_new_license description]
     * @param  [type]  $new    [description]
     * @param  [type]  &$addon [description]
     * @return boolean         [description]
     */
    public function is_new_license($new, &$addon)
    {
        $expires = get_option('gfpdfe_addon_'.$addon['id'].'_license_expires');
        $old = get_option('gfpdfe_addon_'.$addon['id'].'_license');

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
     * [remove_license_from_db description]
     * @param  [type]  &$addon  [description]
     * @param  boolean $license [description]
     * @return [type]           [description]
     */
    public function remove_license_from_db(&$addon, $license = true)
    {
        if ($license) {
            delete_option('gfpdfe_addon_'.$addon['id'].'_license'); // new license has been entered, so must reactivate
            $addon['license_key'] = '';
        }

        delete_option('gfpdfe_addon_'.$addon['id'].'_license_expires');
        $addon['license_expires'] = false;
        $addon['valid_license'] = false;
    }

    /**
     * [check_license description]
     * @param  [type] $license [description]
     * @param  [type] &$addon  [description]
     * @return [type]          [description]
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
            'item_name' => urlencode($addon['name']), // the name of our product in EDD
        );

        // Call the custom API.
        $response = wp_remote_get(add_query_arg($api_params, $gfpdfe_data->store_url), array( 'timeout' => 25, 'sslverify' => false ));

        // make sure the response came back okay
        if (is_wp_error($response)) {
            return false;
        }

        // decode the license data
        $license_data = json_decode(wp_remote_retrieve_body($response));

        print_r($license_data);

        if ($license_data->license === 'valid') {
            update_option('gfpdfe_addon_'.$addon['id'].'_license_expires', $license_data->expires);
            update_option('gfpdfe_addon_'.$addon['id'].'_license_status', $license_data->license);

            $addon['license_expires'] = $license_data->expires;
            $addon['license_status'] = $license_data->license;
        } else {
            delete_option('gfpdfe_addon_'.$addon['id'].'_license_expires', $license_data->expires);
            update_option('gfpdfe_addon_'.$addon['id'].'_license_status', $license_data->error);
            $addon['license_status'] = $license_data->license;
        }
    }

    /**
     * [deactivate_license_key description]
     * @return [type] [description]
     */
    public function deactivate_license_key()
    {
        $addon_id = PDF_Common::get('deactivate');
        $addon = false;

        if (! wp_verify_nonce(PDF_Common::get('nonce'), 'gfpdfe_deactive_license')) {
            /*
             * Show error message
             */
            return;
        }

        foreach (GFPDF_Core::$addon as &$plugin) {
            if ($plugin['id'] == $addon_id) {
                $addon = &$plugin;
                break;
            }
        }

        if ($addon) {
            /* deactivate license key */
            $this->do_deactive_license_key($addon);
        }
    }

    /**
     * [do_deactive_license_key description]
     * @param  [type] &$addon [description]
     * @return [type]         [description]
     */
    public function do_deactive_license_key(&$addon)
    {
        global $gfpdfe_data;

        if (strlen(trim($addon['license_key'])) === 0) {
            return;
        }

        // data to send in our API request
        $api_params = array(
            'edd_action' => 'deactivate_license',
            'license'    => $addon['license_key'],
            'item_name' => urlencode($addon['name']), // the name of our product in EDD
        );

        // Call the custom API.
        $response = wp_remote_get(add_query_arg($api_params, $gfpdfe_data->store_url), array( 'timeout' => 15, 'sslverify' => false ));

        // make sure the response came back okay
        if (is_wp_error($response)) {
            /* TODO - custom error message */
            return false;
        }

        // decode the license data
        $license_data = json_decode(wp_remote_retrieve_body($response));

        print_r($license_data);

        if ($license_data->license == 'deactivated') {
            $this->remove_license_from_db($addon, true);
            update_option('gfpdfe_addon_'.$addon['id'].'_license_status', $license_data->license);
            $addon['license_status'] = $license_data->license;

            return;
        }

        /* TODO - custom error message */
    }

    /**
     * [check_license_key_status description]
     * @return [type] [description]
     */
    public static function check_license_key_status()
    {
        foreach (GFPDF_Core::$addon as &$plugin) {
            self::do_license_key_status_check($plugin);
        }
    }

    /**
     * [do_license_key_status_check description]
     * @param  [type] &$addon [description]
     * @return [type]         [description]
     */
    public static function do_license_key_status_check(&$addon)
    {
        global $gfpdfe_data;

        if (strlen(trim($addon['license_key'])) === 0) {
            return;
        }

        // data to send in our API request
        $api_params = array(
            'edd_action' => 'check_license',
            'license'    => $addon['license_key'],
            'item_name' => urlencode($addon['name']), // the name of our product in EDD
        );

        // Call the custom API.
        $response = wp_remote_get(add_query_arg($api_params, $gfpdfe_data->store_url), array( 'timeout' => 15, 'sslverify' => false ));

        if (is_wp_error($response)) {
            continue;
        }

        $license_data = json_decode(wp_remote_retrieve_body($response));

        if ($license_data->license === 'valid') {
            update_option('gfpdfe_addon_'.$addon['id'].'_license_expires', $license_data->expires);
            update_option('gfpdfe_addon_'.$addon['id'].'_license_status', $license_data->license);

            $addon['license_expires'] = $license_data->expires;
            $addon['license_status']  = $license_data->license;
        } else {
            $license_status = (isset($license_data->license) && $license_data->license === 'inactive') ? $license_data->license : $license_data->error;

            delete_option('gfpdfe_addon_'.$addon['id'].'_license_expires', $license_data->expires);
            update_option('gfpdfe_addon_'.$addon['id'].'_license_status', $license_status);

            $addon['license_status'] = $license_data->license;
        }
    }
}

new GFPDF_License_Model();
