<?php

/**
 * Test Welcome Screen class
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
 * Test the welcome screen functionality to ensure it functions correctly
 * @since 4.0
 */
class Test_GFPDF_Toolkit_Welcome_Screen extends WP_UnitTestCase
{

    /**
     * Ensure the welcome screen hooks are loaded
     * @group welcome
     * @since 4.0
     */
    public function test_welcome_screen_hooks()
    {
        $controller = new PDF_Controller_Welcome_Screen();

        $this->assertEquals(10, has_action('admin_menu', array($controller->model, 'admin_menus')));
        $this->assertEquals(10, has_action('admin_init', array($controller, 'welcome')));
    }

    /**
     * Run welcome screen tests to ensure it only loads when it's support to
     * @group welcome
     * @since 4.0
     */
    public function test_welcome_screen_loaded()
    {
        $controller        = new PDF_Controller_Welcome_Screen();
        $update_controller = new PDF_Controller_Update();

        if (!defined('WP_NETWORK_ADMIN')) {
            /* check that welcome screen won't display */
            $this->assertFalse($controller->welcome());

            /* enable multi-plugin activation */
            $update_controller->activation(); /* set the welcome screen transient */
            $_GET['activate-multi'] = true;
            $this->assertFalse($controller->welcome());

            unset($_GET['activate-multi']);
            $update_controller->activation(); /* set the welcome screen transient */
            $this->assertEquals(null, $controller->welcome());

            define('WP_NETWORK_ADMIN', true);
            $update_controller->activation(); /* set the welcome screen transient */
            $this->assertFalse($controller->welcome());
        }
    }

    /**
     * Test the view files exist
     * @group welcome
     * @since 4.0
     */
    public function test_view_files_exists()
    {
        $this->assertTrue(is_file(GFPDF_TOOLKIT_PDF_ADDON_PATH.'src/views/html/Welcome/about.php'));
        $this->assertTrue(is_file(GFPDF_TOOLKIT_PDF_ADDON_PATH.'src/views/html/Welcome/tabs.php'));
    }
}
