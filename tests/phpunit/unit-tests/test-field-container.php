<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Field_Container;

use WP_UnitTestCase;
use GF_Field;

use ReflectionClass;

/**
 * Test Gravity PDF Helper_Field_Container class
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * @since 4.0
 * @group field-container
 */
class Test_Field_Container extends WP_UnitTestCase {

	/**
	 * Our Helper_Field_Container
	 *
	 * @var \GFPDF\Helper\Helper_Field_Container
	 *
	 * @since 4.0
	 */
	public $container;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function setUp() {

		/* run parent method */
		parent::setUp();

		/* Setup our test classes */
		$this->container = new Helper_Field_Container();
	}

	/**
	 * Buffers our "generate" output and returns it for testing
	 *
	 * @param  object $field A mockup of the Gravity Form field
	 *
	 * @return string
	 *
	 * @since  4.0
	 */
	private function generate( $field ) {
		ob_start();
		$this->container->generate( $field );

		return ob_get_clean();
	}

	/**
	 * Buffers our "close" output and returns it for testing
	 *
	 * @return string
	 *
	 * @since  4.0
	 */
	private function close() {
		ob_start();
		$this->container->close();

		return ob_get_clean();
	}

	/**
	 * Buffers our "maybe_display_faux_column" output and returns it for testing
	 *
	 * @param GF_Field $field
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	private function maybe_display_faux_column( GF_Field $field ) {
		ob_start();
		$this->container->maybe_display_faux_column( $field );

		return ob_get_clean();
	}

	/**
	 * Check that full rows give the correct output
	 *
	 * @since 4.0
	 */
	public function test_row() {

		$field           = new GF_Field();
		$field->cssClass = 'normal';

		/* Check it opens correctly */
		$this->assertEquals( '<div class="row-separator odd">', $this->generate( $field ) );

		/* Check it closes / opens correctly */
		$this->assertEquals( '</div><div class="row-separator even">', $this->generate( $field ) );

		/* Check it now closes correctly */
		$this->assertEquals( '</div>', $this->close() );
	}

	/**
	 * Check that two-columns give the correct output
	 *
	 * @since 4.0
	 */
	public function test_two_columns() {

		$field           = new GF_Field();
		$field->cssClass = 'gf_left_half';

		/* Check it opens correctly */
		$this->assertEquals( '<div class="row-separator odd">', $this->generate( $field ) );

		$field           = new GF_Field();
		$field->cssClass = 'gf_right_half';

		/* Check it does nothing */
		$this->assertEquals( '', $this->generate( $field ) );

		/* Check the row closes / opens new row correctly */
		$this->assertEquals( '</div><div class="row-separator even">', $this->generate( $field ) );

		/* Check it now closes correctly */
		$this->assertEquals( '</div>', $this->close() );
	}

	/**
	 * Check that three-columns give the correct output
	 *
	 * @since 4.0
	 */
	public function test_three_columns() {

		$field           = new GF_Field();
		$field->cssClass = 'gf_left_third';

		/* Check it opens correctly */
		$this->assertEquals( '<div class="row-separator odd">', $this->generate( $field ) );

		$field           = new GF_Field();
		$field->cssClass = 'gf_middle_third';

		/* Check it does nothing */
		$this->assertEquals( '', $this->generate( $field ) );

		$field           = new GF_Field();
		$field->cssClass = 'gf_right_third';

		/* Check it does nothing */
		$this->assertEquals( '', $this->generate( $field ) );

		/* Check the row closes / opens new row correctly */
		$this->assertEquals( '</div><div class="row-separator even">', $this->generate( $field ) );

		/* Check it now closes correctly */
		$this->assertEquals( '</div>', $this->close() );
	}

	/**
	 * Check that four-columns give the correct output
	 *
	 * @since 4.1
	 */
	public function test_four_columns() {

		$field           = new GF_Field();
		$field->cssClass = 'gf_first_quarter';

		/* Check it opens correctly */
		$this->assertEquals( '<div class="row-separator odd">', $this->generate( $field ) );

		$field           = new GF_Field();
		$field->cssClass = 'gf_second_quarter';

		/* Check it does nothing */
		$this->assertEquals( '', $this->generate( $field ) );

		$field           = new GF_Field();
		$field->cssClass = 'gf_third_quarter';

		/* Check it does nothing */
		$this->assertEquals( '', $this->generate( $field ) );

		$field           = new GF_Field();
		$field->cssClass = 'gf_fourth_quarter';

		/* Check it does nothing */
		$this->assertEquals( '', $this->generate( $field ) );

		/* Check the row closes / opens new row correctly */
		$this->assertEquals( '</div><div class="row-separator even">', $this->generate( $field ) );

		/* Check it now closes correctly */
		$this->assertEquals( '</div>', $this->close() );
	}

