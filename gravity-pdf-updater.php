<?php

use GFPDF\Helper\Licensing\EDD_SL_Plugin_Updater;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\GFPDF\Helper\Licensing\EDD_SL_Plugin_Updater' ) ) {
	require_once __DIR__ . '/src/Helper/Licensing/EDD_SL_Plugin_Updater.php';
}

/**
 * Support automatic updates from GravityPDF.com
 *
 * @since 6.12.0
 */
add_action(
	'init',
	function() {
		new EDD_SL_Plugin_Updater(
			GPDF_API_URL,
			GPDF_PLUGIN_FILE,
			[
				'version' => PDF_EXTENDED_VERSION,
				'item_id' => 137043,
				'license' => md5( site_url() ),
				'author'  => 'Blue Liquid Designs',
				'beta'    => false,
			]
		);
	}
);

/**
 * Remove dismissible message about upgrading
 *
 * @since 6.12.0
 */
add_action(
	'admin_init',
	function() {
		if ( ! method_exists( '\GFCommon', 'remove_dismissible_message' ) ) {
			return;
		}

		\GFCommon::remove_dismissible_message( 'gravity-pdf-canonical-plugin-notice' );
	}
);
