<?php

namespace GFPDF\Model;
use GFPDF\Helper\Helper_Model;

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
 * Model_Welcome_Screen
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class Model_Welcome_Screen extends Helper_Model {
    
    /**
     * @var string The capability users should have to view the page
     */
    public $minimum_capability = 'manage_options';

    /**
     * Register the Dashboard Welcome pages and then hide them so they aren't displayed in the navigation     
     *
     * @access public
     * @since 4.0
     * @return void
     */
    public function admin_menus() {
        $controller = $this->getController(); 

        add_dashboard_page(
            __( 'Welcome to Gravity PDF', 'pdfextended' ),
            __( 'Welcome to Gravity PDF', 'pdfextended' ),
            $this->minimum_capability,
            'gfpdf-getting-started',
            array( $controller, 'getting_started_screen' )
        );

        add_dashboard_page(
            __( 'What\'s new in Gravity PDF?', 'pdfextended' ),
            __( 'What\'s new in Gravity PDF?', 'pdfextended' ),
            $this->minimum_capability,
            'gfpdf-update',
            array( $controller, 'update_screen' )
        );

        /* hide the new page from the menu bar */
        remove_submenu_page( 'index.php', 'gfpdf-getting-started' );
        remove_submenu_page( 'index.php', 'gfpdf-update' );
    }     
}