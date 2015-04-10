<?php

/**
 * Plugin: Gravity PDF
 * File: framework.php
 *
 * This class provides an upgrade framework for our premium add ons
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

/* Include the core licensing class */
require_once 'licensing.php';

/* Add our cron action hook */
add_action('gfpdf_check_license_key_status', array('GFPDF_License_Model', 'check_license_key_status'));

/**
 *
 * @since 3.8
 */
abstract class GFPDFAddonFramework
{
    /**
     * Holds our addon information
     * @var array
     * @since  3.8
     */
    protected $addon = array();

    /**
     * Set our addon information based on the abstract functions set in the child class 
     * Also apply any filters needed
     * @since 3.8
     */
    public function __construct()
    {
        /*
         * Set up the addon details
         */
		$this->addon['name']     = $this->setName();
		$this->addon['version']  = $this->setVersionNumber();
		$this->addon['author']   = $this->setAuthorName();
		$this->addon['path']     = $this->setPluginPath();
		$this->addon['url']      = $this->setPluginUrl();
		$this->addon['file']     = $this->setFile();
		$this->addon['basename'] = $this->setPluginBasename();
		$this->addon['license']  = $this->setLicense();
		$this->addon['settings'] = $this->setSettings();

        $this->setID();
        $this->setLicenseKey();

        /*
         * Set up our hooks and filters which the base plugin fires
         */
        add_action('gfpdfe_addons', array($this, 'setup'));
        add_action('gfpdfe_addons', array($this, 'init'));

        add_filter('pdf_extended_settings_navigation', array($this, 'add_license_page'));
    }

    /**
     * Make the 'addon' variable accessible to outside classes
     * @return array The $this->addon information
     * @since  3.8
     */
    final public function get_addon_details() {
        return $this->addon;
    }

    /**
     * Convert our plugin name to an ID so we can more easily use for post and get requests
     * @since 3.8
     */
    final private function setID()
    {
        $name = $this->addon['name'];
        $name = strtolower($name);
        $name = str_replace(' ', '_', $name);

        $this->addon['id'] = $name;
    }

    /**
     * If a premium plugin we will pull the license key from the database
     * @since 3.8
     */
    final private function setLicenseKey()
    {
        if ($this->addon['license'] === true) {
            /*
             * Pull license keys from database and store in our addon
             */
            $this->addon['license_key']     = get_option('gfpdfe_addon_'.$this->addon['id'].'_license');
            $this->addon['license_expires'] = get_option('gfpdfe_addon_'.$this->addon['id'].'_license_expires');
            $this->addon['license_status']  = get_option('gfpdfe_addon_'.$this->addon['id'].'_license_status');

            /*
             * Run our plugin update checker
             */
            add_action('admin_init', array($this, 'plugin_updater'));
        }

        return false;
    }

    /**
     * Set up a daily license scheduler to check for updates
     * @since 3.8
     */
    final private static function add_cron_license_event()
    {
        if (! wp_next_scheduled('gfpdf_check_license_key_status')) {
            /* run daily at midnight */
            wp_schedule_event(mktime(0, 0, 0), 'daily', 'gfpdf_check_license_key_status');
        }
    }

    /**
     * Check if updates / valid license
     * @return [type] [description]
     * @since 3.8
     */
    final public function plugin_updater()
    {
        global $gfpdfe_data;

        // retrieve our license key from the DB
        $license_key = trim($this->addon['license_key']);

        // setup the updater
        $updater = new GFPDF_Plugin_Updater($gfpdfe_data->store_url, $this->addon['file'], $this->addon, array(
				'version'   => $this->addon['version'],
				'license'   => $license_key,
				'item_name' => $this->addon['name'],
				'author'    => $this->addon['author'],
            )
        );
    }

    /**
     * Tell base plugin about add on and set up any additional details
     * @since 3.8
     */
    final public function setup()
    {
        global $gfpdfe_data;

        /*
         * Tell the base plugin about our addon
         */
        $gfpdfe_data->addon[] = $this->addon;

        /*
         * Assign a cron even to run every day to check the validity of the license
         */
        self::add_cron_license_event();
    }

    /**
     * Add our license page to the settings navigation, if it doesn't already exist
     * @param array $navigation order => array('name', 'id', 'template')
     * @since 3.8
     */
    final public function add_license_page($navigation)
    {
        /* If the plugin is licensed we will add a new settings page */
        if ($this->addon['license'] === true) {
            if (!$this->check_settings_page_exists($navigation, 'license')) {
                $navigation[50] = array(
					'name'     => __('License', 'pdfextended'),
					'id'       => 'license',
					'template' => PDF_PLUGIN_DIR.'view/templates/settings/license.php',
                );
            }
        }

        if ($this->addon['settings'] === true) {
            if (!$this->check_settings_page_exists($navigation, 'addon')) {
                $navigation[40] = array(
					'name'     => __('Addon', 'pdfextended'),
					'id'       => 'addon',
					'template' => PDF_PLUGIN_DIR.'view/templates/settings/addon.php',
                );
            }
        }

        return $navigation;
    }

    /**
     * See if a settings page already exists
     * @param  array $navigation The navigation array
     * @param  string $id The navigation item to look for
     * @return boolean Whether nav item found or not
     * @since 3.8
     */
    final private function check_settings_page_exists($navigation, $id)
    {
        /* check if page already exists */
        foreach ($navigation as $item) {
            if ($item['id'] == $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Automatically triggered to run on GFPDFE's 'gfpdfe_addons' hook which fires after the plugin is successfully installed (right after WP 'init' hook)
     * Add core plugin logic here
     * @since 3.8
     */
    abstract public function init();

    /**
     * Set the plugin name
     * This should be the name in EDD which we'll use for upgrades
     * @return string the name of the plugin. If using licensing software, must be the exact name in EDD
     * @since 3.8
     */
    abstract protected function setName();

    /**
     * Set the current version number of the addon
     * @return string The current version number. Used for licensing updates
     * @since 3.8
     */
    abstract protected function setVersionNumber();

    /**
     * Set the author name of the add on
     * @return Name of plugin developer/company
     * @since 3.8
     */
    abstract protected function setAuthorName();

    /**
     * Set the plugin path using the inbuilt plugin_dir_path() function
     * This can't be included in the abstract class as it is in the GFPDFE folder.
     * @return string should always return plugin_dir_path( __FILE__ );
     * @since 3.8
     */
    abstract protected function setPluginPath();

    /**
     * Set the plugin path using the inbuilt plugin_dir_path() function
     * This can't be included in the abstract class as it is in the GFPDFE folder.
     * @return string should always return plugin_dir_url( __FILE__ );
     * @since 3.8
     */
    abstract protected function setPluginUrl();

    /**
     * Set the plugin path using the inbuilt plugin_dir_path() function
     * This can't be included in the abstract class as it is in the GFPDFE folder.
     * @return string should always return plugin_basename(__FILE__);
     * @since 3.8
     */
    abstract protected function setPluginBasename();

    /**
     * Whether the plugin is a premium addon and should have a license key
     * @return boolean Whether the software is tied to our license key system
     * @since 3.8
     */
    abstract protected function setLicense();

    /**
     * Whether the plugin has any settings that should be added to the 'AddOn' page
     * @return boolean Whether the addon adds any settings to the addon page
     * @since 3.8
     */
    abstract protected function setSettings();

    /**
     * Set the current file plugin is running from
     * @return string must always return __FILE__
     * @since 3.8
     */
    abstract protected function setFile();
}
