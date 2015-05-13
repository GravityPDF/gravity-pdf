<?php

/**
 * Abstract Helper Model
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
 * A simple abstract class controlers can extent to share similar variables
 * @since 4.0
 */
abstract class GFPDF_Helper_Model {
	
    /**
	 * Classes will store a controler object to allow user access
	 * @var Object
	 * @since 4.0
	 */
	private $controller = null;

    /**
     * Add a controller setter function with type hinting to ensure compatiiblity 
     * @param GFPDF_Helper_Controller $class The controller class
     * @since 4.0
     */
    public function setController(GFPDF_Helper_Controller $class) {
        $this->controller = $class;
    }

    /**
     * Get the controller
     * @since 4.0
     */
    public function getController() {
        return $this->controller;
    }    
}