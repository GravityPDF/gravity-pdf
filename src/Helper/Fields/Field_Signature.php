<?php

namespace GFPDF\Helper\Fields;

use GFFormsModel;
use GFPDF\Helper\Helper_Abstract_Fields;

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
		$signature             = $signature_upload_path . $signature_name;

		/* Get some sane signature defaults */
		$width  = 75;
		$height = 45;
		$html   = '<img src="' . $signature . '" alt="Signature" width="' . $width . '" />';

		/* If we can load in the signature let's optimise the signature size for PDF display */
		if ( is_file( $signature ) ) {

			/**
			 * [0] Is the original width
			 * [1] Is the original height
			 */
			$signature_details = getimagesize( $signature );

			/**
			 * For optimal image resolution at 96dpi we'll divide the original width by 3
			 *
			 * Add filters to allow the user to change the signature image width in the PDF.
			 *
			 * @param integer The original image width divided by 3
			 * @param integer The original image width
			 */
			$optimised_width = apply_filters( 'gfpdfe_signature_width', $signature_details[0] / 3, $signature_details[0] ); /* backwards compat */

			/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_signature_width/ for more details about this filter */
			$optimised_width = apply_filters( 'gfpdf_signature_width', $optimised_width, $signature_details[0] );

			$optimised_height = $signature_details[1] / 3;
			$html             = str_replace( 'width="' . $width . '"', 'width="' . $optimised_width . '"', $html );

			/* override the default width */
			$width  = $optimised_width;
			$height = $optimised_height;
		}

		/*
		 * Build our signature array
		 */
		$value = [
			'img'    => $html,
			'path'   => $signature,
			'url'    => $signature_upload_url . $signature_name,
			'width'  => $width,
			'height' => $height,
		];

		$this->cache( $value );

		return $this->cache();
	}
}
