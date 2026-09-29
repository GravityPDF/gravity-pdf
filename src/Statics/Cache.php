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
 * Where cached PDFs are stored and how they're keyed. Model_Pdf_Cache purges them and runs the background sweep.
 *
 * A cached PDF is reused until something in its key changes (see get_hash()) or the cache duration runs out. The key
 * includes the modification time of the template and its config file, but not of the partials, stylesheets or images
 * the template includes. While developing a template, add `?cache=0` to the PDF URL (needs Debug Mode or a
 * non-production environment, and the `gravityforms_logging` capability) or turn off Cache PDFs under
 * Forms > Settings > PDF > Settings.
 *
 * On hosts with several web servers, keep `gfpdf_tmp_location` on storage they all share. On disk local to each server,
 * deleting an entry's PDFs, and the background sweep, only reach the server they run on. A changed entry, form or
 * setting, or a cleared cache, still stops every server serving old PDFs, because the key comes from the database.
 *
 * @since 7.0.0
 */
class Cache {

	/**
	 * Entry properties that don't change a PDF. Also the default `gfpdf_cache_hash_ignored_entry_meta` list.
	 *
	 * @since 7.0
	 */
	const IGNORED_ENTRY_META = [ 'is_read', 'is_starred', 'is_approved', 'status', 'source_url', 'user_agent' ];

	/**
	 * Get the unique directory path for the current PDF: `{cache}/e{entry}/p{pdf}-{hash}/`
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
		if ( $hash === false ) {
			return false;
		}

		$pdf_id = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $pdf_settings['id'] ?? '' ) );

		return static::create_dir( static::get_entry_dir( $entry['id'] ?? 0 ) ) . 'p' . $pdf_id . '-' . $hash . '/';
	}

	/**
	 * The directory holding every cached PDF for an entry, on the current site
	 *
	 * @param int $entry_id
	 *
	 * @return string
	 *
	 * @since 7.0
	 */
	public static function get_entry_dir( $entry_id ) {
		return static::get_root() . 'e' . (int) $entry_id . '/';
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
		$hours = static::get_setting( 'cache_duration', 0 );
		$hours = is_numeric( $hours ) && $hours > 0 ? (float) $hours : 12;

		/**
		 * How long a cached PDF can be served for, in seconds. At least 1.
		 *
		 * @param int $ttl Defaults to the Cache Duration setting
		 *
		 * @since 7.0
		 */
		return max( 1, (int) apply_filters( 'gfpdf_cache_ttl', (int) round( $hours * HOUR_IN_SECONDS ) ) );
	}

	/**
	 * Read one of the current site's Gravity PDF settings
	 *
	 * The options class holds the settings of the site it loaded on, so a sweep run under switch_to_blog() would read
	 * the wrong site's
	 *
	 * @param string $key
	 * @param mixed  $fallback Returned when the setting is empty
	 *
	 * @return mixed
	 *
	 * @since 7.0
	 */
	protected static function get_setting( $key, $fallback ) {
		$settings = get_option( 'gfpdf_settings', [] );

		return is_array( $settings ) && ! empty( $settings[ $key ] ) ? $settings[ $key ] : $fallback;
	}

	/**
	 * Whether a PDF is cached. When it isn't, every request renders it to a one-off path.
	 *
	 * @param array $form         The form object
	 * @param array $entry        The entry object
	 * @param array $pdf_settings The PDF object/settings
	 *
	 * @return bool
	 *
	 * @since 7.0
	 */
	public static function is_enabled( $form, $entry, $pdf_settings ) {
		/**
		 * @param bool  $enabled Defaults to the Cache PDFs setting
		 * @param array $form
		 * @param array $entry
		 * @param array $pdf_settings
		 *
		 * @since 7.0
		 */
		return (bool) apply_filters( 'gfpdf_enable_pdf_cache', static::is_enabled_by_setting(), $form, $entry, $pdf_settings );
	}

