<?php

/**
 * Install Update Controller
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
 * Controller_Update
 *
 * A general class for controlling the installation and update procedure 
 *
 * @since 4.0
 */
class GFPDF_Controller_Update
{
    /**
     * Run plugin activation functionality 
     * @since 4.0
     * @return void
     */
    public static function activation() {
        /* Add Upgraded From Option */
        set_transient('_gravitypdf_activation_redirect', true, 30);
    }

    /**
     * Run plugin deactivation functionality
     * @since 4.0
     * @return void
     */
    public static function deactivation() {
        
    }

}