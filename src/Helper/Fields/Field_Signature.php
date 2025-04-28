<?php

namespace GFPDF\Helper\Fields;

use GFFormsModel;
use GFPDF\Helper\Helper_Abstract_Fields;

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
 * Controls the display and output of a Gravity Form field
 *
 * @since 4.0
 */
class Field_Signature extends Helper_Abstract_Fields {

	/**
	 * Display the HTML version of this field
	 *
	 * @param string $value
	 * @param bool   $label
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function html( $value = '', $label = true ) {
		$value = $this->value();

		$output = ( ! $this->is_empty() ) ? $value['img'] : ''; /* prevents image loading error when non existent */

		return parent::html( $output );
	}

	/**
	 * Used to check if the current field has a value
	 *
	 * @since    4.0
	 */
	public function is_empty() {
		$value = $this->value();

		/* if the path is both a file and is readable then the field is NOT empty */
		if ( is_file( $value['path'] ) && is_readable( $value['path'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Return the HTML form data
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function form_data() {

		$data = [];

		$value = $this->value();

		$data['signature'][]                              = $value['img'];
		$data['signature_details'][]                      = $value;
		$data['signature_details_id'][ $this->field->id ] = $value;

		return $data;
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return string|array
	 *
	 * @since 4.0
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		/* Get our signature details */
		$signature_name        = $this->get_value();
		$signature_upload_url  = GFFormsModel::get_upload_url_root() . 'signatures/';
		$signature_upload_path = GFFormsModel::get_upload_root() . 'signatures/';

		/*
		 * Add support for https://wordpress.org/plugins/digital-signature-for-gravity-forms/
		 * If uses the same $field->type as Gravity Forms official signature add-on
		 * so we cannot create a new PDF field class to process it.
		 *
		 * Note: if the user does not sign the plugin currently generates an empty file,
		 * so the additional filesize() check has been added
		 */
		if ( is_a( $this->field, '\GFDS_Digital_Signature' ) ) {
			$path                  = $this->misc->convert_url_to_path( $signature_name );
			$signature_upload_path = $path !== false && filesize( $path ) ? trailingslashit( dirname( $path ) ) : $signature_upload_path;
			$signature_upload_url  = trailingslashit( dirname( $signature_name ) );
			$signature_name        = wp_basename( $signature_name );
		}

		/* Get some sane signature defaults */
		$width  = 75;
		$height = 45;
		$html   = '<img src="' . esc_url( $signature_upload_url . $signature_name ) . '" alt="Signature" width="' . esc_attr( $width ) . '" />';

		/* If we can load in the signature let's optimise the signature size for PDF display */
		if ( is_file( $signature_upload_path . $signature_name ) ) {

			/**
			 * [0] Is the original width
			 * [1] Is the original height
			 */
			$signature_details = @getimagesize( $signature_upload_path . $signature_name ); //phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			/**
			 * For optimal image resolution at 96dpi we'll divide the original width by 3
			 *
			 * Add filters to allow the user to change the signature image width in the PDF.
			 *
			 * @param integer The original image width divided by 3
			 * @param integer The original image width
			 */
			if ( $signature_details !== false ) {
				$optimised_width = apply_filters( 'gfpdfe_signature_width', $signature_details[0] / 3, $signature_details[0] ); /* backwards compat */

				/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_signature_width/ for more details about this filter */
				$optimised_width = apply_filters( 'gfpdf_signature_width', $optimised_width, $signature_details[0] );

				$optimised_height = $signature_details[1] / 3;
				$html             = '<img src="' . esc_attr( $signature_upload_path . $signature_name ) . '" alt="Signature" width="' . esc_attr( $optimised_width ) . '" />';

				/* override the default width */
				$width  = $optimised_width;
				$height = $optimised_height;
			}
		}

		/*
		 * Build our signature array
		 */
		$value = [
			'img'    => $html,
			'path'   => $signature_upload_path . $signature_name,
			'url'    => $signature_upload_url . $signature_name,
			'width'  => $width,
			'height' => $height,
		];

		$this->cache( $value );

		return $this->cache();
	}
}
