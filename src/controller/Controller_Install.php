<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Controller;
use GFPDF\Helper\Helper_Int_Actions;
use GFPDF\Helper\Helper_Int_Filters;
use GFPDF\Helper\Helper_Model;


/**
 * Install Update Controller
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 *
 */

/*
 * This file is called before compatibility checks are run
 * We cannot add namespace support here which means no access
 * to the rest of the plugin
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
 * Controller_Installer
 * Controls the installation and uninstallation of Gravity PDF
 *
 * @since 4.0
 */
class Controller_Install extends Helper_Controller implements Helper_Int_Actions, Helper_Int_Filters
{
    /**
     * Load our model and view and required actions
     */
    public function __construct(Helper_Model $model)
    {
        /* load our model and view */
        $this->model = $model;
        $this->model->setController($this);
    }

    /**
     * Initialise our class defaults
     * @since 4.0
     * @return void
     */
    public function init() {
         $this->add_actions();
         $this->add_filters();
    }

    /**
     * Apply any actions needed for the settings page
     * @since 4.0
     * @return void
     */
    public function add_actions() {
        /* rewrite filters / endpoints */
        add_action( 'init', array($this->model, 'register_rewrite_rules'));
    }

    /**
     * Apply any filters needed for the settings page
     * @since 4.0
     * @return void
     */
    public function add_filters() {
        /* rewrite filters */
        add_filter( 'query_vars', array($this->model, 'register_rewrite_tags'));
    }

    /**
     * Set up data related to the plugin setup and installation
     * @return void
     * @since 4.0
     * @dependancy $gfpdf GFPDF\Router
     */
    public function setup_defaults() {
        global $gfpdf;

        $gfpdf->data->is_installed   = $this->model->is_installed();
        $gfpdf->data->permalink      = $this->model->get_permalink_regex();
        $gfpdf->data->working_folder = $this->model->get_working_directory();
    }
}