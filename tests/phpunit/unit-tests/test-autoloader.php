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
 *
 * @since 4.0
 * @group autoloader
 */
class Test_Autoloader extends WP_UnitTestCase {

	/**
	 * Ensure our auto initialiser is firing correctly
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_classes
	 */
	public function test_classes( $class ) {
		$this->assertTrue( class_exists( $class ) );
	}

	/**
	 * A data provider to check the classes exist
	 *
	 * @return array Our test data
	 *
	 * @since 4.0
	 */
	public function provider_classes() {
		return array(
			array( 'GFPDF\Controller\Controller_Actions' ),
			array( 'Controller_Activation' ),
			array( 'GFPDF\Controller\Controller_Form_Settings' ),
			array( 'GFPDF\Controller\Controller_Install' ),
			array( 'GFPDF\Controller\Controller_PDF' ),
			array( 'GFPDF\Controller\Controller_Settings' ),
			array( 'GFPDF\Controller\Controller_Shortcodes' ),
			array( 'GFPDF\Controller\Controller_Welcome_Screen' ),

			array( 'GFPDF\Helper\Helper_Abstract_Controller' ),
			array( 'GFPDF\Helper\Helper_Abstract_Fields' ),
			array( 'GFPDF\Helper\Helper_Abstract_Form' ),
			array( 'GFPDF\Helper\Helper_Abstract_Model' ),
			array( 'GFPDF\Helper\Helper_Abstract_View' ),

			array( 'GFPDF\Helper\Helper_Data' ),
			array( 'GFPDF\Helper\Helper_Field_Container' ),
			array( 'GFPDF\Helper\Helper_Form' ),
			array( 'GFPDF\Helper\Helper_Migration' ),
			array( 'GFPDF\Helper\Helper_Misc' ),
			array( 'GFPDF\Helper\Helper_Notices' ),
			array( 'GFPDF\Helper\Helper_Abstract_Options' ),
			array( 'GFPDF\Helper\Helper_Options_Fields' ),
			array( 'GFPDF\Helper\Helper_PDF' ),
			array( 'GFPDF\Helper\Helper_PDF_List_Table' ),

			array( 'GFPDF\Model\Model_Actions' ),
			array( 'GFPDF\Model\Model_Form_Settings' ),
			array( 'GFPDF\Model\Model_Install' ),
			array( 'GFPDF\Model\Model_PDF' ),
			array( 'GFPDF\Model\Model_Settings' ),
			array( 'GFPDF\Model\Model_Shortcodes' ),
			array( 'GFPDF\Model\Model_Welcome_Screen' ),

			array( 'GFPDF\View\View_Actions' ),
			array( 'GFPDF\View\View_Form_Settings' ),
			array( 'GFPDF\View\View_PDF' ),
			array( 'GFPDF\View\View_Settings' ),
			array( 'GFPDF\View\View_Shortcodes' ),
			array( 'GFPDF\View\View_Welcome_Screen' ),

		);
	}

	/**
	 * Ensure our auto initialiser is firing correctly and loading any interfaces
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_interfaces
	 */
	public function test_interface( $class ) {
		$this->assertTrue( interface_exists( $class ) );
	}

	/**
	 * A data provider to check the classes exist
	 *
	 * @return array Our test data
	 *
	 * @since 4.0
	 */
	public function provider_interfaces() {
		return array(
			array( 'GFPDF\Helper\Helper_Interface_Actions' ),
			array( 'GFPDF\Helper\Helper_Interface_Config' ),
			array( 'GFPDF\Helper\Helper_Interface_Filters' ),
		);
	}

	/**
	 * Check our composer files are loaded correctly
	 *
	 * @since        4.0
	 * @dataProvider provider_composer_dependancies
	 */
	public function test_composer_dependancies( $class ) {
		$this->assertTrue( class_exists( $class ) );
	}

	/**
	 * Test we have appropriate composer classes loaded
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function provider_composer_dependancies() {
		return array(
			array( 'mPDF' ),
			array( 'QueryPath' ),
			array( 'Monolog\Logger' ),
			array( 'Monolog\Processor\IntrospectionProcessor' ),
			array( 'Monolog\Processor\MemoryPeakUsageProcessor' ),
			array( 'Monolog\Handler\NullHandler' ),
			array( 'Monolog\Formatter\LineFormatter' ),
			array( 'Monolog\Handler\StreamHandler' ),
			array( 'Monolog\Formatter\LogglyFormatter' ),
			array( 'Monolog\Handler\LogglyHandler' ),
			array( 'Monolog\Handler\BufferHandler' ),
			array( 'Monolog\Processor\WebProcessor' ),
		);
	}
}
