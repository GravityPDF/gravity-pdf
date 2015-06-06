<?php

namespace GFPDF\Controller;
use GFPDF\Helper\Helper_Controller;
use GFPDF\Helper\Helper_Model;
use GFPDF\Helper\Helper_View;
use GFPDF\Helper\Helper_Int_Actions;
use GFPDF\Helper\Helper_Int_Filters;

/**
 * PDF Display Controller
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
 * Controller_PDF
 * Handles the PDF display and authentication
 *
 * @since 4.0
 */
class Controller_PDF extends Helper_Controller implements Helper_Int_Actions, Helper_Int_Filters
{
    /**
     * Load our model and view and required actions
     */
    public function __construct(Helper_Model $model, Helper_View $view)
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
        /*
         * Tell Gravity Forms to add our form PDF settings pages
         */
         $this->add_actions();
         $this->add_filters();
    }

    /**
     * Apply any actions needed for the settings page
     * @since 4.0
     * @return void
     */
    public function add_actions() {
        add_action( 'init', array($this, 'register_rewrite_rules'));
        add_action( 'parse_request', array($this, 'process_pdf'));
    }

    /**
     * Apply any filters needed for the settings page
     * @since 4.0
     * @return void
     */
    public function add_filters() {
        add_filter( 'query_vars', array($this, 'register_rewrite_tags'));
    }

    /**
     * Register our PDF custom rewrite rules
     * @since 4.0
     * @return void
     */
    public function register_rewrite_rules() {
        global $gfpdf;

        /* store query */
        $query = $gfpdf->data->permalink;

        /* Add our main endpoint */
        add_rewrite_rule(
            $query,
            'index.php?gf_pdf=1&nid=$matches[1]&lid=$matches[2]',
            'top');

        /* check to see if we need to flush the rewrite rules */
        $this->model->maybe_flush_rewrite_rules($query);
    }

    /**
     * Register our PDF custom rewrite rules
     * @since 4.0
     * @return void
     */
    public function register_rewrite_tags( $tags ) {
        $tags[] = 'gf_pdf';
        $tags[] = 'nid';
        $tags[] = 'lid';

        return $tags;
    }

    /**
     * Determines if we should process the PDF at this stage
     * Fires just before the main WP_Query is executed (we don't need it)
     * @since 4.0
     * @return void
     */
    public function process_pdf() {
        echo 'running';

        print_r($_GET);
        echo get_query_var( 'gf_pdf' );
        echo get_query_var( 'nid' );
        echo get_query_var( 'lid' );

        exit;
    }
}