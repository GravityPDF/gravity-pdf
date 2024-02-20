<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Singleton;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Singleton Helper class
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/**
 * @since 4.0
 * @group singleton
 */
class Test_Singleton_Helper extends WP_UnitTestCase {

	/**
	 * Our Gravity PDF Data object
	 *
	 * @var Helper_Singleton
	 *
	 * @since 4.0
	 */
	public $singleton;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function set_up() {

		/* run parent method */
		parent::set_up();

		/* Setup out loader class */
		$this->singleton = new Helper_Singleton();
	}

	/**
	 * Check our add_class() and get_class() methods functions correctly
	 *
	 * @since 4.0
	 */
	public function test_singleton() {
		/* create a test class */
		$data          = new Helper_Data();
		$data->working = 'yes';

		/* Add our class to the singleton */
		$this->singleton->add_class( $data );

		/* Do other stuff with original object */
		$data->stuff = 'completed';

		/* Get our stored class and verify the results */
		$singleton_data = $this->singleton->get_class( 'Helper_Data' );

		/* Run our tests */
		$this->assertNotFalse( $singleton_data );
		$this->assertEquals( 'GFPDF\Helper\Helper_Data', get_class( $singleton_data ) );
		$this->assertEquals( 'yes', $singleton_data->working );
		$this->assertEquals( 'completed', $singleton_data->stuff );

		/* Check for class that doesn't exist */
		$this->assertFalse( $this->singleton->get_class( 'non_existent' ) );
	}

	/**
	 * Ensure Gravity PDF correctly registers all our MVC classes
	 *
	 * @param string $expected
	 * @param string $class
	 *
	 * @since        4.0
	 * @dataProvider provider_registered_classes
	 */
	public function test_registered_classes( $expected, $class ) {
		global $gfpdf;

		$singleton = $gfpdf->singleton;

		$this->assertEquals( $expected, get_class( $singleton->get_class( $class ) ) );

	}

	/**
	 * A data provider for our test_registered_classes() method
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function provider_registered_classes() {
		return [
			[ 'GFPDF\Controller\Controller_Actions', 'Controller_Actions' ],
			[ 'GFPDF\Controller\Controller_Form_Settings', 'Controller_Form_Settings' ],
			[ 'GFPDF\Controller\Controller_Install', 'Controller_Install' ],
			[ 'GFPDF\Controller\Controller_PDF', 'Controller_PDF' ],
			[ 'GFPDF\Controller\Controller_Settings', 'Controller_Settings' ],
			[ 'GFPDF\Controller\Controller_Shortcodes', 'Controller_Shortcodes' ],
			[ 'GFPDF\Model\Model_Actions', 'Model_Actions' ],
			[ 'GFPDF\Model\Model_Form_Settings', 'Model_Form_Settings' ],
			[ 'GFPDF\Model\Model_Install', 'Model_Install' ],
			[ 'GFPDF\Model\Model_PDF', 'Model_PDF' ],
			[ 'GFPDF\Model\Model_Settings', 'Model_Settings' ],
			[ 'GFPDF\Model\Model_Shortcodes', 'Model_Shortcodes' ],
			[ 'GFPDF\View\View_Actions', 'View_Actions' ],
			[ 'GFPDF\View\View_Form_Settings', 'View_Form_Settings' ],
			[ 'GFPDF\View\View_PDF', 'View_PDF' ],
			[ 'GFPDF\View\View_Settings', 'View_Settings' ],
			[ 'GFPDF\View\View_Shortcodes', 'View_Shortcodes' ],
		];
	}

	/**
	 * Check that we can correctly remove actions / filters using the singleton classes
	 *
	 * @since 4.0
	 */
	public function test_remove_actions_filters() {
		global $gfpdf;

		$singleton = $gfpdf->singleton;

		$actions = $singleton->get_class( 'Controller_Actions' );

		/* Verify action exists */
		$this->assertEquals( 10, has_action( 'admin_init', [ $actions, 'route' ] ) );

		/* Remove the action */
		remove_action( 'admin_init', [ $actions, 'route' ] );

		$this->assertFalse( has_action( 'admin_init', [ $actions, 'route' ] ) );
	}
}
