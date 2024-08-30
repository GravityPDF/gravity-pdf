<?php

namespace GFPDF\Tests;

use GF_UnitTest_Factory;
use GFPDF\Rest\Rest_Form_Settings;
use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTestCase;

/**
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group api
 */
abstract class Test_Rest extends WP_UnitTestCase {

	/**
	 * @var GF_UnitTest_Factory
	 */
	protected $factory;

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

	function set_up() {
		parent::set_up();

		self::$admin_id  = $this->factory->user->create( [ 'role' => 'administrator', ] );
		self::$editor_id = $this->factory->user->create( [ 'role' => 'editor', ] );

		$this->factory = new GF_UnitTest_Factory();
		$this->form_id = $this->factory->form->create();
		$this->factory->pdf->set_form_id( $this->form_id );

		add_filter( 'rest_url', [ $this, 'filter_rest_url_for_leading_slash' ], 10, 2 );
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = new \Spy_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );
	}

	public function tear_down() {
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
