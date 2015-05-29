<?php

namespace GFPDF\Tests;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Class AutoLoader Class
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
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
 * Test the PSR-4 Autoloader Implimentation
 * @since 4.0
 */
class Test_Autoloader extends WP_UnitTestCase
{

    /**
     * Ensure our auto initialiser is firing correctly
     * @group init
     * @since 4.0
     * @dataProvider provider_classes
     */
    public function test_classes($class) {
        $this->assertTrue(class_exists($class));
    }

    /**
     * A data provider to check the classes exist
     * @return Array Our test data
     * @since 4.0
     */
    public function provider_classes() {
        return array(
            array('GFPDF\Controller\Controller_Form_Settings'),
            array('GFPDF\Controller\Controller_Settings'),
            array('GFPDF\Controller\Controller_Welcome_Screen'),

            array('GFPDF\Helper\Helper_Controller'),
            array('GFPDF\Helper\Helper_Data'),
            array('GFPDF\Helper\Helper_Model'),
            array('GFPDF\Helper\Helper_PDF_List_Table'),
            array('GFPDF\Helper\Helper_View'),

            array('GFPDF\Model\Model_Form_Settings'),
            array('GFPDF\Model\Model_Settings'),
            array('GFPDF\Model\Model_Welcome_Screen'),

            array('GFPDF\Stat\Stat_Functions'),

            array('GFPDF\View\View_Form_Settings'),
            array('GFPDF\View\View_Settings'),
            array('GFPDF\View\View_Welcome_Screen'),

        );
    }

    /**
     * Ensure our auto initialiser is firing correctly and loading any interfaces
     * @group init
     * @since 4.0
     * @dataProvider provider_interfaces
     */
    public function test_interface($class) {
        $this->assertTrue(interface_exists($class));
    }

    /**
     * A data provider to check the classes exist
     * @return Array Our test data
     * @since 4.0
     */
    public function provider_interfaces() {
        return array(
            array('GFPDF\Helper\Helper_Int_Actions'),
            array('GFPDF\Helper\Helper_Int_Filters'),
        );
    }

    /**
     * Ensure all depreciated classes have appropriate fallbacks
     * @group init
     * @since 4.0
     */
    public function test_depreciated() {
        $this->markTestIncomplete('This test has not been implimented yet');
    }
}
