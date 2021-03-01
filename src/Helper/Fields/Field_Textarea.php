<?php

namespace GFPDF\Helper\Fields;

use Exception;
use GF_Field_Textarea;
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
class Field_Textarea extends Helper_Abstract_Fields {

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

		if ( ! is_object( $field ) || ! $field instanceof GF_Field_Textarea ) {
			throw new Exception( '$field needs to be in instance of GF_Field_Textarea' );
		}

		/* call our parent method */
		parent::__construct( $field, $entry, $gform, $misc );
	}

	public function html( $value = '', $label = true ) {
		$value = $this->value();

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

		$value = $this->get_value();

		if ( isset( $this->field->useRichTextEditor ) && true === $this->field->useRichTextEditor ) {
			$html = wp_kses_post(
				wpautop(
					$this->gform->process_tags( $value, $this->form, $this->entry )
				)
			);
		} else {
			$html = nl2br( esc_html( $value ) );
		}

		$this->cache( $html );

		return $this->cache();
	}
}
