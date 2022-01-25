<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Notices;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Actions functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/**
 * Test the Helper_Notices class
 *
 * @since 4.0
 * @group notices
 */
class Test_Notices extends WP_UnitTestCase {
	/**
	 * Our notice object
	 *
	 * @var Helper_Notices
	 *
	 * @since 4.0
	 */
	public $notices;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function set_up() {

		/* run parent method */
		parent::set_up();

		/* Setup our test classes */
		$this->notices = new Helper_Notices();
		$this->notices->init();
	}

	/**
	 * Test the appropriate actions are set up
	 *
	 * @since 4.0
	 */
	public function test_actions() {
		$this->assertEquals( 10, has_action( 'admin_notices', [ $this->notices, 'process' ] ) );
	}

	/**
	 * Check we can correctly add a notice
	 *
	 * @since 4.0
	 */
	public function test_add_notice() {

		$this->assertFalse( $this->notices->has_notice() );
		$this->notices->add_notice( 'My First Notice' );
		$this->assertTrue( $this->notices->has_notice() );

		/* Cleanup notices */
		$this->notices->clear();
	}

	/**
	 * Check we can correctly add an error
	 *
	 * @since 4.0
	 */
	public function test_add_error() {

		$this->assertFalse( $this->notices->has_error() );
		$this->notices->add_error( 'My First Error' );
		$this->assertTrue( $this->notices->has_error() );

		/* Cleanup notices */
		$this->notices->clear();
	}

	/**
	 * Ensure we can clear notices correctly
	 *
	 * @since 4.0
	 */
	public function test_clear() {

		/* Load some data */
		$this->notices->add_notice( 'My First Notice' );
		$this->notices->add_notice( 'My First Notice' );
		$this->notices->add_error( 'My First Error' );
		$this->notices->add_error( 'My First Error' );

		/* Verify that data */
		$this->assertTrue( $this->notices->has_notice() );
		$this->assertTrue( $this->notices->has_error() );

		/* Clear all notices */
		$this->notices->clear( 'all' );

		$this->assertFalse( $this->notices->has_notice() );
		$this->assertFalse( $this->notices->has_error() );

		/* Test clearing errors only */
		$this->notices->add_notice( 'My First Notice' );
		$this->notices->add_notice( 'My First Notice' );
		$this->notices->add_error( 'My First Error' );
		$this->notices->add_error( 'My First Error' );

		$this->notices->clear( 'errors' );

		$this->assertTrue( $this->notices->has_notice() );
		$this->assertFalse( $this->notices->has_error() );

		/* Test clearing notices only */
		$this->notices->add_notice( 'My First Notice' );
		$this->notices->add_notice( 'My First Notice' );
		$this->notices->add_error( 'My First Error' );
		$this->notices->add_error( 'My First Error' );

		$this->notices->clear( 'notices' );

		$this->assertFalse( $this->notices->has_notice() );
		$this->assertTrue( $this->notices->has_error() );
	}

	/**
	 * Ensure we display / process errors and notices correctly
	 *
	 * @since 4.0
	 */
	public function test_process() {

		$this->notices->add_notice( 'My First Notice' );
		$this->notices->add_error( 'My First Error' );

		ob_start();
		$this->notices->process();
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, '<p>My First Notice</p>' ) );
		$this->assertNotFalse( strpos( $html, '<p>My First Error</p>' ) );
	}
}
