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
		$form = $this->gform->get_form( $this->field->gpnfForm );
		if ( is_wp_error( $form ) ) {
			return parent::html( '' );
		}

		$html = '';

		/* Get the Nested Form Entries */
		$value = explode( ',', $this->value() );
		foreach ( $value as $id ) {
			$entry = $this->gform->get_entry( (int) trim( $id ) );
			if ( is_wp_error( $entry ) ) {
				continue;
			}

			/* Output the entry HTML mark-up */
			$html .= parent::html( $this->get_repeater_html( $form, $entry ) );
		}

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

		/* Loop through the Repeater fields */
		foreach ( $form['fields'] as $field ) {
			/* Output a field using the standard method if not empty */
			$class = $pdf_model->get_field_class( $field, $form, $entry, $products );
			if ( ! $class->is_empty() && strpos( $field->cssClass, 'exclude' ) === false ) {
				$container->generate( $field );
				echo $class->html();
			}
		}

		$container->close( $field );

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
