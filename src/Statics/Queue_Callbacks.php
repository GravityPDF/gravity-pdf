<?php

namespace GFPDF\Statics;

use Exception;
use GFCommon;
use GFPDF\Helper\Helper_PDF;
use GFPDF\Model\Model_PDF;
use GPDFAPI;

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
 * Class _Queue_Callbacks
 *
 * @package GFPDF\Helper
 *
 * @since   5.0
 */
class Queue_Callbacks {

	/**
	 * Generate and save a PDF to disk
	 *
	 * @param $entry_id
	 * @param $pdf_id
	 *
	 * @throws Exception
	 *
	 * @since 5.0
	 */
	public static function create_pdf( $entry_id, $pdf_id ) {
		$log     = GPDFAPI::get_log_class();
		$results = GPDFAPI::create_pdf( $entry_id, $pdf_id );

		if ( is_wp_error( $results ) ) {
			$log->error(
				'PDF Generation Error',
				[
					'code'    => $results->get_error_code(),
					'message' => $results->get_error_message(),
				]
			);

			throw new Exception();
		}
	}

	/**
	 * Send a Gravity Forms notification
	 *
	 * @param int   $form_id
	 * @param int   $entry_id
	 * @param array $notification
	 *
	 * @throws Exception
	 * @since 5.0
	 */
	public static function send_notification( $form_id, $entry_id, $notification ) {
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

		GFCommon::send_notification( $notification, $form, $entry );
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
	 */
	public static function cleanup_pdfs( $form_id, $entry_id ) {
		$gform     = GPDFAPI::get_form_class();
		$data      = GPDFAPI::get_data_class();
		$misc      = GPDFAPI::get_misc_class();
		$templates = GPDFAPI::get_templates_class();
		$log       = GPDFAPI::get_log_class();

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

		$pdfs = ( isset( $form['gfpdf_form_settings'] ) ) ? $model_pdf->get_active_pdfs( $form['gfpdf_form_settings'], $entry ) : [];

		foreach ( $pdfs as $pdf ) {
			$notification = ( isset( $pdf['notification'] ) && is_array( $pdf['notification'] ) ) ? $pdf['notification'] : [];
			if ( count( $notification ) > 0 || $model_pdf->maybe_always_save_pdf( $pdf ) ) {
				$pdf_generator = new Helper_PDF( $entry, $pdf, $gform, $data, $misc, $templates, $log );
				$misc->rmdir( $pdf_generator->get_path() );
				break;
			}
		}
	}
}
