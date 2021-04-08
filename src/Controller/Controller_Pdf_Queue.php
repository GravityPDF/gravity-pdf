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
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
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
class Controller_Pdf_Queue extends Helper_Abstract_Controller implements Helper_Interface_Actions, Helper_Interface_Filters {

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var Helper_Form
	 *
	 * @since 5.0
	 */
	protected $gform;

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
	 * Determines if our PDF queue should execute
	 *
	 * @var bool
	 *
	 * @since 5.0
	 */
	protected $disable_queue = false;

	/**
	 * Set up our dependencies
	 *
	 * @param Helper_Pdf_Queue $queue
	 * @param Model_PDF        $model_pdf
	 * @param LoggerInterface  $log Our logger class
	 *
	 * @since 5.0
	 */
	public function __construct( Helper_Pdf_Queue $queue, Model_PDF $model_pdf, LoggerInterface $log ) {
		/* Assign our internal variables */
		$this->log       = $log;
		$this->model_pdf = $model_pdf;
		$this->queue     = $queue;
	}

	/**
	 * Initialise our class defaults
	 *
	 * @return void
	 * @since 5.0
	 *
	 */
	public function init() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Apply any actions needed for the welcome page
	 *
	 * @return void
	 * @since 5.0
	 *
	 */
	public function add_actions() {
		add_action( 'gform_after_submission', [ $this, 'queue_async_form_submission_tasks' ], 5, 2 );

		add_action( 'gform_post_resend_notification', [ $this, 'queue_async_resend_notification_tasks' ], 10, 3 );
		add_action( 'gform_post_resend_all_notifications', [ $this, 'queue_dispatch_resend_notification_tasks' ] );
	}

	/**
	 * @return void
	 * @since 5.0
	 *
	 */
	public function add_filters() {
		add_filter( 'gform_disable_notification', [ $this, 'maybe_disable_submission_notifications' ], 9999, 4 );
		add_filter( 'gform_disable_resend_notification', [ $this, 'maybe_disable_resend_notifications' ], 10, 4 );
	}

	/**
	 * Only process notifications that occur on form submission
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
	public function maybe_disable_submission_notifications( $is_disabled, $notification, $form, $entry ) {

		/* If a plugin has already disabled notifications we won't queue up the notifications/PDFs as a background process */
		if ( $is_disabled ) {
			$this->disable_queue = true;

			return $is_disabled;
		}

		if ( empty( $notification['event'] ) || $notification['event'] !== 'form_submission' ) {
			return $is_disabled;
		}

