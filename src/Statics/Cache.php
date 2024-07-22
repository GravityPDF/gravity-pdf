<?php

namespace GFPDF\Statics;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages the directory structure for the temporary PDF cache
 *
 * @since 6.12.0
 */
class Cache {

	/**
	 * @var string|null Holds the cache directory path
	 * @since 6.12.0
	 */
	protected static $template_tmp_location = null;

	/**
	 * Get the unique directory path for the current PDF
	 *
	 * @param array $form         The form object
	 * @param array $entry        The entry object
	 * @param array $pdf_settings The PDF object/settings
	 *
	 * @return string
	 *
	 * @since 6.12.0
	 */
	public static function get_path( $form, $entry, $pdf_settings ) {
		return self::get_basepath() . self::get_hash( $form, $entry, $pdf_settings ) . '/';
	}

	/**
	 * Get and set the cache directory basepath
	 *
	 * @return string
	 * @since 6.12.0
	 */
	protected static function get_basepath() {
		if ( self::$template_tmp_location !== null ) {
			return self::$template_tmp_location;
		}

		$data      = \GPDFAPI::get_data_class();
		$base_path = $data->template_tmp_location;
		if ( is_multisite() ) {
			$base_path .= get_current_blog_id();
		}

		self::$template_tmp_location = trailingslashit( $base_path ) . 'cache/';

		return self::$template_tmp_location;
	}

	/**
	 * Calculate a unique hash based on the form/entry/pdf objects
	 *
	 * @param array $form         The form object
	 * @param array $entry        The entry object
	 * @param array $pdf_settings The PDF object/settings
	 *
	 * @return string
	 *
	 * @internal if $form, $entry, or $pdf_settings are modified a new hash and PDF will be generated
	 *
	 * @since    6.12.0
	 */
	public static function get_hash( $form, $entry, $pdf_settings ) {
		/*
		 * Remove invalid field property which Gravity Forms can unintentionally add to fields when outputting field values.
		 *
		 * The issue was identified when generating a PDF and checking if a Section Break was empty.
		 * This eventually calls GF_Field::get_allowable_tags(), which accesses `$this->form_id` instead of `$this->formId`.
		 * As all form fields are PHP objects, the new property is added to the object when accessed and eventually gets hashed below.
		 * This becomes a problem when generating the same PDF repeatedly during a single request, as the cache is bypassed every call.
		 * Until the issue is fixed upstream, we'll force-remove the invalid property.
		 */
		array_map(
			function( $field ) {
				unset( $field['form_id'] );
			},
			$form['fields']
		);

		return sprintf(
			'%1$d-%2$d-%3$s',
			$form['id'] ?? 0,
			$entry['id'] ?? 0,
			wp_hash( wp_json_encode( [ $form, $entry, $pdf_settings ] ) )
		);
	}
}
