<?php

namespace GFPDF\Tests;

use Exception;
use GFPDF\Controller\Controller_Pdf_Queue;
use GFPDF\Helper\Helper_Pdf_Queue;
use GFPDF\Statics\Queue_Callbacks;
use WP_UnitTestCase;

/**
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * Test the model / controller for the Templates UI
 *
 * @since 5.0
 * @group queue
 */
class Test_Pdf_Queue extends WP_UnitTestCase {

	/**
	 * @var Controller_Pdf_Queue
	 * @since 5.0
	 */
	public $controller;

	/**
	 * @var Helper_Pdf_Queue
	 * @since 5.0
	 */
	public $queue;

	/**
	 * @since 5.0
	 */
	public $queue_mock;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 5.0
	 */
	public function setUp() {
		global $gfpdf;

		/* run parent method */
		parent::setUp();

		/* Setup our test classes */
		$this->queue = new Helper_Pdf_Queue( $gfpdf->log );
		$model_pdf   = $gfpdf->singleton->get_class( 'Model_PDF' );

		$this->queue_mock = $this->getMockBuilder( '\GFPDF\Helper\Helper_Pdf_Queue' )
								 ->setConstructorArgs( [ $gfpdf->log ] )
								 ->setMethods( [ 'save', 'dispatch' ] )
								 ->getMock();

		$this->queue_mock->expects( $this->any() )
						 ->method( 'save' )
						 ->will( $this->returnValue( $this->queue_mock ) );

		$this->controller = new Controller_Pdf_Queue( $this->queue_mock, $model_pdf, $gfpdf->log );
	}

	/**
	 * Create our testing data
	 *
	 * @since 4.0
	 */
	private function create_form_and_entries() {
		global $gfpdf;

		$form  = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		$gfpdf->data->form_settings                = [];
		$gfpdf->data->form_settings[ $form['id'] ] = $form['gfpdf_form_settings'];

		return [
			'form'  => $form,
			'entry' => $entry,
		];
	}

	/**
	 * Test our queue runs once when the function runs without any problems
	 *
	 * @since 5.0
	 */
	public function test_queue_tasks() {

		$mock = $this->getMockBuilder( 'stdClass' )
					 ->setMethods( [ 'callback' ] )
					 ->getMock();

		$mock->expects( $this->exactly( 1 ) )
			 ->method( 'callback' );

		$callback = [
			[
				'id'   => 'test',
				'func' => [ $mock, 'callback' ],
			],
		];

		while ( $callback !== false ) {
			$callback = $this->queue->task( $callback );
		}
	}

	/**
	 * Test our queue attempts to run up to three times when a function throws an exception
	 *
	 * @since 5.0
	 */
	public function test_failed_queue_tasks() {
		$mock = $this->getMockBuilder( 'stdClass' )
					 ->setMethods( [ 'callback' ] )
					 ->getMock();

		$mock->expects( $this->exactly( 3 ) )
			 ->method( 'callback' )
			 ->will( $this->throwException( new Exception ) );

		$callback = [
			[
				'id'   => 'test',
				'func' => [ $mock, 'callback' ],
			],
		];

		while ( $callback !== false ) {
			$callback = $this->queue->task( $callback );
		}
	}

	/**
	 * Test our callback is passed the correct arguments
	 *
	 * @since 5.0
	 */
	public function test_arguments_queue_tasks() {
		$mock = $this->getMockBuilder( 'stdClass' )
					 ->setMethods( [ 'callback' ] )
					 ->getMock();

		$mock->expects( $this->exactly( 1 ) )
			 ->method( 'callback' )
			 ->with( 'item1', true, [ 1, 2, 3 ] );

		$callback = [
			[
				'id'   => 'test',
				'func' => [ $mock, 'callback' ],
				'args' => [ 'item1', true, [ 1, 2, 3 ] ],
			],
		];

		while ( $callback !== false ) {
			$callback = $this->queue->task( $callback );
		}
	}