		return $this->do_we_disable_notification( $is_disabled, $notification, $form, $entry );
	}

	/**
	 * Only process notifications that occur when resending notifications
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
		return $this->do_we_disable_notification( $is_disabled, $notification, $form, $entry );
	}

	/**
	 * Check if there are any PDFs that need to be sent with the notifications and disable so we can process in the background
	 *
	 * @param bool  $default
	 * @param array $notification
	 * @param array $form
	 * @param array $entry
	 *
	 * @return bool
	 *
	 * @since 5.0
	 */
	public function do_we_disable_notification( $default, $notification, $form, $entry ) {
		$pdfs = ( isset( $form['gfpdf_form_settings'] ) ) ? $this->model_pdf->get_active_pdfs( $form['gfpdf_form_settings'], $entry ) : [];

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

		return $default;
	}

	/**
	 * Queue all PDFs/Notifications during form submission and dispatch
	 *
	 * @param $entry
	 * @param $form
	 *
	 * @since 5.0
	 */
	public function queue_async_form_submission_tasks( $entry, $form ) {
		if ( ! $this->disable_queue ) {
			/* Push and trigger async queue */
			$this->queue
				->push_to_queue( $this->get_queue_tasks( $entry, $form ) )
				->save()
				->dispatch();
		}

		$this->disable_queue = false;
	}

	/**
	 * Push jobs to our background process queue when resending notifications
	 *
	 * @param $notification
	 * @param $form
	 * @param $entry
	 *
	 * @since 5.0
	 */
	public function queue_async_resend_notification_tasks( $notification, $form, $entry ) {
		add_filter( 'gfpdf_maybe_always_save_pdf', '__return_false' );

		/* Push to async queue */
		$this->queue->push_to_queue( $this->get_queue_tasks( $entry, $form, [ $notification ] ) );

		remove_filter( 'gfpdf_maybe_always_save_pdf', '__return_false' );
	}

	/**
	 * If we have any jobs in our background process queue after resending the notifications, dispatch them
	 *
	 * @since 5.0
	 */
	public function queue_dispatch_resend_notification_tasks() {
		if ( count( $this->queue->get_data() ) > 0 ) {
			$this->queue
				->save()
				->dispatch();
		}
	}

	/**
	 * Create and dispatch an async queue that will generate the PDFs and send the submission notification(s)
	 * Filters are also available for devs to run processes before or after the tasks
	 *
	 * We use static callbacks to keep the queue database size small (queues are stored in the options table)
	 *
	 * @param $entry
	 * @param $form
	 * @param $notifications
	 *
	 * @return array
	 * @since 5.0
	 *
	 */
	protected function get_queue_tasks( $entry, $form, $notifications = [] ) {
		/* Check if the PDF should be generated  */
		$pdfs = ( isset( $form['gfpdf_form_settings'] ) ) ? $this->model_pdf->get_active_pdfs( $form['gfpdf_form_settings'], $entry ) : [];

		$queue_data = apply_filters( 'gfpdf_queue_initialise', [], $entry, $form );

		/* Queue up the PDF generation callback */
		if ( count( $pdfs ) > 0 ) {
			$notifications = ( count( $notifications ) > 0 ) ? $notifications : $this->get_active_notifications( $form, $entry );

			$pdf_queue_data          = $this->queue_pdfs( $notifications, $pdfs, $form, $entry );
			$notification_queue_data = $this->queue_notifications( $notifications, $pdfs, $form, $entry );

			$queue_data = array_merge( $queue_data, $pdf_queue_data, $notification_queue_data );

			/* Queue up a cleanup callback */
			if ( count( $pdf_queue_data ) > 0 ) {
				$queue_data[] = [
					'id'   => 'cleanup-pdf-' . $form['id'] . '-' . $entry['id'],
					'func' => '\GFPDF\Statics\Queue_Callbacks::cleanup_pdfs',
					'args' => [ $form['id'], $entry['id'] ],
				];
			}
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
	 * Get all active notifications who's conditional logic has been met
	 *
	 * @param $form
	 * @param $entry
	 *
	 * @return array
	 *
	 * @since 5.0
	 */
	protected function get_active_notifications( $form, $entry ) {
		$notifications = GFCommon::get_notifications_to_send( 'form_submission', $form, $entry );
		$notifications = array_filter(
			$notifications,
			function( $notification ) {
				return ( ! isset( $notification['isActive'] ) || $notification['isActive'] );
			}
		);

		return $notifications;
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
			foreach ( $notifications as $notification ) {
				if ( $this->model_pdf->maybe_always_save_pdf( $pdf, $form['id'] ) || $this->model_pdf->maybe_attach_to_notification( $notification, $pdf, $entry, $form ) ) {
					$queue_data[] = [
						'id'            => $this->get_queue_id( $form, $entry, $pdf ),
						'func'          => '\GFPDF\Statics\Queue_Callbacks::create_pdf',
						'args'          => [ $entry['id'], $pdf['id'] ],
						'unrecoverable' => true,
					];

					/* Only queue each PDF once (even if attached to multiple notifications) */
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
						'id'   => $this->get_queue_id( $form, $entry, $pdf ) . '-' . $notification['id'],
						'func' => '\GFPDF\Statics\Queue_Callbacks::send_notification',
						'args' => [ $form['id'], $entry['id'], $notification ],
					];

					/* Only queue each notification once (even if there are multiple PDFs) */
					break;
				}
			}
		}

		$queue_data = apply_filters( 'gfpdf_queue_post_notifications', $queue_data, $entry, $form );

		return $queue_data;
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
		return $form['id'] . '-' . $entry['id'] . '-' . $pdf['id'];
	}
}
