<?php

namespace GFPDF\Controller;

use GFCommon;
use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Form;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Helper\Helper_Pdf_Queue;
use GFPDF\Model\Model_PDF;
use Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Controller_Save_Core_Fonts
 *
 * @package GFPDF\Controller
 *
 * @since   5.0
 */
class Controller_Pdf_Queue extends Helper_Abstract_Controller {

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 5.0
	 */
	protected $log;

	/**
	 * @var Model_PDF
	 *
	 * @since 5.0
	 */
	protected $model_pdf;

	/**
	 * @var Helper_Pdf_Queue
	 *
	 * @since 5.0
	 */
	protected $queue;

	/**
	 * @var Helper_Form
	 *
	 * @since 6.13.5
	 */
	protected $form;

	/**
	 * @var array An array containing the notification objects to be sent using background processing during a request
	 *
	 * @since 6.11.0
	 * @since 6.13.5 Group by form ID and Entry ID [ formId => [ entryId => [] ] ]
	 */
	protected $form_async_notifications = [];

	/**
	 * Set up our dependencies
	 *
	 * @param Helper_Pdf_Queue $queue
	 * @param Model_PDF        $model_pdf
	 * @param LoggerInterface  $log Our logger class
	 *
	 * @since 5.0
	 */
	public function __construct( Helper_Pdf_Queue $queue, Model_PDF $model_pdf, LoggerInterface $log, Helper_Form $form ) {
		/* Assign our internal variables */
		$this->log       = $log;
		$this->model_pdf = $model_pdf;
		$this->queue     = $queue;
		$this->form      = $form;
	}

	/**
	 * Initialise our class defaults
	 *
	 * @return void
	 * @since 5.0
	 *
	 */
	public function init() {
		add_filter( 'gform_disable_notification', [ $this, 'maybe_disable_submission_notifications' ], 9999, 5 );
		add_action( 'gform_after_submission', [ $this, 'queue_async_form_submission_tasks' ], 5, 2 );
		add_action( 'gform_after_submission', [ $this, 'dispatch_queue' ], 100 );

		add_filter( 'gform_disable_resend_notification', [ $this, 'maybe_disable_resend_notifications' ], 10, 4 );
		add_action( 'gform_post_resend_all_notifications', [ $this, 'queue_dispatch_resend_notification_tasks' ] );
	}

	/**
	 * Disable the form submission notifications if PDF attached and GF async notifications not enabled/notification is enabled
	 *
	 * @param bool  $is_disabled
	 * @param array $notification
	 * @param array $form
	 * @param array $entry
	 * @param array $data
	 *
	 * @return bool
	 *
	 * @since 5.0
	 */
	public function maybe_disable_submission_notifications( $is_disabled, $notification, $form, $entry, $data = [] ) {
		/* Notification already disabled via some other method */
		if ( $is_disabled ) {
			return $is_disabled;
		}

		/* Not form submission event */
		if ( empty( $notification['event'] ) || $notification['event'] !== 'form_submission' ) {
			return $is_disabled;
		}

		/* Skip if the notification is not enabled or conditional logic doesn't pass */
		if ( ! $this->is_notification_enabled( $notification['id'] ?? '', $form, $entry ) ) {
			return $is_disabled;
		}

		/* If Gravity Forms async notification feature is enabled, let it handle the sending process  */
		if ( $this->model_pdf->is_gform_asynchronous_notifications_enabled( [ $notification['id'] ?? '' ], $form, $entry, $data ) ) {
			return $is_disabled;
		}

		/* Check if a PDF should be attached to this notification */
		return $this->should_send_async_notification( $is_disabled, $notification, $form, $entry );
	}

	/**
	 * Disable resend notification if PDF attached
	 *
	 * @param bool  $is_disabled
	 * @param array $notification
	 * @param array $form
	 * @param array $entry
	 *
	 * @return bool
	 *
	 * @since 5.0
	 */
	public function maybe_disable_resend_notifications( $is_disabled, $notification, $form, $entry ) {
		return $this->should_send_async_notification( $is_disabled, $notification, $form, $entry );
	}

	/**
	 * Track the notifications to send asynchronous via Gravity PDF
	 *
	 * @param $is_disabled
	 * @param $notification
	 * @param $form
	 * @param $entry
	 *
	 * @return bool
	 *
	 * @since 6.11.0
	 */
	protected function should_send_async_notification( $is_disabled, $notification, $form, $entry ) {
		$send_async_notification = $this->do_we_disable_notification( $is_disabled, $notification, $form, $entry );
		if ( $send_async_notification ) {
			if ( ! isset( $this->form_async_notifications[ $form['id'] ] ) ) {
				$this->form_async_notifications[ $form['id'] ] = [];
			}

			if ( ! isset( $this->form_async_notifications[ $form['id'] ][ $entry['id'] ] ) ) {
				$this->form_async_notifications[ $form['id'] ][ $entry['id'] ] = [];
			}

			$this->form_async_notifications[ $form['id'] ][ $entry['id'] ][] = $notification;
		}

		return $send_async_notification;
	}

