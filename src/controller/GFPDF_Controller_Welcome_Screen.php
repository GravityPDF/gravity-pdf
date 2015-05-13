<?php

/**
 * Welcome Screen Controller
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if (! defined('ABSPATH')) {
    exit;
}

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
 * Controller_Welcome_Screen
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class GFPDF_Controller_Welcome_Screen extends GFPDF_Helper_Controller
{
    /**
     * Load our model and view and required actions
     */
    public function __construct(GFPDF_Helper_Model $model, GFPDF_Helper_View $view)
    {
        /* load our model and view */
        $this->model = $model;
        $this->model->setController($this);   

        $this->view  = $view;  
    }

    /**
     * Initialise our class defaults 
     * @since 4.0
     * @return void
     */
    public function init() {
        /* Load the welcome screen into the menu */
        add_action('admin_menu', array( $this->model, 'admin_menus'));
        add_action('admin_init', array( $this, 'welcome'));
    }    

    /**
     * Sends user to the Welcome page on first activation, as well as everytime plugin is upgraded 
     *
     * @access public
     * @since 4.0
     * @return void
     * @todo configure upgrade page as needed
     */
    public function welcome()
    {
        /* Bail if no activation redirect */
        if (!get_transient('_gravitypdf_activation_redirect')) {
            return false;
        }

        /* Delete the redirect transient */
        delete_transient('_gravitypdf_activation_redirect');

        $installed = get_option('gfpdf_is_installed');

        if(!$installed) {
            update_option('gfpdf_is_installed', true);
        }

        /* Bail if activating from network, or bulk */
        if (is_network_admin() || isset($_GET['activate-multi'])) {
            return false;
        }

        /* add own update tracker */
        if (!$installed) { 
            /* First time install */
            wp_safe_redirect(admin_url('index.php?page=gfpdf-getting-started'));
            exit;
        } else { 
            /* Update */
            wp_safe_redirect(admin_url('index.php?page=gfpdf-update'));
            exit;
        }
    }

    /**
     * Load our welcome screen
     * @return void 
     * @since 4.0
     */
    public function getting_started_screen() {
        $this->view->welcome();
    }

    /**
     * Load our update welcome screen
     * @return void 
     * @since 4.0
     */
    public function update_screen() {
    	$this->view->update();
    }


}
