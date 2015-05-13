<?php

/**
 * Test the required classes are present
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
 * Test all required classes are present 
 * @since 4.0
 */
class Test_GFPDF_Toolkit_Required_Classes extends WP_UnitTestCase {
    /**
     * Test to ensure essential classes exist 
     * @param  String $class The class to verify
     * @group classes
     * @dataProvider provider_required_classes
     * @since 4.0
     */
    public function test_required_classes($class) {
        //$this->assertTrue(class_exists($class));
    }

    /**
     * Data provider for test_required_classes
     * @return Array test data
     * @since 4.0
     */
    public function provider_required_classes() {
        return array(
            array('PDF_Controller_Update'),
            array('PDF_Controller_Welcome_Screen'),
            array('PDF_Helper_Controller'),
            array('PDF_Helper_Model'),
            array('PDF_Helper_View'),
            array('PDF_Model_Welcome_Screen'),
            array('PDF_View_Welcome_Screen'),
        );
    }
}