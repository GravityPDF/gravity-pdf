<?php

use GFPDF\Stat\Stat_Functions;
use GFPDF\Router;

/**
 * Depreciated Functionality / Classes
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
 * Add backwards compatibility for expired / renamed classes
 */
class GFPDF_Core
{
    public function __construct()
    {
        global $gfpdf;
        $gfpdf = new Router();
        $gfpdf->init();
    }
}

class PDF_Common
{
    public static function setup_ids() {
    }

    public static function get_upload_dir() {
        return Stat_Functions::get_upload_dir();
    }

    public static function view_data($form_data = array()) {
    }

   /* public static function get_ids() {
    	return Stat_Functions::get_ids();
    }

    public static function get_site_name() {
        return Stat_Functions::get_site_name();
    }

    public static function get_html_template($filename) {
    	return Stat_Functions::get_html_template($filename);
    }

    public static function get_pdf_filename($form_id, $lead_id) {
    	return Stat_Functions::get_pdf_filename($form_id, $lead_id);
    }

    public static function validate_pdf_name($name, $form_id = false, $lead_id = false) {
    	return Stat_Functions::validate_pdf_name($name, $form_id, $lead_id);
    }

    public static function remove_invalid_characters($name) {
    	return Stat_Functions::remove_invalid_characters($name);
    }

    public static function do_mergetags($string, $form_id, $lead_id) {
    	return Stat_Functions::do_mergetags($string, $form_id, $lead_id);
    }



    public static function initialise_WP_filesystem_API($post, $nonce) {
    	return Stat_Functions::initialise_WP_filesystem_API($post, $nonce);
    }

    public static function is_settings() {
    	return Stat_Functions::is_settings();
    }

    public static function post($name) {
    	return Stat_Functions::post($name);
    }

    public static function get($name) {
    	return Stat_Functions::get($name);
    }


    public static function is_currency_decimal_dot($currency = null) {
    	return Stat_Functions::is_currency_decimal_dot($currency);
    }

    public static function add_message($message, $type = 'notice') {
    	return Stat_Functions::add_message($message, $type);
    }*/
}

class GFPDFEntryDetail {
    public static function lead_detail_grid_array() {
        
    }
}
