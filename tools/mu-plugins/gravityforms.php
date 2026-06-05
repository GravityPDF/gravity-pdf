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

		global $gf_webapi;
		$gf_webapi = GFWebAPI::get_instance();

		$settings = (array) $gf_webapi->get_plugin_settings();
		if ( ( $settings['enabled'] ?? '' ) === '1' && ( $settings['version'] ?? '' ) === 'v2' ) {
			return;
		}

		update_option( 'gform_pending_installation', false );
		GFSettings::enable_logging();

		$gf_webapi->update_plugin_settings(
			[
				'enabled' => '1',
				'version' => 'v2',
			]
		);
	},
	1000
);
