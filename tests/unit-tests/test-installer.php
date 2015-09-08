<?php

namespace GFPDF\Tests;

use GFPDF\Controller\Controller_Install;
use GFPDF\Model\Model_Install;

use WP_UnitTestCase;

/**
 * Test Gravity PDF Actions functionality
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
 * Test the model / controller for the Installer
 * @since 4.0
 * @group install
 */
class Test_Installer extends WP_UnitTestCase
{
    /**
     * Our Controller
     * @var Object
     * @since 4.0
     */
    public $controller;

    /**
     * Our Model
     * @var Object
     * @since 4.0
     */
    public $model;

    /**
     * The WP Unit Test Set up function
     * @since 4.0
     */
    public function setUp() {
        global $gfpdf;

        /* run parent method */
        parent::setUp();

        /* Setup our test classes */
        $this->model = new Model_Install( $gfpdf->form, $gfpdf->log, $gfpdf->data, $gfpdf->misc, $gfpdf->notices );

        $this->controller = new Controller_Install( $this->model, $gfpdf->form, $gfpdf->log, $gfpdf->notices, $gfpdf->data, $gfpdf->misc );
        $this->controller->init();
    }

    /**
     * Test the appropriate actions are set up
     * @since 4.0
     */
    public function test_actions() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Test the appropriate filters are set up
     * @since 4.0
     */
    public function test_filters() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Test our defaults are set up (run by default when plugin loaded so no need to re-run)
     * @since 4.0
     */
    public function test_setup_defaults() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check if the plugin has been installed (otherwise run installer) and the version number is up to date
     * @since 4.0
     */
    public function test_install_status() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check our uninstaller permissions are correct and it actually functions as expected
     * @since 4.0
     */
    public function test_maybe_uninstall() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Test we are marking the plugin as installed correctly
     * @since 4.0
     */
    public function test_install_plugin() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check the multisite template location is set up correctly
     * @since 4.0
     */
    public function test_multisite_template_location() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check our folder structure is created as expected
     * @since 4.0
     */
    public function test_create_folder_structure() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check our rewrite rules get registered correctly
     * @since 4.0
     */
    public function test_register_rewrite_rules() {
    	$this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check our rewrite tags get registered correctly
     * @since 4.0
     */
    public function test_register_rewrite_tags() {
    	$this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check our rewrite rules flusher works
     * @since 4.0
     */
    public function test_maybe_flush_rewrite_rules() {
    	$this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check we are uninstalling correctly
     * @since 4.0
     */
    public function test_uninstall_plugin() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check we are removing all traces of our gfpdf options
     * @since 4.0
     */
    public function test_remove_plugin_options() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check we are successfully removing our GF PDF Settings
     * @since 4.0
     */
    public function test_remove_plugin_form_settings() {
        $this->markTestIncomplete( 'Write unit test' );
    }
}
