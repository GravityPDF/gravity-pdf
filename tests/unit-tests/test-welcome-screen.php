<?php

namespace GFPDF\Tests;

use GFPDF\Controller\Controller_Welcome_Screen;
use GFPDF\Model\Model_Welcome_Screen;
use GFPDF\View\View_Welcome_Screen;

use WP_UnitTestCase;

/**
 * Test Gravity PDF Welcome Screen Functionality
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
 * Test the model / view / controller for the Welcome Screen
 * @since 4.0
 * @group welcome
 */
class Test_Welcome_Screen extends WP_UnitTestCase
{

	/**
	 * Our Welcome Screen Controller
	 * @var Object
	 * @since 4.0
	 */
	public $controller;

	/**
	 * Our Welcome Screen Model
	 * @var Object
	 * @since 4.0
	 */
	public $model;

	/**
	 * Our Welcome Screen View
	 * @var Object
	 * @since 4.0
	 */
	public $view;

	/**
	 * The WP Unit Test Set up function
	 * @since 4.0
	 */
	public function setUp() {

		/* run parent method */
		parent::setUp();

		/* Setup our test classes */
		$this->model = new Model_Welcome_Screen();
		$this->view  = new View_Welcome_Screen(array(
			'display_version' => PDF_EXTENDED_VERSION,
		) );

		$this->controller = new Controller_Welcome_Screen( $this->model, $this->view );
		$this->controller->init();
	}

	/**
	 * Test the appropriate actions are set up
	 * @since 4.0
	 */
	public function test_actions() {
		$this->assertEquals( 10, has_action( 'admin_menu', array( $this->model, 'admin_menus' ) ) );
		$this->assertEquals( 10, has_action( 'admin_init', array( $this->controller, 'welcome' ) ) );
	}
}
