<?php

namespace GFPDF\Helper\Fields;

use Exception;
use GF_Field;
use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Field_Container;
use GFPDF\Helper\Helper_Field_Container_Gf25;
use GFPDF\Helper\Helper_Misc;
use GP_Field_Nested_Form;
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
class Field_Form extends Helper_Abstract_Fields {

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

		if ( ! is_object( $field ) || ! $field instanceof GP_Field_Nested_Form ) {
			throw new Exception( '$field needs to be in instance of GP_Field_Nested_Form' );
		}

		/* call our parent method */
		parent::__construct( $field, $entry, $gform, $misc );
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
		/* Get the Nested Form */
		$form = apply_filters( 'gfpdf_current_form_object', $this->gform->get_form( $this->field->gpnfForm ), $this->entry, __FUNCTION__ );
		if ( is_wp_error( $form ) ) {
			return parent::html();
		}

		$html = '';

		/* Get the Nested Form Entries */
		$value    = explode( ',', $this->value() );
		$field_id = $this->field->id;
		foreach ( $value as $key => $id ) {
			$entry = $this->gform->get_entry( (int) trim( $id ) );
			if ( is_wp_error( $entry ) ) {
				continue;
			}

			/* Output the entry HTML mark-up */
			$markup = $this->get_repeater_html( $form, $entry );

			/* Ensure the IDs are all unique by suffixing with the key */
			$markup = preg_replace( '/id="(.+?)"/', 'id="nested-$1-' . esc_attr( $key ) . '"', $markup );

			$this->field->id = "$field_id-$key";

			$html .= parent::html( $markup );
		}

		/* Reset the ID back to the original value */
		$this->field->id = $field_id;

		return $html;
	}

	/**
	 * Output a nested form entry
	 *
	 * @param array $form
	 * @param array $entry
	 *
	 * @return false|string
	 *
	 * @throws Exception
	 * @since 5.1
	 */
	public function get_repeater_html( $form, $entry ) {
		ob_start();

		$container = ! \GFCommon::is_legacy_markup_enabled( $form['id'] ) ? new Helper_Field_Container_Gf25() : new Helper_Field_Container();
		$container = apply_filters( 'gfpdf_field_container_class', $container );

		$pdf_model = GPDFAPI::get_mvc_class( 'Model_PDF' );
		$products  = new Field_Products( new GF_Field(), $entry, $this->gform, $this->misc );

		$config                   = $this->get_pdf_config();
		$show_empty_fields        = $config['meta']['empty'] ?? false;
		$show_section_description = $config['meta']['section_content'] ?? false;

		/* Always display nested form products individually */
		if ( isset( $config['meta'] ) ) {
			$config['meta']['individual_products'] = true;
		}

		/* Ensure the field outputs the HTML and can be reset to the original value */
		$output_already_enabled = $this->get_output();
		if ( ! $output_already_enabled ) {
			$this->enable_output();
		}

		/* Skip over any of the following blacklisted fields */
		$blacklisted = apply_filters( 'gfpdf_blacklisted_fields', [ 'captcha', 'password' ] );

		/* Loop through the Repeater fields */
		foreach ( $form['fields'] as $field ) {
			/* Output a field using the standard method if not empty */
			$class = $pdf_model->get_field_class( $field, $form, $entry, $products, $config );
			$class->enable_output();

			$middleware = apply_filters( 'gfpdf_field_middleware', false, $field, $entry, $form, $config, $products, $blacklisted );

			if ( $middleware ) {
				$container->maybe_display_faux_column( $field );
				continue;
			}

			if ( $show_empty_fields === true || ! $class->is_empty() ) {
				$container->generate( $field );

				$class->enable_output();
				$field->type !== 'section' ? $class->html() : $class->html( $show_section_description );
			} else {
				/* To prevent display issues we will output the column markup needed */
				$container->maybe_display_faux_column( $field );
			}
		}

		/* If output wasn't enabled by default, disable again */
		if ( ! $output_already_enabled ) {
			$this->disable_output();
		}

		$container->close();

		return $this->gform->process_tags( ob_get_clean(), $form, $entry );
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
}
