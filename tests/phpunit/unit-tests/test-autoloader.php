<?php

namespace GFPDF\Tests;

use WP_UnitTestCase;

/**
 * Test Gravity PDF Class AutoLoader Class
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/**
 * Test the PSR-4 Autoloader Implementation
 *
 * @since 4.0
 * @group autoloader
 */
class Test_Autoloader extends WP_UnitTestCase {

	/**
	 * Ensure our auto initializer is firing correctly
	 *
	 * @param string $class
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

			[ 'GFPDF\Helper\Helper_Abstract_Controller' ],
			[ 'GFPDF\Helper\Helper_Abstract_Fields' ],
			[ 'GFPDF\Helper\Helper_Abstract_Form' ],
			[ 'GFPDF\Helper\Helper_Abstract_Model' ],
			[ 'GFPDF\Helper\Helper_Abstract_View' ],

			[ 'GFPDF\Helper\Helper_Data' ],
			[ 'GFPDF\Helper\Helper_Field_Container' ],
			[ 'GFPDF\Helper\Helper_Form' ],
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

			[ 'GFPDF\View\View_Actions' ],
			[ 'GFPDF\View\View_Form_Settings' ],
			[ 'GFPDF\View\View_PDF' ],
			[ 'GFPDF\View\View_Settings' ],
			[ 'GFPDF\View\View_Shortcodes' ],
		];
	}

	/**
	 * Ensure our auto initializer is firing correctly and loading any interfaces
	 *
	 * @param string $class
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
	 * @param string $class
	 *
	 * @since        4.0
	 * @dataProvider provider_composer_dependencies
	 */
	public function test_composer_dependencies( $class ) {
		$this->assertTrue( class_exists( $class ) );
	}

	/**
	 * Test we have appropriate composer classes loaded
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function provider_composer_dependencies() {
		return [
			[ 'GFPDF_Vendor\Mpdf\Mpdf' ],
			[ 'GFPDF_Vendor\Monolog\Logger' ],
			[ 'GFPDF_Vendor\QueryPath' ],
			[ 'GFPDF_Vendor\QueryPath' ],
			[ 'GFPDF_Vendor\Upload\File' ],
		];
	}
}
