<?php

/* Only run on test site and excluded from production build */

if ( ! defined( 'E2E_TEST_SUITE' ) || ! E2E_TEST_SUITE ) {
	return;
}

/**
 * Stub Gravity PDF license checks
 *
 * @param $return
 * @param $req
 * @param $url
 *
 * @return array|mixed
 */
$stub = static function ( $return, $req, $url ) {

	/* Handle valid and invalid license responses */
	if ( isset( $req['body']['edd_action'] ) && $req['body']['edd_action'] === 'activate_license' ) {
		if ( $req['body']['license'] === '123456789' ) {
			return [
				'headers'  => [],
				'body'     => '{"error":"missing"}',
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
				'body'     => '{"license":"valid"}',
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
		if ( substr( $url, - 4 ) === '.txt' ) {
			return [
				'headers'  => [],
				'body'     => '',
				'response' => [ 'code' => 404 ],
				'cookies'  => [],
				'filename' => '',
			];
		}

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

	return $return;
};

add_filter( 'pre_http_request', $stub, 10, 3 );
