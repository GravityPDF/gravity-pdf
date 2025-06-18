<?php

namespace GFPDF\Helper\Fields;

use Exception;
use GF_Field_Textarea;
use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_QueryPath;
use GFPDF\Statics\Kses;
use GFPDF_Vendor\QueryPath\CSS\ParseException;
use GFPDF_Vendor\QueryPath\DOMQuery;

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

		/**
		 * Allow a maximum of 8 User-Defined CSS Classes to prevent memory issues in mPDF
		 * @see https://github.com/mpdf/mpdf/issues/1753
		 */
		if ( ! empty( $this->field->useRichTextEditor ) ) {
			try {
				$qp  = new Helper_QueryPath();
				$dom = $qp->html5( $value );
				$this->strip_extra_classes_from_dom( $dom );
				$value = (string) $dom->top( 'html' )->innerHTML5();
			} catch ( \Exception $e ) {
				// do nothing
			}
		}

		return parent::html( $value );
	}

	/**
	 * Recursively check DOM node for class attribute and remove any with 9+ classes
	 *
	 * @param DOMQuery $dom
	 *
	 * @return void
	 * @throws ParseException
	 */
	protected function strip_extra_classes_from_dom( $dom ) {
		foreach ( $dom->children() as $child_dom ) {
			$this->strip_extra_classes_from_dom( $child_dom );
		}

		$classes     = $dom->attr( 'class' );
		$class_array = explode( ' ', $classes );
		if ( count( $class_array ) > 8 ) {
			$class_array = array_slice( $class_array, 0, 8 );
			// Set the new class attribute value.
			$dom->attr( 'class', implode( ' ', $class_array ) );
		}
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

		if ( ! empty( $this->field->useRichTextEditor ) ) {
			$html = Kses::parse(
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
