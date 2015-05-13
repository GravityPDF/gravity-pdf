<?php

/**
 * Test PDF_Helper_View class
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
 * Test the helper view class
 * @since 4.0
 */
class Test_GFPDF_Toolkit_Helper_View_Class extends WP_UnitTestCase
{

    /**
     * The path to the tmp file we will create for our testing 
     * @var String
     * @since 4.0
     */
    private $file;

    /**
     * Run class constructor
     * @since 4.0
     */
    public function __construct() {
        $this->file = GFPDF_TOOLKIT_PDF_ADDON_PATH . 'src/views/html/Welcome/phpunit.php';
    }

    /**
     * Run phpunit setup commands 
     * @return void
     * @since 4.0
     */
    public function setUp() {
        parent::setUp();

        /*
         * Create our test file 
         */    
        $this->remove_file($this->file);
        $this->create_file($this->file);
    }

    /**
     * Run phpunit teardown commands 
     * @return void
     * @since 4.0
     */
    public function tearDown() {
        parent::tearDown();
        $this->remove_file($this->file);
    }

    /**
     * Create our tmp test file with appropriate output data
     * @param  String $file The path to the file
     * @return void
     * @since 4.0
     */
    protected function create_file($file) {
        file_put_contents($file, 'This file is for unit testing only');
    }

    /**
     * Remove our tmp test file if it exists
     * @param  String $file The path to the file
     * @return void
     * @since 4.0
     */
    protected function remove_file($file) {
        if(is_file($file)) {
            unlink($file);
        }
    }

    /**
     * Test our PDF_Helper_View abstract class methods
     * @since 4.0
     * @group view
     */
    public function test_load() {
        $method = $this->get_helper_view('load');
        $view = new PDF_View_Welcome_Screen();
        
        /*
         * Test our protected 'load' method 
         */
        $this->assertEquals(true, is_wp_error($method->invokeArgs($view, array('throw_error_report'))));
        $this->assertEquals('This file is for unit testing only', $method->invokeArgs($view, array('phpunit', array(), false)));

        $method->invokeArgs($view, array('phpunit'));
        $this->expectOutputString('This file is for unit testing only');
        
    }

    /**
     * Convert PDF_Helper_View methods to public using a Reflection class
     * @param  String $method The method name to access
     * @return Array          Return a reflection method
     * @since 4.0
     */
    protected function get_helper_view($method) {
        /*
         * Get our class and set the passed in method to public 
         */
        $class = new ReflectionClass('PDF_View_Welcome_Screen');
        $method = $class->getMethod($method);
        $method->setAccessible(true);

        return $method;
    }
}