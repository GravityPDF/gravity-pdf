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
 * @since 7.0.0
 */
class Cache {

	/**
	 * Get the unique directory path for the current PDF
	 *
	 * @param array $form         The form object
	 * @param array $entry        The entry object
	 * @param array $pdf_settings The PDF object/settings
	 *
	 * @return string|false False when the cache key can't be built
	 *
	 * @since 7.0.0
	 */
	public static function get_path( $form, $entry, $pdf_settings ) {
		$hash = static::get_hash( $form, $entry, $pdf_settings );

		return $hash === false ? false : static::get_basepath() . $hash . '/';
	}

	/**
	 * Get a new, unique directory path for a PDF that must not be reused by another request
	 *
	 * @return string
	 *
	 * @since 7.0.0
	 */
	public static function get_uncached_path() {
		return static::get_basepath( 'uncached' ) . bin2hex( random_bytes( 16 ) ) . '/';
	}

	/**
	 * How long a cached PDF can be served for, in seconds
	 *
	 * @return int
	 *
	 * @since 7.0.0
	 */
	public static function get_ttl() {
		return 12 * HOUR_IN_SECONDS;
	}

	/**
	 * Check a cached PDF can be served: a non-empty file written within the TTL
	 *
	 * @param string $file Absolute path to the PDF
	 *
	 * @return bool
	 *
	 * @since 7.0.0
	 */
	public static function is_hit( $file ) {
		return is_file( $file ) && filesize( $file ) > 0 && filemtime( $file ) > time() - static::get_ttl();
	}

	/**
	 * Get and create the current blog's cache (or uncached) directory
	 *
	 * Not memoised, so a switch_to_blog() never reuses another blog's tree
	 *
	 * @param string $type Either "cache" or "uncached"
	 *
	 * @return string
	 * @since 7.0.0
	 */
	protected static function get_basepath( $type = 'cache' ) {
		$base_path = \GPDFAPI::get_data_class()->template_tmp_location;
		if ( is_multisite() ) {
			$base_path .= get_current_blog_id();
		}

		$path = trailingslashit( $base_path ) . $type . '/';

		/* Another request may create the directory first */
		if ( ! is_dir( $path ) && ( wp_mkdir_p( $path ) || is_dir( $path ) ) ) {
			file_put_contents( $path . 'index.html', '' );
		}

		return $path;
	}

	/**
	 * Calculate a unique hash based on the form/entry/pdf objects
	 *
	 * @param array $form         The form object
	 * @param array $entry        The entry object
	 * @param array $pdf_settings The PDF object/settings
	 *
	 * @return string|false False when the data can't be encoded, so no key would tell two renders apart
	 *
	 * @internal if $form, $entry, $pdf_settings, user ID, site ID, or template files are changed a new hash and PDF will be generated
	 *
	 * @since    7.0.0
	 */
	public static function get_hash( $form, $entry, $pdf_settings ) {

		/*
		 * Standardize field properties that may be added dynamically when fields are processed
		 * for the first run of a PDF. When Gravity Forms accesses properties that don't exist
		 * in a GF_Field object, it adds the value automatically and sets it to an empty string.
		 */
		array_map(
			function ( $field ) {
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

		/* Key the template that renders, which a `?template=` override can change */
		$template    = \GPDFAPI::get_templates_class();
		$template_id = $template->get_requested_template_id( $pdf_settings['template'] ?? '' );

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
				'site_id'               => get_current_blog_id(),
				'user_id'               => get_current_user_id(),
				'fields'                => $form['fields'],
				'entry'                 => $entry,
				'pdf_settings'          => $pdf_settings,
				'template'              => $template_id,
				'template_last_updated' => $template_timestamps,
			],
			$form,
			$entry,
			$pdf_settings
		);

		/* e.g. INF or NAN in a field property. Hashing the failed encode would give every viewer the same key */
		$json = wp_json_encode( $unique_array );
		if ( $json === false ) {
			\GPDFAPI::get_log_class()->warning(
				'The PDF cache key could not be built, so this PDF will not be cached',
				[
					'form_id'    => $form['id'] ?? 0,
					'entry_id'   => $entry['id'] ?? 0,
					'pdf_id'     => $pdf_settings['id'] ?? '',
					'json_error' => json_last_error_msg(),
				]
			);

			return false;
		}

		return sprintf( '%s-%s', static::get_hash_prefix( $form, $entry, $pdf_settings ), wp_hash( $json ) );
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
	 * @since 7.0
	 */
	protected static function get_hash_prefix( $form, $entry, $pdf_settings ) {
		return sprintf(
			's%1$d-f%2$d-e%3$d-p%4$s',
			get_current_blog_id(),
			$form['id'] ?? 0,
			$entry['id'] ?? 0,
			$pdf_settings['id'] ?? '',
		);
	}
}
