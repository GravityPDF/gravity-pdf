<?php

namespace GFPDF\Stat;

use WP_Error;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Exception;

/**
 * Common Static Functions Shared throughour Gravity PDF
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
 * @since  4.0
 */
class Stat_Functions
{
    /**
     * Check if the current admin page is a Gravity PDF page
     * @since 4.0
     * @return void
     */
    public static function is_gfpdf_page() {
        if(is_admin()) {
            if(isset($_GET['page']) && (substr($_GET['page'], 0, 6) === 'gfpdf-') ||
            (isset($_GET['subview']) && strtoupper($_GET['subview']) === 'PDF')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if we are on the current global settings page / tab
     * @since 4.0
     * @return void
     */
    public static function is_gfpdf_settings_tab($name) {
        if(is_admin()) {
            if(self::is_gfpdf_page()) {
                $tab = (isset($_GET['tab'])) ? $_GET['tab'] : 'general';

                if($name === $tab) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Gravity Forms has a 'type' for each field.
     * Based on that type, attempt to match it to Gravity PDFs field classes
     * @param  String $type The field type we are looking up
     * @return String / Boolean       The Fully Qualified Namespaced Class we matched, or false
     * @since 4.0
     */
    public static function get_field_class($type) {
        /* change our product field types to use a single master product class */
        $convert_product_type = array('quantity', 'option', 'shipping', 'total');

        if(in_array(strtolower($type), $convert_product_type)) {
            $type = 'product';
        }

        /* Change our rank field to be processed as a rating field */
        if($type == 'rank') {
            $type = 'rating';
        }

        /* Format the type name correctly */
        $typeArray = explode('_', $type);
        $typeArray = array_map('ucwords', $typeArray);
        $type      = implode('_', $typeArray);

        /* See if we have a class that matches */
        $fqns = 'GFPDF\Helper\Fields\Field_';
        if(class_exists($fqns . $type)) {
            return $fqns . $type;
        }

        return false;
    }

    /**
     * Converts a name into something a human can more easily read
     * @param  String $name The string to convert
     * @return String
     * @since  4.0
     */
    public static function human_readable($name) {
        $name = str_replace(array('-', '_'), ' ', $name);
        return mb_convert_case($name, MB_CASE_TITLE);
    }

    /**
     * mPDF currently has no cascading CSS ability to target 'inline' elements. Fix image display issues in header / footer
     * by adding a specific class name we can target
     * @param  String $html The HTML to parse
     * @return String
     */
    public static function fix_header_footer($html) {
        try {
            /* return the modified HTML */
            return qp($html, 'img')->addClass('header-footer-img')->top('body')->children()->html();
        } catch (Exception $e) {
            /* if there was any issues we'll just return the $html */
            return $html;
        }
    }

    /**
     * Processes a hex colour and returns an appopriately contrasting black or white
     * @param  String $color The Hex to be inverted
     * @return String
     * @since 4.0
     */
    public static function get_contrast($hexcolor) {
        $hexcolor = str_replace('#', '', $hexcolor);
        
        if (strlen($hexcolor) != 6){
            $hexcolor = str_repeat(substr($hexcolor,0,1),2) . str_repeat(substr($hexcolor,1,1),2) . str_repeat(substr($hexcolor,2,1),2);
        }

        $r   = hexdec(substr($hexcolor,0,2));
        $g   = hexdec(substr($hexcolor,2,2));
        $b   = hexdec(substr($hexcolor,4,2));
        $yiq = ( ($r*299) + ($g*587) + ($b*114) ) / 1000;

        return ($yiq >= 128) ? 'black' : 'white';
    }

    /**
     * Modified version of get_upload_dir() which just focuses on the base directory
     * no matter if single or multisite installation
     * We also only needed the basedir and baseurl so stripped out all the extras
     * @return Array Base dir and url for the upload directory
     */
    public static function get_upload_dir() {
        $siteurl     = get_option('siteurl');
        $upload_path = trim(get_option('upload_path'));
        $dir         = $upload_path;

        if (empty($upload_path) || $upload_path == 'wp-content/uploads') {
            $dir = WP_CONTENT_DIR . '/uploads';
        } elseif (strpos($upload_path, ABSPATH) !== 0) {
            /* $dir is absolute, $upload_path is (maybe) relative to ABSPATH */
            $dir = path_join(ABSPATH, $upload_path);
        }

        /*
         * Honor the value of UPLOADS. This happens as long as ms-files rewriting is disabled.
         * We also sometimes obey UPLOADS when rewriting is enabled -- see the next block.
         */
        if (defined('UPLOADS') && ! (is_multisite() && get_site_option('ms_files_rewriting'))) {
            $dir = ABSPATH . UPLOADS;
        }

        return $dir;
    }

    /**
     * This function recursively deletes all files and folders under the given directory, and then the directory itself
     * equivalent to Bash: rm -r $dir
     * @param String $dir The path to be deleted
     */
    function rmdir($dir) {
        try {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
        
            foreach ($files as $fileinfo) {
                $function = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $function($fileinfo->getRealPath());
            }
        } catch (Exception $e) {
            return new WP_Error('recursion_delete_problem', $e);
        }

        return rmdir($dir);
    }
}
