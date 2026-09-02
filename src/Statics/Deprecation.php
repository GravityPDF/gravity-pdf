<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

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
 * Detects and reports the deprecated Gravity PDF functionality a site still relies on
 *
 * The functionality itself is described by the providers in self::get_providers(), so each new round of removals is
 * registered rather than wired in. This class only knows how to detect, group and warn about whatever they declare.
 *
 * @since 6.17.0
 */
class Deprecation {

	/**
	 * Functionality that has been removed from Gravity PDF
	 *
	 * @var string
	 * @since 6.17.0
	 */
	const GROUP_UNSUPPORTED = 'unsupported';

	/**
	 * Functionality that still works, but is scheduled for removal
	 *
	 * @var string
	 * @since 6.17.0
	 */
	const GROUP_DEPRECATED = 'deprecated';

	/**
	 * Memoised self::get_signals(), keyed by the registry that produced it
	 *
	 * @var array<string, array>
	 * @since 6.17.0
	 */
	protected static $signals = [];

	/**
	 * The classes describing the functionality Gravity PDF is removing
	 *
	 * Add a class here to have its features detected and reported everywhere the existing ones are.
	 *
	 * @return string[] Helper_Interface_Deprecated_Features implementations
	 * @since 6.17.0
	 */
	protected static function get_providers(): array {
		return [
			Deprecation_V3::class,
		];
	}

	/**
	 * Every feature the providers declare, keyed by the ID it is detected and reported under
	 *
	 * @return array<string, array> See Helper_Interface_Deprecated_Features::get_features() for the shape of an entry
	 * @since 6.17.0
	 */
	public static function get_features(): array {
		static $features = [];

		/* Keyed by the registry, since a subclass can declare a different one */
		$key = static::class;

		if ( ! isset( $features[ $key ] ) ) {
			$registered = [];

			foreach ( static::get_providers() as $provider ) {
				$registered += $provider::get_features();
			}

			$features[ $key ] = $registered;
		}

		return $features[ $key ];
	}

	/**
	 * Get a single feature, or an empty array when nothing is registered under that ID
	 *
	 * @param string $key The feature ID
	 *
	 * @since 6.17.0
	 */
	public static function get_feature( string $key ): array {
		return static::get_features()[ $key ] ?? [];
	}

	/**
	 * Get every signal that deprecated functionality is in use on this site
	 *
	 * Features with nothing to report are omitted, so the return value doubles as the list of features in use.
	 *
	 * @return array<string, array> Feature ID mapped to what its detector found
	 * @since 6.17.0
	 */
	public static function get_signals(): array {
		/* Keyed by the registry like self::get_features() */
		$key = static::class;

		if ( ! isset( static::$signals[ $key ] ) ) {
			$detected = [];

			foreach ( static::get_features() as $feature_key => $feature ) {
				$detected[ $feature_key ] = call_user_func( $feature['detect'] );
			}

			static::$signals[ $key ] = array_filter( $detected );
		}

		return static::$signals[ $key ];
	}

	/**
	 * Forget what the detectors found this request, here and in every provider
	 *
	 * The one entry point for invalidating detection, so nothing calling it has to know which class holds what.
	 *
	 * @since 6.17.0
	 */
	public static function flush_cache(): void {
		/* Every registry, not just this one: a subclass shares the store, and a caller flushing means all of it */
		static::$signals = [];

		foreach ( static::get_providers() as $provider ) {
			$provider::flush_cache();
		}
	}

	/**
	 * The features this site was last found to be using
	 *
	 * The admin notices read this rather than detecting for themselves: detection walks the database and the
	 * template directory, which is far too much to repeat on every admin page a notice can appear on. The record is
	 * rewritten whenever detection genuinely runs — see self::store_detected_features().
	 *
	 * @return array A list of feature IDs
	 * @since 6.17.0
	 */
	public static function get_detected_features(): array {
		return (array) \GPDFAPI::get_options_class()->get_option( 'deprecated_features', [] );
	}

	/**
	 * Record what a run of self::get_signals() found
	 *
	 * Called on every version change, which is when a new round of removals arrives, and again by the report and
	 * Site Health screens, which detect live — so a site that has fixed everything stops being told about it
	 * without waiting for the next release.
	 *
	 * @param array $signals The signals from self::get_signals()
	 *
	 * @since 6.17.0
	 */
	protected static function store_detected_features( array $signals ): void {
		$detected = array_keys( $signals );

		if ( $detected !== static::get_detected_features() ) {
			\GPDFAPI::get_options_class()->update_option( 'deprecated_features', $detected );
		}
	}

