<?php

namespace GFPDF\Helper;

use GF_Field;
use GFPDF\Statics\Kses;

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
 * @since 6.0
 */
class Helper_Field_Container_Gf25 extends Helper_Field_Container {

	const GRID_COLUMN_WIDTH = 100 / 12;

	/**
	 * The Gravity Form fields we should not wrap in a container
	 *
	 * @var array
	 *
	 * @since 6.0
	 */
	protected $skip_fields = [
		'page',
		'section',
		'hidden',
	];

	protected $end_of_row = false;

	/**
	 * Handles the opening and closing of our container
	 *
	 * @param GF_Field $field The Gravity Form field currently being processed
	 *
	 * @return void
	 *
	 * @since 6.0
	 */
	public function generate( GF_Field $field ) {

		/* Remove legacy classmaps */
		$this->strip_field_of_any_classmaps( $field );

		/* Close the row if marked */
		if ( $this->end_of_row ) {
			$this->close();
			$this->end_of_row = false;
		}

		parent::generate( $field );

		if ( $this->get_field_width( $field ) < 100 && strpos( $field->cssClass, 'grid grid-' ) === false ) {
			$field->cssClass = trim( 'grid grid-' . $field->layoutGridColumnSpan . ' ' . $field->cssClass );
		}

		/* Mark as the end of this row if Gravity Forms would insert a spacer */
		if ( ! empty( $field->layoutSpacerGridColumnSpan ) ) {
			$gform = \GPDFAPI::get_form_class();
			$form  = $gform->get_form( $field->form_id );

			if ( isset( $form['fields'] ) && ! empty( \GFFormDisplay::get_row_spacer( $field, $form ) ) ) {
				$this->end_of_row = true;
			}
		}
	}

	/**
	 * Will check if the current field will fit in the open row, or if a new row needs to be open
	 * to accommodate the field.
	 *
	 * @param GF_Field $field The Gravity Form field currently being processed
	 *
	 * @return boolean
	 *
	 * @since 6.0
	 */
	public function does_fit_in_row( GF_Field $field ) {

		if ( $this->currently_open ) {
			$width = $this->get_field_width( $field ); /* current field width */

			/* Check if the new field will fit in the row */
			if ( ( $this->current_width + $width ) <= 100 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param GF_Field $field The Gravity Form field currently being processed
	 */
	public function maybe_display_faux_column( GF_Field $field ) {
	}

	/**
	 * Close the current container if still open.
	 * This is usually called publicly after the form loop
	 *
	 * @since 6.0
	 */
	public function close(): void {
		if ( $this->currently_open ) {
			$this->close_container();
			$this->reset();

			$row_html = ob_get_clean();

			try {
				$qp       = new Helper_QueryPath();
				$row_html = $qp->html5( $row_html, '.grid:last-of-type .inner-container' )
						->css( 'width', '100%' )
						->top( 'html' )->innerHTML();
			} catch ( \Exception $e ) {

			}

			Kses::output( $row_html );
		}
	}

	/**
	 * Open the container
	 *
	 * @param GF_Field $field The Gravity Form field currently being processed
	 *
	 * @since 6.0
	 */
	protected function handle_closed_container( GF_Field $field ) {
		$this->start();
		$this->open_container();
		$this->increment_width( $field );
	}

	/**
	 * Determine if we should close a container based on its classes
	 *
	 * @param GF_Field $field The Gravity Form field currently being processed
	 *
	 * @return void
	 *
	 * @since 6.0
	 */
	protected function handle_open_container( GF_Field $field ) {

		/* if the current field width is more than 100 we will close the container */
		if ( ! $this->does_fit_in_row( $field ) || $this->does_field_have_stopper_class( $field ) ) {
			$this->close();

			return;
		}

		$this->increment_width( $field );
	}

	/**
	 * Mark our class as currently being open
	 *
	 * @since 6.0
	 */
	protected function start() {
		$this->currently_open = true;
		ob_start();
	}

	/**
	 * Reset our class back to its original state
	 *
	 * @since 6.0
	 */
	protected function reset() {
		$this->currently_open = false;
		$this->current_width  = 0;
		$this->end_of_row     = false;
	}

	/**
	 * Increment our current field width
	 *
	 * @param GF_Field $field
	 *
	 * @return void
	 *
	 * @since  4.0
	 */
	protected function increment_width( $field ) {
		$this->current_width += $this->get_field_width( $field );
	}

	/**
	 * Convert the field grid span to a width out of 100
	 *
	 * @param GF_Field $field
	 *
	 * @return integer The field width based on assigned class
	 *
	 * @since  6.0
	 */
	protected function get_field_width( $field ) {
		$grid_span = ! empty( $field->layoutGridColumnSpan ) ? $field->layoutGridColumnSpan : 12;

		return $grid_span * self::GRID_COLUMN_WIDTH;
	}
}
