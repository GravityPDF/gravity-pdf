<?php

namespace GFPDF\Helper;

use Exception;
use GF_Field;
use GFFormsModel;
use GFPDF\Model\Model_Signed_Url_Trust;
use GFPDF\Statics\Kses;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
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
abstract class Helper_Abstract_Fields implements Helper_Interface_Field_Pdf_Config {

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
	 * Backwards-compatible way to echo the HTML content
	 *
	 * @var bool
	 *
	 * @since 6.4.0
	 */
	private $output = false;

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
	 * Holds the current PDF settings and metadata
	 *
	 * @var array Contains the keys 'meta' and 'settings'
	 *
	 * @since 6.9
	 */
	protected $pdf_config;

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
		$this->form  = apply_filters( 'gfpdf_current_form_object', $gform->get_form( $entry['form_id'] ), $entry, 'helper_abstract_fields' );
		$this->gform = $gform;
	}

	/**
	 * Echo the HTML content when calling self::html()
	 *
	 * @return void
	 *
	 * @since 6.4.0
	 */
	final public function enable_output(): void {
		$this->output = true;
	}

	/**
	 * Do not echo the HTML content when calling self::html()
	 *
	 * @return void
	 *
	 * @since 6.4.0
	 */
	final public function disable_output(): void {
		$this->output = false;
	}

	/**
	 * Checks if the output should be echoed or not
	 *
	 * @return bool
	 *
	 * @since 6.4.0
	 */
	final public function get_output(): bool {
		return $this->output;
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

		/*
		 * Get the Gravity Forms field value
		 *
		 * See https://docs.gravitypdf.com/developers/filters/gfpdf_field_value for more details about this filter
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
		 * See https://docs.gravitypdf.com/developers/filters/gfpdf_field_label for usage
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
		$value = $this->encode_value_tags( $value );

		/* Backwards compat */
		$value = apply_filters( 'gfpdf_field_content', $value, $this->field, GFFormsModel::get_lead_field_value( $this->entry, $this->field ), $this->entry['id'] ?? 0, $this->form['id'] ?? 0 );

		/**
		 * See https://docs.gravitypdf.com/developers/filters/gfpdf_pdf_field_content for usage
		 *
		 * @since 4.2
		 */
		$value = apply_filters( 'gfpdf_pdf_field_content', $value, $this->field, $this->entry, $this->form, $this );
		$value = apply_filters( 'gfpdf_pdf_field_content_' . $this->field->type, $value, $this->field, $this->entry, $this->form, $this );

		$label = $this->get_label();

		$html = '<div id="' . esc_attr( 'field-' . $this->field->id ) . '" class="' . esc_attr( $this->get_field_classes() ) . '">
					<div class="inner-container">';

		if ( $show_label ) {
			$html .= '<div class="label"><strong>' . Kses::parse( $label ) . '</strong></div>';
		}

		/* If the field value is empty we'll add a non-breaking space to act like a character and maintain proper layout */
		if ( strlen( trim( $value ) ) === 0 ) {
			$value = '&nbsp;';
		}

		$html .= '<div class="value">' . Kses::parse( $value ) . '</div>'
				 . '</div>'
				 . '</div>';

		/* See https://docs.gravitypdf.com/developers/filters/gfpdf_field_html_value for more details about this filter */
		$html = apply_filters( 'gfpdf_field_html_value', $html, $value, $show_label, $label, $this->field, $this->form, $this->entry, $this );

		if ( $this->get_output() ) {
			Kses::output( $html );
		}

		return $html;
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
	 * @param string|null $value The text to be converted
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function encode_tags( $value ) {
		$find  = [ '[', ']', '{', '}' ];
		$value = (string) $value;
		if ( strpbrk( $value, '[]{}' ) === false ) {
			return $value;
		}

		/* Kses rebuilds attribute values, so URL-encode the tags there */
		if ( strpos( $value, '<' ) !== false && class_exists( '\WP_HTML_Tag_Processor' ) ) {
			$processor = new \WP_HTML_Tag_Processor( $value );
			while ( $processor->next_tag() ) {
				foreach ( $processor->get_attribute_names_with_prefix( '' ) as $name ) {
					$attribute = $processor->get_attribute( $name );
					if ( is_string( $attribute ) && strpbrk( $attribute, '[]{}' ) !== false ) {
						$processor->set_attribute( $name, str_replace( $find, [ '%5B', '%5D', '%7B', '%7D' ], $attribute ) );
					}
				}
			}

			$value = $processor->get_updated_html();
		}

		return str_replace( $find, [ '&#91;', '&#93;', '&#123;', '&#125;' ], $value );
	}

	/**
	 * Prevent shortcodes and merge tags being processed from user input fields, in the field's HTML and form data.
	 * We'll allow them in HTML and Section fields, and the ones in an administrative field's default value.
	 *
	 * @internal Called while building the PDF's field HTML and $form_data
	 *
	 * @param mixed $value A value, or an array of them
	 *
	 * @return mixed
	 *
	 * @since 6.17.3
	 */
	public function encode_value_tags( $value ) {
		$skip_fields = apply_filters( 'gfpdf_skip_encode_mergetags_on_fields', [ 'html', 'section' ], $this->field, $this->entry, $this->form );
		if ( in_array( $this->field->type, $skip_fields, true ) ) {
			return $value;
		}

		$default_tags = null;
		$walk         = function ( &$item ) use ( &$default_tags ) {
			if ( is_string( $item ) && strpbrk( $item, '[]{}' ) !== false ) {
				$default_tags = $default_tags ?? $this->get_encoded_default_tags();
				$item         = strtr( $this->encode_tags( $item ), $default_tags );
			}
		};

		if ( is_array( $value ) ) {
			array_walk_recursive( $value, $walk );
		} else {
			$value = (string) $value;
			$walk( $value );
		}

		return $value;
	}

	/**
	 * Allow the HTML and merge tags a form editor put in a choice's escaped text
	 *
	 * @param string $text
	 *
	 * @return string
	 *
	 * @since 6.17.3
	 */
	protected function parse_choice_text( $text ) {
		return Kses::parse( $this->gform->process_tags( wp_specialchars_decode( $text, ENT_QUOTES ), $this->form, $this->entry ) );
	}

	/**
	 * Restore the quotes the field's escaping encoded in trusted shortcodes, so their attributes still parse
	 *
	 * @param string $value
	 *
	 * @return string
	 *
	 * @since 6.17.3
	 */
	private function decode_shortcode_quotes( $value ) {
		if ( strpos( $value, '[' ) === false ) {
			return $value;
		}

		return preg_replace_callback(
			'/' . get_shortcode_regex() . '/',
			function ( $shortcode ) {
				return str_replace( [ '&quot;', '&#039;', '&#39;' ], [ '"', "'", "'" ], $shortcode[0] );
			},
			$value
		);
	}

	/**
	 * Map the encoded copies of the tags in an administrative field's default back to the tags, so they're processed
	 *
	 * @return array<string,string>
	 *
	 * @since 6.17.3
	 */
	private function get_encoded_default_tags() {
		$map = [];
		foreach ( $this->get_signed_url_trust()->get_default_tags( $this->field ) as $tag ) {
			$escaped = esc_html( $tag );

			$map[ $this->encode_tags( $escaped ) ] = $this->decode_shortcode_quotes( $escaped );
			$map[ $this->encode_tags( $tag ) ]     = $tag;

			/* As encode_tags() encodes them in an HTML attribute */
			$map[ str_replace( [ '[', ']', '{', '}' ], [ '%5B', '%5D', '%7B', '%7D' ], $tag ) ] = $tag;
		}

		return $map;
	}

	/**
	 * Whether a value is one of the field's choices
	 *
	 * @param string $value
	 *
	 * @return bool
	 *
	 * @since 6.17.3
	 */
	protected function is_choice_value( $value ) {
		if ( ! $this->field instanceof GF_Field ) {
			return false;
		}

		foreach ( $this->field->choices ?: [] as $choice ) {
			/* Gravity Forms saves a choice's value sanitised, so match either form */
			$choice = (string) ( $choice['value'] ?? '' );
			if ( in_array( (string) $value, [ $choice, wp_kses_post( $choice ) ], true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Process the tags in an administrative field's default, and encode the rest of its tags. Any other field's value is
	 * left as is, unless the `gfpdf_field_process_merge_tags` filter opts in, and its PDF merge tags and shortcodes are
	 * never trusted.
	 *
	 * @param string $value
	 *
	 * @return string
	 *
	 * @since 6.17.3
	 */
	protected function process_value_tags( $value ) {
		$default_tags = $this->get_encoded_default_tags();
		if ( $default_tags ) {
			return $this->gform->process_tags( strtr( $this->encode_tags( $value ), $default_tags ), $this->form, $this->entry );
		}

		if ( ! apply_filters( 'gfpdf_field_process_merge_tags', false, $this->field, $this->entry, $this->form ) ) {
			return $value;
		}

		return $this->get_signed_url_trust()->run_untrusted(
			function () use ( $value ) {
				return $this->gform->process_tags( $value, $this->form, $this->entry );
			}
		);
	}

	/**
	 * @return Model_Signed_Url_Trust
	 *
	 * @since 6.17.3
	 */
	private function get_signed_url_trust() {
		return \GPDFAPI::get_mvc_class( 'Model_Signed_Url_Trust' );
	}

	/**
	 * To avoid mPDF memory errors trying to determine the CSS specificity we will limit the field to 8 user classes
	 *
	 * @see https://github.com/mpdf/mpdf/issues/1753
	 *
	 * @return string
	 *
	 * @since 6.5
	 */
	public function get_field_classes(): string {

		$core_classes = [
			'gfpdf-field',
			'gfpdf-' . $this->field->get_input_type(),
		];

		if ( $this->field->type !== $this->field->get_input_type() ) {
			$core_classes[] = 'gfpdf-' . $this->field->type;
		}

		return implode(
			' ',
			array_merge(
				$core_classes,
				array_slice( explode( ' ', $this->field->cssClass ), 0, 8 ),
			)
		);
	}

	/**
	 * Set the current PDF configuration
	 *
	 * @param array $config
	 *
	 * @return void
	 *
	 * @since 6.9
	 */
	public function set_pdf_config( $config ) {
		$this->pdf_config = $config;
	}

	/**
	 * Get the current PDF configuration
	 *
	 * @return array
	 *
	 * @since 6.9
	 */
	public function get_pdf_config() {
		return $this->pdf_config;
	}
}
