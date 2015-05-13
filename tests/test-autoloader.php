<?php

/**
 * Test autoloader class 
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
class Test_GFPDF_Toolkit_Autoloader extends WP_UnitTestCase {
    /**
     * Run autoloader tests 
     * @group autoloader
     * @since 4.0
     */
    public function test_autoloader() {
        $this->assertTrue($this->check_for_autoloader('GFPDF_Overlay_Toolkit_Autoloader', 'helper'));
        $this->assertTrue($this->check_for_autoloader('GFPDF_Overlay_Toolkit_Autoloader', 'controller'));
        $this->assertTrue($this->check_for_autoloader('GFPDF_Overlay_Toolkit_Autoloader', 'model'));
        $this->assertTrue($this->check_for_autoloader('GFPDF_Overlay_Toolkit_Autoloader', 'view'));
    }

    /**
     * Check what classes / methods have been autoloaded 
     * @param  String $class  The class name
     * @param  String $method The method name
     * @return Boolean        Whether class/method is found
     * @since 4.0
     */
    private function check_for_autoloader($class, $method) {
        $autoloader = array_reverse(spl_autoload_functions()); /* Unit test adds a bunch of autoloaders that come before ours. Quicker to reverse the array */

        foreach($autoloader as $type) {
            if(is_array($type) && isset($type[0]) && isset($type[1])) {
                if($class == get_class($type[0]) && $method == $type[1]) {
                    return true;
                }
            }
        }
        return false;
    }
}
