<?php

namespace GFPDF\Helper\Fields;

use Exception;
use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Helper\Helper_QueryPath;

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
class Field_Quiz extends Helper_Abstract_Fields {

	/**
	 * Return the HTML form data
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function form_data() {

		$value = $this->value();
		$label = $this->get_label();
		$data  = [];

		$data['field'][ $this->field->id . '.' . $label ] = $value;
		$data['field'][ $this->field->id ]                = $value;
		$data['field'][ $label ]                          = $value;

		/* Backwards compatible */
		$data['field'][ $this->field->id . '.' . $label . '_name' ] = $value;
		$data['field'][ $this->field->id . '_name' ]                = $value;
		$data['field'][ $label . '_name' ]                          = $value;

		return $data;
	}

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
		$value = apply_filters( 'gform_entry_field_value', $this->get_value(), $this->field, $this->entry, $this->form );

		/* Return early to prevent any problems with when field is empty or the quiz plugin isn't enabled */
		if ( ! class_exists( 'GFQuiz' ) || ! is_string( $value ) || trim( $value ) === false ) {
			return parent::html( '' );
		}

		/* Add a class to the quiz images */
		try {
			$qp     = new Helper_QueryPath();
			$images = $qp->html5( $value, 'img' );

			/* Only target images found in the quiz plugin */
			foreach ( $images as $image ) {
				if ( strpos( $image->attr( 'src' ), 'gravityformsquiz' ) !== false ) {
					$image->addClass( 'gf-quiz-img' );
				}
			}

			$value = $images->top( 'html' )->innerHTML();
		} catch ( Exception $e ) {
			/* do nothing */
		}

		return parent::html( $value );
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return string|array
	 *
	 * @since 4.0
	 */
	public function value() {

		/* Get the field value */
		$value = $this->get_value();
		$value = ( ! is_array( $value ) ) ? [ $value ] : $value;

		$formatted = [];

		/* Loop through our results */
		foreach ( $value as $item ) {
			foreach ( $this->field->choices as $choice ) {
				if ( $choice['value'] === $item ) {
					$formatted[] = [
						'text'      => esc_html( $choice['text'] ),
						'isCorrect' => isset( $choice['gquizIsCorrect'] ) ? $choice['gquizIsCorrect'] : '',
						'weight'    => ( isset( $choice['gquizWeight'] ) ) ? $choice['gquizWeight'] : '',
					];
				}
			}
		}

		/* Ensure results are formatted to v3 expectations */
		if ( 1 === count( $formatted ) ) {
			return $formatted[0];
		}

		/* Return our results, if we have any */
		if ( 0 < count( $formatted ) ) {
			return $formatted;
		}

		/* Return the default expected structure */

		return [];
	}
}
