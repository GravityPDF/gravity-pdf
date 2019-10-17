<?php

/*
 * Add stubbing functionality for E2E testing
 */

use GFPDF\Helper\Helper_Logger;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Singleton;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Register fake add-on */
add_action(
	'init',
	function() {

		if ( ! class_exists( 'GFForms' ) || ! class_exists( 'GPDFAPI' ) ) {
			return;
		}

		include __DIR__ . '/src/Add_On_Bootstrap.php';

		$name = 'Gravity PDF Core Booster';
		$slug = 'gravity-pdf-core-booster';

		$plugin = new GFPDF\Add_On_Bootstrap(
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
	}
);

/* Stub Gravity PDF remote requests */
add_filter(
	'pre_http_request',
	function( $return, $req, $url ) {
		/* Handle valid and invalid license responses */
		if ( isset( $req['body']['edd_action'] ) && $req['body']['edd_action'] === 'activate_license' ) {
			if ( $req['body']['license'] === '123456789' ) {
				return [
					'headers'  => [],
					'body'     => '{"success":false}',
					'response' => [
						'code'    => 200,
						'message' => 'OK',
					],
					'cookies'  => [],
					'filename' => '',
				];
			}

			if ( $req['body']['license'] === '987654321' ) {
				return [
					'headers'  => [],
					'body'     => '{"success":true}',
					'response' => [
						'code'    => 200,
						'message' => 'OK',
					],
					'cookies'  => [],
					'filename' => '',
				];
			}
		}

		/* Handle License Deactivation */
		if ( isset( $req['body']['edd_action'] ) && $req['body']['edd_action'] === 'deactivate_license' ) {
			return [
				'headers'  => [],
				'body'     => '{"license":"deactivated"}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
				'cookies'  => [],
				'filename' => '',
			];
		}

		/* Handle Core Font Installer */
		if ( strpos( $url, '/GravityPDF/mpdf-core-fonts/master/' ) !== false ) {
			/* Throw error */
			if ( substr( $url, -4 ) === '.txt' ) {
				return [
					'headers'  => [],
					'body'     => '',
					'response' => [ 'code' => 404 ],
					'cookies'  => [],
					'filename' => '',
				];
			} else {
				return [
					'headers'  => [],
					'body'     => '',
					'response' => [
						'code'    => 200,
						'message' => 'OK',
					],
					'cookies'  => [],
					'filename' => '',
				];
			}

			$counter++;
		}

		return $return;
	},
	10,
	3
);

/* Override mPDF configuration to use the Core fonts */
add_filter(
	'gfpdf_mpdf_class_config',
	function( $config ) {
		return [
			'mode'    => 'c',
			'tempDir' => sys_get_temp_dir(),
		];
	}
);

/* Import Dummy Data Into Database */
add_action(
	'init',
	function() {
		if ( ! class_exists( GFAPI::class ) || ! class_exists( GPDFAPI::class ) ) {
			return;
		}

		if ( get_option( 'freshinstall', false ) ) {
			$form = json_decode( file_get_contents( __DIR__ . '/src/json/sample-form.json' ), true );

			for ( $i = 1; $i <= 5; $i++ ) {
				$form['title'] = "Sample $i";
				$form_id       = GFAPI::add_form( $form );

				if ( ! is_wp_error( $form ) ) {
					GFAPI::add_entry(
						[
							'ip'      => '20.130.10.5',
							'form_id' => $form_id,
							1         => 'value',
							'2.3'     => 'First',
							'2.6'     => 'Last',
							3         => 'name@example.com',
						]
					);
				}

				/* Add a PDF for form 3 / 4 */
				if ( in_array( $i, [ 3, 4 ], true ) ) {
					GPDFAPI::add_pdf(
						$form_id,
						[
							'name'     => 'Sample',
							'template' => 'zadani',
							'filename' => 'Sample',
							'font'     => 'dejavusans',
							'format'   => 'standard',
							'security' => 'no',
						]
					);
				}
			}

			GFSettings::enable_logging();
			update_option( 'gform_pending_installation', false );
			delete_option( 'freshinstall' );
		}

	},
	1000
);
