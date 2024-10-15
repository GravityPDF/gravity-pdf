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

/*
 * Add notice below the non-canonical plugin, if it exists
 */
add_action(
	'after_plugin_row',
	function( $plugin_file, $plugin_data ) {
		if ( ! isset( $plugin_data['TextDomain'] ) || $plugin_data['TextDomain'] !== 'gravity-forms-pdf-extended' ) {
			return;
		}

		printf(
			'<tr class="plugin-update-tr %3$s" id="%1$s-update" data-slug="%1$s" data-plugin="%2$s">',
			esc_attr( $plugin_data['slug'] ),
			esc_attr( $plugin_data['plugin'] ),
			'inactive'
		);

		echo '<td colspan="4" class="plugin-update colspanchange">';
		echo '<div class="notice inline notice-warning notice-alt"><p>';

		echo esc_html__( 'This is the non-canonical version of Gravity PDF.', 'gravity-pdf' );

		echo '</p></div>';
		echo '</td>';
		echo '</tr>';
	},
	10,
	2
);
