<?php

namespace GFPDF\Helper\Fields;

use Exception;
use GF_Field;
use GF_Field_Repeater;
use GF_Fields;
use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Field_Container;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Statics\Kses;
use GPDFAPI;

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
 * @since 5.1
 */
class Field_Repeater extends Helper_Abstract_Fields {

	/**
	 * Check the appropriate variables are parsed in send to the parent construct
	 *
	 * @param object               $field The GF_Field_* Object
	 * @param array                $entry The Gravity Forms Entry
	 *
	 * @param Helper_Abstract_Form $gform
	 * @param Helper_Misc          $misc
	 *
	 * @throws Exception
	 *
	 * @since 5.1
	 */
	public function __construct( $field, $entry, Helper_Abstract_Form $gform, Helper_Misc $misc ) {

		if ( ! is_object( $field ) || ! $field instanceof GF_Field_Repeater ) {
			throw new Exception( '$field needs to be in instance of GF_Field_Repeater' );
		}

		/* call our parent method */
		parent::__construct( $field, $entry, $gform, $misc );
	}

	/**
	 * Return the form data
	 *
	 * @return array
	 *
	 * @throws Exception
	 * @since 5.1
	 */
	public function form_data() {
		$value = $this->get_repeater_form_data( [], $this->value(), $this->field );

		/* Add our List HTML */
		$label = $this->get_label();
		$data['repeater'][ $this->field->id . '.' . $label ] = $value;
		$data['repeater'][ $this->field->id ]                = $value;
		$data['repeater'][ $label ]                          = $value;

		return $data;
	}

