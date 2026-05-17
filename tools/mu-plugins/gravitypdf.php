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
