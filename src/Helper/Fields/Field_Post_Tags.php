<?php

namespace GFPDF\Helper\Fields;

use Exception;
use GF_Field_Post_Tags;
use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;

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
class Field_Post_Tags extends Helper_Abstract_Fields {

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
	 * @since 4.0
	 */
	public function __construct( $field, $entry, Helper_Abstract_Form $gform, Helper_Misc $misc ) {

		if ( ! is_object( $field ) || ! $field instanceof GF_Field_Post_Tags ) {
			throw new Exception( '$field needs to be in instance of GF_Field_Post_Tags' );
		}

		/* call our parent method */
		parent::__construct( $field, $entry, $gform, $misc );
	}

	/**
	 * Standardised method for returning the field's correct $form_data['field'] keys
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function form_data() {

		$value    = implode( ', ', $this->value() );
		$label    = $this->get_label();
		$field_id = (int) $this->field->id;
		$data     = [];

		/* Add HTML output */
		$data[ $field_id . '.' . $label ] = $value;
		$data[ $field_id ]                = $value;
		$data[ $label ]                   = $value;

		return [ 'field' => $data ];
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
		$value = implode( ', ', $this->value() );

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
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		$value = explode( ',', $this->get_value() ); /* get tags and turn into array */
		$value = array_map( 'trim', $value );
		$value = array_map( 'esc_html', $value );

		$this->cache( $value );

		return $this->cache();
	}
}
