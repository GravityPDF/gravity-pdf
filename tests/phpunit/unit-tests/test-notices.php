<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Notices;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Actions functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
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

	public function test_html_notice() {
		$form = 'Message <form method="post"><p><button class="button">Action</button><input class="button primary" type="submit" value="Dismiss" /></p></form>';

		$this->notices->add_notice( $form );

		ob_start();
		$this->notices->process();
		$html = ob_get_clean();

		$this->assertNotFalse( strpos( $html, $form ) );
	}

	/**
	 * Testing hooks form gform_admin_messages and gform_admin_error_messages
	 **/
	public function test_gform_admin_messages_hooks() {
		/* Set up PDF page */
		remove_all_actions( 'init' );
		$_GET['page']    = 'gf_entries';
		$_GET['subview'] = 'PDF';
		set_current_screen( 'dashboard-user' );
		$notice = new Helper_Notices();
		$notice->init();
		do_action( 'init' );

		$notice->add_notice( 'My First Notice.' );
		$notice->add_error( 'My First Error.' );

		/* Run this method to initialize the hooks overrides. */
		$this->assertSame( 'My First Notice.', apply_filters( 'gform_admin_messages', [ 'Global Notice Message.' ] )[0] );
		$this->assertSame( 'My First Error.', apply_filters( 'gform_admin_error_messages', [ 'Global Error Message.' ] )[0] );

		/* Reset actions and $_GET parameters. */
		remove_all_actions( 'gform_admin_messages' );
		remove_all_actions( 'gform_admin_error_messages' );
		unset( $_GET['page'], $_GET['subview'] );

		/* Re-run this method to make sure that the hooks is skipped. */
		$notice->maybe_remove_non_pdf_messages();
		$this->assertSame( 'Global Notice Message.', apply_filters( 'gform_admin_messages', [ 'Global Notice Message.' ] )[0] );
		$this->assertSame( 'Global Error Message.', apply_filters( 'gform_admin_error_messages', [ 'Global Error Message.' ] )[0] );
	}

	public function test_empty_set_gravitypdf_errors() {
		$this->notices->clear( 'errors' );
		$this->assertCount( 0, $this->notices->set_gravitypdf_errors( [] ) );
	}

	public function test_empty_set_gravitypdf_notices() {
		$this->notices->clear( 'notices' );
		$this->assertCount( 0, $this->notices->set_gravitypdf_notices( [] ) );
	}

	/* Test if reset_gravityforms_messages always return empty. */
	public function test_reset_gravityforms_messages() {
		$this->assertCount( 0, $this->notices->reset_gravityforms_messages( [ 'test' ] ) );
	}

	/* Non Filter tests, make sure that errors and messages were properly merged and return. */
	public function test_set_gravitypdf_notices() {
		$this->notices->add_notice( 'My Third Notice.' );
		$this->notices->add_notice( 'My Fourth Notice.' );

		$messages = $this->notices->set_gravitypdf_notices( [ 'My First Notice.', 'My Second Notice.' ] );

		$this->assertCount( 4, $messages );
		/* Test merge positions. */
		$this->assertSame( 'My First Notice.', $messages[0] );
		$this->assertSame( 'My Third Notice.', $messages[2] );

		$this->notices->clear( 'notices' );
		$this->assertCount( 2, $this->notices->set_gravitypdf_notices( [ 'My First Error.', 'My Second Error.' ] ) );
		$this->assertEmpty( $this->notices->set_gravitypdf_notices( [] ) );
	}

	public function test_set_gravitypdf_error() {
		$this->notices->add_error( 'My Third Error.' );
		$this->notices->add_error( 'My Fourth Error.' );

		$messages = $this->notices->set_gravitypdf_errors( [ 'My First Error.', 'My Second Error.' ] );

		$this->assertCount( 4, $messages );
		/* Test merge positions. */
		$this->assertSame( 'My First Error.', $messages[0] );
		$this->assertSame( 'My Third Error.', $messages[2] );

		$this->notices->clear( 'errors' );
		$this->assertCount( 2, $this->notices->set_gravitypdf_errors( [ 'My First Error.', 'My Second Error.' ] ) );
		$this->assertEmpty( $this->notices->set_gravitypdf_errors( [] ) );
	}
}
