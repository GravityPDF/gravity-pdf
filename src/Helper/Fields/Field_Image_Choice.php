<?php

namespace GFPDF\Helper\Fields;

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
 * @since 6.12
 *
 * @property \GF_Field_Image_Choice $field
 */
class Field_Image_Choice extends Field_Multi_Choice {

	/**
	 * Return the correct form data information for the selected fields
	 *
	 * @return array
	 *
	 * @since 6.12
	 */
	public function form_data() {
		if ( method_exists( $this->fieldObject, 'form_data' ) ) {
			$data = $this->fieldObject->form_data();
		} else {
			$data = parent::form_data();
		}

		/* Add image URL / Paths */
		$label   = $this->get_label();
		$choices = $this->get_value();

		/* Treat radio and checkbox fields the same */
		$choices = array_filter( ! is_array( $choices ) ? [ $choices ] : $choices );

		foreach ( $choices as $choice ) {
			$attachment_id   = $this->get_attachment_id_from_value( $choice );
			$attachment_data = $this->get_attachment_information( $attachment_id );

			$data['field'][ $this->field->id . '.' . $label . '_image' ][] = $attachment_data;
			$data['field'][ $this->field->id . '_image' ][]                = $attachment_data;
			$data['field'][ $label . '_image' ][]                          = $attachment_data;
		}

		return $data;
	}

	/**
	 * Search for a field choice that has the same value and return the attachment ID
	 *
	 * @param string $value
	 *
	 * @return int The attachment ID or 0 if not found
	 *
	 * @since 6.12
	 */
	protected function get_attachment_id_from_value( $value ) {
		$choices = $this->field->choices;
		if ( ! is_array( $choices ) ) {
			return 0;
		}

		foreach ( $choices as $choice ) {
			$choice_value = $choice['value'] ?? '';
			if ( $choice_value === $value ) {
				return $choice['attachment_id'] ?? 0;
			}
		}

		return 0;
	}

	/**
	 * Return details about the full size image file
	 *
	 * @param int $attachment_id
	 *
	 * @return array
	 *
	 * @since 6.12
	 */
	protected function get_attachment_information( $attachment_id ) {
		$image_url = wp_get_attachment_image_url( $attachment_id, 'full' );

		return [
			'attachment_id' => $attachment_id,
			'url'           => $image_url,
			'path'          => $this->misc->convert_url_to_path( $image_url ),
			'alt'           => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
		];
	}
}
