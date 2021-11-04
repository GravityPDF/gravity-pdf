<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Data;
use StdClass;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Data Helper Class
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/**
 * Test the PSR-4 Autoloader Implementation
 *
 * @since 4.0
 * @group data-helper
 */
class Test_Data_Helper extends WP_UnitTestCase {
	/**
	 * Our Gravity PDF Data object
	 *
	 * @var Helper_Data
	 *
	 * @since 4.0
	 */
	public $data;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function set_up() {
		/* run parent method */
		parent::set_up();

		/* Setup out loader class */
		$this->data = new Helper_Data();
	}

	/**
	 * Check if our getter / setter is functional with different data types
	 *
	 * @param string $key
	 * @param string $val
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_setter
	 */
	public function test_setter( $key, $val ) {
		$this->data->$key = $val;

		$result = $this->data->$key;

		$this->assertSame( $result, $val );
	}

	/**
	 * Check if our isset and unset magic methods work correctly
	 *
	 * @param string $key
	 * @param string $val
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_setter
	 */
	public function test_isset( $key, $val ) {
		/* check data is empty */
		$this->assertFalse( isset( $this->data->$key ) );

		$this->data->$key = $val;

		/* check data exists */
		$this->assertTrue( isset( $this->data->$key ) );

		unset( $this->data->$key );

		/* check data is empty after unset */
		$this->assertFalse( isset( $this->data->$key ) );
	}

	/**
	 * Ensure data accessed is returned by reference
	 *
	 * @since 4.0
	 */
	public function test_by_reference() {
		/* set up data */
		$this->data->item = 'Reference';

		/* assign item, returned by reference and setup so $item refers to $data->item */
		$item = &$this->data->item;

		/* update initial data object */
		$this->data->item = 'Reference Working';

		/* ensure item correctly matches */
		$this->assertEquals( 'Reference Working', $item );
	}

	/**
	 * A data provider used to check the getter / setter functionality is working correctly
	 *
	 * @return array Our test data
	 *
	 * @since 4.0
	 */
	public function provider_setter() {
		$object           = new StdClass();
		$object->item     = 'This';
		$object->function = function() {
			return false;
		};

		return [
			[ 'test', 'This is my test data' ],
			[ 'item1', 20 ],
			[
				'array',
				[
					'one',
					'two',
					'three',
				],
			],
			[ 'object', new StdClass() ],
			[ 'object2', $object ],
			[ 'boolean', true ],
			[ 'boolean2', false ],
			[ 'float', 12.2324 ],
			[ 'float2', 0.24 ],
		];
	}

	/**
	 * Test the localised script data
	 *
	 * @since 4.0
	 */
	public function test_localised_script() {
		global $gfpdf;

		$localised_data = $this->data->get_localised_script_data( $gfpdf->options, $gfpdf->gform );
		$required_keys  = [
			'ajaxUrl',
			'ajaxNonce',
			'currentVersion',
			'pdfWorkingDir',
			'customFontData',
			'spinnerUrl',
			'spinnerAlt',
		];

		foreach ( $required_keys as $key ) {
			$this->assertArrayHasKey( $key, $localised_data );
		}
	}
}
