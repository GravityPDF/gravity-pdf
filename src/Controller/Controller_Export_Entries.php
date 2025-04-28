<?php

namespace GFPDF\Controller;

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
 * Class Controller_Export_Entries
 *
 * @package GFPDF\Controller
 */
class Controller_Export_Entries {

	/**
	 * @since 6.0
	 */
	public function init(): void {
		add_filter( 'gform_export_fields', [ $this, 'add_pdfs_to_export_fields' ] );
		add_filter( 'gform_export_field_value', [ $this, 'get_export_field_value' ], 10, 4 );
	}

	/**
	 * Include active PDFs for the form in the Entry Export list
	 *
	 * @since 6.0
	 */
	public function add_pdfs_to_export_fields( $form ) {
		if ( empty( $form['id'] ) ) {
			return $form;
		}

		$pdfs = \GPDFAPI::get_form_pdfs( $form['id'] );

		if ( is_wp_error( $pdfs ) ) {
			return $form;
		}

		foreach ( $pdfs as $pdf ) {
			if ( ! $pdf['active'] ) {
				continue;
			}

			$form['fields'][] = [
				'id'    => 'gpdf_' . $pdf['id'],
				'label' => sprintf( __( 'PDF: %s', 'gravity-pdf' ), $pdf['name'] ),
			];
		}

		return $form;
	}

	/**
	 * If exporting a PDF, get the URL for the entry (if valid)
	 *
	 * @param string           $value
	 * @param int              $form_id
	 * @param string|int|float $field_id
	 * @param array            $entry
	 *
	 * @return string The URL, or an empty string
	 *
	 * @since 6.0
	 */
	public function get_export_field_value( $value, $form_id, $field_id, $entry ) {
		if ( ! isset( $entry['id'] ) || substr( $field_id, 0, 5 ) !== 'gpdf_' ) {
			return $value;
		}

		$pdf_id = substr( $field_id, 5 );

		$shortcode = apply_filters(
			'gfpdf_export_pdf_shortcode',
			sprintf(
				'[gravitypdf id="%1$s" entry="%2$d" raw="1" type="%3$s"]',
				$pdf_id,
				$entry['id'] ?? '',
				'view'
			),
			$pdf_id,
			$entry
		);

		return do_shortcode( $shortcode );
	}
}
