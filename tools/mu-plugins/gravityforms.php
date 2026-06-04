<?php

/* Only run on test site and excluded from production build */
if ( ! defined( 'TEST_SUITE' ) || ! TEST_SUITE ) {
	return;
}

/* Disable Gravity Forms notice prompt */
if ( ! defined( 'GF_LOGGING_DISABLE_NOTICE' ) ) {
	define( 'GF_LOGGING_DISABLE_NOTICE', true );
}

/* Setup Gravity Forms */
add_action(
	'init',
	function () {
		if ( ! class_exists( '\GFForms' ) || ! class_exists( 'GFWebAPI' ) ) {
			return;
		}

		/*
		 * Enable the GF REST API (v2). This is idempotent rather than gated on the one-shot
		 * `gform_pending_installation` flag: in CI the wp-env work-dir cache can be restored against a fresh
		 * MySQL volume, which wipes the saved API setting while leaving the flag unset — so the flag-gated
		 * version never re-enabled the API and /gf/v2 returned 404. Re-enable whenever it isn't already on.
		 */
		global $gf_webapi;
		$gf_webapi = GFWebAPI::get_instance();

		$settings = (array) $gf_webapi->get_plugin_settings();
		if ( ( $settings['enabled'] ?? '' ) === '1' && ( $settings['version'] ?? '' ) === 'v2' ) {
			return;
		}

		update_option( 'gform_pending_installation', false );
		\GFSettings::enable_logging();

		$gf_webapi->update_plugin_settings(
			[
				'enabled' => '1',
				'version' => 'v2',
			]
		);
	},
	1000
);
