<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use Exception;
use GFPDF\Controller\Controller_Pdf_Queue;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Pdf_Queue;
use GFPDF\Statics\Cache;
use GFPDF\Statics\Queue_Callbacks;
use GFPDF\Tests\Integration\TestCase;

/**
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * Test the model / controller for the Templates UI
 *
 * @since 5.0
 * @group queue
 */
class Test_Controller_Pdf_Queue extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}

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
	public function set_up(): void {
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

		$this->controller = new Controller_Pdf_Queue( $this->queue_mock, $model_pdf, $gfpdf->log, $gfpdf->gform );
	}

	public function tear_down(): void {
		/*
		 * Background-process batches live in wp_options under the
		 * gfpdf_background_process_batch_* keys. WP rolls back wp_options on
		 * single-site, but if a test fails mid-flight (or an object cache is
		 * active) the rows can leak into later queue tests.
		 */
		if ( $this->queue ) {
			$this->queue->delete_all();
		}

		parent::tear_down();
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
	 * Test our queue attempts to run up to two times when a function throws an exception
	 *
	 * @since 5.0
	 */
	public function test_failed_queue_tasks() {
		$mock = $this->getMockBuilder( 'stdClass' )
					 ->addMethods( [ 'callback' ] )
					 ->getMock();

		$mock->expects( $this->exactly( 2 ) )
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
		$results = $this->form_and_entry();
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
		$results                             = $this->form_and_entry();
		$form                                = $results['form'];
		$entry = $results['entry'];
		$form['notifications']['1254123223'] = $form['notifications']['54bca349732b8'];
		$form['notifications']['54bca349732b8']['isActive'] = true;

		/* Queue multiple entry notifications */
		foreach ( $form['notifications'] as $notification ) {
			$this->controller->maybe_disable_submission_notifications( false, $notification, $form, $entry );
		}
		$this->controller->queue_async_form_submission_tasks( $entry, $form );

		$queue = $this->queue_mock->get_data();

		$this->assertCount( 4, $queue );

		$prefix = "{$form['id']}-{$entry['id']}";
		$this->assertStringContainsString( "create-pdf-$prefix", $queue[0][0]['id'] );
		$this->assertStringContainsString( "create-pdf-$prefix", $queue[1][0]['id'] );
		$this->assertStringContainsString( "send-notification-$prefix", $queue[2][0]['id'] );
		$this->assertStringContainsString( "send-notification-$prefix", $queue[3][0]['id'] );
	}

	/**
	 * Test the resend notification queue works as expected
	 *
	 * @since 5.0
	 */
	public function test_queue_async_resend_notification_tasks() {
		$results = $this->form_and_entry();
		$form    = $results['form'];
		$form['notifications']['54bca349732b8']['isActive'] = true;

		foreach ( $this->entries( 'all-form-fields' ) as $entry ) {
			foreach ( $form['notifications'] as $notification ) {
				$this->controller->maybe_disable_submission_notifications( false, $notification, $form, $entry );
			}
		}

		$this->controller->queue_dispatch_resend_notification_tasks();

		$queue = $this->queue_mock->get_data();

		$this->assertCount( 21, $queue );

		$entries     = $this->entries( 'all-form-fields' );
		$first_entry = "{$form['id']}-{$entries[0]['id']}";
		$last_entry  = "{$form['id']}-{$entries[6]['id']}";

		$this->assertStringContainsString( "create-pdf-$first_entry", $queue[0][0]['id'] );
		$this->assertStringContainsString( "create-pdf-$first_entry", $queue[1][0]['id'] );
		$this->assertStringContainsString( "send-notification-$first_entry", $queue[2][0]['id'] );

		$this->assertStringContainsString( "create-pdf-$last_entry", $queue[18][0]['id'] );
		$this->assertStringContainsString( "create-pdf-$last_entry", $queue[19][0]['id'] );
		$this->assertStringContainsString( "send-notification-$last_entry", $queue[20][0]['id'] );
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

		$this->controller->queue_dispatch_resend_notification_tasks();

		$this->assertSame( 0, $spy->getInvocationCount() );

		$this->queue_mock->push_to_queue( 'item' );
		$this->controller->queue_dispatch_resend_notification_tasks();

		$this->assertSame( 1, $spy->getInvocationCount() );
	}

	/**
	 * Test PDFs are cleaned up correctly
	 *
	 * @since 5.0
	 */
	public function test_cleanup_pdfs() {
		$this->setExpectedDeprecated( 'GFPDF\Statics\Queue_Callbacks::cleanup_pdfs' );
		$this->setExpectedDeprecated( 'GFPDF\Model\Model_PDF::cleanup_pdf' );

		$form_class = \GPDFAPI::get_form_class();

		$results = $this->form_and_entry();
		$entry   = $results['entry'];
		$form    = $form_class->get_form( $results['form']['id'] );

		$path = Cache::get_path( $form, $entry, $form['gfpdf_form_settings']['556690c67856b'] );
		$file = "test-{$form['id']}.pdf";

		wp_mkdir_p( $path );
		touch( $path . $file );

		$this->assertFileExists( $path . $file );

		Queue_Callbacks::cleanup_pdfs( $form['id'], $entry['id'] );

		$this->assertFileDoesNotExist( $path . $file );
		$this->assertFileDoesNotExist( $path );
	}

	/**
	 * Verify any scheduled queues are cleaned up when the queue setting is toggled
	 *
	 * @since 6.12.6
	 */
	public function test_queue_cleanup() {
		global $gfpdf, $wp_settings_errors;

		/*
		 * Wipe state that other tests leak and that quietly breaks settings_sanitize:
		 *  - $wp_settings_errors: prior add_settings_error calls flip update_settings into the empty-output branch (line 1188).
		 *  - gfpdf_settings_user_data transient + $_GET keys: trigger get_settings to return transient instead of DB.
		 */
		$wp_settings_errors = [];
		delete_transient( 'gfpdf_settings_user_data' );
		unset( $_GET['page'], $_GET['subview'] );

		/* Seed gfpdf_settings deterministically and reload the in-memory cache. */
		update_option( 'gfpdf_settings', [ 'background_processing' => 'No' ] );
		$gfpdf->options->set_plugin_settings();

		/* setup page */
		$_POST['option_page'] = 'gfpdf_settings';
		$_POST['_wp_http_referer'] = '/';

		$queue = new Helper_Pdf_Queue( $gfpdf->log );

		/* Create queue and verify  */
		$queue->push_to_queue( 'item1' )->save();
		$queue->push_to_queue( 'item2' )->save();

		$this->assertCount( 2, $queue->get_batches() );

		/* Toggle the settings and verify */
		/** @var Helper_Abstract_Options $options */
		$options = $gfpdf->options;

		$options->update_option( 'background_processing', 'Yes' );
		$options->update_settings( [ 'background_processing' => 'No' ] );

		$this->assertCount( 0, $queue->get_batches() );

		/* Add new queue items, update the settings without toggling and verify the queue remains */
		$queue->push_to_queue( 'item1' )->save();
		$queue->push_to_queue( 'item2' )->save();

		$this->assertCount( 2, $queue->get_batches() );

		$options->update_settings( [ 'background_processing' => 'No' ] );
		$this->assertCount( 2, $queue->get_batches() );
	}
}