	/**
	 * Whether the Cache PDFs setting is on. It's on until it's saved as "No".
	 *
	 * @param array|null $settings Gravity PDF's settings. Defaults to the current site's.
	 *
	 * @return bool
	 *
	 * @since 7.0
	 */
	public static function is_enabled_by_setting( $settings = null ) {
		$settings = $settings ?? get_option( 'gfpdf_settings', [] );

		return ! is_array( $settings ) || empty( $settings['cache_pdfs'] ) || $settings['cache_pdfs'] === 'Yes';
	}

	/**
	 * The site-wide cache generation. Bumping it moves every cache key, so nothing cached before is served again.
	 *
	 * @return int
	 *
	 * @since 7.0
	 */
	public static function get_generation() {
		return (int) get_option( 'gfpdf_cache_generation', 0 );
	}

	/**
	 * Stop serving everything cached on the current site
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public static function bump_generation() {
		update_option( 'gfpdf_cache_generation', static::get_generation() + 1, false );
	}

	/**
	 * @param int $form_id
	 *
	 * @return int
	 *
	 * @since 7.0
	 */
	public static function get_form_generation( $form_id ) {
		$generations = get_option( 'gfpdf_cache_form_generation', [] );

		return is_array( $generations ) ? (int) ( $generations[ (int) $form_id ] ?? 0 ) : 0;
	}

	/**
	 * Stop serving everything cached for one form
	 *
	 * @param int $form_id
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public static function bump_form_generation( $form_id ) {
		$generations = get_option( 'gfpdf_cache_form_generation', [] );
		$generations = is_array( $generations ) ? $generations : [];

		$generations[ (int) $form_id ] = (int) ( $generations[ (int) $form_id ] ?? 0 ) + 1;

		update_option( 'gfpdf_cache_form_generation', $generations, false );
	}

	/**
	 * Bump the cache generation when a Gravity PDF setting that can change a PDF is saved
	 *
	 * Also hooked to `add_option_gfpdf_settings`, which passes the option name in place of the old settings, so every key
	 * counts as changed. Settings a render never reads, or only reads before generation
	 * (access checks), are ignored, as are the licence and notice state saved in the background.
	 *
	 * @param array|string $old_settings
	 * @param array        $new_settings
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public static function maybe_bump_generation( $old_settings, $new_settings ) {
		$old_settings = is_array( $old_settings ) ? $old_settings : [];
		$new_settings = is_array( $new_settings ) ? $new_settings : [];

		/**
		 * Settings that don't change a generated PDF, so saving them doesn't clear the cache. Accepts glob patterns.
		 *
		 * @param string[] $ignored
		 *
		 * @since 7.0
		 */
		$ignored = (array) apply_filters(
			'gfpdf_cache_generation_ignored_settings',
			[
				'logged_out_timeout',
				'admin_capabilities',
				'default_restrict_owner',
				'default_action',
				'background_processing',
				'license_*',
				'action_dismissal',
				'deprecated_features',
				'signed_secret_token',
				'cache_duration',
				'clear_pdf_cache',
			]
		);