	/**
	 * Add a single feature to the record, when something detects it outside of a full detection run
	 *
	 * @param string $key The feature ID
	 *
	 * @since 6.17.0
	 */
	public static function mark_feature_detected( string $key ): void {
		$detected = static::get_detected_features();

		if ( in_array( $key, $detected, true ) ) {
			return;
		}

		$detected[] = $key;

		\GPDFAPI::get_options_class()->update_option( 'deprecated_features', $detected );
	}

	/**
	 * Delete everything the providers have stored, so a new provider doesn't have to be added to the uninstaller
	 *
	 * The engine's own record needs no handling here: it lives in `gfpdf_settings`, which is deleted wholesale.
	 *
	 * @since 6.17.0
	 */
	public static function delete_stored_data(): void {
		foreach ( static::get_providers() as $provider ) {
			foreach ( $provider::get_stored_options() as $option ) {
				delete_option( $option );
			}
		}
	}

	/**
	 * Detect what this site is using, and record it for the admin notices to read
	 *
	 * The notices read the record rather than detecting for themselves, so every surface that detects live
	 * refreshes it as it goes and a site that has fixed everything stops being told about it.
	 *
	 * @return array<string, array> See self::get_signals()
	 * @since 6.17.0
	 */
	public static function refresh_signals(): array {
		$signals = static::get_signals();

		static::store_detected_features( $signals );

		return $signals;
	}

	/**
	 * State what becomes of a feature, which is all its group and removal version have to say
	 *
	 * Read by every surface that reports the feature, so it says the same thing wherever it is named. The fallback
	 * names the feature and the release, which suits most of them; one that reads badly out of the context its
	 * report row gives it declares a `notice` of its own instead.
	 *
	 * @param array $feature The feature's registration
	 *
	 * @since 6.17.0
	 */
	public static function get_feature_notice( array $feature ): string {
		/* A feature saying it better than the fallback can declares its own sentence */
		if ( isset( $feature['notice'] ) ) {
			return sprintf( $feature['notice'], $feature['removed_in'] );
		}

		if ( $feature['group'] === static::GROUP_UNSUPPORTED ) {
			/* translators: %s: The name of the deprecated feature */
			return sprintf( __( '%s is no longer supported.', 'gravity-pdf' ), $feature['label'] );
		}

		/* translators: 1: The name of the deprecated feature, 2: The Gravity PDF version it is removed in */
		return sprintf( __( 'Support for %1$s will be removed in Gravity PDF %2$s.', 'gravity-pdf' ), $feature['label'], $feature['removed_in'] );
	}

	/**
	 * The groups a registered feature belongs to, in the order they are reported
	 *
	 * A group nothing declares is left out, so no surface carries an empty section around until a later round of
	 * removals has something to put in it.
	 *
	 * @return string[]
	 * @since 6.17.0
	 */
	public static function get_groups(): array {
		$declared = array_column( static::get_features(), 'group' );

		return array_values(
			array_filter(
				[ static::GROUP_UNSUPPORTED, static::GROUP_DEPRECATED ],
				static function ( $group ) use ( $declared ) {
					return in_array( $group, $declared, true );
				}
			)
		);
	}

	/**
	 * Split the detected signals by the group their feature belongs to
	 *
	 * @param array $signals The signals from self::get_signals()
	 *
	 * @return array<string, array> Group mapped to its signals, with empty groups omitted
	 * @since 6.17.0
	 */
	public static function group_signals( array $signals ): array {
		$features = static::get_features();
		$groups   = array_fill_keys( static::get_groups(), [] );

		foreach ( $signals as $key => $signal ) {
			$groups[ $features[ $key ]['group'] ][ $key ] = $signal;
		}

		return array_filter( $groups );
	}

