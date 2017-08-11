<?php

namespace GFPDF\Tests;

use WP_UnitTestCase;

/**
 * Test Gravity PDF Class AutoLoader Class
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2017, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2017, Blue Liquid Designs

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
		return [
			[ 'GFPDF\Controller\Controller_Actions' ],
			[ 'Controller_Activation' ],
			[ 'GFPDF\Controller\Controller_Form_Settings' ],
			[ 'GFPDF\Controller\Controller_Install' ],
			[ 'GFPDF\Controller\Controller_PDF' ],
			[ 'GFPDF\Controller\Controller_Settings' ],
			[ 'GFPDF\Controller\Controller_Shortcodes' ],
			[ 'GFPDF\Controller\Controller_Welcome_Screen' ],

			[ 'GFPDF\Helper\Helper_Abstract_Controller' ],
			[ 'GFPDF\Helper\Helper_Abstract_Fields' ],
			[ 'GFPDF\Helper\Helper_Abstract_Form' ],
			[ 'GFPDF\Helper\Helper_Abstract_Model' ],
			[ 'GFPDF\Helper\Helper_Abstract_View' ],

			[ 'GFPDF\Helper\Helper_Data' ],
			[ 'GFPDF\Helper\Helper_Field_Container' ],
			[ 'GFPDF\Helper\Helper_Form' ],
			[ 'GFPDF\Helper\Helper_Migration' ],
			[ 'GFPDF\Helper\Helper_Misc' ],
			[ 'GFPDF\Helper\Helper_Notices' ],
			[ 'GFPDF\Helper\Helper_Abstract_Options' ],
			[ 'GFPDF\Helper\Helper_Options_Fields' ],
			[ 'GFPDF\Helper\Helper_PDF' ],
			[ 'GFPDF\Helper\Helper_PDF_List_Table' ],
			[ 'GFPDF\Helper\Helper_Templates' ],

			[ 'GFPDF\Model\Model_Actions' ],
			[ 'GFPDF\Model\Model_Form_Settings' ],
			[ 'GFPDF\Model\Model_Install' ],
			[ 'GFPDF\Model\Model_PDF' ],
			[ 'GFPDF\Model\Model_Settings' ],
			[ 'GFPDF\Model\Model_Shortcodes' ],
			[ 'GFPDF\Model\Model_Welcome_Screen' ],

			[ 'GFPDF\View\View_Actions' ],
			[ 'GFPDF\View\View_Form_Settings' ],
			[ 'GFPDF\View\View_PDF' ],
			[ 'GFPDF\View\View_Settings' ],
			[ 'GFPDF\View\View_Shortcodes' ],
			[ 'GFPDF\View\View_Welcome_Screen' ],

		];
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
		return [
			[ 'GFPDF\Helper\Helper_Interface_Actions' ],
			[ 'GFPDF\Helper\Helper_Interface_Config' ],
			[ 'GFPDF\Helper\Helper_Interface_Filters' ],
		];
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
		return [
			[ 'mPDF' ],
			[ 'QueryPath' ],
			[ 'Monolog\Logger' ],
			[ 'Monolog\Processor\IntrospectionProcessor' ],
			[ 'Monolog\Processor\MemoryPeakUsageProcessor' ],
			[ 'Monolog\Handler\NullHandler' ],
			[ 'Monolog\Formatter\LineFormatter' ],
			[ 'Monolog\Handler\StreamHandler' ],
			[ 'Monolog\Formatter\LogglyFormatter' ],
			[ 'Monolog\Handler\LogglyHandler' ],
			[ 'Monolog\Handler\BufferHandler' ],
			[ 'Monolog\Processor\WebProcessor' ],
		];
	}
}
