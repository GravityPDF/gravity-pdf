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
		return static::get_basepath() . static::get_hash( $form, $entry, $pdf_settings ) . '/';
	}

	/**
	 * Get and set the cache directory basepath
	 *
	 * @return string
	 * @since 6.12.0
	 */
	protected static function get_basepath() {
		if ( static::$template_tmp_location !== null ) {
			return static::$template_tmp_location;
		}

		$data      = \GPDFAPI::get_data_class();
		$base_path = $data->template_tmp_location;
		if ( is_multisite() ) {
			$base_path .= get_current_blog_id();
		}

		static::$template_tmp_location = trailingslashit( $base_path ) . 'cache/';

		return static::$template_tmp_location;
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
	 * @internal if $form, $entry, $pdf_settings, user ID, site ID, or template files are changed a new hash and PDF will be generated
	 *
	 * @since    6.12.0
	 */
	public static function get_hash( $form, $entry, $pdf_settings ) {

		/*
		 * Standardize field properties that may be added dynamically when fields are processed
		 * for the first run of a PDF. When Gravity Forms accesses properties that don't exist
		 * in a GF_Field object, it adds the value automatically and sets it to an empty string.
		 */
		array_map(
			function( $field ) {
				/** @var \GF_Field $field */
				/* Set when accessing \GFCommon::selection_display() */
				if ( in_array( $field->get_input_type(), [ 'checkbox', 'radio', 'select' ], true ) ) {
					$field->enablePrice;
				}

				/* Set when accessing \GF_Fields::get_allowable_tags() */
				if ( $field->get_input_type() === 'section' ) {
					$field->form_id;
				}

				/* Set the `use_admin_label` context for all fields */
				$field->set_context_property( 'use_admin_label', $field->get_context_property( 'use_admin_label' ) );
			},
			$form['fields']
		);

		/*
		 * Ignore specific entry meta that is considered unimportant to PDFs
		 */
		$ignored_entry_meta = apply_filters( 'gfpdf_cache_hash_ignored_entry_meta', [ 'is_read', 'is_starred', 'is_approved', 'status', 'source_url', 'user_agent' ], $form, $entry, $pdf_settings );
		foreach ( $ignored_entry_meta as $meta ) {
			unset( $entry[ $meta ] );
		}

		/* Add last modified date of template files to hash */
		$template    = \GPDFAPI::get_templates_class();
		$template_id = $pdf_settings['template'] ?? '';

		try {
			$template_path       = $template->get_template_path_by_id( $template_id );
			$template_timestamps = filemtime( $template_path );
		} catch ( \Exception $e ) {
			$template_timestamps = 0;
		}

		/* Include config template timestamp if it exists */
		try {
			$template_config_path = $template->get_config_path_by_id( $template_id );
			$template_timestamps .= filemtime( $template_config_path );
		} catch ( \Exception $e ) {
			/* do nothing */
		}

		/* Build an array of unique data relevant to the current PDF */
		$unique_array = apply_filters(
			'gfpdf_cache_hash_array',
			[
				get_current_blog_id(),
				get_current_user_id(),
				$form['fields'],
				$entry,
				$pdf_settings,
				$template_timestamps,
			],
			$form,
			$entry,
			$pdf_settings
		);

		/* Generate the hash based on that unique data */
		$hash_prefix = static::get_hash_prefix( $form, $entry, $pdf_settings );
		$hash        = wp_hash( wp_json_encode( $unique_array ) );

		/* @TODO - debugging, remove later */
		$path = static::get_basepath() . $hash_prefix . '-' . $hash;
		wp_mkdir_p( $path );
		file_put_contents( $path . '/debug.log', print_r( $unique_array, true ) );

		return $hash_prefix . '-' . $hash;
	}

	/**
	 * Gets the easily-identifiable prefix to add before the hash
	 *
	 * @param array $form         The form object
	 * @param array $entry        The entry object
	 * @param array $pdf_settings The PDF object/settings
	 *
	 * @return string
	 *
	 * @since 6.12
	 */
	public static function get_hash_prefix( $form, $entry, $pdf_settings ) {
		return sprintf(
			's%1$d-f%2$d-e%3$d-p%4$s',
			get_current_blog_id(),
			$form['id'] ?? 0,
			$entry['id'] ?? 0,
			$pdf_settings['id'] ?? '',
		);
	}
}
