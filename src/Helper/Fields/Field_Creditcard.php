<?php

namespace GFPDF\Helper\Fields;

use Exception;
use GF_Field_CreditCard;
use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;

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
class Field_Creditcard extends Helper_Abstract_Fields {

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

		if ( ! is_object( $field ) || ! ( $field instanceof GF_Field_CreditCard ) ) {
			throw new Exception( '$field needs to be in instance of GF_Field_CreditCard' );
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
	 * @since 4.0
	 */
	public function html( $value = '', $label = true ) {
		$data = array_filter( $this->value() ); /* remove any empty fields from the array */

		$value = implode( '<br>', $data );

		return parent::html( $value );
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		$value = $this->get_value();

		$this->cache(
			[
				'type'   => esc_html( rgget( $this->field->id . '.4', $value ) ),
				'number' => esc_html( rgget( $this->field->id . '.1', $value ) ),
			]
		);

		return $this->cache();
	}
}
