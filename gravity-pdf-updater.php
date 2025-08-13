<?php

use GFPDF\Helper\Licensing\EDD_SL_Plugin_Updater;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
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
	function () {
		$plugin_updater = new EDD_SL_Plugin_Updater(
			GPDF_API_URL,
			GPDF_PLUGIN_FILE,
			[
				'version'   => PDF_EXTENDED_VERSION,
				'item_name' => 'Gravity PDF',
				'item_id'   => 137043,
				'license'   => md5( site_url() ),
				'author'    => 'Blue Liquid Designs',
				'beta'      => false,
			]
		);

		$plugin_updater->init();

		/* Only make plugin updater available when fully initialized */
		if ( ! class_exists( 'GPDFAPI' ) ) {
			return;
		}

		$data          = \GPDFAPI::get_data_class();
		$data->updater = $plugin_updater;
	}
);

/**
 * Log licencing API calls for debugging
 */
add_action(
	'http_api_debug',
	function ( $response, $context, $class_object, $parsed_args, $url ) {
		/* Log only Gravity PDF requests */
		if ( $url !== GPDF_API_URL ) {
			return;
		}

		/* Only log requests when fully initialized */
		if ( ! class_exists( 'GPDFAPI' ) ) {
			return;
		}

		$logger = \GPDFAPI::get_log_class();

		$logger->notice(
			'Gravity PDF License Check API Request',
			[
				'url'    => $url,
				'method' => $parsed_args['method'] ?? '',
				'body'   => $parsed_args['body'] ?? [],
			]
		);

		$response_code      = wp_remote_retrieve_response_code( $response );
		$response_body      = wp_remote_retrieve_body( $response );
		$response_body_json = json_decode( $response_body, true );

		$response_context = [
			'status'  => $response_code,
			'headers' => (array) wp_remote_retrieve_headers( $response ),
			'body'    => $response_body_json ?? $response_body,
		];

		if ( $response_code >= 300 ) {
			// response failed
			$logger->warning( 'Gravity PDF License Check API Response', $response_context );
		} else {
			// response success
			$logger->notice( 'Gravity PDF License Check API Response', $response_context );
		}
	},
	10,
	5
);


/**
 * Remove dismissible message about upgrading
 *
 * @since 6.12.0
 */
add_action(
	'admin_init',
	function () {
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
	function ( $plugin_file, $plugin_data ) {
		if ( ! isset( $plugin_data['TextDomain'] ) || $plugin_data['TextDomain'] !== 'gravity-forms-pdf-extended' ) {
			return;
		}

		printf(
			'<tr class="plugin-update-tr %3$s" id="%1$s-update" data-slug="%1$s" data-plugin="%2$s">',
			esc_attr( 'gravity-forms-pdf-extended' ),
			esc_attr( 'gravity-forms-pdf-extended/pdf.php' ),
			'inactive'
		);

		echo '<td colspan="4" class="plugin-update colspanchange">';
		echo '<div class="notice inline notice-warning notice-alt"><p>';

		echo esc_html__( 'This is the non-canonical release of Gravity PDF which can be deleted.', 'gravity-pdf' );

		echo '</p></div>';
		echo '</td>';
		echo '</tr>';
	},
	10,
	2
);
