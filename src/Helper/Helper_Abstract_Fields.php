<?php

namespace GFPDF\Helper;

use Exception;
use GF_Field;
use GFCache;
use GFCommon;
use GFFormsModel;

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
 * Helper fields can be extended to allow each Gravity Form field type to be displayed correctly
 * We found the default GF display functionality isn't quite up to par for the Gravity PDF requirements
 *
 * @since 4.0
 */
abstract class Helper_Abstract_Fields {

	/**
	 * Contains the field array
	 *
	 * @var array|object
	 *
	 * @since 4.0
	 */
	public $field;

	/**
	 * Contains the form information
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	public $form;

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var Helper_Form
	 *
	 * @since 4.0
	 */
	public $gform;

	/**
	 * Contains the entry information
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	public $entry;

	/**
	 * Used to cache the $this->value() results
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	protected $cached_results;

	/**
	 * As come fields can have multiple field types we'll use $fieldObject to store the object
	 *
	 * @var object
	 *
	 * @since 4.0
	 */
	public $fieldObject;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var Helper_Misc
	 *
	 * @since 4.0
	 */
	public $misc;

	/**
	 * Set up the object
	 * Check the $entry is an array, or throw exception
	 * The $field is validated in the child classes
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

		/* Assign our internal variables */
		$this->misc = $misc;

		/* Throw error if not dependencies not met */
		if ( ! class_exists( 'GFFormsModel' ) ) {
			throw new Exception( 'Gravity Forms is not correctly loaded.' );
		}

		if ( ! is_object( $field ) || ! ( $field instanceof GF_Field ) ) {
			throw new Exception( '$field needs to be in instance of GF_Field' );
		}

		/* Throw error if $entry is not an array */
		if ( ! is_array( $entry ) ) {
			throw new Exception( '$entry needs to be an array' );
		}

