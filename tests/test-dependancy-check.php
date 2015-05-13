<?php

/**
 * Test initial Gravity PDF Dependancy requirements class
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
 * Test the initial bootup plugin phase
 * @since 4.0
 */
class Test_GFPDF_Toolkit_Dependancy_Checker extends WP_UnitTestCase
{

    /**
     * The plugin constant prefix
     * @since 4.0
     */
    const prefix    = 'GFPDF_TOOLKIT_PDF';

    /**
     * The dependancy tester class name
     * @since 4.0
     */
    const className = 'GFPDF_Overlay_Toolkit_Addon_Fallback';

    /**
     * Ensure correct constants are called
     * @group dependancy
     * @since 4.0
     */
    public function test_constants()
    {
        $this->assertTrue(defined(self::prefix.'_VERSION'));
        $this->assertTrue(defined(self::prefix.'_REQUIRED_VERSION'));
        $this->assertTrue(defined(self::prefix.'_ADDON_PATH'));
        $this->assertTrue(defined(self::prefix.'_ADDON_URL'));
        $this->assertTrue(defined(self::prefix.'_ADDON_BASENAME'));
    }

    /**
     * Test that GFPDF_Overlay_Toolkit_Addon_Fallback::plugins_loaded has been correctly added to 'plugins_loaded'
     * @group dependancy
     * @since 4.0
     */
    public function test_loader()
    {
        /* create new instance of class */
        $class = self::className;
        $router = new $class('', '', '');

        /* test dependancies */
        $this->assertEquals(10, has_action('plugins_loaded', array($router, 'plugins_loaded')));
        $this->assertTrue(class_exists('GFPDF_Core'));
    }

    /**
     * [test_version_requirements description]
     * @param  String $version The version number to to our test on 
     * @param   String $expected The expected results from the assertion     
     * @since 4.0
     * @group dependancy
     * @dataProvider provider_version_test
     */
    public function test_version_requirements($version, $expected)
    {
        $class = self::className;
        $test = new $class('', '', $version);
        $this->assertEquals($expected, $test->plugins_loaded());
    }

    /**
     * The data provider for the run_ip_test() function
     * @group dependancy
     * @since 4.0
     */
    public function provider_version_test()
    {
        $version    = explode('.', PDF_EXTENDED_VERSION);

        $expected   = array();
        $expected[] = array(implode('.', $version), null);
        $expected[] = array(implode('.', $version).'-alpha', false);
        $expected[] = array(implode('.', $version).'-beta', false);
        $expected[] =  array(implode('.', $version).'-rc1', false);

        $test       = $version;
        $test[1] + 1;
        $expected[] = array(implode('.', $test), null);

        if(isset($test[2])) {
            $test       = $version;
            $test[2] + 1;
            $expected[] = array(implode('.', $test), null);
        }

        $test       = $version;
        $test[1] + 1;
        $expected[] = array(implode('.', $test), null);

        $test       = $version;
        $test[1] - 1;
        $expected[] = array(implode('.', $test), false);

        if(isset($test[2])) {
            $test       = $version;
            $test[2] - 1;
            $expected[] = array(implode('.', $test), false);
        }

        $test       = $version;
        $test[1] - 1;
        $expected[] = array(implode('.', $test), false);

        return $expected;
    }
}
