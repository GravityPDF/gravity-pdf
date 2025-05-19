<?php

namespace GFPDF\Helper;

use GF_Field;

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
 * @since 4.0
 */
class Helper_Field_Container {

	/**
	 * Holds the current width of our container based on the field passed in
	 * The value is out of 100
	 *
	 * @var integer
	 *
	 * @since 4.0
	 */
	protected $current_width = 0;

	/**
	 * Boolean value to tell if the element is currently opened
	 *
	 * @var boolean
	 *
	 * @since 4.0
	 */
	protected $currently_open = false;

	/**
	 * Matches class names to width percentages
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	protected $class_map = [
		'gf_left_half'      => 50,
		'gf_right_half'     => 50,
		'gf_left_third'     => 33.3,
		'gf_middle_third'   => 33.3,
		'gf_right_third'    => 33.3,
		'gf_first_quarter'  => 25,
		'gf_second_quarter' => 25,
		'gf_third_quarter'  => 25,
		'gf_fourth_quarter' => 25,
	];

	/**
	 * The HTML tag used when opening the container
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $open_tag = '<div class="row-separator">';

	/**
	 * The HTML tag used when closing the container
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $close_tag = '</div>';

	/**
	 * Whether to enable/disable the faux column feature
	 *
	 * @var bool
	 *
	 * @since 5.0
	 */
	protected $faux_column = true;

	/**
	 * The Gravity Form fields we should not wrap in a container
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	protected $skip_fields = [
		'page',
		'section',
		'html',
	];

	/**
	 * Classes which will automatically begin a new row
	 *
	 * @var array
	 *
	 * @since 4.2
	 */
	protected $row_stopper_classes = [
		'pagebreak',
	];

	/**
	 * Holds the number of times a new row has been open
	 *
	 * @var int
	 *
	 * @since 4.0
	 */
	protected $counter = 0;

	/**
	 * Set up the object
	 *
	 * @param array $config Allow user to override the open / close tag and which fields are skipped
	 *
	 * @since 4.0
	 */
	public function __construct( $config = [] ) {
		if ( isset( $config['open_tag'] ) ) {
			$this->open_tag = $config['open_tag'];
		}

		if ( isset( $config['close_tag'] ) ) {
			$this->close_tag = $config['close_tag'];
		}

		if ( isset( $config['skip_fields'] ) ) {
			$this->skip_fields = $config['skip_fields'];
		}

		if ( isset( $config['row_stopper_classes'] ) ) {
			$this->row_stopper_classes = $config['row_stopper_classes'];
		}

		$this->open_tag            = apply_filters( 'gfpdf_container_open_tag', $this->open_tag );
		$this->close_tag           = apply_filters( 'gfpdf_container_close_tag', $this->close_tag );
		$this->skip_fields         = apply_filters( 'gfpdf_container_skip_fields', $this->skip_fields );
		$this->row_stopper_classes = apply_filters( 'gfpdf_container_row_stopper_classes', $this->row_stopper_classes );
		$this->faux_column         = apply_filters( 'gfpdf_container_disable_faux_columns', $this->faux_column );
		$this->class_map           = apply_filters( 'gfpdf_container_class_map', $this->class_map );
	}


	/**
	 * Handles the opening and closing of our container
	 *
	 * @param GF_Field $field The Gravity Form field currently being processed
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function generate( GF_Field $field ) {

		/* Check if we are processing a field that should not be floated and treat it as a 100% field */
		$this->process_skipped_fields( $field );

		/* Check if we need to close the container */
		if ( $this->currently_open ) {
			$this->handle_open_container( $field );
		}