	/**
	 * Recursively get the form data array
	 *
	 * @param array $data
	 * @param array $value The current Repeater entry data
	 * @param array $field The current Repeater Field
	 *
	 * @return array
	 * @throws Exception
	 * @since 5.1
	 */
	public function get_repeater_form_data( $data, $value, $field ) {
		$pdf_model = GPDFAPI::get_mvc_class( 'Model_PDF' );
		$products  = new Field_Products( new GF_Field(), $this->entry, $this->gform, $this->misc );

		foreach ( $value as $id => $item ) {
			$item = $this->add_entry_meta( $item );

			/* Loop through the Repeater fields */
			foreach ( $field->fields as $sub_field ) {
				if ( $sub_field instanceof GF_Field_Repeater ) {
					if ( isset( $item[ $sub_field->id ] ) ) {
						$data = array_replace_recursive( $data, [ $id => [ $sub_field->id => $this->get_repeater_form_data( [], $item[ $sub_field->id ], $sub_field ) ] ] );
					}
					continue;
				}

				$class     = $pdf_model->get_field_class( $sub_field, $this->form, $item, $products, $this->get_pdf_config() );
				$form_data = $class->form_data();

				if ( isset( $form_data['field'] ) ) {
					$data = array_replace_recursive( $data, [ $id => $form_data['field'] ] );
				}

				/* Allow HTML to be included in $form_data */
				if ( isset( $form_data['html_id'] ) ) {
					$html = [];
					/* Extract field id and html content */
					foreach ( $form_data['html_id'] as $field_id => $content ) {
						/* Format data, with the exact same order ( label.id,id,label ) with the other fields */
						$html[ $class->field->label . '.' . $field_id ] = $content;
						$html[ $field_id ]                              = $content;
						$html[ $class->field->label ]                   = $content;
					}

					$data = array_replace_recursive( $data, [ $id => $html ] );
				}
			}
		}

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
	 * @throws Exception
	 * @since 5.1
	 */
	public function html( $value = '', $label = true ) {
		$value = $this->value();

		/* Ensure the field outputs the HTML and can be reset to the original value */
		$output_already_enabled = $this->get_output();
		if ( ! $output_already_enabled ) {
			$this->enable_output();
		}

		ob_start();
		$this->get_repeater_html( $value, $this->field );
		$html = ob_get_clean();

		/* Ensure a unique ID for all elements in the Repeater field */
		$i    = 0;
		$html = preg_replace_callback(
			'/id="(.+?)"/',
			function ( $matches ) use ( &$i ) {
				return sprintf(
					'id="repeater-%s-%s-%s"',
					$this->field->id,
					$matches[1],
					$i++
				);
			},
			$html
		);

		/* If output wasn't enabled by default, disable again */
		if ( ! $output_already_enabled ) {
			$this->disable_output();
		}

		if ( $this->get_output() ) {
			Kses::output( $html );
		}

		return $html;
	}

	/**
	 * Output the Repeater HTML
	 *
	 * @param array $value The current Repeater entry data
	 * @param GF_Field_Repeater $field The current Repeater Field
	 *
	 * @throws Exception
	 * @since 5.1
	 */
	public function get_repeater_html( $value, $field ) {
		$is_top_level = $field === $this->field;

		$container = new Helper_Field_Container();
		$container = apply_filters( 'gfpdf_field_container_class', $container );

		$pdf_model = GPDFAPI::get_mvc_class( 'Model_PDF' );
		$products  = new Field_Products( new GF_Field(), $this->entry, $this->gform, $this->misc );

		/* Output the Repeater Label if a sub Repeater */
		if ( $this->maybe_show_section_title( $is_top_level, $field, $value ) ) {
			printf( '<div class="gfpdf-section-title"><h3>%s</h3></div>', esc_html( $field->label ) );
		}

		/* Loop through the entry data for the current repeater */
		foreach ( $value as $item ) {
			if ( $is_top_level ) {
				ob_start();
			}

			$item = $this->add_entry_meta( $item );

			/* Loop through the Repeater fields */
			foreach ( $field->fields as $sub_field ) {
				$sub_field = GF_Fields::create( $sub_field );

				if ( $sub_field instanceof GF_Field_Repeater ) {

					/* Only recursively output if a value exists */
					if ( ! empty( $item[ $sub_field->id ] ) ) {
						echo '<div class="repeater-container">';
						$this->get_repeater_html( $item[ $sub_field->id ], $sub_field );
						echo '</div>';
					}

					continue;
				}

				/* Output a field using the standard method if not empty */
				/** @var Helper_Abstract_Fields $class */
				$class = $pdf_model->get_field_class( $sub_field, $this->form, $item, $products, $this->get_pdf_config() );

				if ( ! $class->is_empty() ) {
					$field->cssClass = '';
					$container->generate( $sub_field );
					$class->enable_output();
					$class->html();
					$container->close( $sub_field );
				}

				unset( $class );
			}

			if ( $is_top_level ) {
				parent::html( ob_get_clean() );
			}
		}
	}

	/**
	 * Allow the Repeater fields to act as an entry by padding the needed values for entry.
	 *
	 * @param array $item
	 *
	 * @return array
	 *
	 * @since 6.4
	 */
	public function add_entry_meta( $item ) {
		$entry_keys = [
			'id',
			'form_id',
			'post_id',
			'date_created',
			'date_updated',
			'is_starred',
			'is_read',
			'ip',
			'source_url',
			'user_agent',
			'currency',
			'payment_status',
			'payment_date',
			'payment_amount',
			'payment_method',
			'transaction_id',
			'is_fulfilled',
			'created_by',
			'transaction_type',
			'status',
		];

		return array_replace( array_intersect_key( $this->entry, array_flip( $entry_keys ) ), $item );
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return string|array
	 *
	 * @since 5.1
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		$this->cache( $this->get_value() );

		return $this->cache();
	}

	/**
	 * Check if the section title should show based on if the repeater field was filled out
	 *
	 * @param bool              $is_top_level
	 * @param GF_Field_Repeater $field
	 * @param mixed             $value
	 *
	 * @return bool
	 *
	 * @since 6.4
	 */
	public function maybe_show_section_title( bool $is_top_level, GF_Field_Repeater $field, $value ): bool {
		return ! $is_top_level && ! $field->empty_deep( $value );
	}
}
