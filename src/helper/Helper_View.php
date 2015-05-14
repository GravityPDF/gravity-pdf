<?php

namespace GFPDF\Helper;
use \WP_Error;

/**
 * Abstract Helper View
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
 * A simple abstract class views can extent to share similar variables
 * @since 4.0
 */
abstract class Helper_View {
    /**
     * Each object should have a view name 
     * @var String
     * @since 4.0
     */    
    protected $ViewType = null;
    
    /**
     * Load a view file based on the filename and type
     * @param  String $filename The filename to load
     * @param  Array $vars Variables to pass to the included file
     * @param  Boolean $output Whether to automatically display the included file or return it's output as a String
     * @return String/Object           The loaded file, or WP_ERROR
     * @since 4.0
     */
    protected function load($filename, $vars = array(), $output = true) {
        $path = PDF_PLUGIN_DIR . 'src/views/html/' . $this->ViewType . '/' . $filename . '.php';
        if(is_readable($path)) {
            if($output) {
                include $path;
                return true;
            } else {
                return $this->buffer($path);
            }
        }
        return new WP_Error('invalid_path', sprintf(__('Cannot find file %s', 'pdfextended'), $filename));
    }

    /**
     * Store output of included file in a buffer and return
     * @param  String $path File path to include
     * @return String       The contents of the included file
     */
    private function buffer($path) {
        ob_start();
        include $path;
        return ob_get_clean();        
    }
}