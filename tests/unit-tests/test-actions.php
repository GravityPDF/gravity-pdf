<?php

namespace GFPDF\Tests;

use GFPDF\Controller\Controller_Actions;
use GFPDF\Model\Model_Actions;
use GFPDF\View\View_Actions;

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
 * Test the model / view / controller for the Actions MVC
 * @since 4.0
 * @group actions
 */
class Test_Actions extends WP_UnitTestCase
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
     * Our View
     * @var Object
     * @since 4.0
     */
    public $view;

    /**
     * The WP Unit Test Set up function
     * @since 4.0
     */
    public function setUp() {
        global $gfpdf;

        /* run parent method */
        parent::setUp();

        /* Setup our test classes */
        $this->model = new Model_Actions( $gfpdf->form, $gfpdf->options, $gfpdf->notices );
        $this->view  = new View_Actions( array() );

        $this->controller = new Controller_Actions( $this->model, $this->view, $gfpdf->form, $gfpdf->log, $gfpdf->notices );
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
     * Test we are registering the required default routes
     * @since 4.0
     */
    public function test_get_routes() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Test route notices are displayed correctly (verfiy capability, check for dismissal, check condition met)
     * @since 4.0
     */
    public function test_route_notices() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Test the route actions trigger correctly
     * @since 4.0
     */
    public function test_route() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check if the notice dismisal checker is accurate
     * @since 4.0
     */
    public function test_is_notice_already_dismissed() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check the notice dismissal function is accurate
     * @since 4.0
     */
    public function test_dismiss_notice() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check the core review action conditional works as expected
     * @since 4.0
     */
    public function test_review_condition() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check the core migration action conditional works as expected
     * @since 4.0
     */
    public function test_migration_condition() {
        $this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check our primary action button view generates correctly
     * @since 4.0
     */
    public function test_get_action_buttons() {
    	$this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check our review view generates correctly
     * @since 4.0
     */
    public function test_review_plugin() {
    	$this->markTestIncomplete( 'Write unit test' );
    }

    /**
     * Check our migration view generates correctly
     * @since 4.0
     */
    public function test_migration() {
    	$this->markTestIncomplete( 'Write unit test' );
    }
}
