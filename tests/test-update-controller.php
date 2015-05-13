<?php

/**
 * Test Controller_Update class 
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

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
 * Test the activate and deactivate functionality 
 * @since 4.0
 */
class Test_GFPDF_Toolkit_Update_Controller extends WP_UnitTestCase {

    /**
     * Check activation redirect transient which triggers the welcome screen 
     * @group update
     * @since 4.0
     */
    public function test_activation_redirect() {
        $controller = new PDF_Controller_Update();

        /* ensure transient not present */
        $this->assertFalse(get_transient('_gfpdftoolkit_activation_redirect'));

        /* run activation and check transient is present */
        $controller->activation();
        $this->assertTrue(get_transient('_gfpdftoolkit_activation_redirect'));

        /* remove transient and verify it is gone */
        delete_transient('_gfpdftoolkit_activation_redirect');
        $this->assertFalse(get_transient('_gfpdftoolkit_activation_redirect'));
    }
}