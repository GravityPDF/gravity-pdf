<?php

namespace GFPDF\Statics;

use GFPDF\Helper\Helper_PDF;

use Psr\Log\LoggerInterface;
use GFCommon;
use GPDFAPI;
use Exception;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

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
			$log->addError( 'PDF Generation Error', [
				'code'    => $results->get_error_code(),
				'message' => $results->get_error_message(),
			] );

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
	 * @since 5.0
	 */
	public static function send_notification( $form_id, $entry_id, $notification ) {
		$gform = GPDFAPI::get_form_class();

		GFCommon::send_notification( $notification, $gform->get_form( $form_id ), $gform->get_entry( $entry_id ) );
	}

	/**
	 * Cleanup PDFs saved to disk
	 *
	 * @param $form_id
	 * @param $entry_id
	 *
	 * @since 5.0
	 */
	public static function cleanup_pdfs( $form_id, $entry_id ) {
		$gform     = GPDFAPI::get_form_class();
		$data      = GPDFAPI::get_data_class();
		$misc      = GPDFAPI::get_misc_class();
		$templates = GPDFAPI::get_templates_class();
		$log       = GPDFAPI::get_log_class();
		$model_pdf = GPDFAPI::get_mvc_class( 'Model_PDF' );

		$form  = $gform->get_form( $form_id );
		$entry = $gform->get_entry( $entry_id );
		$pdfs  = ( isset( $form['gfpdf_form_settings'] ) ) ? $model_pdf->get_active_pdfs( $form['gfpdf_form_settings'], $entry ) : [];

		foreach ( $pdfs as $pdf ) {
			$notification = ( isset( $pdf['notification'] ) && is_array( $pdf['notification'] ) ) ? $pdf['notification'] : [];
			if ( count( $notification ) > 0 || $pdf['save'] === 'Yes' ) {
				$pdf_generator = new Helper_PDF( $entry, $pdf, $gform, $data, $misc, $templates, $log );
				$misc->rmdir( $pdf_generator->get_path() );
				break;
			}
		}
	}
}