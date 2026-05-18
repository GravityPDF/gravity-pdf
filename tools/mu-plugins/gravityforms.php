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
		if ( ! class_exists( '\GFForms' ) ) {
			return;
		}

		$pending_installation = get_option( 'gform_pending_installation' ) || isset( $_GET['gform_installation_wizard'] ); //phpcs:ignore

		if ( ! $pending_installation ) {
			return;
		}

		update_option( 'gform_pending_installation', false );
		\GFSettings::enable_logging();

		/* Enable API */
		if ( ! class_exists( 'GFWebAPI' ) ) {
			return;
		}

		global $gf_webapi;
		$gf_webapi = GFWebAPI::get_instance();
		$gf_webapi->update_plugin_settings(
			[
				'enabled' => '1',
				'version' => 'v2',
			]
		);
	},
	1000
);