	/**
	 * Fire one of the deprecated hooks, warning any third party still listening to it
	 *
	 * The version and replacement come from whichever registered feature owns the hook, so the notice names the
	 * release it disappears in. It is only emitted when a listener is actually attached, so sites that have already
	 * moved on stay quiet.
	 *
	 * @param string $hook_name   The deprecated filter to fire
	 * @param array  $args        The arguments to pass to the filter, the first of which is returned
	 * @param string $replacement Override the mapped replacement, for dynamic hooks
	 *
	 * @return mixed
	 * @since 6.17.0
	 */
	public static function apply_filters( string $hook_name, array $args, string $replacement = '' ) {
		/* Sidestep building the deprecation message on the vast majority of sites, which have no listener */
		if ( ! has_filter( $hook_name ) ) {
			return $args[0];
		}

		$feature    = static::get_feature_by_hook( $hook_name );
		$removed_in = $feature['removed_in'] ?? '';

		if ( $replacement === '' ) {
			$replacement = $feature['hooks'][ $hook_name ] ?? '';
		}

		static::log_deprecated_filter( $hook_name, $replacement, $removed_in );

		return apply_filters_deprecated(
			$hook_name,
			$args,
			$feature['deprecated_in'] ?? '',
			$replacement,
			$removed_in === '' ? '' : sprintf(
				/* translators: %s: The Gravity PDF version the filter is removed in */
				esc_html__( 'This filter is removed in Gravity PDF %s.', 'gravity-pdf' ),
				$removed_in
			)
		);
	}

	/**
	 * Find the registered feature a deprecated hook belongs to
	 *
	 * @param string $hook_name The deprecated hook
	 *
	 * @since 6.17.0
	 */
	protected static function get_feature_by_hook( string $hook_name ): array {
		foreach ( static::get_features() as $feature ) {
			$prefix = $feature['hook_prefix'] ?? '';

			if ( isset( $feature['hooks'][ $hook_name ] ) || ( $prefix !== '' && strpos( $hook_name, $prefix ) === 0 ) ) {
				return $feature;
			}
		}

		return [];
	}

	/**
	 * Record a deprecated hook with a third-party listener in the Gravity PDF log
	 *
	 * Written once per hook per request so a PDF that fires the same filter for every field doesn't flood the log.
	 *
	 * @param string $hook_name   The deprecated filter being fired
	 * @param string $replacement The filter to use instead, if one exists
	 * @param string $removed_in  The Gravity PDF version it is removed in, if it is known
	 *
	 * @since 6.17.0
	 */
	protected static function log_deprecated_filter( string $hook_name, string $replacement, string $removed_in ): void {
		static $logged = [];

		if ( isset( $logged[ $hook_name ] ) ) {
			return;
		}

		/* Some deprecated hooks fire while the container is still being built, so the logger may not exist yet */
		$log = \GPDFAPI::get_log_class();
		if ( $log === null ) {
			return;
		}

		$logged[ $hook_name ] = true;

		$log->warning(
			sprintf(
				'The %1$s filter has a third-party listener attached%2$s.%3$s',
				$hook_name,
				$removed_in !== '' ? sprintf( ' and is removed in Gravity PDF %s', $removed_in ) : '',
				$replacement !== '' ? sprintf( ' Use the %s filter instead.', $replacement ) : ''
			)
		);
	}

	/**
	 * Get the deprecated hooks that currently have a third-party listener attached
	 *
	 * Walks every registered hook so dynamic ones, like `gfpdfe_pdf_template_{form_id}`, are included too. Only the
	 * System Report and Site Health screens ask for this, so the walk stays off the paths that matter.
	 *
	 * @param array  $hooks            The known hooks, mapped to their replacement
	 * @param string $prefix           Also match any hook starting with this
	 * @param array  $ignore_callbacks The callbacks Gravity PDF registers itself, which aren't third party
	 *
	 * @return array<string, int> Hook name mapped to the number of listeners
	 * @since 6.17.0
	 */
	public static function get_hooks_with_listeners( array $hooks, string $prefix = '', array $ignore_callbacks = [] ): array {
		global $wp_filter;

		$active = [];

		foreach ( (array) $wp_filter as $name => $hook ) {
			$name = (string) $name;

			if ( ! isset( $hooks[ $name ] ) && ( $prefix === '' || strpos( $name, $prefix ) !== 0 ) ) {
				continue;
			}

			$count = static::count_third_party_callbacks( $hook, $ignore_callbacks );
			if ( $count > 0 ) {
				$active[ $name ] = $count;
			}
		}

		ksort( $active );

		return $active;
	}

	/**
	 * Count the callbacks on a hook, ignoring the ones Gravity PDF registers itself
	 *
	 * @param \WP_Hook $hook
	 * @param array    $ignore_callbacks
	 *
	 * @since 6.17.0
	 */
	protected static function count_third_party_callbacks( $hook, array $ignore_callbacks ): int {
		$count = 0;

		foreach ( $hook->callbacks ?? [] as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( in_array( $callback['function'] ?? null, $ignore_callbacks, true ) ) {
					continue;
				}

				++$count;
			}
		}

		return $count;
	}
}
