<?php

namespace GFPDF\Tests;

use Exception;
use GFPDF\Controller\Controller_Pdf_Queue;
use GFPDF\Helper\Helper_Pdf_Queue;
use GFPDF\Statics\Cache;
use GFPDF\Statics\Queue_Callbacks;
use WP_UnitTestCase;

/**
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * Test the model / controller for the Templates UI
 *
 * @since 5.0
 * @group queue
 */
class Test_Controller_Pdf_Queue extends WP_UnitTestCase {

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
	 * @var \GFPDF\Helper\Helper_Pdf_Queue
	 * @since 5.0
	 */
	public $queue_mock;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 5.0
	 */
	public function set_up() {
		global $gfpdf;

		/* run parent method */
		parent::set_up();

		/* Setup our test classes */
		$this->queue = new Helper_Pdf_Queue( $gfpdf->log );
		$model_pdf   = $gfpdf->singleton->get_class( 'Model_PDF' );

		$this->queue_mock = $this->getMockBuilder( '\GFPDF\Helper\Helper_Pdf_Queue' )
								 ->setConstructorArgs( [ $gfpdf->log ] )
								 ->onlyMethods( [ 'save', 'dispatch' ] )
								 ->getMock();

		$this->queue_mock->method( 'save' )
						 ->willReturn( $this->queue_mock );

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

		$mock->expects( $this->once() )
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

		/* Test we skip inactive notifications */
		$this->assertFalse(
			$this->controller->maybe_disable_submission_notifications(
				false,
				$form['notifications']['54bca349732b8'],
				$form,
				$entry
			)
		);

		$form['notifications']['54bca349732b8']['isActive'] = true;

		$this->assertTrue(
			$this->controller->maybe_disable_submission_notifications(
				false,
				$form['notifications']['54bca349732b8'],
				$form,
				$entry
			)
		);

		/* Test we skip notifications that do not pass conditional logic */
		$form['notifications']['54bca349732b8']['conditionalLogic'] = [
			'logicType' => 'any',
			'rules'     => [
				[
					'fieldId'  => 1,
					'operator' => 'isnot',
					'value'    => 'My Single Line Response',
				],
			],
		];

		$this->assertFalse(
			$this->controller->maybe_disable_submission_notifications(
				false,
				$form['notifications']['54bca349732b8'],
				$form,
				$entry
			)
		);

		$form['notifications']['54bca349732b8']['conditionalLogic']['rules'][0]['operator'] = 'is';

		$this->assertTrue(
			$this->controller->maybe_disable_submission_notifications(
				false,
				$form['notifications']['54bca349732b8'],
				$form,
				$entry
			)
		);

		/* Check the notification is skipped if Gravity Forms Async notifications enabled */
		add_filter( 'gform_is_asynchronous_notifications_enabled', '__return_true' );

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
		$this->assertFalse(
			$this->controller->maybe_disable_submission_notifications(
				false,
				$form['notifications']['54bca349732b8'],
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

		foreach( $form['notifications'] as $notification ) {
			$this->controller->maybe_disable_submission_notifications( false, $notification, $form, $entry );
		}
		
		$this->controller->queue_async_form_submission_tasks( $entry, $form );

		$queue = $this->queue_mock->get_data();

		$this->assertCount( 3, $queue[0] );
		$this->assertCount( 3, $queue[1] );

		$actions = [ 'create_pdf', 'create_pdf', 'send_notification' ];
		for ( $i = 0; $i < 3; $i++ ) {
			$this->assertStringContainsString( $actions[ $i ], $queue[0][ $i ]['func'] );
			$this->assertStringContainsString( $actions[ $i ], $queue[1][ $i ]['func'] );
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

		foreach( $form['notifications'] as $notification ) {
			$this->controller->maybe_disable_resend_notifications( false, $notification, $form, $entry );
		}

		$this->controller->queue_dispatch_resend_notification_tasks( $form, $entry );

		$queue = $this->queue_mock->get_data();

		$this->assertCount( 3, $queue[0] );

		$actions = [ 'create_pdf', 'create_pdf', 'send_notification' ];
		for ( $i = 0; $i < 3; $i++ ) {
			$this->assertStringContainsString( $actions[ $i ], $queue[0][ $i ]['func'] );
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
						 ->willReturn( $this->queue_mock );


		$this->controller->queue_dispatch_resend_notification_tasks( [ 'id' => 0 ], [ 'id' => 0, 'form_id' => 0 ] );

		$this->assertSame( 0, $spy->getInvocationCount() );

		$this->queue_mock->push_to_queue( 'item' );
		$this->controller->queue_dispatch_resend_notification_tasks( [ 'id' => 0 ], [ 'id' => 0, 'form_id' => 0 ]);

		$this->assertSame( 1, $spy->getInvocationCount() );
	}

	/**
	 * Test PDFs are cleaned up correctly
	 *
	 * @since 5.0
	 */
	public function test_cleanup_pdfs() {
		$this->setExpectedIncorrectUsage( 'GFPDF\Statics\Queue_Callbacks::cleanup_pdfs');
		$this->setExpectedIncorrectUsage( 'GFPDF\Model\Model_PDF::cleanup_pdf');

		$form_class = \GPDFAPI::get_form_class();

		$results = $this->create_form_and_entries();
		$entry   = $results['entry'];
		$form    = $form_class->get_form( $results['form']['id'] );  /* get from the database so the date created is accurate */

		$path = Cache::get_path( $form, $entry, $form['gfpdf_form_settings']['556690c67856b'] );
		$file   = "test-{$form['id']}.pdf";

		wp_mkdir_p( $path );
		touch( $path . $file );

		$this->assertFileExists( $path . $file );

		Queue_Callbacks::cleanup_pdfs( $form['id'], $entry['id'] );

		$this->assertFileDoesNotExist( $path . $file );
		$this->assertFileDoesNotExist( $path );
	}
}