	/**
	 * Determine if a PDF should be included as an email attachment in the current notification
	 *
	 * @param bool  $is_disabled
	 * @param array $notification
	 * @param array $form
	 * @param array $entry
	 *
	 * @return bool
	 *
	 * @since 5.0
	 */
	public function do_we_disable_notification( $is_disabled, $notification, $form, $entry ) {
		$pdfs = $this->model_pdf->get_active_pdfs( $form['gfpdf_form_settings'] ?? [], $entry );

		/* Disable notification if PDF needs to be attached to it */
		foreach ( $pdfs as $pdf ) {
			if ( $this->model_pdf->maybe_attach_to_notification( $notification, $pdf, $entry, $form ) ) {
				$this->log->notice(
					'Gravity Forms Notification Delayed for Async Processing',
					[
						'notification' => $notification,
						'pdf'          => $pdf,
					]
				);

				return true;
			}
		}

		return $is_disabled;
	}

	/**
	 * Queue and send the notifications after the form submission process has completed
	 *
	 * @param array $entry
	 * @param array $form
	 *
	 * @since 5.0
	 * @since 6.13.5 Move queue dispatch to self::queue_async_form_submission_dispatch()
	 */
	public function queue_async_form_submission_tasks( $entry, $form ) {
		$this->queue_async_tasks( $form, $entry );

		if ( count( $this->queue->get_data() ) > 0 ) {
			$this->queue_cleanup_task( $form, $entry );
		}
	}

	/**
	 * Queue and send the notifications after the resend notification process has completed
	 *
	 * @since 5.0
	 * @since 6.13.5 $form and $entry arguments ignored and now set in self::should_send_async_notification()
	 */
	public function queue_dispatch_resend_notification_tasks( $form = null, $entry = null ) {
		if ( ! is_null( $entry ) ) {
			_doing_it_wrong( __METHOD__, '$entry argument ignored and now set in self::should_send_async_notification()', '6.13.5' );
		}

		/* loop over all form/entries */
		foreach ( $this->form_async_notifications as $form_id => $entry_data ) {
			$form = $this->form->get_form( $form_id );
			if ( ! $form ) {
				$this->log->error( 'Form not found', $form_id );
				continue;
			}

			$entry_ids = array_keys( $entry_data );
			foreach ( $entry_ids as $entry_id ) {
				$entry = $this->form->get_entry( $entry_id );
				if ( is_wp_error( $entry ) ) {
					$this->log->error(
						'Entry not found',
						[
							'entry_id'  => $entry_id,
							'exception' => $entry->get_error_message() ,
						]
					);
					continue;
				}

				$this->queue_async_form_submission_tasks( $entry, $form );
			}
		}

		$this->dispatch_queue();
	}

	/**
	 * Push tasks to the queue for requested notifications
	 *
	 * @param array $form
	 * @param array $entry
	 *
	 * @return void
	 *
	 * @since 6.11.0
	 */
	public function queue_async_tasks( $form, $entry ) {
		$notifications = $this->form_async_notifications[ $form['id'] ][ $entry['id'] ] ?? [];
		$tasks         = $this->get_queue_tasks( $entry, $form, $notifications );

		/* Push each task individually for forwards compatibility with new Background Processing update */
		foreach ( $tasks as $task ) {
			$this->queue->push_to_queue( [ $task ] );
		}
	}

	/**
	 * Delete PDFs from disk once all tasks are processed
	 *
	 * @param array $form
	 * @param array $entry
	 *
	 * @return void
	 *
	 * @since 6.11.0
	 */
	public function queue_cleanup_task( $form, $entry ) {
		$this->queue->push_to_queue(
			[
				[
					'id'   => sprintf( 'cleanup-pdf-%d-%d', $form['id'], $entry['id'] ),
					'func' => '\GFPDF\Statics\Queue_Callbacks::cleanup_pdfs',
					'args' => [ $form['id'], $entry['id'] ],
				],
			]
		);
	}

	/**
	 * Dispatch the queue if it has any tasks
	 *
	 * @return void
	 *
	 * @since 6.11.0
	 */
	public function dispatch_queue() {
		if ( count( $this->queue->get_data() ) === 0 ) {
			return;
		}

		$this->queue->save()->dispatch();
		$this->reset_queue();
	}

	/**
	 * Create and dispatch an async queue that will generate the PDFs and send the submission notification(s)
	 * Filters are also available for devs to run processes before or after the tasks
	 *
	 * We use static callbacks to keep the queue database size small (queues are stored in the options table)
	 *
	 * @param array $entry
	 * @param array $form
	 * @param array $notifications
	 *
	 * @return array
	 * @since 5.0
	 *
	 */
	protected function get_queue_tasks( $entry, $form, $notifications = [] ) {
		/* Check if the PDF should be generated  */
		$pdfs = $this->model_pdf->get_active_pdfs( $form['gfpdf_form_settings'] ?? [], $entry );

		$queue_data = apply_filters( 'gfpdf_queue_initialise', [], $entry, $form );

		if ( count( $pdfs ) > 0 ) {
			$pdf_queue_data          = $this->queue_pdfs( $notifications, $pdfs, $form, $entry );
			$notification_queue_data = $this->queue_notifications( $notifications, $pdfs, $form, $entry );

			$queue_data = array_merge( $queue_data, $pdf_queue_data, $notification_queue_data );
		}

		$queue_data = apply_filters( 'gfpdf_queue_pre_dispatch', $queue_data, $entry, $form );

		$this->log->notice(
			'PDF Background Processing Queue',
			[
				'queue' => $queue_data,
			]
		);

		return $queue_data;
	}

