<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Model;

use GFCommon;

/**
 * Welcome Screen Model
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
 * Model_Install
 *
 * Handles the grunt work of our installer / uninstaller
 *
 * @since 4.0
 */
class Model_Install extends Helper_Model {

    /**
     * Get our current installation status
     * @return  String
     * @since  4.0
     */
    public function is_installed() {
        return get_option('gfpdf_is_installed');
    }

    /**
     * Get our permalink regex structure
     * @return  String
     * @since  4.0
     */
    public function get_permalink_regex() {
        return '^pdf/([A-Za-z0-9]+)/([0-9]+)/?';
    }

    /**
     * Get the plugin working directory name
     * @return String
     * @since  4.0
     */
    public function get_working_directory() {
        return apply_filters('gfpdf_working_folder_name', 'PDF_EXTENDED_TEMPLATES');
    }

    public function install() {
            update_option('gfpdf_is_installed', true);
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
            'index.php?gf_pdf=1&pid=$matches[1]&lid=$matches[2]',
            'top');

        /* check to see if we need to flush the rewrite rules */
        $this->maybe_flush_rewrite_rules($query);
    }

    /**
     * Check if we need to force the rewrite rules to be flushed
     * @param  $rule The rule to check
     * @since 4.0
     * @return void
     */
    public function maybe_flush_rewrite_rules($rule) {
        $rules = get_option( 'rewrite_rules' );

        if ( ! isset( $rules[ $rule ] ) ) {
            flush_rewrite_rules(false);
        }
    }

    /**
     * Register our PDF custom rewrite rules
     * @since 4.0
     * @return void
     */
    public function register_rewrite_tags( $tags ) {
        $tags[] = 'gf_pdf';
        $tags[] = 'pid';
        $tags[] = 'lid';

        return $tags;
    }
}