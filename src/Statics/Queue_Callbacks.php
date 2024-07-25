<?php

namespace GFPDF\Statics;

use Exception;
use GFCommon;
use GFPDF\Helper\Helper_PDF;
use GFPDF\Model\Model_PDF;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Queue_Callbacks
 *
 * @package GFPDF\Helper
 *
 * @since   5.0
 */
class Queue_Callbacks {

	/**
	 * Generate and save a PDF to disk
	 *
	 *  @param int    $entry_id Entry ID to process
	 *  @param string $pdf_id   PDF ID to process
	 *  @param int    $user_id  User ID who triggered the queue
	 *
	 * @throws Exception
	 *
	 * @since 5.0
	 */
	public static function create_pdf( $entry_id, $pdf_id, $user_id = 0 ) {
		$log = GPDFAPI::get_log_class();

		/* Masquerade as the user ID who scheduled the queue so caching and the {user} merge tag works correctly */
		$backup_user_id = get_current_user_id();
		wp_set_current_user( $user_id );

		/* For performance, only generate the PDF if it does not currently exist on disk */
		$pdf = GPDFAPI::create_pdf( $entry_id, $pdf_id );

		/* Reset existing user */
		wp_set_current_user( $backup_user_id );

		if ( is_wp_error( $pdf ) ) {
			$log->error(
				'PDF Generation Error',
				[
					'code'    => $pdf->get_error_code(),
					'message' => $pdf->get_error_message(),
				]
			);

			throw new Exception();
		}

		$log->notice( sprintf( 'PDF successfully generated and saved to %s', $pdf ) );
	}

	/**
	 * Send a Gravity Forms notification
	 *
	 * @param int    $entry_id     Entry ID to process
	 * @param string $pdf_id       PDF ID to process
	 * @param array  $notification Gravity Forms Notification to send
	 * @param int    $user_id      User ID who triggered the queue
	 *
	 * @throws Exception
	 * @since 5.0
	 */
	public static function send_notification( $form_id, $entry_id, $notification, $user_id = 0 ) {
		$log   = GPDFAPI::get_log_class();
		$gform = GPDFAPI::get_form_class();

		$form  = $gform->get_form( $form_id );
		$entry = $gform->get_entry( $entry_id );

		if ( $form === null ) {
			$log->error( 'Could not locate form', [ 'id' => $form_id ] );

			throw new Exception();
		}

		if ( is_wp_error( $entry ) ) {
			$log->error(
				'Entry Error',
				[
					'code'    => $entry->get_error_code(),
					'message' => $entry->get_error_message(),
				]
			);

			throw new Exception();
		}

		/* Masquerade as the user ID who scheduled the queue so caching and the {user} merge tag works correctly */
		$backup_user_id = get_current_user_id();
		wp_set_current_user( $user_id );

		GFCommon::send_notification( $notification, $form, $entry );

		/* Reset existing user */
		wp_set_current_user( $backup_user_id );
	}

	/**
	 * Cleanup PDFs saved to disk
	 *
	 * @param $form_id
	 * @param $entry_id
	 *
	 * @throws Exception
	 *
	 * @since 5.0
	 * @deprecated 6.12 Caching layer + auto-purge added
	 */
	public static function cleanup_pdfs( $form_id, $entry_id ) {
		_doing_it_wrong( __METHOD__, 'This method is deprecated and no alternative is available. The temporary cache is automatically cleaned every hour using the WP Cron.', '6.12' );

		$gform = GPDFAPI::get_form_class();
		$log   = GPDFAPI::get_log_class();

		/** @var Model_PDF $model_pdf */
		$model_pdf = GPDFAPI::get_mvc_class( 'Model_PDF' );

		$form  = $gform->get_form( $form_id );
		$entry = $gform->get_entry( $entry_id );

		if ( $form === null ) {
			$log->error( 'Could not locate form', [ 'id' => $form_id ] );

			throw new Exception();
		}

		if ( is_wp_error( $entry ) ) {
			$log->error(
				'Entry Error',
				[
					'code'    => $entry->get_error_code(),
					'message' => $entry->get_error_message(),
				]
			);

			throw new Exception();
		}

		$model_pdf->cleanup_pdf( $entry, $form );
	}
}