	/**
	 * Queue up the PDFs that should always be saved to disk, or should be attached to one of the notifications
	 *
	 * @param array $notifications
	 * @param array $pdfs
	 * @param array $form
	 * @param array $entry
	 *
	 * @return array
	 *
	 * @since 5.0
	 */
	protected function queue_pdfs( $notifications, $pdfs, $form, $entry ) {
		$queue_data = apply_filters( 'gfpdf_queue_pre_pdf_creation', [], $entry, $form );

		foreach ( $pdfs as $pdf ) {
			$pdf_queue_data = [
				'id'   => 'create-pdf-' . $this->get_queue_id( $form, $entry, $pdf ),
				'func' => '\GFPDF\Statics\Queue_Callbacks::create_pdf',
				'args' => [ $entry['id'], $pdf['id'] ],
			];

			/* Check if we need to save the PDF due to a filter */
			if ( $this->model_pdf->maybe_always_save_pdf( $pdf, $form['id'] ) ) {
				$queue_data[] = $pdf_queue_data;
				continue;
			}

			/* If a filter isn't implemented to force the PDF to save, check if it needs to be attached to a notification email */
			foreach ( $notifications as $notification ) {
				if ( $this->model_pdf->maybe_attach_to_notification( $notification, $pdf, $entry, $form ) ) {
					$queue_data[] = $pdf_queue_data;

					/* Only queue each PDF once */
					break;
				}
			}
		}

		$queue_data = apply_filters( 'gfpdf_queue_post_pdf_creation', $queue_data, $entry, $form );

		return $queue_data;
	}

	/**
	 * Queue up the notifications that we delayed because PDFs needed to be attached to them
	 *
	 * @param array $notifications
	 * @param array $pdfs
	 * @param array $form
	 * @param array $entry
	 *
	 * @return array
	 *
	 * @since 5.0
	 */
	protected function queue_notifications( $notifications, $pdfs, $form, $entry ) {

		$queue_data = apply_filters( 'gfpdf_queue_pre_notifications', [], $entry, $form );

		foreach ( $notifications as $notification ) {
			foreach ( $pdfs as $pdf ) {
				if ( $this->model_pdf->maybe_attach_to_notification( $notification, $pdf, $entry, $form ) ) {
					$queue_data[] = [
						'id'   => 'send-notification-' . $this->get_queue_id( $form, $entry, $pdf ) . '-' . $notification['id'],
						'func' => '\GFPDF\Statics\Queue_Callbacks::send_notification',
						'args' => [ $form['id'], $entry['id'], $notification ],
					];

					/* Only queue each notification once */
					break;
				}
			}
		}

		$queue_data = apply_filters( 'gfpdf_queue_post_notifications', $queue_data, $entry, $form );

		return $queue_data;
	}

	/**
	 * Verify the notification is still enabled for the current form/entry when sending
	 *
	 * @param string $notification_id
	 * @param array $form
	 * @param array $entry
	 *
	 * @return bool
	 *
	 * @since 6.11.1
	 */
	protected function is_notification_enabled( $notification_id, $form, $entry ) {
		if ( ! isset( $form['notifications'][ $notification_id ] ) ) {
			return false;
		}

		$notification = $form['notifications'][ $notification_id ];
		if ( isset( $notification['isActive'] ) && ! $notification['isActive'] ) {
			return false;
		}

		if ( ! \GFCommon::evaluate_conditional_logic( rgar( $notification, 'conditionalLogic' ), $form, $entry ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Return the PDF queue ID used for logging
	 *
	 * @param $form
	 * @param $entry
	 * @param $pdf
	 *
	 * @return string
	 *
	 * @since 5.0
	 */
	protected function get_queue_id( $form, $entry, $pdf ) {
		return sprintf(
			'%d-%d-%s',
			$form['id'] ?? 0,
			$entry['id'] ?? 0,
			sanitize_html_class( $pdf['id'] )
		);
	}

	/**
	 * Create a fresh queue in case it needs to be used again this request
	 *
	 * @return void
	 *
	 * @since 6.11.0
	 */
	public function reset_queue() {
		/* Create a fresh queue in case the queue needs to be used again */
		$this->queue                    = new Helper_Pdf_Queue( $this->log );
		$this->form_async_notifications = [];
	}

	/**
	 * Push jobs to our background process queue when resending notifications
	 *
	 * @param $notification
	 * @param $form
	 * @param $entry
	 *
	 * @since 5.0
	 *
	 * @deprecated 6.11
	 */
	public function queue_async_resend_notification_tasks( $notification, $form, $entry ) {
		_doing_it_wrong( esc_html( 'queue_async_resend_notification_tasks() was removed in Gravity PDF 6.11' ) );
	}
}
