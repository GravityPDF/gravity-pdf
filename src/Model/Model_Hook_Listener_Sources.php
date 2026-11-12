<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use Throwable;

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
 * Names the plugin, must-use plugin or theme defining a hook callback. WordPress doesn't record who called add_filter(),
 * so this is where the callback is defined, not where it was registered.
 *
 * @since 6.19.0
 */
class Model_Hook_Listener_Sources {

	/**
	 * @var array<string, string> Source name by file, '' where no plugin or theme holds it
	 * @since 6.19.0
	 */
	protected $names = [];

	/**
	 * @var array<string, string>|null Plugin header name by its folder, or by its file for a single-file plugin
	 * @since 6.19.0
	 */
	protected $plugins;

	/**
	 * Name the source of each callback, in order with duplicates removed
	 *
	 * @return string[] '' stands in for a callback no plugin or theme defines
	 * @since 6.19.0
	 */
	public function get_sources( array $callbacks ): array {
		return array_values( array_unique( array_map( [ $this, 'get_source' ], $callbacks ) ) );
	}

	/**
	 * @param mixed $callback A hook callback
	 *
	 * @return string '' when it can't be traced, or no plugin or theme holds it
	 * @since 6.19.0
	 */
	public function get_source( $callback ): string {
		$file = $this->get_callback_file( $callback );
		if ( $file === '' ) {
			return '';
		}

		if ( ! isset( $this->names[ $file ] ) ) {
			$this->names[ $file ] = $this->get_file_source( $file );
		}

		return $this->names[ $file ];
	}

	/**
	 * A method is traced to its object's class, so one inherited from a shared library names the plugin extending it
	 *
	 * @param mixed $callback A hook callback
	 *
	 * @return string '' for a PHP function or an invalid callback
	 * @since 6.19.0
	 */
	public function get_callback_file( $callback ): string {
		if ( is_string( $callback ) && strpos( $callback, '::' ) !== false ) {
			$callback = explode( '::', $callback, 2 );
		}

		try {
			if ( $callback instanceof Closure || is_string( $callback ) ) {
				$reflection = new ReflectionFunction( $callback );
			} elseif ( is_object( $callback ) ) {
				$reflection = new ReflectionClass( $callback );
			} elseif ( is_array( $callback ) && isset( $callback[0] ) ) {
				$reflection = new ReflectionClass( $callback[0] );
			} else {
				return '';
			}
		} catch ( Throwable $e ) {
			return '';
		}

		$file = $reflection->getFileName();

		return is_string( $file ) ? wp_normalize_path( $file ) : '';
	}

	/**
	 * @param string $file A normalised absolute path
	 *
	 * @return string '' when no plugin, must-use plugin or theme holds it
	 * @since 6.19.0
	 */
	public function get_file_source( string $file ): string {
		$mu_plugin = $this->get_root_entry( $file, WPMU_PLUGIN_DIR );
		if ( $mu_plugin !== '' ) {
			return $mu_plugin;
		}

		/* plugin_basename() also maps a symlinked plugin back under the plugins folder */
		$plugin = $this->get_plugin_names()[ explode( '/', plugin_basename( $file ) )[0] ] ?? '';
		if ( $plugin !== '' ) {
			return $plugin;
		}

		foreach ( (array) ( $GLOBALS['wp_theme_directories'] ?? [] ) as $theme_root ) {
			$entry = $this->get_root_entry( $file, $theme_root );
			if ( $entry !== '' ) {
				$theme = wp_get_theme( $entry, $theme_root );

				return $theme->exists() ? (string) $theme->get( 'Name' ) : '';
			}
		}

		return '';
	}

	/**
	 * The file or folder directly inside a root that holds a file, or '' when the root doesn't
	 *
	 * @since 6.19.0
	 */
	protected function get_root_entry( string $file, string $root ): string {
		/* Callbacks report the resolved path, which a symlinked root doesn't share */
		foreach ( array_filter( [ $root, realpath( $root ) ] ) as $path ) {
			$path = trailingslashit( wp_normalize_path( $path ) );

			if ( strpos( $file, $path ) === 0 ) {
				return explode( '/', substr( $file, strlen( $path ) ) )[0];
			}
		}

		return '';
	}

	/**
	 * @return array<string, string>
	 * @since 6.19.0
	 */
	protected function get_plugin_names(): array {
		if ( $this->plugins === null ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$this->plugins = [];
			foreach ( get_plugins() as $basename => $plugin ) {
				$this->plugins[ explode( '/', $basename )[0] ] = (string) ( $plugin['Name'] ?? '' );
			}
		}

		return $this->plugins;
	}
}
