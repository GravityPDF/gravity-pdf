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

/* The deprecated features spec plants its signals site-wide, and the notice they raise would otherwise appear on
   every admin page the other specs take snapshots of */
if ( $gfpdf_e2e_header( 'deprecations' ) !== 'yes' ) {
	add_filter( 'gfpdf_get_option_deprecated_features', '__return_empty_array' );
}

/* The editors open on the Visual tab whatever the last test left them on. WordPress remembers the tab per user, and
   every test shares the one admin */
add_filter(
	'wp_default_editor',
	static function () {
		return 'tinymce';
	}
);