		foreach ( array_keys( $old_settings + $new_settings ) as $key ) {
			if ( ( $old_settings[ $key ] ?? null ) !== ( $new_settings[ $key ] ?? null ) && ! static::is_ignored_setting( (string) $key, $ignored ) ) {
				static::bump_generation();

				return;
			}
		}
	}

	/**
	 * @param string   $key
	 * @param string[] $ignored Setting keys, or glob patterns
	 *
	 * @return bool
	 *
	 * @since 7.0
	 */
	protected static function is_ignored_setting( $key, $ignored ) {
		foreach ( $ignored as $pattern ) {
			if ( fnmatch( (string) $pattern, $key ) ) {
				return true;
			}
		}

		return false;
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
	 * Take the lock for a cache key, so only one request renders it while the others wait for the result
	 *
	 * Waits up to 10 seconds (filter `gfpdf_cache_lock_wait`). Returns null when the lock times out or the filesystem
	 * doesn't support locking, and the caller renders unprotected rather than holding up the request.
	 *
	 * @param string $path The cache directory from get_path()
	 *
	 * @return resource|null Pass to unlock()
	 *
	 * @since 7.0
	 */
	public static function lock( $path ) {
		$file = static::get_lock_file( $path );
		$fp   = @fopen( $file, 'c' ); //phpcs:ignore
		if ( $fp === false ) {
			\GPDFAPI::get_log_class()->warning( 'Could not open the PDF cache lock', [ 'file' => $file ] );

			return null;
		}

		$deadline = microtime( true ) + max( 0, (float) apply_filters( 'gfpdf_cache_lock_wait', 10, $path ) );

		while ( true ) {
			$wouldblock = 0;
			if ( static::try_lock( $fp, $wouldblock ) ) {
				/* Keeps the tmp cleanup from reaping a lock in use */
				@touch( $file ); //phpcs:ignore

				return $fp;
			}

			/* Anything but contention means this filesystem can't lock (some NFS mounts), so don't wait for it */
			if ( ! $wouldblock ) {
				\GPDFAPI::get_log_class()->warning( 'The PDF cache lock is not supported on this filesystem', [ 'file' => $file ] );
				break;
			}

			if ( microtime( true ) >= $deadline ) {
				\GPDFAPI::get_log_class()->notice( 'Timed out waiting for another request to generate the PDF', [ 'file' => $file ] );
				break;
			}

			usleep( 100000 );
		}

		fclose( $fp );

		return null;
	}

	/**
	 * Get the lock file for a cache key, in a directory outside every key's own
	 *
	 * @param string $path The cache directory from get_path()
	 *
	 * @return string
	 *
	 * @since 7.0
	 */
	public static function get_lock_file( $path ) {
		$root = static::get_root();
		$name = strpos( $path, $root ) === 0 ? substr( $path, strlen( $root ) ) : basename( $path );

		return static::create_dir( $root . 'locks/' ) . str_replace( '/', '-', trim( $name, '/' ) ) . '.lock';
	}

	/**
	 * @param resource $fp
	 * @param int      $wouldblock Set to 1 when another process holds the lock
	 *
	 * @return bool
	 *
	 * @since 7.0
	 */
	protected static function try_lock( $fp, &$wouldblock ) {
		return flock( $fp, LOCK_EX | LOCK_NB, $wouldblock );
	}

	/**
	 * Release a lock taken by lock()
	 *
	 * @param resource|null $lock
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public static function unlock( $lock ) {
		if ( is_resource( $lock ) ) {
			flock( $lock, LOCK_UN );
			fclose( $lock );
		}
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
		return static::create_dir( static::get_root( $type ) );
	}

	/**
	 * Get the current blog's cache (or uncached) directory, without creating it
	 *
	 * @param string $type Either "cache" or "uncached"
	 *
	 * @return string
	 *
	 * @since 7.0
	 */
	public static function get_root( $type = 'cache' ) {
		$base_path = \GPDFAPI::get_data_class()->template_tmp_location;
		if ( is_multisite() ) {
			$base_path .= get_current_blog_id();
		}

		return trailingslashit( $base_path ) . $type . '/';
	}

	/**
	 * Create a directory, and any missing parents, each with a blank index.html
	 *
	 * @param string $path
	 *
	 * @return string The path
	 *
	 * @since 7.0
	 */
	protected static function create_dir( $path ) {
		if ( is_dir( $path ) ) {
			return $path;
		}

		/* Every directory made gets one, not only the last */
		$missing = [];
		$dir     = $path;
		while ( ! is_dir( $dir ) && dirname( $dir ) !== rtrim( $dir, '/' ) ) {
			$missing[] = $dir;
			$dir       = trailingslashit( dirname( $dir ) );
		}

		/* Another request may create the directory first */
		if ( wp_mkdir_p( $path ) || is_dir( $path ) ) {
			foreach ( $missing as $dir ) {
				file_put_contents( $dir . 'index.html', '' );
			}
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
	 * @internal a change to any component of the key generates a new hash and PDF
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
		$ignored_entry_meta = apply_filters( 'gfpdf_cache_hash_ignored_entry_meta', static::IGNORED_ENTRY_META, $form, $entry, $pdf_settings );
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

		/**
		 * Add to the data that keys a cached PDF, e.g. an add-on's version, data its fields render from another source, or
		 * the modification time of a partial or stylesheet a template includes. Nested values must be in a stable order.
		 *
		 * @param array $extra
		 * @param array $form
		 * @param array $entry
		 * @param array $pdf_settings
		 *
		 * @since 7.0
		 */
		$extra = apply_filters( 'gfpdf_cache_hash_extra', [], $form, $entry, $pdf_settings );
		$extra = is_array( $extra ) ? $extra : [];
		ksort( $extra );

		$user  = wp_get_current_user();
		$roles = array_values( (array) $user->roles );
		sort( $roles );

		/* Viewer identity sits outside the filter, so no listener can remove it */
		$unique_array = [
			'version'               => [ PDF_EXTENDED_VERSION, class_exists( '\GFForms' ) ? \GFForms::$version : '' ],
			'generation'            => static::get_generation(),
			'form_generation'       => static::get_form_generation( $form['id'] ?? 0 ),
			'options'               => static::get_hash_options(),
			'form'                  => static::get_hash_form( $form, $entry, $pdf_settings ),
			'entry'                 => $entry,
			'pdf_settings'          => $pdf_settings,
			'template'              => $template_id,
			'template_last_updated' => $template_timestamps,
			'extra'                 => $extra,
			'site_id'               => get_current_blog_id(),
			'user_id'               => $user->ID,
			'roles'                 => $roles,
			'locale'                => determine_locale(),
		];

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

		return wp_hash( $json );
	}

	/**
	 * The form, without the parts a PDF doesn't render
	 *
	 * @param array $form         The form object
	 * @param array $entry        The entry object
	 * @param array $pdf_settings The PDF object/settings
	 *
	 * @return array
	 *
	 * @since 7.0
	 */
	protected static function get_hash_form( $form, $entry, $pdf_settings ) {
		/**
		 * Top-level form keys that don't change a PDF, so editing them doesn't change its cache key. The PDF being
		 * rendered is keyed from its own settings, so `gfpdf_form_settings` is ignored too.
		 *
		 * @param string[] $ignored
		 * @param array    $form
		 * @param array    $entry
		 * @param array    $pdf_settings
		 *
		 * @since 7.0
		 */
		$ignored_form_keys = apply_filters( 'gfpdf_cache_hash_ignored_form_keys', [ 'notifications', 'confirmations', 'confirmation', 'date_created', 'is_active', 'is_trash', 'page_instance', 'gfpdf_form_settings' ], $form, $entry, $pdf_settings );
		foreach ( (array) $ignored_form_keys as $key ) {
			unset( $form[ $key ] );
		}

		return $form;
	}

	/**
	 * Site options a render reads outside Gravity PDF's own settings
	 *
	 * @return array
	 *
	 * @since 7.0
	 */
	protected static function get_hash_options() {
		$options = [];
		foreach ( [ 'blogname', 'admin_email', 'home', 'siteurl', 'date_format', 'time_format', 'timezone_string', 'gmt_offset', 'gform_upload_page_slug' ] as $option ) {
			$options[ $option ] = get_option( $option );
		}

		/* Gravity Forms' fallback for an entry saved without a currency */
		$options['currency'] = class_exists( '\GFCommon' ) ? \GFCommon::get_currency() : '';

		return $options;
	}
}