	/**
	 * Ensure we disable the standard form submission notifications when a PDF is being attached
	 *
	 * @since 5.0
	 */
	public function test_maybe_disable_notifications() {
		$results = $this->create_form_and_entries();
		$entry   = $results['entry'];
		$form    = $results['form'];

		$this->assertFalse( $this->controller->maybe_disable_submission_notifications( false, [], $form, $entry ) );
		$this->assertFalse( $this->controller->maybe_disable_submission_notifications( false, [ 'event' => 'paid' ], $form, $entry ) );
		$this->assertFalse(
			$this->controller->maybe_disable_submission_notifications(
				false,
				[
					'id'    => '',
					'event' => 'form_submission',
				],
				$form,
				$entry
			)
		);
		$this->assertTrue(
			$this->controller->maybe_disable_submission_notifications(
				false,
				[
					'id'    => '54bca349732b8',
					'event' => 'form_submission',
				],
				$form,
				$entry
			)
		);
	}

	/**
	 * Test the form submission queue works as expected
	 *
	 * @since 5.0
	 */
	public function test_queue_async_form_submission_tasks() {
		$results                             = $this->create_form_and_entries();
		$entry                               = $results['entry'];
		$form                                = $results['form'];
		$form['notifications']['1254123223'] = $form['notifications']['54bca349732b8'];
		$form['notifications']['54bca349732b8']['isActive'] = true;

		$this->controller->queue_async_form_submission_tasks( $entry, $form );

		$queue = $this->queue_mock->get_data();

		$this->assertSame( 4, count( $queue[0] ) );

		$actions = [ 'create_pdf', 'create_pdf', 'send_notification', 'cleanup_pdfs' ];
		for ( $i = 0; $i < 4; $i++ ) {
			$this->assertNotFalse( strpos( $queue[0][ $i ]['func'], $actions[ $i ] ) );
		}
	}

	/**
	 * Test the resend notification queue works as expected
	 *
	 * @since 5.0
	 */
	public function test_queue_async_resend_notification_tasks() {
		$results = $this->create_form_and_entries();
		$entry   = $results['entry'];
		$form    = $results['form'];
		$form['notifications']['54bca349732b8']['isActive'] = true;

		$this->controller->queue_async_resend_notification_tasks( $form['notifications']['54bca349732b8'], $form, $entry );

		$queue = $this->queue_mock->get_data();

		$this->assertSame( 4, count( $queue[0] ) );

		$actions = [ 'create_pdf', 'create_pdf', 'send_notification', 'cleanup_pdfs' ];
		for ( $i = 0; $i < 4; $i++ ) {
			$this->assertNotFalse( strpos( $queue[0][ $i ]['func'], $actions[ $i ] ) );
		}
	}

	/**
	 * Test our queue dispatch runs only when the queue has data
	 *
	 * @since 5.0
	 */
	public function test_queue_dispatch_resend_notification_tasks() {
		$spy = $this->any();
		$this->queue_mock->expects( $spy )
						 ->method( 'dispatch' )
						 ->will( $this->returnValue( $this->queue_mock ) );

		$this->controller->queue_dispatch_resend_notification_tasks();

		$invocations = $spy->getInvocations();
		$this->assertEquals( 0, count( $invocations ) );

		$this->queue_mock->push_to_queue( 'item' );
		$this->controller->queue_dispatch_resend_notification_tasks();

		$invocations = $spy->getInvocations();
		$this->assertEquals( 1, count( $invocations ) );
	}

	/**
	 * Test PDFs are cleaned up correctly
	 *
	 * @since 5.0
	 */
	public function test_cleanup_pdfs() {
		global $gfpdf;

		$results = $this->create_form_and_entries();
		$entry   = $results['entry'];
		$form    = $results['form'];

		$path = $gfpdf->data->template_tmp_location . $entry['form_id'] . $entry['id'] . '/';
		wp_mkdir_p( $path );
		$test_file = $path . 'file';
		touch( $test_file );
		$this->assertFileExists( $test_file );

		Queue_Callbacks::cleanup_pdfs( $form['id'], $entry['id'] );

		$this->assertFileNotExists( $test_file );
		$this->assertFileNotExists( $path );
	}
}
