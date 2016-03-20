<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Data;

use WP_UnitTestCase;

use StdClass;

/**
 * Test Gravity PDF Data Helper Class
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
 * @group data-helper
 */
class Test_Data_Helper extends WP_UnitTestCase {
	/**
	 * Our Gravity PDF Data object
	 *
	 * @var \GFPDF\Helper\Helper_Data
	 *
	 * @since 4.0
	 */
	public $data;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function setUp() {
		/* run parent method */
		parent::setUp();

		/* Setup out loader class */
		$this->data = new Helper_Data();
	}

	/**
	 * Check if our getter / setter is functional with different data types
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

		return array(
			array( 'test', 'This is my test data' ),
			array( 'item1', 20 ),
			array(
				'array',
				array(
					'one',
					'two',
					'three',
				),
			),
			array( 'object', new StdClass() ),
			array( 'object2', $object ),
			array( 'boolean', true ),
			array( 'boolean2', false ),
			array( 'float', 12.2324 ),
			array( 'float2', 0.24 ),
		);
	}

	/**
	 * Test the localised script data
	 *
	 * @since 4.0
	 */
	public function test_localised_script() {
		global $gfpdf;

		$localised_data = $this->data->get_localised_script_data( $gfpdf->options, $gfpdf->gform );
		$required_keys  = array(
			'ajaxurl',
			'GFbaseUrl',
			'pluginUrl',
			'spinnerUrl',
			'spinnerAlt',
			'general_advanced_show',
			'general_advanced_hide',
			'tools_template_copy_confirm',
			'tools_uninstall_confirm',
			'tools_cancel',
			'pdf_list_delete_confirm',
			'active',
			'inactive',
			'conditionalText',
			'conditionalShow',
			'conditionalHide',
			'help_search_placeholder',
			'ajax_error',
			'update_success',
			'delete_success',
			'custom_fonts',
			'no',
			'yes',
			'standard',
			'migration_start',
			'migration_complete',
			'migration_error_specific',
			'migration_error_generic',
			'no_pdfs_found',
			'no_pdfs_found_link',
			'no_template_preview',
		);

		foreach ( $required_keys as $key ) {
			$this->assertArrayHasKey( $key, $localised_data );
		}
	}
}
