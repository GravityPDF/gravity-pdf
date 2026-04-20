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

/* Per-run cleanup endpoint for the Playwright global setup. Each test run
   creates new Gravity Forms entries; without truncation they accumulate
   (1000+ rows after a few weeks of CI) and post-new.php's block sidebar
   loader exhausts PHP's 256MB memory limit. Restricted to admin caps and
   only registered when E2E_TEST_SUITE is set, so it can never reach prod. */
add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'gravitypdf-test/v1',
			'/reset-gf',
			[
				'methods'             => 'POST',
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
				'callback'            => static function () {
					global $wpdb;
					$tables = [
						'gf_form',
						'gf_form_meta',
						'gf_form_view',
						'gf_entry',
						'gf_entry_meta',
						'gf_entry_notes',
					];
					foreach ( $tables as $table ) {
						$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}{$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					}
					return [ 'ok' => true ];
				},
			]
		);
	}
);

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