		/* Open the tag if not currently opened*/
		if ( ! $this->currently_open ) {
			$this->handle_closed_container( $field );
		}
	}

	/**
	 * Close the current container if still open.
	 * This is usually called publicly after the form loop
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function close() {
		if ( $this->currently_open ) {
			$this->close_container();
			$this->reset();
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
	 * @since 4.0
	 */
	public function does_fit_in_row( GF_Field $field ) {

		if ( true === $this->currently_open ) {
			$width = $this->get_field_width( $field->cssClass ); /* current field width */

			/* Check if the new field will fit in the row */
			if ( 100 >= ( $this->current_width + $width ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Output placeholder HTML if empty or hidden field is part of CSS Ready Class columns
	 * and the row is currently open and the field will fit in that row without opening another row
	 *
	 * @param GF_Field $field The Gravity Form field currently being processed
	 *
	 * @return void
	 */
	public function maybe_display_faux_column( GF_Field $field ) {

		/* Check if we should create a placeholder column */
		if ( $this->faux_column && $this->does_fit_in_row( $field ) ) {
			echo '<div id="' . esc_html( 'field-' . $field->id ) . '" class="gfpdf-column-placeholder gfpdf-field ' . esc_html( $field->cssClass ) . '"></div>';

			/* Increase column width */
			$this->increment_width( $field->cssClass );
		}
	}

	/**
	 * Open the container
	 *
	 * @param GF_Field $field The Gravity Form field currently being processed
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	protected function handle_closed_container( GF_Field $field ) {
		$this->start();
		$this->open_container();
		$this->increment_width( $field->cssClass );
	}

	/**
	 * Determine if we should close a container based on its classes
	 *
	 * @param GF_Field $field The Gravity Form field currently being processed
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	protected function handle_open_container( GF_Field $field ) {

		/* if the current field width is more than 100 we will close the container */
		if ( false === $this->does_fit_in_row( $field ) || $this->does_field_have_stopper_class( $field ) ) {
			$this->close();
		} else {
			$this->increment_width( $field->cssClass );
		}
	}

	/**
	 * Process our skipped Gravity Form fields (close the container if needed)
	 *
	 * @param GF_Field $field The Gravity Form field currently being processed
	 *
	 * @return boolean true if we processed a skipped field, false otherwise
	 *
	 * @since 4.0
	 */
	protected function process_skipped_fields( GF_Field $field ) {
		/* if we have a skipped field and the container is open we will close it */
		if ( in_array( $field->type, $this->skip_fields, true ) ) {
			$this->strip_field_of_any_classmaps( $field );
			$this->close();

			return true;
		}

		return false;
	}

	/**
	 * Remove any mapped classes from our skipped fields
	 *
	 * @param GF_Field $field The Gravity Form field currently being processed
	 *
	 * @return void
	 *
	 * @since  4.0
	 */
	protected function strip_field_of_any_classmaps( GF_Field $field ) {
		$field->cssClass = trim( str_replace( array_keys( $this->class_map ), ' ', $field->cssClass ) );
	}

	/**
	 * Output the open tag
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	protected function open_container() {

		$class = $this->is_row_odd_or_even();
		/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
		echo str_replace( 'row-separator', 'row-separator ' . $class, $this->open_tag );

		$this->increment_row_counter();
	}

	/**
	 * Output the close tag
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	protected function close_container() {
		/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
		echo $this->close_tag;
	}

	/**
	 * Mark our class as currently being open
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	protected function start() {
		$this->currently_open = true;
	}

	/**
	 * Reset our class back to its original state
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	protected function reset() {
		$this->currently_open = false;
		$this->current_width  = 0;
	}

	/**
	 * Increment our current field width
	 *
	 * @param string $classes The field classes
	 *
	 * @return void
	 *
	 * @since  4.0
	 */
	protected function increment_width( $classes ) {
		$this->current_width += $this->get_field_width( $classes );
	}

	/**
	 * Loop through all classes and return our class map if found, or 100
	 *
	 * @param String $classes The field classes
	 *
	 * @return integer The field width based on assigned class
	 *
	 * @since  4.0
	 */
	protected function get_field_width( $classes ) {
		$classes = $this->get_field_classes( $classes );

		foreach ( $classes as $class ) {
			if ( isset( $this->class_map[ $class ] ) ) {
				/* return field width */
				return $this->class_map[ $class ];
			}
		}

		/* no match, so assuming full width */

		return 100;
	}

	/**
	 * Checks if the row counter is currently odd or even
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	protected function is_row_odd_or_even() {
		return ( $this->counter % 2 ) ? 'even' : 'odd';
	}

	/**
	 * Increases the internal row counter
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	protected function increment_row_counter() {
		++$this->counter;
	}

	/**
	 * Get a list of classes assigned to the field
	 *
	 * @param string $classes
	 *
	 * @return array
	 *
	 * @since 4.2
	 */
	protected function get_field_classes( $classes ) {
		return array_filter( explode( ' ', $classes ) );
	}

	/**
	 * Check if the field has a row stopping class
	 *
	 * @param GF_Field $field
	 *
	 * @return bool
	 *
	 * @since 4.2
	 */
	protected function does_field_have_stopper_class( GF_Field $field ) {
		$field_classes = array_flip( $this->get_field_classes( $field->cssClass ) );

		foreach ( $this->row_stopper_classes as $class ) {
			if ( isset( $field_classes[ $class ] ) ) {
				return true;
			}
		}

		return false;
	}
}
