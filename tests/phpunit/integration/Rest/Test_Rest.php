<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Rest\Rest_Form_Settings;
use WP_REST_Request;
use WP_REST_Server;
use GFPDF\Tests\Integration\TestCase;

/**
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group api
 */
abstract class Test_Rest extends TestCase {

	/**
	 * @var int
	 */
	protected $form_id;

	/**
	 * @var int
	 */
	protected static $admin_id;

	/**
	 * @var int
	 */
	protected static $editor_id;

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		/* Start anonymous — tests like test_create_item_preview assert a 401 before they wp_set_current_user themselves. */
		unset( $GLOBALS['current_user'] );
		wp_set_current_user( 0 );

		/*
		 * Flush template caches. Other tests can prime GFCache with a stale (often empty) template list;
		 * the REST schema enum for 'template'/'pdf_size' is built from that list at dispatch time, so a stale
		 * cache yields an empty enum, which fails rest_not_in_enum validation against the field defaults.
		 */
		\GFCache::flush();
		$gfpdf->templates->flush_template_transient_cache();

		/*
		 * Re-sync gfpdf_settings cache with DB. Tests that write 'default_template'/'default_pdf_size'
		 * values (e.g. via test_get_settings) leave the in-memory cache populated with non-default values
		 * — those flow into the REST schema's std → default → enum-validation failure here.
		 */
		$gfpdf->options->set_plugin_settings();

		self::$admin_id  = self::factory()->user->create( [ 'role' => 'administrator', ] );
		self::$editor_id = self::factory()->user->create( [ 'role' => 'editor', ] );

		$this->form_id = $this->gf_factory()->form->create();
		$this->gf_factory()->pdf->set_form_id( $this->form_id );

		add_filter( 'rest_url', [ $this, 'filter_rest_url_for_leading_slash' ], 10, 2 );
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = new \Spy_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );
	}

	public function tear_down(): void {
		remove_filter( 'rest_url', [ $this, 'test_rest_url_for_leading_slash' ], 10, 2 );
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tear_down();
	}

	public function filter_rest_url_for_leading_slash( $url, $path ) {
		if ( is_multisite() || get_option( 'permalink_structure' ) ) {
			return $url;
		}

		// Make sure path for rest_url has a leading slash for proper resolution.
		if ( 0 !== strpos( $path, '/' ) ) {
			$this->fail(
				sprintf(
					'REST API URL "%s" should have a leading slash.',
					$path
				)
			);
		}

		return $url;
	}
}