		$this->field = $field;
		$this->entry = $entry;
		$this->form  = $gform->get_form( $entry['form_id'] );
		$this->gform = $gform;

	}

	/**
	 * Control the getting and setting of the cache
	 *
	 * @param mixed $value is passed in it will set a new cache
	 *
	 * @return mixed The current cached_results
	 *
	 * @since 4.0
	 */
	final public function cache( $value = null ) {
		if ( ! is_null( $value ) ) {
			$this->cached_results = $value;
		}

		return $this->cached_results;
	}

	/**
	 * Check if we currently have a cache
	 *
	 * @return boolean True is we have a cache and false if we do not
	 *
	 * @since 4.0
	 */
	final public function has_cache() {
		if ( ! is_null( $this->cached_results ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Reset the cache
	 *
	 * @since 4.0
	 */
	final public function remove_cache() {
		$this->cached_results = null;
	}

	/**
	 * Used to process the Gravity Forms value extracted from the entry array
	 * Each value is then passed to the value method set up by the child objects
	 *
	 * @since 4.0
	 */
	final public function get_value() {

		/**
		 * Gravity Forms' GFCache function was thrashing the database, causing double the amount of time for the field_value() method to run.
		 * The reason is that the cache was checking against a field value stored in a transient every time `GFFormsModel::get_lead_field_value()` is called.
		 * We're forcing the cache to skip the extra database lookup and just get the value.
		 *
		 * @hack
		 * @since  4.0
		 * @credit Zack Katz (Gravity View author)
		 * @fixed  Gravity Forms 1.9.13.25
		 */
		if ( class_exists( 'GFCache' ) && version_compare( GFCommon::$version, '1.9.13.25', '<' ) ) {
			GFCache::set( 'GFFormsModel::get_lead_field_value_' . $this->entry['id'] . '_' . $this->field->id, false, false, 0 );
		}

		/*
		 * Get the Gravity Forms field value
		 *
		 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_field_value for more details about this filter
		 */

		return apply_filters( 'gfpdf_field_value', GFFormsModel::get_lead_field_value( $this->entry, $this->field ), $this->field, $this->entry, $this->form, $this );
	}

	/**
	 * Return the current field label
	 *
	 * @return string
	 *
	 * @since 4.2
	 */
	final public function get_label() {
		/*
		 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_field_label for usage
		 */
		return apply_filters( 'gfpdf_field_label', $this->field->label, $this->field, $this->entry );
	}

	/**
	 * Used to check if the current field has a value
	 *
	 * @return boolean Return true if the field is empty, false if it has a value
	 * @internal Child classes can override this method when dealing with a specific use case
	 *
	 * @since    4.0
	 *
	 */
	public function is_empty() {
		$value = $this->value();

		if ( is_array( $value ) && count( array_filter( $value ) ) === 0 ) { /* check for an array */
			return true;
		} elseif ( is_string( $value ) && strlen( trim( $value ) ) === 0 ) { /* check for a string */
			return true;
		}

		return false;
	}

	/**
	 * Standardised method for returning the field's correct $form_data['field'] keys
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function form_data() {

		$value    = $this->value();
		$label    = $this->get_label();
		$field_id = (int) $this->field->id;
		$data     = [];

		/* Add field data using standardised naming conversion */
		$data[ $field_id . '.' . $label ] = $value;

		/* Add field data using standardised naming conversion */
		$data[ $field_id ] = $value;

		/* Keep backwards compatibility */
		$data[ $label ] = $value;

		return [ 'field' => $data ];
	}

	/**
	 * Get the default HTML output for this field
	 *
	 * @param string  $value      The field value to be displayed
	 * @param boolean $show_label Whether or not to show the field's label
	 *
	 * @return string
	 * @since 4.0
	 *
	 */
	public function html( $value = '', $show_label = true ) {

		/*
		 * Prevent shortcodes and merge tags being processed from user input fields
		 * We'll allow them in administrative fields (not hidden fields) and HTML and Section fields
		 *
		 * @since 4.2 Skipping Administrative fields was added
		 */
		$skip_fields = apply_filters( 'gfpdf_skip_encode_mergetags_on_fields', [ 'html', 'section' ], $this->field, $this->entry, $this->form );
		if ( ( empty( $this->field->visibility ) || $this->field->visibility !== 'administrative' ) &&
			 ! in_array( $this->field->type, $skip_fields, true ) ) {
			$value = $this->encode_tags( $value );
		}

		/* Backwards compat */
		$value = apply_filters( 'gfpdf_field_content', $value, $this->field, GFFormsModel::get_lead_field_value( $this->entry, $this->field ), $this->entry['id'], $this->form['id'] );

		/**
		 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_pdf_field_content for usage
		 *
		 * @since 4.2
		 */
		$value = apply_filters( 'gfpdf_pdf_field_content', $value, $this->field, $this->entry, $this->form, $this );
		$value = apply_filters( 'gfpdf_pdf_field_content_' . $this->field->get_input_type(), $value, $this->field, $this->entry, $this->form, $this );

		$label = esc_html( $this->get_label() );
		$type  = $this->field->get_input_type();

		$html = '<div id="field-' . $this->field->id . '" class="gfpdf-' . $type . ' gfpdf-field ' . $this->field->cssClass . '">
					<div class="inner-container">';

		if ( $show_label ) {
			$html .= '<div class="label"><strong>' . $label . '</strong></div>';
		}

		/* If the field value is empty we'll add a non-breaking space to act like a character and maintain proper layout */
		if ( strlen( trim( $value ) ) === 0 ) {
			$value = '&nbsp;';
		}

		$html .= '<div class="value">' . $value . '</div>'
				 . '</div>'
				 . '</div>';

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_field_html_value for more details about this filter */

		return apply_filters( 'gfpdf_field_html_value', $html, $value, $show_label, $label, $this->field, $this->form, $this->entry, $this );
	}

	/**
	 * Used to process the Gravity Forms value extracted from the entry
	 *
	 * @since 4.0
	 */
	abstract public function value();

	/**
	 * Prevent user-data shortcodes from being processed by the PDF templates
	 *
	 * @param string $value The text to be converted
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function encode_tags( $value ) {
		$find      = [ '[', ']', '{', '}' ];
		$converted = [ '&#91;', '&#93;', '&#123;', '&#125;' ];

		return str_replace( $find, $converted, $value );
	}
}
