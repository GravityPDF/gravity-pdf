<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Addon;
use GFPDF\Helper\Helper_Logger;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Singleton;
use GFPDF\Statics\Cache;
use GFPDF\Tests\Integration\AjaxTestCase;
use WP_Error;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;

/**
 * @group ajax
 */
class Test_Model_Settings_Ajax extends AjaxTestCase {

	public function tear_down(): void {
		remove_all_filters( 'pre_http_request' );
		$this->gfpdf()->data->addon = [];

		parent::tear_down();
	}

	public function test_ajax_process_license_deactivation() {
		$this->_setRole( 'administrator' );

		try {
			$this->_handleAjax( 'gfpdf_deactivate_license' );
			$this->fail( 'Expected WPAjaxDieStopException was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '401', $e->getMessage() );
		}

		$_POST['nonce'] = wp_create_nonce( 'gfpdf_deactivate_license' );

		try {
			$this->_handleAjax( 'gfpdf_deactivate_license' );
			$this->fail( 'Expected WPAjaxDieContinueException was not thrown.' );
		} catch ( WPAjaxDieContinueException $e ) {
			$this->assertStringContainsString( 'An unknown error occurred', json_decode( $this->_last_response )->error );
		}
	}

	/**
	 * Access Pass keys are site-activated, so deactivating one add-on deactivates every sibling sharing the pass. The
	 * `extra` array names those siblings and is consumed by assets/js/admin/settings/common/setupLicenseDeactivation.js.
	 */
	public function test_ajax_process_license_deactivation_shares_access_pass_siblings() {
		$this->_setRole( 'administrator' );

		$master  = $this->register_deactivation_addon( 'master-plugin', 'Master', '/master/file.php', 5 );
		$sibling = $this->register_deactivation_addon( 'sibling-plugin', 'Sibling', '/sibling/file.php', 10 );

		$license = [
			'license' => 'AP-KEY',
			'status'  => 'active',
			'message' => 'ok',
		];

		$master->update_license_info( $license );
		$sibling->update_license_info( $license );

		/* The API confirms deactivation and reports the pass products, so the sibling auto-deactivates too */
		add_filter(
			'pre_http_request',
			static function () {
				return [
					'response' => [ 'code' => 200 ],
					'body'     => wp_json_encode(
						[
							'license'  => 'deactivated',
							'products' => [ 5, 10 ],
						]
					),
				];
			}
		);

		$_POST['nonce']      = wp_create_nonce( 'gfpdf_deactivate_license' );
		$_POST['addon_name'] = 'master-plugin';

		try {
			$this->_handleAjax( 'gfpdf_deactivate_license' );
			$this->fail( 'Expected WPAjaxDieContinueException was not thrown.' );
		} catch ( WPAjaxDieContinueException $e ) {
			$response = json_decode( $this->_last_response );

			$this->assertSame( 'Access Pass license key deactivated.', $response->success );
			$this->assertSame( [ 'sibling-plugin' ], $response->extra );
		}

		/* The sibling is flagged by the Access Pass listener, while the add-on doing the deactivation is skipped */
		$this->assertTrue( $sibling->has_license_auto_deactivated() );
		$this->assertFalse( $master->has_license_auto_deactivated() );
	}

	public function test_ajax_process_license_deactivation_api_failure() {
		$this->_setRole( 'administrator' );

		$master = $this->register_deactivation_addon( 'master-plugin', 'Master', '/master/file.php', 5 );
		$master->update_license_info(
			[
				'license' => 'KEY',
				'status'  => 'active',
				'message' => 'ok',
			]
		);

		/* API unreachable → deactivate_license() returns false → the API-error branch responds */
		add_filter(
			'pre_http_request',
			static function () {
				return new WP_Error( 'down', 'API unreachable' );
			}
		);

		$_POST['nonce']      = wp_create_nonce( 'gfpdf_deactivate_license' );
		$_POST['addon_name'] = 'master-plugin';

		try {
			$this->_handleAjax( 'gfpdf_deactivate_license' );
			$this->fail( 'Expected WPAjaxDieContinueException was not thrown.' );
		} catch ( WPAjaxDieContinueException $e ) {
			$this->assertStringContainsString( 'An API error occurred', json_decode( $this->_last_response )->error );
		}
	}

	/**
	 * @dataProvider provider_ajax_clear_pdf_cache_rejects
	 */
	public function test_ajax_clear_pdf_cache_rejects_a_bad_nonce_or_missing_capability( $role, $nonce ) {
		$this->_setRole( $role );
		$generation = Cache::get_generation();

		$_POST['nonce'] = wp_create_nonce( $nonce );

		try {
			$this->_handleAjax( 'gfpdf_clear_pdf_cache' );
			$this->fail( 'Expected WPAjaxDieStopException was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '401', $e->getMessage() );
		}

		$this->assertSame( $generation, Cache::get_generation() );
	}

	public function provider_ajax_clear_pdf_cache_rejects() {
		return [
			'bad nonce'          => [ 'administrator', 'gfpdf_deactivate_license' ],
			'missing capability' => [ 'editor', 'gfpdf_ajax_nonce' ],
		];
	}

	public function test_ajax_clear_pdf_cache_bumps_the_generation_and_requests_a_purge() {
		$this->_setRole( 'administrator' );
		$generation = Cache::get_generation();
		delete_option( Model_Pdf_Cache::PURGE_REQUEST_OPTION );

		/* Keeps request_purge() from spawning a cron request */
		add_filter(
			'pre_http_request',
			static function () {
				return new WP_Error( 'blocked' );
			}
		);

		$_POST['nonce'] = wp_create_nonce( 'gfpdf_ajax_nonce' );

		try {
			$this->_handleAjax( 'gfpdf_clear_pdf_cache' );
			$this->fail( 'Expected WPAjaxDieContinueException was not thrown.' );
		} catch ( WPAjaxDieContinueException $e ) {
			$this->assertSame( 'PDF cache cleared.', json_decode( $this->_last_response )->success );
		}

		$this->assertSame( $generation + 1, Cache::get_generation() );
		$this->assertNotSame( '', get_option( Model_Pdf_Cache::PURGE_REQUEST_OPTION, '' ) );
		$this->assertNotFalse( wp_next_scheduled( 'gfpdf_cache_sweep' ) );
	}

	/**
	 * Build and register an add-on for the license-deactivation AJAX tests.
	 *
	 * @param string $slug
	 * @param string $name
	 * @param string $file
	 * @param int    $edd_id
	 *
	 * @return AjaxDeactivationAddon
	 */
	private function register_deactivation_addon( $slug, $name, $file, $edd_id ) {
		$gfpdf = $this->gfpdf();

		$addon = new AjaxDeactivationAddon(
			$slug,
			$name,
			'Gravity PDF',
			'1.0',
			$file,
			$gfpdf->data,
			$gfpdf->options,
			new Helper_Singleton(),
			new Helper_Logger( $slug, $name ),
			new Helper_Notices()
		);

		$addon->set_edd_download_id( $edd_id );
		$addon->init();

		return $addon;
	}
}

/**
 * Minimal concrete add-on used by the license-deactivation AJAX tests
 */
class AjaxDeactivationAddon extends Helper_Abstract_Addon {
}