	/**
	 * Check that two and three column layouts can intermingle
	 *
	 * @since 4.0
	 */
	public function test_mixture() {

		$field           = new GF_Field();
		$field->cssClass = 'gf_left_third';

		/* Check it opens correctly */
		$this->assertEquals( '<div class="row-separator odd">', $this->generate( $field ) );

		$field           = new GF_Field();
		$field->cssClass = 'gf_left_half';

		/* Check it does nothing */
		$this->assertEquals( '', $this->generate( $field ) );

		/* Check the row closes / opens new row correctly */
		$this->assertEquals( '</div><div class="row-separator even">', $this->generate( $field ) );

		/* Check it now closes correctly */
		$this->assertEquals( '</div>', $this->close() );
	}

	/**
	 * Check that any skipped fields have their containers closed correctly
	 *
	 * @since 4.0
	 */
	public function test_skipped_fields() {
		$field           = new GF_Field();
		$field->cssClass = 'gf_left_third';

		/* Check it opens correctly */
		$this->assertEquals( '<div class="row-separator odd">', $this->generate( $field ) );

		/* Create a skipped field and verify the container closes correctly */
		$field           = new GF_Field();
		$field->cssClass = 'gf_left_third';
		$field->type     = 'html';

		$this->generate( $field );

		/* If the field was skipped we remove any of our column class fields (gf_left_third ect) */
		$this->assertEquals( ' ', $field->cssClass );
	}

	/**
	 * Check that the row closes when a class is present in a field
	 *
	 * @since 4.2
	 */
	public function test_row_stopper() {
		$field           = new GF_Field();
		$field->cssClass = 'gf_left_third';

		/* Check it opens correctly */
		$this->assertEquals( '<div class="row-separator odd">', $this->generate( $field ) );

		/* Create a skipped field and verify the container closes correctly */
		$field           = new GF_Field();
		$field->cssClass = 'gf_middle_third pagebreak';

		/* If the field was skipped we remove any of our column class fields (gf_left_third ect) */
		$this->assertEquals( '</div><div class="row-separator even">', $this->generate( $field ) );

		/* Verify the results */
		$field           = new GF_Field();
		$field->cssClass = 'gf_middle_third';

		$this->assertEquals( '', $this->generate( $field ) );
	}

	/**
	 * Check if the current field is part of a multi-column row and it will fit into that row
	 *
	 * @since 4.0
	 */
	public function test_does_fit_in_row() {

		$field = new GF_Field();

		/* Ensure our container is closed and check we get a false results */
		$this->close();
		$this->assertFalse( $this->container->does_fit_in_row( $field ) );

		/* Add a column to a new row then check if we can add another one */
		$field->cssClass = 'gf_left_third';
		$this->generate( $field );

		$this->assertTrue( $this->container->does_fit_in_row( $field ) );

		/* Overload our column so it won't fit */
		$this->generate( $field );
		$field->cssClass = 'gf_left_half';

		$this->assertFalse( $this->container->does_fit_in_row( $field ) );

		$this->close();
	}

	/**
	 * Check if we should create a placeholder column
	 *
	 * @since 4.0
	 */
	public function test_maybe_display_faux_column() {
		$field = new GF_Field();

		/* Check that nothing is output */
		$this->assertEquals( null, $this->maybe_display_faux_column( $field ) );

		/* Add a column to a new row then see if we get a faux column returned */
		$field->cssClass = 'gf_left_third';
		$this->generate( $field );

		$this->assertNotFalse( strpos( $this->maybe_display_faux_column( $field ), 'gfpdf-column-placeholder' ) );

		/* Overload our column so it won't fit */
		$this->generate( $field );
		$field->cssClass = 'gf_left_half';

		$this->assertEquals( null, $this->maybe_display_faux_column( $field ) );
	}

	/**
	 * Ensure our Helper_Field_Container_Void class overrides all public methods in the parent class, with the
	 * exception of the __construct method
	 *
	 * @since 4.0
	 */
	public function test_helper_field_container_void() {
		$reflection         = new ReflectionClass( 'GFPDF\Helper\Helper_Field_Container_Void' );
		$methods            = $reflection->getMethods( \ReflectionMethod::IS_PUBLIC );
		$total_methods      = sizeof( $methods ) - 1; /* Do not count the __construct public method */
		$overridden_methods = 0;

		foreach ( $methods as $method ) {
			if ( '__construct' !== $method->name && $method->class === $reflection->getName() ) {
				$overridden_methods++;
			}
		}

		$this->assertEquals( $total_methods, $overridden_methods, 'Helper_Field_Container_Void does not override all public methods of the parent object' );
	}
}
