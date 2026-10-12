<?php

use GFPDF\Helper\Helper_Abstract_Addon;
use GFPDF\Helper\Helper_Logger;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Singleton;

/* Only run on test site and excluded from production build */
if ( ! defined( 'TEST_SUITE' ) || ! TEST_SUITE ) {
	return;
}

add_filter( 'gfpdf_one_time_action_routes', '__return_empty_array' );

/* Only run on E2E site and excluded from production build */
if ( ! defined( 'E2E_TEST_SUITE' ) || ! E2E_TEST_SUITE ) {
	return;
}

/**
 * Register a fake addon with Gravity PDF
 */
$addon = static function () {
	if ( ! class_exists( 'GFForms' ) || ! class_exists( 'GPDFAPI' ) || ! class_exists( '\GFPDF\Helper\Helper_Abstract_Addon' ) ) {
		return;
	}

	if ( ! class_exists( 'E2E_Add_On_Bootstrap' ) ) {
		class E2E_Add_On_Bootstrap extends Helper_Abstract_Addon {
		}
	}

	$name = 'Gravity PDF Example Plugin';
	$slug = 'gravity-pdf-example-plugin';

	$plugin = new E2E_Add_On_Bootstrap(
		$slug,
		$name,
		'Gravity PDF',
		'1.0',
		'',
		GPDFAPI::get_data_class(),
		GPDFAPI::get_options_class(),
		new Helper_Singleton(),
		new Helper_Logger( $slug, $name ),
		new Helper_Notices()
	);

	$plugin->set_edd_download_id( '' );
	$plugin->set_addon_documentation_slug( '' );
	$plugin->init();
};

add_action( 'init', $addon, 20 );

/* The deprecation notices are under test (scoped to the requests asking for them, below), so only the core-font one
   stays suppressed for E2E runs */
remove_filter( 'gfpdf_one_time_action_routes', '__return_empty_array' );

add_filter(
	'gfpdf_one_time_action_routes',
	static function ( $routes ) {
		return array_values(
			array_filter(
				$routes,
				static function ( $route ) {
					return $route['action'] !== 'install_core_fonts';
				}
			)
		);
	}
);

/* Give the deprecation detection a third-party filter listener to find, on one hook of each shape the map names:
   a v3-shaped alias and a `gfpdf_legacy_` one. The dynamic `gfpdfe_` prefix walk is covered by PHPUnit instead,
   since it needs a form ID. The callbacks are pass-throughs, so they change nothing for the tests alongside them */
add_action(
	'init',
	static function () {
		if ( ! get_option( 'gfpdf_e2e_deprecated_filter' ) ) {
			return;
		}

		$passthrough = static function ( $value ) {
			return $value;
		};

		add_filter( 'gfpdf_rtl', $passthrough );
		add_filter( 'gfpdf_legacy_templates', $passthrough );
	}
);

/* Site-wide state a test needs, scoped to the requests that send the matching `X-GPDF-E2E-*` header, so the specs
   needing it run in parallel with the rest instead of changing the site under them. Playwright adds the header to
   every request a page makes, downloads and redirects included */
$gfpdf_e2e_header = static function ( string $name ): string {
	return sanitize_key( wp_unslash( $_SERVER[ 'HTTP_X_GPDF_E2E_' . strtoupper( $name ) ] ?? '' ) );
};

/* The site is set up on pretty permalinks, which keeps their rewrite rules and .htaccess in place for the requests
   that need them. Plain permalinks don't read either */
if ( $gfpdf_e2e_header( 'permalinks' ) === 'plain' ) {
	add_filter( 'pre_option_permalink_structure', '__return_empty_string' );
}

if ( $gfpdf_e2e_header( 'debug_mode' ) === 'yes' ) {
	add_filter(
		'gfpdf_get_option_debug_mode',
		static function () {
			return 'Yes';
		}
	);
}

/* Stands in for the Developer Toolkit, which registers the "Toolkit" template header */
if ( $gfpdf_e2e_header( 'toolkit' ) === 'active' ) {
	add_filter(
		'gfpdf_template_header_details',
		static function ( $headers ) {
			$headers['toolkit'] = 'Toolkit';

			return $headers;
		}
	);
}

/* Stands in for a multisite network's media upload limit, which mustn't cap PDF template uploads */
if ( $gfpdf_e2e_header( 'media_upload_limit' ) === 'tiny' ) {
	add_filter(
		'upload_size_limit',
		static function () {
			return KB_IN_BYTES;
		}
	);
}

/* Other specs save settings and fonts, which move every PDF's cache key */
$gfpdf_e2e_cache_generation = $gfpdf_e2e_header( 'cache_generation' );
if ( $gfpdf_e2e_cache_generation !== '' ) {
	add_filter(
		'pre_option_gfpdf_cache_generation',
		static function () use ( $gfpdf_e2e_cache_generation ) {
			return (int) $gfpdf_e2e_cache_generation;
		}
	);
}

/* The deprecated features spec plants its signals site-wide, and the notice they raise would otherwise appear on
   every admin page the other specs take snapshots of */
if ( $gfpdf_e2e_header( 'deprecations' ) !== 'yes' ) {
	add_filter( 'gfpdf_get_option_deprecated_features', '__return_empty_array' );
}

/* Adds a folder the write check fails on. Its failures and dismissals are kept in options of their own, as the other
   specs' system reports and settings saves overwrite the shared ones */
if ( $gfpdf_e2e_header( 'unwritable_folders' ) === 'yes' ) {
	add_filter(
		'gfpdf_installer_create_folders',
		static function ( $folders ) {
			/* Outside the bind mounts, so the mode holds. wp-env never runs PHP as root. Matches the spec's READ_ONLY_FOLDER */
			$dir = '/tmp/gfpdf-e2e-read-only/';
			if ( ! is_dir( $dir ) ) {
				mkdir( $dir, 0555 );
			}

			$folders[] = $dir;

			return $folders;
		}
	);

	add_filter(
		'pre_option_gfpdf_unwritable_folders',
		static function () {
			return get_option( 'gfpdf_e2e_unwritable_folders', [] );
		}
	);

	add_filter(
		'pre_update_option_gfpdf_unwritable_folders',
		static function ( $value, $old_value ) {
			update_option( 'gfpdf_e2e_unwritable_folders', $value );

			return $old_value;
		},
		10,
		2
	);

	add_filter(
		'gfpdf_get_option_action_dismissal',
		static function () {
			return get_option( 'gfpdf_e2e_action_dismissal', [] );
		}
	);

	add_filter(
		'gfpdf_update_option_action_dismissal',
		static function ( $value ) {
			update_option( 'gfpdf_e2e_action_dismissal', $value );

			return $value;
		}
	);
}

/* The editors open on the Visual tab whatever the last test left them on. WordPress remembers the tab per user, and
   every test shares the one admin */
add_filter(
	'wp_default_editor',
	static function () {
		return 'tinymce';
	}
);
